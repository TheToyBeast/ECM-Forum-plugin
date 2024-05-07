<?php

// Report Handler

function handle_report_submission() {
	
	// Security check: Verify the nonce
    if (!isset($_POST['ecm_nonce']) || !wp_verify_nonce($_POST['ecm_nonce'], 'ecm_nonce_action')) {
        wp_send_json_error('Security check failed', 403);
        return;
    }
    // Check if the necessary data is set
    if (!isset($_POST['report_reason'], $_POST['report_details'], $_POST['post_id'])) {
        wp_send_json_error('Missing required fields', 400);
        return;
    }

    // Sanitize and validate inputs
    $report_reason = sanitize_text_field($_POST['report_reason']);
    $report_details = sanitize_textarea_field($_POST['report_details']);
    $post_id = intval($_POST['post_id']);
    $comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0; // Default to 0 if not set

    // Additional validation (if needed)
    if (empty($report_reason) || empty($post_id)) {
        wp_send_json_error('Invalid data provided', 400);
        return;
    }

    // Insert data into the database
    global $wpdb;
    $table_name = $wpdb->prefix . 'ecm_forum_custom_table';
    $result = $wpdb->insert($table_name, array(
        'time' => current_time('mysql'),
        'user_id' => get_current_user_id(),
        'post_id' => $post_id,
        'comment_id' => $comment_id, // Save the comment ID if available
        'report_reason' => $report_reason,
        'report_details' => $report_details,
    	'report_status' => 0 // Assuming 0 indicates a new report
    ));

    // Check if the insertion was successful
    if ($result === false) {
        wp_send_json_error('Database insertion failed', 500);
    } else {
        wp_send_json_success('Report submitted successfully');
    }
}


// Hook the function to WordPress AJAX actions
add_action('wp_ajax_submit_report', 'handle_report_submission'); // For logged-in users

//check reports
function get_reports() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ecm_forum_custom_table';
    $query = "SELECT * FROM
$table_name WHERE report_status = 0 ORDER BY time DESC"; // Adjust the query as needed
return $wpdb->get_results($query);
}

// mark reports as checked
add_action('wp_ajax_update_report_status', 'update_report_status');
add_action('wp_ajax_nopriv_update_report_status', 'update_report_status');

function update_report_status() {
    // Check if the current user is an admin (or add your own logic for authorization)
    if (current_user_can('administrator')) {
        // Get the report ID and status from the AJAX request
        $report_id = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;
        $is_reviewed = isset($_POST['is_reviewed']) ? intval($_POST['is_reviewed']) : 0;

        // Update the report status in the database
        global $wpdb;
        $table_name = $wpdb->prefix . 'ecm_forum_custom_table';

        $result = $wpdb->update(
            $table_name,
            array('report_status' => $is_reviewed),
            array('id' => $report_id),
            array('%d'),
            array('%d')
        );

        if ($result !== false) {
            echo 'Success'; // You can return any message you want upon success.
        } else {
            echo 'Error'; // Handle the error case.
        }
    }

    // Always exit to avoid extra output
    wp_die();
}