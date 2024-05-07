<?php

// messaging handler

function ecm_send_message() {
	
	if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in.');
        wp_die();
    }

    // Verify nonce for security
    check_ajax_referer('ecm_nonce', 'nonce');

    // Sanitize and validate inputs
    $sender_id = get_current_user_id(); // Assuming the sender is the current logged-in user
    $receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
    $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';

    // Validate receiver ID and message
    if ($receiver_id <= 0 || empty($message)) {
        wp_send_json_error('Invalid input.');
        wp_die();
    }
	
	// Check if the sender or receiver is blocked
    if (ecm_is_user_blocked($sender_id, $receiver_id)) {
        wp_send_json_error('You may not message this user.');
        wp_die();
    }

    // Insert the message into the database
    global $wpdb;
    $table_name = $wpdb->prefix . 'ecm_messages';
    $wpdb->insert(
        $table_name,
        array(
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id,
            'message' => $message
        ),
        array('%d', '%d', '%s')
    );

    // Check for successful insertion
    if ($wpdb->insert_id) {
        wp_send_json_success('Message sent successfully');
    } else {
        wp_send_json_error('Failed to send message.');
    }

    wp_die();
}
add_action('wp_ajax_ecm_send_message', 'ecm_send_message');

function ecm_is_user_blocked($user_id, $friend_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ecm_friendships';

    // Check if either user has blocked the other
    $is_blocked = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name 
         WHERE ((user_id1 = %d AND user_id2 = %d) OR (user_id1 = %d AND user_id2 = %d))
         AND is_blocked = 1",
        $user_id, $friend_id, $friend_id, $user_id
    ));

    return $is_blocked > 0;
}


function ecm_get_messages() {
    // Verify nonce for security
    check_ajax_referer('ecm_nonce', 'nonce');

    $current_user_id = get_current_user_id();
    $chat_user_id = isset($_POST['chat_user_id']) ? intval($_POST['chat_user_id']) : 0;

    if ($chat_user_id <= 0) {
        wp_send_json_error('Invalid user.');
        wp_die();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'ecm_messages';

    // Fetch messages between the current user and the chat user
    $messages = $wpdb->get_results($wpdb->prepare(
        "SELECT m.*, u.display_name FROM $table_name m
         LEFT JOIN {$wpdb->prefix}users u ON m.sender_id = u.ID
         WHERE (m.sender_id = %d AND m.receiver_id = %d) 
         OR (m.sender_id = %d AND m.receiver_id = %d)",
        $current_user_id, $chat_user_id, $chat_user_id, $current_user_id
    ));

    wp_send_json_success($messages);
    wp_die();
}
add_action('wp_ajax_ecm_get_messages', 'ecm_get_messages');

function ecm_delete_older_messages() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ecm_messages';

    $wpdb->query(
        "DELETE FROM $table_name
        WHERE id NOT IN (
            SELECT id FROM (
                SELECT id FROM $table_name
                ORDER BY time_sent DESC
                LIMIT 50
            ) sub
        )"
    );
}


// include messaging on every page

function ecm_include_messaging_interface() {
    include_once plugin_dir_path(__FILE__) . '/messaging.php';
}
add_action('wp_footer', 'ecm_include_messaging_interface');


// Friend Requests

function ecm_send_friend_request() {
    // Verify nonce for security
    check_ajax_referer('ecm_nonce', 'nonce');

    $sender_id = get_current_user_id();
    $receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;

    if ($sender_id <= 0 || $receiver_id <= 0) {
        wp_send_json_error('Invalid user IDs.');
        wp_die();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'ecm_friendships';
	
	// Check if a friend request already exists between these users
    $existing_request = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_name WHERE 
        (user_id1 = %d AND user_id2 = %d) OR (user_id1 = %d AND user_id2 = %d)",
        $sender_id, $receiver_id, $receiver_id, $sender_id
    ));

    if ($existing_request) {
        wp_send_json_error('A friend request already exists between you and this user.');
        wp_die();
    }

    // Insert friend request into the database
    $wpdb->insert(
        $table_name,
        array(
            'user_id1' => $sender_id,
            'user_id2' => $receiver_id,
            'status' => 'pending'
        ),
        array('%d', '%d', '%s')
    );

    if ($wpdb->insert_id) {
        wp_send_json_success('Friend request sent.');
    } else {
        wp_send_json_error('Failed to send friend request.');
    }

    wp_die();
}
add_action('wp_ajax_ecm_send_friend_request', 'ecm_send_friend_request');

