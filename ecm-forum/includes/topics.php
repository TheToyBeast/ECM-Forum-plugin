<?php
function ecm_handle_new_topic_creation() {
    // Check nonce
    if (!isset($_POST['ecm_nonce']) || !wp_verify_nonce($_POST['ecm_nonce'], 'ecm_create_new_topic_action')) {
        wp_send_json_error(array('message' => 'Security check failed'));
        return;
    }

    $new_subcategory_name = sanitize_text_field($_POST['new_topic_name']);
    $parent_id = intval($_POST['parent_category']);
    $taxonomy_name = sanitize_text_field($_POST['taxonomy_name']); // This should be 'forum-category'

    $result = wp_insert_term($new_subcategory_name, $taxonomy_name, array('parent' => $parent_id));

    if (!is_wp_error($result)) {
        // Success: Send a JSON response back to the AJAX call
        wp_send_json_success(array('message' => 'Topic created successfully'));
    } else {
        // Error: Send a JSON response back to the AJAX call
        wp_send_json_error(array('message' => 'An error occurred while creating the subcategory.'));
    }
}
add_action('wp_ajax_ecm_create_new_topic', 'ecm_handle_new_topic_creation');