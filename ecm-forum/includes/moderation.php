<?php

function moderate_post_handler() {
    // Verify the nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ecm_nonce')) {
        wp_send_json_error(array('message' => 'Invalid nonce.'));
    }
	error_log('Post ID for Deletion: ' . $postId);
    // Handle post deletion and replacement
    if (isset($_POST['post_id'])) {
        $postId = intval($_POST['post_id']);
        
        // Replace the post content with the new message
        $message = "<p style='color:red'>This post has been flagged for removal due to a violation of our forum guidelines.</p>";
        
        // Update the post content for custom post type
        $updated_post = array(
            'ID'           => $postId,
            'post_type'    => 'ecm_forum_post', // Specify the custom post type here
            'post_content' => $message,
			'comment_status' => 'closed',
        );
        
        // Update the post with the new content
        wp_update_post($updated_post);
		
		 // Schedule comment deletion after 48 hours (adjust as needed)
        $deletion_time = current_time('timestamp') + 48 * 3600; // 48 hours
        wp_schedule_single_event($deletion_time, 'delete_post_event', array($postId));

        // Send a success response back to the AJAX request
        wp_send_json_success(array('message' => $message));
    }
}

function delete_post_handler() {
    // Verify the nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ecm_nonce')) {
        wp_send_json_error(array('message' => 'Invalid nonce.'));
    }

    // Handle post deletion
    if (isset($_POST['post_id'])) {
        $postId = intval($_POST['post_id']);

        // Delete the post
        wp_delete_post($postId, true); // Set the second parameter to 'true' to force delete

        // Send a success response back to the AJAX request
        wp_send_json_success(array('message' => 'The post has been successfully deleted.'));
    }
}

function moderate_comment_handler() {
    // Verify the nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ecm_nonce')) {
        wp_send_json_error(array('message' => 'Invalid nonce.'));
    }

    // Handle comment deletion and replacement
    if (isset($_POST['comment_id'])) {
        $commentId = intval($_POST['comment_id']);
        
        // Replace the comment content with the new message
        $message = "<p style='color:red'>This comment has been flagged for removal due to a violation of our forum guidelines.</p>";
        
        // Update the comment content
        $updated_comment = array(
            'comment_ID'      => $commentId,
            'comment_content' => $message,
        );
        
        // Update the comment with the new content
        wp_update_comment($updated_comment);
		
		 // Schedule comment deletion after 48 hours (adjust as needed)
        $deletion_time = current_time('timestamp') + 48 * 3600; // 48 hours
        wp_schedule_single_event($deletion_time, 'delete_comment_event', array($commentId));

        // Send a success response back to the AJAX request
        wp_send_json_success(array('message' => $message));
    }
}

function delete_comment_handler() {
    // Verify the nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ecm_nonce')) {
        wp_send_json_error(array('message' => 'Invalid nonce.'));
    }

    // Handle comment deletion
    if (isset($_POST['comment_id'])) {
        $commentId = intval($_POST['comment_id']);
        
        // Delete the comment
        wp_delete_comment($commentId, true); // Set the second parameter to 'true' to force delete

        // Send a success response back to the AJAX request
        wp_send_json_success(array('message' => 'The comment has been successfully deleted.'));
    }
}

add_action('wp_ajax_delete_post', 'delete_post_handler');

add_action('wp_ajax_delete_comment', 'delete_comment_handler');

add_action('wp_ajax_moderate_post', 'moderate_post_handler');

add_action('wp_ajax_moderate_comment', 'moderate_comment_handler');


// Hook to handle comment deletion event
add_action('delete_comment_event', 'delete_comment_event_handler');

function delete_comment_event_handler($commentId) {
    // Delete the comment
    wp_delete_comment($commentId, true); // Set the second parameter to 'true' to force delete

    // You can also perform additional actions here if needed
}
function delete_post_event_handler($commentId) {
    // Delete the comment
    wp_delete_comment($postId, true); // Set the second parameter to 'true' to force delete

    // You can also perform additional actions here if needed
}