// Display Friends
function ecm_get_friends_list() {
    global $wpdb;
    $current_user_id = get_current_user_id();
    $table_name = $wpdb->prefix . 'ecm_friendships';
    $users_table = $wpdb->prefix . 'users';

    // Query to fetch accepted friends of the current user
    // Fetch only friends who are not blocked
    $friends = $wpdb->get_results($wpdb->prepare(
        "SELECT u.ID, u.display_name 
        FROM $table_name f
        JOIN $users_table u ON u.ID = IF(f.user_id1 = %d, f.user_id2, f.user_id1)
        WHERE (f.user_id1 = %d OR f.user_id2 = %d) 
        AND f.status = 'accepted' 
        AND f.is_blocked = 0", // Ensure the friend is not blocked
        $current_user_id, $current_user_id, $current_user_id
    ), ARRAY_A);

    return $friends;
}

function ecm_get_pending_friend_requests() {
    global $wpdb;
    $current_user_id = get_current_user_id();
    $table_name = $wpdb->prefix . 'ecm_friendships';
    $users_table = $wpdb->prefix . 'users';

    $requests = $wpdb->get_results($wpdb->prepare(
        "SELECT f.id, u.ID as user_id, u.display_name 
         FROM $table_name f
         JOIN $users_table u ON u.ID = f.user_id1
         WHERE f.user_id2 = %d AND f.status = 'pending'",
        $current_user_id
    ), ARRAY_A);

    return $requests;
}

function ecm_ajax_get_friends_list() {
    // Verify nonce for security
    check_ajax_referer('ecm_nonce', 'nonce');

    $friends_list = ecm_get_friends_list();

    if (!empty($friends_list)) {
        wp_send_json_success($friends_list);
    } else {
        wp_send_json_error('No friends found.');
    }

    wp_die();
}
add_action('wp_ajax_ecm_get_friends_list', 'ecm_ajax_get_friends_list');

function ecm_ajax_get_pending_friend_requests() {
    check_ajax_referer('ecm_nonce', 'nonce');

    $friend_requests = ecm_get_pending_friend_requests();

    if (!empty($friend_requests)) {
        wp_send_json_success($friend_requests);
    } else {
        wp_send_json_error('No friend requests.');
    }

    wp_die();
}
add_action('wp_ajax_ecm_get_pending_friend_requests', 'ecm_ajax_get_pending_friend_requests');

//Handler to accept friend requests

