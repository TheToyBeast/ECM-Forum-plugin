<?php

function ecm_add_forum_meta_boxes() {
    add_meta_box(
        'ecm_forum_meta_box',          // Unique ID
        __('Forum Post Details', 'text_domain'),  // Box title
        'ecm_forum_meta_box_html',     // Content callback, must be of type callable
        'ecm_forum_post'               // Post type
    );
}
add_action('add_meta_boxes', 'ecm_add_forum_meta_boxes');

function ecm_forum_meta_box_html($post) {
    // Add a nonce field so we can check for it later.
    wp_nonce_field('ecm_forum_meta_box_nonce', 'ecm_forum_meta_box_nonce');

    // Use get_post_meta to retrieve an existing value from the database.
    $value = get_post_meta($post->ID, '_ecm_forum_meta_key', true);

    // Echo out the field
    echo '<label for="ecm_forum_field">' . __('Description for this field', 'text_domain') . '</label>';
    echo '<input type="text" id="ecm_forum_field" name="ecm_forum_field" value="' . esc_attr($value) . '">';
}

function ecm_save_forum_meta_box_data($post_id) {
    // Check if our nonce is set.
    if (!isset($_POST['ecm_forum_meta_box_nonce'])) {
        return;
    }

    // Verify that the nonce is valid.
    if (!wp_verify_nonce($_POST['ecm_forum_meta_box_nonce'], 'ecm_forum_meta_box_nonce')) {
        return;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check the user's permissions.
    if (isset($_POST['post_type']) && 'ecm_forum_post' == $_POST['post_type']) {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    // Make sure that it is set.
    if (!isset($_POST['ecm_forum_field'])) {
        return;
    }

    // Sanitize user input.
    $my_data = sanitize_text_field($_POST['ecm_forum_field']);

    // Update the meta field in the database.
    update_post_meta($post_id, '_ecm_forum_meta_key', $my_data);
}
add_action('save_post', 'ecm_save_forum_meta_box_data');