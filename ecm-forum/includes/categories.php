<?php

add_action('created_forum_category', 'ecm_save_category_separator_title');
add_action('edited_forum_category', 'ecm_save_category_separator_title');
function ecm_save_category_separator_title($term_id) {
    if (isset($_POST['ecm_category_separator_title'])) {
        update_term_meta(
            $term_id,
            'ecm_category_separator_title',
            sanitize_text_field($_POST['ecm_category_separator_title'])
        );
    }
}

function ecm_handle_new_category_creation() {
    // Check nonce
    if (!isset($_POST['ecm_nonce']) || !wp_verify_nonce($_POST['ecm_nonce'], 'ecm_create_new_category_action')) {
    wp_send_json_error(array('message' => 'Security check failed'));
    return;
	}	

    $separator_id = isset($_POST['parent_category']) ? intval($_POST['parent_category']) : 0;
	$category_weight = isset($_POST['category_weight']) ? intval($_POST['category_weight']) : 0;
	$category_name = sanitize_text_field($_POST['new_category_name']);

    $new_category_args = array(
        'name' => $category_name,
        'taxonomy' => 'forum_category',
        'parent' => $separator_id,  // Set the parent category (separator) if provided
    );

	$result = wp_insert_term($category_name, 'forum_category', $new_category_args);

    if (is_wp_error($result)) {
        error_log('Error creating term: ' . $result->get_error_message());
        wp_send_json_error(array('message' => 'An error occurred while creating the category.'));
    } else {
        // Update term meta for category weight
        update_term_meta($result['term_id'], 'ecm_category_weight', $category_weight);

        error_log('Term created successfully. Term ID: ' . $result['term_id']);
        wp_send_json_success(array('message' => 'Category created successfully.'));
    }
}
add_action('wp_ajax_ecm_create_new_category', 'ecm_handle_new_category_creation');

function ecm_edit_category_weight_field($term) {
    // Get the current weight of the category
    $current_weight = get_term_meta($term->term_id, 'ecm_category_weight', true);
    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="ecm-category-weight">Category Weight</label></th>
        <td>
            <input type="number" name="ecm_category_weight" id="ecm-category-weight" value="<?php echo esc_attr($current_weight); ?>" min="0">
            <p class="description">Set the weight for this category. Categories are ordered by weight.</p>
        </td>
    </tr>
    <?php
}
add_action('forum_category_edit_form_fields', 'ecm_edit_category_weight_field');

function ecm_save_category_weight($term_id) {
    if (isset($_POST['ecm_category_weight'])) {
        update_term_meta(
            $term_id,
            'ecm_category_weight',
            intval($_POST['ecm_category_weight'])
        );
    }
}
add_action('edited_forum_category', 'ecm_save_category_weight');

function ecm_get_category_post_count($term_id) {
    $args = array(
        'post_type' => 'ecm_forum_post', // Change to your custom post type
        'tax_query' => array(
            array(
                'taxonomy' => 'forum_category',
                'field' => 'term_id',
                'terms' => $term_id,
            ),
        ),
        'fields' => 'ids', // Only get post IDs for performance
    );

    $query = new WP_Query($args);
    return $query->found_posts;
}

function ecm_get_category_replies_count($term_id) {
    $args = array(
        'post_type' => 'ecm_forum_post', // Change to your custom post type
        'tax_query' => array(
            array(
                'taxonomy' => 'forum_category',
                'field' => 'term_id',
                'terms' => $term_id,
            ),
        ),
        'fields' => 'ids', // Only get post IDs for performance
    );

    $query = new WP_Query($args);

    $total_replies = 0;
    foreach ($query->posts as $post_id) {
        $total_replies += get_comments_number($post_id);
    }

    return $total_replies;
}

function ecm_display_forum_categories($parent_id = 0, $current_depth = 0, $max_depth = 1) {
    $args = array(
        'taxonomy' => 'forum_category',
        'parent' => $parent_id,
        'hide_empty' => false,
        'meta_key' => 'ecm_category_weight',
        'orderby' => 'meta_value_num',
        'order' => 'ASC',
    );

    $categories = get_terms($args);

    if (!empty($categories) && $current_depth <= $max_depth) {
        echo '<div class"category-container">';
        foreach ($categories as $category) {
            // Check if the category is a separator
            $is_separator = get_term_meta($category->term_id, 'ecm_category_separator_title', true); // Adjust meta key as needed

            if (!$is_separator) {
                // It's a regular category, add a link
                $category_link = get_term_link($category);
                if (!is_wp_error($category_link)) {
                    echo '<div class="cat-item"><i class="fas fa-comments"></i> <a href="' . esc_url($category_link) . '">' . esc_html($category->name) . '</a>';
					$topics_args = array(
						'taxonomy' => $category->taxonomy,
						'parent' => $category->term_id,
						'hide_empty' => false,
						'number' => 2, // Limit to 2 topics
						'orderby' => 'id', // Assuming 'id' as the sort parameter for latest
						'order' => 'DESC'
					);
					
					$topics = get_terms($topics_args);
					if (!empty($topics)) {
						echo '<ul class="topics-list">';
						echo '<div class="latest-post"><i class="fa-solid fa-paperclip"></i> Latest Topics:  ';
						foreach ($topics as $topic) {
							echo '<li><a href="' . esc_url(get_term_link($topic)) . '">' . esc_html($topic->name) . '</a></li>';
						}
						echo '</div></ul>';
					}
					
					
					$latest_post_args = array(
						'post_type' => 'ecm_forum_post', // Change to your custom post type if different
						'posts_per_page' => 1,
						'tax_query' => array(
							array(
								'taxonomy' => 'forum_category',
								'field' => 'term_id',
								'terms' => $category->term_id,
							),
						),
					);
					$latest_post_query = new WP_Query($latest_post_args);
					if ($latest_post_query->have_posts()) {
						$latest_post_query->the_post();
						echo '<div class="latest-post"><i class="fa fa-pencil-alt"></i> Latest Post: <a href="' . get_permalink() . '">' . get_the_title() . '</a> - Posted: '. ecm_time_ago_format(get_the_date('Y-m-d H:i:s')) . '</div>';
					}
					
					// Fetch the number of posts in this category
					$posts_count = ecm_get_category_post_count($category->term_id);
					echo '<div class="cat-count"> (' . $posts_count . ' Posts';

					// Fetch the total number of replies for this category
					$replies_count = ecm_get_category_replies_count($category->term_id);
					echo ', ' . $replies_count . ' Replies)</div>';

            wp_reset_postdata();
                } else {
                    echo '<div>' . esc_html($category->name);
                }
            } else {
                // It's a separator, don't add a link
                echo '<div class="cat-sep"><div class="cat-sep-title">' . esc_html($category->name) . '</div>';
            }
            
			// Recursive call with increased depth
            ecm_display_forum_categories($category->term_id, $current_depth + 1, $max_depth);
            echo '</div>';
        }
        echo '</div>';
    }
}

function ecm_forum_categories_shortcode() {
    ob_start();
    ecm_display_forum_categories(); // Start with top-level categories
    return ob_get_clean();
}

add_shortcode('ecm_forum_categories', 'ecm_forum_categories_shortcode');

function ecm_use_custom_taxonomy_template($template) {
    if (is_tax('forum_category')) { // Replace with your actual taxonomy name
        $custom_template = plugin_dir_path(__FILE__) . '../templates/taxonomy-ecm_forum_category.php'; // Specify the path to your template
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }
    return $template;
}
add_filter('template_include', 'ecm_use_custom_taxonomy_template');