function ecm_accept_friend_request() {
    check_ajax_referer('ecm_nonce', 'nonce'); // Security check

    $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
    if ($request_id <= 0) {
        wp_send_json_error('Invalid request ID.');
        wp_die();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'ecm_friendships';

    $updated = $wpdb->update(
        $table_name,
        array('status' => 'accepted'), // New values
        array('id' => $request_id), // Where
        array('%s'), // Format of new values
        array('%d')  // Format of where clause
    );

    if ($updated) {
        wp_send_json_success('Friend request accepted.');
    } else {
        wp_send_json_error('Failed to accept friend request.');
    }

    wp_die();
}
add_action('wp_ajax_ecm_accept_friend_request', 'ecm_accept_friend_request');

//Handler to deny friend requests

function ecm_decline_friend_request() {
    check_ajax_referer('ecm_nonce', 'nonce'); // Security check

    $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
    if ($request_id <= 0) {
        wp_send_json_error('Invalid request ID.');
        wp_die();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'ecm_friendships';

    // Option 1: Update the status to 'declined'
    $updated = $wpdb->update(
        $table_name,
        array('status' => 'declined'), // New values
        array('id' => $request_id), // Where
        array('%s'), // Format of new values
        array('%d')  // Format of where clause
    );

    // Option 2: Delete the request
    // $deleted = $wpdb->delete($table_name, array('id' => $request_id), array('%d'));

    if ($updated /* or $deleted */) {
        wp_send_json_success('Friend request declined.');
    } else {
        wp_send_json_error('Failed to decline friend request.');
    }

    wp_die();
}
add_action('wp_ajax_ecm_decline_friend_request', 'ecm_decline_friend_request');

function ecm_check_friend_request_status() {
    // Security check with nonce
    if (!check_ajax_referer('ecm_nonce', 'nonce', false)) {
        wp_send_json_error('Nonce verification failed', 403);
        wp_die();
    }

    global $wpdb;
    $current_user_id = get_current_user_id();
    $table_name = $wpdb->prefix . 'ecm_friendships';
    $users_table = $wpdb->prefix . 'users';

    // Query to fetch accepted friend requests
    $accepted_requests = $wpdb->get_results($wpdb->prepare(
        "SELECT u.ID, u.display_name 
        FROM $table_name f
        INNER JOIN $users_table u ON u.ID = f.user_id2
        WHERE f.user_id1 = %d AND f.status = 'accepted' 
        AND f.updated_at > DATE_SUB(NOW(), INTERVAL 10 SECOND)",
        $current_user_id
    ), ARRAY_A);

    if (!empty($accepted_requests)) {
        wp_send_json_success($accepted_requests);
    } else {
        wp_send_json_error('No recent updates to friend requests.');
    }

    wp_die();
}
add_action('wp_ajax_ecm_check_friend_request_status', 'ecm_check_friend_request_status');

function toybeast_get_user_friends($user_id) {
    global $wpdb;
    $friendships_table = $wpdb->prefix . 'ecm_friendships';
    $users_table = $wpdb->prefix . 'users';
    $posts_table = $wpdb->prefix . 'posts';
    $usermeta_table = $wpdb->prefix . 'usermeta';

    $friends = $wpdb->get_results($wpdb->prepare(
        "SELECT u.ID, u.user_login, f.status, f.is_blocked, f.blocked_by_user_id,
                (SELECT COUNT(*) FROM $posts_table WHERE post_author = u.ID AND post_type = 'post' AND post_status = 'publish') as post_count,
                (SELECT meta_value FROM $usermeta_table WHERE user_id = u.ID AND meta_key = 'ecm_reputation_score') as reputation
         FROM $friendships_table f
         JOIN $users_table u ON u.ID = (CASE WHEN f.user_id1 = %d THEN f.user_id2 ELSE f.user_id1 END)
         WHERE (f.user_id1 = %d OR f.user_id2 = %d) AND f.status = 'accepted'",
        $user_id, $user_id, $user_id
    ), ARRAY_A);

    return $friends;
}


// Rermove  friend

function ecm_remove_friend() {
    check_ajax_referer('ecm_nonce', 'nonce'); // Security check

    $friend_id = isset($_POST['friend_id']) ? intval($_POST['friend_id']) : 0;
    $user_id = get_current_user_id();

    if ($friend_id <= 0) {
        wp_send_json_error('Invalid friend ID.');
        wp_die();
    }

    global $wpdb;
    $friendships_table = $wpdb->prefix . 'ecm_friendships';
    $messages_table = $wpdb->prefix . 'ecm_messages';

    // Begin transaction
    $wpdb->query('START TRANSACTION');

    // Delete the friendship record
    $friendship_deleted = $wpdb->query($wpdb->prepare(
    "DELETE FROM $friendships_table WHERE 
    (user_id1 = %d AND user_id2 = %d) OR 
    (user_id1 = %d AND user_id2 = %d)",
    $user_id, $friend_id, $friend_id, $user_id
	));

    // Delete the messages associated with this friendship
    $messages_query = $wpdb->prepare(
        "DELETE FROM $messages_table WHERE 
        (sender_id = %d AND receiver_id = %d) OR 
        (sender_id = %d AND receiver_id = %d)",
        $user_id, $friend_id, $friend_id, $user_id
    );
	
	$messages_deleted = $wpdb->query($messages_query);


    // Check if both operations were successful
    if ($friendship_deleted) {
        $wpdb->query('COMMIT'); // Commit the transaction
        wp_send_json_success('Friend removed.');
    } else {
        $wpdb->query('ROLLBACK'); // Rollback the transaction in case of failure
        wp_send_json_error('Failed to remove friend.');
    }
	
	return $messages_deleted;

    wp_die();
}
add_action('wp_ajax_ecm_remove_friend', 'ecm_remove_friend');

//Blocking and Unblocking

function ecm_block_unblock_friend() {
    check_ajax_referer('ecm_nonce', 'nonce'); // Security check

    $friend_id = isset($_POST['friend_id']) ? intval($_POST['friend_id']) : 0;
    $user_id = get_current_user_id();
    $action = isset($_POST['block_unblock_action']) ? sanitize_text_field($_POST['block_unblock_action']) : '';

    if ($friend_id <= 0 || empty($action)) {
        wp_send_json_error('Invalid request.');
        wp_die();
    }

    if ($action === 'Block') {
        // Logic to block the friend
        ecm_block_friend($user_id, $friend_id);
    } elseif ($action === 'Unblock') {
        // Logic to unblock the friend
        ecm_unblock_friend($user_id, $friend_id);
    }

    wp_send_json_success($action . ' successful.');
    wp_die();
}
add_action('wp_ajax_ecm_block_unblock_friend', 'ecm_block_unblock_friend');

function ecm_block_friend($user_id, $friend_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ecm_friendships';

    // Update the friendship record to set the block status for both possible arrangements
    $wpdb->update(
        $table_name,
        array('is_blocked' => 1,
			 'blocked_by_user_id' => $user_id
			 ), // set block status to 1
        array(
            'user_id1' => $user_id,
            'user_id2' => $friend_id
        ),
        array('%d'),
        array('%d', '%d')
    );

    $wpdb->update(

        $table_name,
        array('is_blocked' => 1,
			 'blocked_by_user_id' => $user_id), // set block status to 1
        array(
            'user_id1' => $friend_id,
            'user_id2' => $user_id
        ),
        array('%d'),
        array('%d', '%d')
    );
}

function ecm_unblock_friend($user_id, $friend_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ecm_friendships';

    // Update the friendship record to remove the block status for both possible arrangements
    $wpdb->update(
        $table_name,
        array('is_blocked' => 0,
			 'blocked_by_user_id' => null
			 ), // set block status to 0
        array(
            'user_id1' => $user_id,
            'user_id2' => $friend_id
        ),
        array('%d'),
        array('%d', '%d')
    );

    $wpdb->update(
        $table_name,
        array('is_blocked' => 0,
			 'blocked_by_user_id' => null), // set block status to 0
        array(
            'user_id1' => $friend_id,
            'user_id2' => $user_id
        ),
        array('%d'),
        array('%d', '%d')
    );
}
function ecm_is_friend_blocked($user_id, $friend_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ecm_friendships';

    // Assuming 'is_blocked' is a column in your 'ecm_friendships' table
    // You need to adjust this query based on your actual database schema
    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT is_blocked FROM $table_name WHERE 
        (user_id1 = %d AND user_id2 = %d) OR (user_id1 = %d AND user_id2 = %d)",
        $user_id, $friend_id, $friend_id, $user_id
    ));

    // If the result is 1, it means the friend is blocked, return true
    return ($result == 1);
}