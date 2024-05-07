<?php

function ecm_create_forum_post_type() {
    $labels = array(
        'name'                  => _x('Forum Posts', 'Post Type General Name', 'text_domain'),
        'singular_name'         => _x('Forum Post', 'Post Type Singular Name', 'text_domain'),
        'menu_name'             => __('Forum Posts', 'text_domain'),
        'name_admin_bar'        => __('Forum Post', 'text_domain'),
        // ... other labels
    );

    $args = array(
        'label'                 => __('Forum Post', 'text_domain'),
        'description'           => __('Forum posts for ECM Forum', 'text_domain'),
        'labels'                => $labels,
        'supports' 				=> array('title', 'editor', 'thumbnail', 'comments', 'author', 'sticky', 'excerpt'),
        'taxonomies'            => array('forum_category', 'forum_tag'),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-admin-post',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
		'map_meta_cap' => true,
		'capabilities' => array(
		// Post-related capabilities
		'edit_post'          => 'edit_ecm_forum_post', 
		'read_post'          => 'read_ecm_forum_post', 
		'delete_post'        => 'delete_ecm_forum_post', 
		'edit_posts'         => 'edit_ecm_forum_posts', 
		'edit_others_posts'  => 'edit_others_ecm_forum_posts', 
		'publish_posts'      => 'publish_ecm_forum_posts', 
		'read_private_posts' => 'read_private_ecm_forum_posts',
		'create_posts'       => 'edit_ecm_forum_posts', // Allows user to create new posts

		// Taxonomy term capabilities
		'manage_terms' => 'manage_forum_category_terms',
		'edit_terms'   => 'edit_forum_category_terms',
		'delete_terms' => 'delete_forum_category_terms',
		'assign_terms' => 'assign_forum_category_terms',
	),
        // ... other arguments
    );

    register_post_type('ecm_forum_post', $args);
}

add_action('init', 'ecm_create_forum_post_type', 0);

//Create Categories

function ecm_create_forum_categories() {
    $labels = array(
        'name'              => _x('Forum Categories', 'taxonomy general name', 'text_domain'),
        'singular_name'     => _x('Forum Category', 'taxonomy singular name', 'text_domain'),
        // ... other labels
    );

    $args = array(
        'hierarchical'      => true, // like categories
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'forum-category'),
    );

    register_taxonomy('forum_category', array('ecm_forum_post'), $args);
}
add_action('init', 'ecm_create_forum_categories');

//Create Taxonomies

function ecm_create_forum_tags() {
    $labels = array(
        'name'                       => _x('Forum Tags', 'taxonomy general name', 'text_domain'),
        'singular_name'              => _x('Forum Tag', 'taxonomy singular name', 'text_domain'),
        // ... other labels
    );

    $args = array(
        'hierarchical'               => false, // like tags
        'labels'                     => $labels,
        'show_ui'                    => true,
        'show_admin_column'          => true,
        'update_count_callback'      => '_update_post_term_count',
        'query_var'                  => true,
        'rewrite'                    => array('slug' => 'forum-tag'),
    );

    register_taxonomy('forum_tag', 'ecm_forum_post', $args);
}
add_action('init', 'ecm_create_forum_tags');

function forum_show_sticky_option__post_edit($post) {
    // Check for your specific custom post type
    if ($post->post_type == 'ecm_forum_post' && current_user_can('edit_others_posts')) {
        $sticky_checkbox_checked = is_sticky($post->ID) ? 'checked="checked"' : '';
        $sticky_span = '<span id="sticky-span" style="margin-left:10px"><input id="sticky" name="sticky" type="checkbox" value="sticky" ' . $sticky_checkbox_checked . ' /> <label for="sticky" class="selectit">' . __('Stick this post to the top') . '</label><br /></span>';
        echo $sticky_span . '<br>';
    }
}
add_action('post_submitbox_misc_actions', 'forum_show_sticky_option__post_edit');