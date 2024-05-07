<?php
/**
 * Plugin Name: ECM Forum
 * Plugin URI: https://eastcoastmarketers.ca/forum-plugin
 * Description: A custom forum plugin for WordPress.
 * Version: 1.0
 * Author: ECM - Cristian Ibanez
 * Author URI: https://eastcoastmarketers.ca
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// enqueue css and js to template file

function ecm_enqueue_forum_scripts() {
    wp_enqueue_script('ecm-forum-script', plugin_dir_url(__FILE__) . 'js/forum.js', array('jquery'), null, true);
	wp_enqueue_style('ecm-forum-style', plugin_dir_url(__FILE__) . 'css/style.css?v=1.0');
	wp_enqueue_script('tinymc_js', 'https://cdn.tiny.cloud/1/it4trbncl8x3v26nharx5knc3xqjndv1d7abilkecsdhymg7/tinymce/6/tinymce.min.js', array('jquery'));
	

    // Localize the script with new data
    $translation_array = array(
        'ecm_ajax_url' => admin_url('admin-ajax.php'),
        'ecm_ajax_nonce' => wp_create_nonce('ecm_nonce'),// Nonce for security
		'current_user_id' => get_current_user_id() // Include the current user's ID
		
    );
    wp_localize_script('ecm-forum-script', 'ecm_ajax_object', $translation_array);
}
add_action('wp_enqueue_scripts', 'ecm_enqueue_forum_scripts');

add_action('ecm_daily_message_cleanup', 'ecm_delete_older_messages');

register_deactivation_hook(__FILE__, 'ecm_forum_deactivate');
function ecm_forum_deactivate() {
    // Remove options or perform other cleanup tasks
    // Be cautious about removing user data
    // Example: delete_option('ecm_forum_options');
	
	$timestamp = wp_next_scheduled('ecm_daily_message_cleanup');
    wp_unschedule_event($timestamp, 'ecm_daily_message_cleanup');
}

function create_forum_tables() {
    global $wpdb;

    // Charset for the tables
    $charset_collate = $wpdb->get_charset_collate();

    // Table for general forum data
$forum_table_name = $wpdb->prefix . 'ecm_forum_custom_table';

	// SQL for creating the general forum table
	$sql_forum = "CREATE TABLE `$forum_table_name` (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		user_id mediumint(9) NOT NULL,
		post_id mediumint(9) DEFAULT 0,
		comment_id mediumint(9) DEFAULT 0,
		report_reason text,
		report_details text,
		report_status tinyint(1) DEFAULT 0,
		PRIMARY KEY (id)
	) $charset_collate;";

    // Table for likes and dislikes
	$likes_table_name = $wpdb->prefix . 'ecm_forum_likes_dislikes';

	// SQL for creating the likes and dislikes table with comments support
	$sql_likes = "CREATE TABLE `$likes_table_name` (
		id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
		post_id MEDIUMINT(9) NOT NULL,
		comment_id MEDIUMINT(9) NOT NULL,
		user_id MEDIUMINT(9) NOT NULL,
		action VARCHAR(10) NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY unique_action (post_id, comment_id, user_id)
	) $charset_collate;";
	
	// Table for messaging
	$message_table_name = $wpdb->prefix . 'ecm_messages';
	
	// SQL for creating the messages table
	$sql_meassaging = "CREATE TABLE $message_table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        sender_id bigint(20) NOT NULL,
        receiver_id bigint(20) NOT NULL,
        message text NOT NULL,
        time_sent datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    );";
	
	// Friends List
	
	$friend_table_name = $wpdb->prefix . 'ecm_friendships';

    $sql_friends = "CREATE TABLE $friend_table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id1 bigint(20) NOT NULL,
        user_id2 bigint(20) NOT NULL,
        status varchar(20) NOT NULL,
		is_blocked TINYINT(1) NOT NULL DEFAULT 0,
		blocked_by_user_id bigint(20) DEFAULT NULL,
		updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Include the file for dbDelta and execute the SQL
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_forum);
    dbDelta($sql_likes);
	dbDelta($sql_meassaging);
	dbDelta($sql_friends);
	
	if (!wp_next_scheduled('ecm_daily_message_cleanup')) {
    wp_schedule_event(time(), 'daily', 'ecm_daily_message_cleanup');
		
		create_forum_post_form_page();
}
}

register_activation_hook(__FILE__, 'create_forum_tables');

include_once(plugin_dir_path(__FILE__) . 'includes/custom-post-types.php');
include_once 'includes/reputation.php';
include_once 'includes/vote.php';
include_once 'includes/roles.php';
include_once 'includes/messages.php';
include_once 'includes/forms.php';
include_once 'includes/categories.php';
include_once 'includes/topics.php';
include_once 'includes/time-format.php';
include_once 'includes/breadcrumbs.php';
include_once 'includes/save-post-topic.php';
include_once 'includes/tinymce-fix.php';
include_once 'includes/comments.php';
include_once 'includes/reporting.php';
include_once 'includes/moderation.php';
include_once 'includes/ticker.php';



//add template

function ecm_forum_template_include($template) {
    if (is_page('Forum')) {
        $custom_template = plugin_dir_path(__FILE__) . '/templates/page-forum-post-form.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }
    return $template;
}
add_filter('template_include', 'ecm_forum_template_include');

// add single page template

function ecm_forum_custom_single_template($single_template) {
    global $post;

    if ($post->post_type == 'ecm_forum_post') { // Replace with your custom post type
        $custom_template = plugin_dir_path(__FILE__) . '/templates/single-ecm_forum_post.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }

    return $single_template;
}
add_filter('single_template', 'ecm_forum_custom_single_template');

function ecm_register_forum_template($templates) {
    $templates[plugin_dir_path(__FILE__) . 'templates/ecm_forum_template.php'] = 'ECM Forum';
    return $templates;
}
add_filter('theme_page_templates', 'ecm_register_forum_template');

function ecm_load_forum_template($template) {
    $post = get_post();
    error_log('Current Page: ' . $post->post_title);  // Debugging

    if ('Forum' === $post->post_title) {
        error_log('Switching to custom template');  // Debugging
        $template = plugin_dir_path(__FILE__) . 'templates/ecm_forum_template.php';
    }

    return $template;
}
add_filter('template_include', 'ecm_load_forum_template', 99);

function ecm_create_forum_page() {
    // Check if the page exists
    $page_title = 'Forum';
    $page_check = get_page_by_title($page_title);
    
    if (!isset($page_check->ID)) {
        // Create a new page
        $forum_page = array(
            'post_type'     => 'page',
            'post_title'    => $page_title,
            'post_status'   => 'publish',
            'post_author'   => 1, // or another user ID
        );
        $page_id = wp_insert_post($forum_page);

        // Assign the custom template to the new page
        if ($page_id) {
            update_post_meta($page_id, '_wp_page_template', 'ecm_forum_template.php');
        }
    }
}
register_activation_hook(__FILE__, 'ecm_create_forum_page');

function ecm_cleanup_forum_page() {
    $page_title = 'Forum';
    $page = get_page_by_title($page_title);
    
    if ($page) {
        wp_delete_post($page->ID, true);
    }
}
register_deactivation_hook(__FILE__, 'ecm_cleanup_forum_page');

function ecm_gravatar_profile_link() {
    $user = wp_get_current_user();
    if ($user->ID == 0) {
        return; // No user is logged in
    }

    $gravatar_url = 'https://en.gravatar.com/emails/';
    echo '<a href="' . esc_url($gravatar_url) . '" target="_blank">Change your avatar at Gravatar.com</a>';
}

function ecm_forum_posts_ticker_shortcode() {
    // Arguments for the query
    $args = array(
        'post_type' => 'ecm_forum_post', // Your custom post type
        'posts_per_page' => 5, // Limit to the last 5 posts
        'orderby' => 'date', // Order by post date
        'order' => 'DESC' // Latest posts first
    );

    // The Query
    $the_query = new WP_Query($args);

    // Start capturing the output
    ob_start();

    // Check if the query returns any posts
    if ($the_query->have_posts()) {
        // Start the ticker wrapper
        echo '<div id="ticker-wrapper">';
		echo '<div class="ticker-item"><i class="fa-regular fa-star" style="color:white;font-size:14px;padding-right:10px;"></i><a href="/forum/"> Our Brand New Forum is Live! </a><i class="fa-regular fa-star" style="color:white;font-size:14px;padding-left:10px;"></i></div>';

        // The Loop
        while ($the_query->have_posts()) {
            $the_query->the_post();
            echo '<div class="ticker-item"><i class="fa fa-pencil-alt" style="color:white;font-size:14px;padding-right:10px;"></i> <a style="color:white;font-size:14px;" href="'. get_the_permalink() .'">' . get_the_title() . '</a></div>'; // Output the post title within a ticker item
        }

        // End the ticker wrapper
        echo '</div>';
    } else {
        // No posts found
        echo '<div class="ticker-item">No recent forum posts found.</div>';
    }

    // Restore original Post Data
    wp_reset_postdata();

    // Get the captured output and stop capturing
    $output = ob_get_clean();

    return $output;
}
add_shortcode('ecm_forum_posts_ticker', 'ecm_forum_posts_ticker_shortcode');


