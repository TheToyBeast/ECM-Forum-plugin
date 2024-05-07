<?php

// initialize Forum Roles

function ecm_update_forum_role($user_id, $new_role) {
    update_user_meta($user_id, 'ecm_forum_role', $new_role);
}

// Initalize to add reputation score for existing users

 function ecm_set_default_reputation_for_existing_users() {
    // Check if the operation has already been done
    if (get_option('ecm_reputation_initialized') === 'yes') {
        return;
    }

    $args = array(
        'meta_query' => array(
            array(
                'key'     => 'ecm_reputation_score',
                'compare' => 'NOT EXISTS' // Select users who don't have this meta key
            )
        )
    );
    $users = get_users($args);

    foreach ($users as $user) {
        update_user_meta($user->ID, 'ecm_reputation_score', 0);
    }

    // Set a flag to indicate the operation is complete
    update_option('ecm_reputation_initialized', 'yes');
}

add_action('admin_init', 'ecm_set_default_reputation_for_existing_users');

//Update User Roles


function ecm_update_forum_user_role($user_id) {
    $points = get_user_meta($user_id, 'ecm_reputation_score', true);
    $roles_points = [
        // ... your roles points array ...
        'subscriber' => get_option('ecm_points_subscriber', 0),
        'enthusiast' => get_option('ecm_points_enthusiast', 20),
        'collector' => get_option('ecm_points_collector', 50),
        'expert_collector' => get_option('ecm_points_expert_collector', 200),
        'ultimate_collector' => get_option('ecm_points_ultimate_collector', 500),

    ];

    $current_role = get_user_meta($user_id, 'ecm_forum_role', true);

    foreach ($roles_points as $role => $required_points) {
        if ($points >= $required_points && $current_role !== $role) {
            update_user_meta($user_id, 'ecm_forum_role', $role);
        }
    }
}
// Add a new column to the Users page in the admin dashboard
function ecm_add_forum_role_column($columns) {
    $columns['ecm_forum_role'] = 'ECM Forum Role';
    return $columns;
}
add_filter('manage_users_columns', 'ecm_add_forum_role_column');

// Populate the new column with user's ECM Forum Role
function ecm_show_forum_role_column_content($value, $column_name, $user_id) {
    if ('ecm_forum_role' == $column_name) {
        $role = get_user_meta($user_id, 'ecm_forum_role', true);
        return $role ? $role : 'None';
    }
    return $value;
}
add_action('manage_users_custom_column', 'ecm_show_forum_role_column_content', 10, 3);

function ecm_forum_role_change_link($actions, $user) {
    if (current_user_can('edit_users')) { // Check if the current user can edit users
        $role = get_user_meta($user->ID, 'ecm_forum_role', true);
        if ($role == 'moderator') {
            $actions['ecm_role'] = "<a href='" . wp_nonce_url("users.php?action=ecm_remove_moderator&amp;user=$user->ID", 'ecm-moderator-nonce') . "'>Remove Moderator</a>";
        } else {
            $actions['ecm_role'] = "<a href='" . wp_nonce_url("users.php?action=ecm_make_moderator&amp;user=$user->ID", 'ecm-moderator-nonce') . "'>Make Moderator</a>";
        }
    }
    return $actions;
}
add_filter('user_row_actions', 'ecm_forum_role_change_link', 10, 2);

function ecm_change_forum_role() {
    if (isset($_REQUEST['action']) && in_array($_REQUEST['action'], array('ecm_make_moderator', 'ecm_remove_moderator'))) {
        if (!current_user_can('edit_users') || !isset($_REQUEST['user']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'ecm-moderator-nonce')) {
            wp_die('You do not have sufficient permissions to perform this action.');
        }

        $user_id = intval($_REQUEST['user']);
        $role = $_REQUEST['action'] == 'ecm_make_moderator' ? 'moderator' : 'subscriber';

        update_user_meta($user_id, 'ecm_forum_role', $role);

        // Optionally, redirect back to the users page
        wp_redirect(admin_url('users.php'));
        exit;
    }
}
add_action('admin_init', 'ecm_change_forum_role');


// Initalize to add user roles for existing users

 /* function ecm_initialize_forum_roles_for_existing_users() {
    $users = get_users(array('fields' => 'ID'));

    foreach ($users as $user_id) {
        ecm_update_forum_user_role($user_id); // Assuming this function updates the ECM Forum role
    }
}
add_action('admin_init', 'ecm_initialize_forum_roles_for_existing_users');
*/

function ecm_update_forum_role_for_admins() {
    $args = array('role' => 'Administrator');
    $admins = get_users($args);

    foreach ($admins as $admin) {
        $current_forum_role = get_user_meta($admin->ID, 'ecm_forum_role', true);

        // If the current ECM Forum role is not 'moderator', update it
        if ($current_forum_role !== 'moderator') {
            update_user_meta($admin->ID, 'ecm_forum_role', 'moderator');
        }
    }
}

function ecm_assign_moderator_role_on_login($user_login, $user) {
    if (in_array('administrator', (array) $user->roles)) {
        update_user_meta($user->ID, 'ecm_forum_role', 'moderator');
    }
}
add_action('wp_login', 'ecm_assign_moderator_role_on_login', 10, 2);

function ecm_add_custom_capabilities() {
    $role = get_role('subscriber');
    $role->add_cap('manage_forum_category_terms');
    $role->add_cap('edit_forum_category_terms');
    $role->add_cap('delete_forum_category_terms');
    $role->add_cap('assign_forum_category_terms');
	// Ensure subscribers have the 'read' capability, typically required for commenting
    $role->add_cap('read');
}
add_action('init', 'ecm_add_custom_capabilities');

function ecm_add_forum_post_type_capabilities() {
    // Get the administrator role
    $admin_role = get_role('administrator');

    // Add capabilities to the administrator role
    $admin_role->add_cap('edit_ecm_forum_post');
    $admin_role->add_cap('read_ecm_forum_post');
    $admin_role->add_cap('delete_ecm_forum_post');
    $admin_role->add_cap('edit_ecm_forum_posts');
    $admin_role->add_cap('edit_others_ecm_forum_posts');
    $admin_role->add_cap('publish_ecm_forum_posts');
    $admin_role->add_cap('read_private_ecm_forum_posts');
    $admin_role->add_cap('manage_forum_category_terms');
    $admin_role->add_cap('edit_forum_category_terms');
    $admin_role->add_cap('delete_forum_category_terms');
    $admin_role->add_cap('assign_forum_category_terms');
}

add_action('admin_init', 'ecm_add_forum_post_type_capabilities');
