<?php

// like and dislike handling

function ecm_handle_like_dislike() {
    // Security check
    check_ajax_referer('ecm_nonce', 'nonce');

    global $wpdb;
    $post_id = intval($_POST['post_id']);
    $comment_id = intval($_POST['comment_id']);
    $user_id = get_current_user_id();
    $action = sanitize_text_field($_POST['user_action']);
    $table_name = $wpdb->prefix . 'ecm_forum_likes_dislikes';

    // Determine whether it's a post or comment action
    if ($comment_id > 0) {
        $post_or_comment_id = $comment_id;
        $post_or_comment_type = 'comment';
    } else {
        $post_or_comment_id = $post_id;
        $post_or_comment_type = 'post';
    }


    // Check if the user already liked/disliked this post or comment
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT action FROM $table_name WHERE post_id = %d AND comment_id = %d AND user_id = %d",
        $post_id, $comment_id, $user_id
    ));

    	// Insert or update like/dislike into the database
		if (!$exists) {
			$wpdb->insert(
				$table_name,
				array('post_id' => $post_id, 'comment_id' => $comment_id, 'user_id' => $user_id, 'action' => $action),
				array('%d', '%d', '%d', '%s')
			);
			// Add an upvote or downvote to the user table
			if ($action === 'like') {
				    if ($comment_id > 0) {
					// Handle points for upvote (call update_points_on_received_upvote)
					update_points_on_received_upvote_comment($comment_id);
					} else {
					// Handle points for upvote (call update_points_on_received_upvote)
					update_points_on_received_upvote($post_id);
					}

		    } elseif ($action === 'dislike') {
				if ($comment_id > 0) {
					// Handle points for downvote (call update_points_on_received_downvote)
					update_points_on_received_downvote_comment($comment_id);
					} else {
					// Handle points for downvote (call update_points_on_received_downvote)
					update_points_on_received_downvote($post_id);
					}
			}	
    } else {
        // Check if the user wants to change the action
        if ($exists !== $action) {
            $wpdb->update(
                $table_name,
                array('action' => $action),
                array('post_id' => $post_id, 'comment_id' => $comment_id, 'user_id' => $user_id),
                array('%s'),
                array('%d', '%d', '%d')
            );

            // Update upvotes and downvotes in the user table accordingly
            if ($action === 'like') {
                // User switched from dislike to like
                if ($comment_id > 0) {
					// Handle points for upvote (call update_points_on_received_upvote)
					update_points_on_received_upvote_comment($comment_id);
					} else {
					// Handle points for upvote (call update_points_on_received_upvote)
					update_points_on_received_upvote($post_id);
					}
            } elseif ($action === 'dislike') {
                // User switched from like to dislike
                if ($comment_id > 0) {
					// Handle points for downvote (call update_points_on_received_downvote)
					update_points_on_received_downvote_comment($comment_id);
					} else {
					// Handle points for downvote (call update_points_on_received_downvote)
					update_points_on_received_downvote($post_id);
					}
            }
        }
    }

    // Calculate the new like and dislike counts
    $like_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND comment_id = %d AND action = 'like'",
        $post_id, $comment_id
    ));
    $dislike_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND comment_id = %d AND action = 'dislike'",
        $post_id, $comment_id
    ));

    // Send the response back to the AJAX request
    wp_send_json(array('likes' => $like_count, 'dislikes' => $dislike_count));

    // Make sure to end the function
    wp_die();
}

// Add action hooks for both logged in and non-logged in users
add_action('wp_ajax_ecm_handle_like_dislike', 'ecm_handle_like_dislike');
add_action('wp_ajax_nopriv_ecm_handle_like_dislike', 'ecm_handle_like_dislike');


// Add columns to forum post showing likes and dislikes

function ecm_add_likes_dislikes_columns($columns) {
    $new_columns = array();

    foreach ($columns as $key => $title) {
        if ($key == 'comments') { // Insert before the 'comments' column
            $new_columns['ecm_likes'] = 'Likes';
            $new_columns['ecm_dislikes'] = 'Dislikes';
        }
        $new_columns[$key] = $title;
    }

    return $new_columns;
}
add_filter('manage_posts_columns', 'ecm_add_likes_dislikes_columns');

function ecm_admin_column_width() {
    echo '
    <style>
        .column-ecm_likes, .column-ecm_dislikes {
            width: 150px;
        }
    </style>
    ';
}
add_action('admin_head', 'ecm_admin_column_width');


function ecm_display_likes_dislikes_columns($column, $post_id) {
    global $wpdb;

    switch ($column) {
        case 'ecm_likes':
            // Fetch likes count
            $likes_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ecm_forum_likes_dislikes WHERE post_id = %d AND action = 'like'", $post_id));
            echo $likes_count ? $likes_count : '0';
            break;

        case 'ecm_dislikes':
            // Fetch dislikes count
            $dislikes_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ecm_forum_likes_dislikes WHERE post_id = %d AND action = 'dislike'", $post_id));
            echo $dislikes_count ? $dislikes_count : '0';
            break;
    }
}
add_action('manage_posts_custom_column', 'ecm_display_likes_dislikes_columns', 10, 2);

function ecm_get_like_dislike_counts($post_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ecm_forum_likes_dislikes';

    // Query to get likes count
    $likes_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND action = 'like'",
        $post_id
    ));

    // Query to get dislikes count
    $dislikes_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND action = 'dislike'",
        $post_id
    ));

    return array('likes' => $likes_count, 'dislikes' => $dislikes_count);
}

function ecm_get_comment_like_dislike_counts($comment_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ecm_forum_likes_dislikes';

    // Query to get likes count for the comment
    $likes_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE comment_id = %d AND action = 'like'",
        $comment_id
    ));

    // Query to get dislikes count for the comment
    $dislikes_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE comment_id = %d AND action = 'dislike'",
        $comment_id
    ));

    return array('likes' => $likes_count, 'dislikes' => $dislikes_count);
}
