<?php

function custom_ecm_forum_post_comments_template($comment_template) {
    // Check if the current post is of the 'ecm_forum_post' post type
    if (get_post_type() === 'ecm_forum_post') {
        // Modify the comment template to match the desired structure
        $comment_template = plugin_dir_path(__FILE__) . '/custom-comment-template.php'; // Replace with the path to your custom comment template
    }

    return $comment_template;
}

// Hook into the comments_template filter
add_filter('comments_template', 'custom_ecm_forum_post_comments_template');

function modify_ecm_forum_comments($comments) {
    // Check if we are in the loop for the ecm_forum_post custom post type
    if (is_singular('ecm_forum_post')) {
        // Sort the comments in descending order (newest to oldest)
        usort($comments, function($a, $b) {
            return strtotime($b->comment_date) - strtotime($a->comment_date);
        });

        // Flatten the comments (remove hierarchy)
        $flat_comments = array();
        foreach ($comments as $comment) {
            $flat_comments[] = $comment;
        }

        return $flat_comments;
    }

    return $comments; // Return comments unchanged for other post types
}
add_filter('comments_array', 'modify_ecm_forum_comments');

function allow_images_in_comments($allowed_tags) {
    // Add 'img' tag and its attributes to the list of allowed tags
    $allowed_tags['img'] = array(
        'src' => true,
        'alt' => true,
        'title' => true,
        'width' => true,
        'height' => true,
    );
	
	$allowed_tags['span'] = array(
        'class' => true,
		'style' => true,
    );

    return $allowed_tags;
}

// Hook the custom function to the 'comment_text' filter
add_filter('comment_text', 'shortcode_unautop');
add_filter('comment_text', 'do_shortcode');
add_filter('wp_kses_allowed_html', 'allow_images_in_comments', 10, 2);

function allow_style_attribute_for_comments($allowed_tags) {
    // Add the 'style' attribute to the allowed attributes for comments
    $allowed_tags['p']['style'] = true;

    return $allowed_tags;
}
add_filter('wp_kses_allowed_html', 'allow_style_attribute_for_comments', 10, 2);