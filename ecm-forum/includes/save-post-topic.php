<?php

add_action('wp_ajax_ecm_save_post', 'ecm_save_post_to_user_meta');
function ecm_save_post_to_user_meta() {
    // Check if nonce is set and valid
    if (!isset($_POST['ecm_nonce']) || !wp_verify_nonce($_POST['ecm_nonce'], 'ecm_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'));
        return;
    }

    $user_id = get_current_user_id();
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;

    if ($post_id) {
        // Logic for saving posts
        // Retrieve the array of saved posts from the user's meta
        $saved_posts = get_user_meta($user_id, 'ecm_saved_posts', true) ?: array();

        // Add the post ID to the array if it's not already there
        if (!in_array($post_id, $saved_posts)) {
            $saved_posts[] = $post_id;
            // Update the user's meta with the new array of saved posts
            update_user_meta($user_id, 'ecm_saved_posts', $saved_posts);
            wp_send_json_success(array('message' => 'Post saved successfully'));
        } else {
            wp_send_json_success(array('message' => 'Post already saved'));
        }
    } elseif ($parent_id) {
        // Logic for saving topics
        $saved_topics = get_user_meta($user_id, 'ecm_saved_topics', true) ?: array();
        if (!in_array($parent_id, $saved_topics)) {
            array_push($saved_topics, $parent_id);
            update_user_meta($user_id, 'ecm_saved_topics', $saved_topics);
            wp_send_json_success(array('message' => 'Topic saved successfully'));
        } else {
            wp_send_json_success(array('message' => 'Topic already saved'));
        }
    } else {
        wp_send_json_error(array('message' => 'Invalid ID'));
    }
}

function ecm_display_saved_items($atts) {
    $user_id = get_current_user_id();
    $atts = shortcode_atts(array(
        'type' => 'posts', // Default type is posts
    ), $atts);

    $output = '';
    $meta_key = $atts['type'] === 'topics' ? 'ecm_saved_topics' : 'ecm_saved_posts';
    $saved_items_ids = get_user_meta($user_id, $meta_key, true);

    if (empty($saved_items_ids) || !is_array($saved_items_ids)) {
        return "No saved {$atts['type']} found.";
    }

    if ($atts['type'] === 'topics') {
        // Logic for displaying saved topics
        foreach ($saved_items_ids as $term_id) {
           $term = get_term($term_id);
			$output .= '<div class="_saved-post">';
			if (!is_wp_error($term) && $term) {
            $output .= '<h3><a href="' . get_term_link($term) . '">' . esc_html($term->name) . '</a></h3>';
			
			$args = array(
                    'post_type' => 'ecm_forum_post',
                    'posts_per_page' => 2,
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'forum_category',
                            'field' => 'term_id',
                            'terms' => $term_id,
                        ),
                    ),
                );
                $recent_posts_query = new WP_Query($args);
                if ($recent_posts_query->have_posts()) {
					$output .= '<p><b>Latest Posts: </b></p>';
                    $output .= '<ul>';
                    while ($recent_posts_query->have_posts()) {
                        $recent_posts_query->the_post();
                        $output .= '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
                    }
                    wp_reset_postdata();
                    $output .= '</ul>';
                } else {
                    $output .= '<p>No recent posts found in this topic.</p>';
                }	
				$output .= ' <a href="#" class="remove-saved-item" data-type="topic" data-id="' . esc_attr($term_id) . '">Remove</a>';
			}
			$output .= '</div>';
        }
		
    } else {
        // Logic for displaying saved posts
        $args = array(
            'post_type' => 'ecm_forum_post',
            'post__in'  => $saved_items_ids,
            'orderby'   => 'post__in'
        );

        $saved_posts_query = new WP_Query($args);
        if ($saved_posts_query->have_posts()) {
            while ($saved_posts_query->have_posts()) {
				$saved_posts_query->the_post(); // Move the_post() to the beginning

        		$savedcontent = get_the_content();
				
				$allowed_tags = [
                'a' => [
                    'href' => true,
                    'title' => true
                ],
                'strong' => [],
                'em' => [],
                'span' => [
                    'class' => true
                ]
            ];

            // Apply the allowed HTML tags and attributes
            $filtered_content = wp_kses($savedcontent, $allowed_tags);
				
			$filtered_content = trim($filtered_content); // Trim any leading/trailing whitespace
			if (strlen($filtered_content) > 250) {
            // If the text content exceeds 250 characters, truncate and add "..."
            $text_content= substr($filtered_content, 0, 250) . '...';
        	} else {
				$text_content= $filtered_content;
			}
				
                $output .= '<div class="_saved-post"><h3><a href="' . get_permalink() . '">' . get_the_title() . '</a></h3>';
				$output .= '<b>Post Content:</b><br>';
				$output .= $filtered_content;
				$output .= ' <br><a href="#" class="remove-saved-item" data-type="post" data-id="' . get_the_ID() . '">Remove</a></h2></div>';
            }
            wp_reset_postdata();
        } else {
            $output .= "No saved posts found.";
        }
    }

    return $output;
}

add_shortcode('ecm_saved_items', 'ecm_display_saved_items');

add_action('wp_ajax_ecm_remove_saved_item', 'ecm_remove_saved_item');
function ecm_remove_saved_item() {
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ecm_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'));
        return;
    }

    $user_id = get_current_user_id();
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $item_type = isset($_POST['item_type']) ? sanitize_text_field($_POST['item_type']) : '';

    if ($item_id && $item_type) {
        $meta_key = $item_type === 'topic' ? 'ecm_saved_topics' : 'ecm_saved_posts';
        $saved_items = get_user_meta($user_id, $meta_key, true);

        if (($key = array_search($item_id, $saved_items)) !== false) {
            unset($saved_items[$key]);
            update_user_meta($user_id, $meta_key, $saved_items);
            wp_send_json_success(array('message' => 'Item removed successfully'));
        } else {
            wp_send_json_error(array('message' => 'Item not found'));
        }
    } else {
        wp_send_json_error(array('message' => 'Invalid item'));
    }
}