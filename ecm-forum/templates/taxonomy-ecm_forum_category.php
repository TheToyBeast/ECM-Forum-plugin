<?php
// Header
include_once(ABSPATH . 'wp-admin/includes/plugin.php');
get_header();

$current_term = get_queried_object();

// Function to determine the depth of a term
function ecm_get_term_depth($term_id, $taxonomy) {
    $depth = 0;
    while ($term_id > 0) {
        $term = get_term($term_id, $taxonomy);
        if (!$term || is_wp_error($term)) {
            break;
        }
        $term_id = $term->parent;
        $depth++;
    }
    return $depth;
}

// Get the depth of the current term
$current_term_depth = ecm_get_term_depth($current_term->term_id, $current_term->taxonomy);
?>
<div class="g-columns__group">
	<div class="g-columns__item--nine">
	<?php
		echo ecm_forum_breadcrumbs();
		//Category Template
		if ($current_term_depth === 2) { ?>
		<div class="ecm_forum">
	 <ins class="adsbygoogle"
     style="display:block; text-align:center;"
     data-ad-layout="in-article"
     data-ad-format="fluid"
     data-ad-client="ca-pub-6520437489717144"
     data-ad-slot="2177416631"></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>
			<div class="ecm-tabs">
				<ul class="ecm-tab-list">
					<li class="ecm-tab-item"><a href="<?php echo site_url() ?>/forum">Return to Forums</a></li>
					<li class="ecm-tab-item"><a href="#topics" class="tab-toggle">Topics</a></li>
					<li class="ecm-tab-item"><a href="#recent-posts" class="tab-toggle">Recent Posts</a></li>
					<?php if (!is_user_logged_in()) : ?>
					<li class="ecm-tab-item"><a href="<?php echo wp_registration_url(); ?>">Register</a></li>
					<li class="ecm-tab-item"><a href="<?php echo wp_login_url(); ?>">Login</a></li>
					<?php endif; ?>
					<?php if (is_user_logged_in()) : ?>
					<li class="ecm-tab-item _create"><a href="#create-topic" class="tab-toggle">Create Topic</a></li>
					<li class="ecm-tab-item"><a href="' . site_url('/user-profile/') .'">User Profile</a></li>
					<?php endif; ?>
				</ul>
				<?php 
				$current_term = get_queried_object();						?>
				<div class="ecm-tab-content" id="topics">
				<?php
				echo '<h2 class="cat-sep-title">Topics under ' . esc_html($current_term->name) . '</h2>';

				if ($current_term instanceof WP_Term) {
					$subcategories_args = array(
						'taxonomy' => $current_term->taxonomy,
						'parent' => $current_term->term_id,
						'hide_empty' => false,
					);

					$subcategories = get_terms($subcategories_args);

					if (!empty($subcategories)) {
						echo '<div class="category-container">';
						foreach ($subcategories as $subcategory) {
							echo '<div class="cat-item"><i class="fas fa-comments"></i> <a href="' . esc_url(get_term_link($subcategory)) . '">' . esc_html($subcategory->name) . '</a>';

												// Fetch the number of posts in this category
							$posts_count = ecm_get_category_post_count($subcategory->term_id);
							echo '<div class="cat-count"> (' . $posts_count . ' Posts';

							// Fetch the total number of replies for this category
							$replies_count = ecm_get_category_replies_count($subcategory->term_id);
							echo ', ' . $replies_count . ' Replies)</div></div>';
						}

						echo '</div>';
					} else {
						echo '<p>No topics found under ' . esc_html($current_term->name) . '.</p>';
					}
				}

				?>
				</div>
				<div class="ecm-tab-content" id="recent-posts">
				<!-- Recent posts content will be loaded here -->
				<?php
				echo '<h2>Recent posts in ' . esc_html($current_term->name) . '</h2>';
				$current_category_id = get_queried_object_id(); // Get the current category ID
				$current_term = get_queried_object();

				if ($current_term instanceof WP_Term) {
					// This is the current category's ID and taxonomy
					$current_term_id = $current_term->term_id;
					$current_taxonomy = $current_term->taxonomy;
				}
				$recent_posts_args = array(
					'post_type' => 'ecm_forum_post', // or your custom post type
					'posts_per_page' => 5, // Number of recent posts to show
					'tax_query' => array(
						array(
							'taxonomy' => $current_taxonomy,
							'field' => 'term_id',
							'terms' => $current_category_id,
						),
					),
				);

				$recent_posts_query = new WP_Query($recent_posts_args);
				echo '<div id="forums">';
				// Loop through the posts and display them
				if ($recent_posts_query->have_posts()) {
					echo '<div class="category-container">';
					while ($recent_posts_query->have_posts()) {
						$recent_posts_query->the_post();
						$replies_count = get_comments_number(get_the_ID());
						echo '<div class="cat-item"><i class="fa fa-pencil-alt" style="font-size:14px;"></i> <a href="' . get_permalink() . '">' . get_the_title() . '</a><div class="cat-count"><b>Posted: ' .  ecm_time_ago_format(get_the_date('Y-m-d H:i:s')) . ' </b>(' . $replies_count . ' Replies)</div></div>';
					}
					echo '</div>';
				} else {
					echo '<p>No recent forum posts found.</p>';
				}
				echo '</div>';

				?>
    			</div>
				<div class="ecm-tab-content" id="create-topic">
				<h2>Create Topic</h2>
				<!-- Form for creating a new topic (subcategory) -->
				<form id="create-topic-form" action="<?php echo admin_url('admin-post.php'); ?>" method="post">
				<input type="hidden" name="action" value="ecm_create_new_topic">
				<input type="hidden" name="parent_category" value="<?php echo esc_attr($current_term_id); ?>">
				<input type="hidden" name="taxonomy_name" value="<?php echo esc_attr($current_taxonomy); ?>">
				<label for="new-topic-name">Topic Name:</label>
				<input type="text" id="new-topic-name" name="new_topic_name" required>
				<?php echo '<div class="explain">*Your new topic will appear under ' . esc_html($current_term->name) . '</div>' ?>
				<?php wp_nonce_field('ecm_create_new_topic_action', 'ecm_nonce'); ?>
				<input type="submit" value="Create Topic">
				</form>
    			</div>
			</div>
		</div>
	</div>
		<?php
		}
		else
			//Topics Template
		{ 
			$parent_term_id = $current_term->parent;
			$parent_term_link = get_term_link($parent_term_id, $current_term->taxonomy);
			$create_post_form_url = site_url() . '/create-forum-post/'; // Get the permalink of the form page
			$current_parent_id = get_queried_object_id();
			$create_post_form_url_with_param = add_query_arg('parent_category', $current_parent_id, $create_post_form_url);
			if (is_user_logged_in()) {
			$post_id = get_the_ID();
		}

		?>

    	<div class="ecm_forum">
		<?php echo '<button id="save-post-btn" data-parentid="' . esc_attr($current_parent_id) . '">Save Topic</button>'; ?>
	 <ins class="adsbygoogle"
     style="display:block; text-align:center;"
     data-ad-layout="in-article"
     data-ad-format="fluid"
     data-ad-client="ca-pub-6520437489717144"
     data-ad-slot="2177416631"></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>
			<div class="ecm-tabs">
				<ul class="ecm-tab-list">
					<li class="ecm-tab-item"><a href="<?php echo site_url() ?>/forum">Return to Forums</a></li>
					<li class="ecm-tab-item"><a href="<?php echo esc_url($parent_term_link) ?>">Return to Topics</a></li>
					<li class="ecm-tab-item"><a href="#recent-posts" class="tab-toggle">Recent Posts</a></li>
					<?php if (!is_user_logged_in()) : ?>
					<li class="ecm-tab-item"><a href="<?php echo wp_registration_url(); ?>">Register</a></li>
					<li class="ecm-tab-item"><a href="<?php echo wp_login_url(); ?>">Login</a></li>
     				<?php endif; ?>
					<?php if (is_user_logged_in()) : ?>
					<li class="ecm-tab-item"><?php echo '<a href="' . $create_post_form_url_with_param . '">Create New Post</a>'; ?></li>
					<li class="ecm-tab-item"><a href="' . site_url('/user-profile/') .'">User Profile</a></li>
					<?php endif; ?>
				</ul>
				<?php
				$current_term = get_queried_object();
				$current_topic_name = $current_term->name; // Retrieves the name of the current term
				// Now you can use $current_topic_name as needed
				echo '<h2  class="cat-sep-title"> Posts in Current Topic: ' . esc_html($current_topic_name) . '</h2>';?>
				<div class="ecm-tab-content" id="posts">
				<?php if ($current_term instanceof WP_Term) {
			 	$subcategories_args = array(
				'taxonomy' => $current_term->taxonomy,
				'parent' => $current_term->term_id,
				'hide_empty' => false,
				);
				$subcategories = get_terms($subcategories_args);
				if (!empty($subcategories)) {
				echo '<h2>Topics in ' . esc_html($current_term->name) . '</h2>';
				echo '<div class="category-container">';
				foreach ($subcategories as $subcategory) {
				echo '<div class="cat-item"><i class="fas fa-comments"></i> <a href="' . esc_url(get_term_link($subcategory)) . '">' . esc_html($subcategory->name) . '</a>';
				// Fetch the number of posts in this category
				$posts_count = ecm_get_category_post_count($subcategory->term_id);
				echo '<div class="cat-count"> (' . $posts_count . ' Posts';
				// Fetch the total number of replies for this category
				$replies_count = ecm_get_category_replies_count($subcategory->term_id);
				echo ', ' . $replies_count . ' Replies)</div></div>';
				}
				echo '</div>';
				} else {
				echo '<p>No subcategories found under ' . esc_html($current_term->name) . '.</p>';
				}
				}?>
				</div>
				<div class="ecm-tab-content" id="recent-posts">	
        		<!-- Recent posts content will be loaded here -->
				<?php $current_category_id = get_queried_object_id(); // Get the current category ID
				$current_term = get_queried_object();
				if ($current_term instanceof WP_Term) {
				// This is the current category's ID and taxonomy
				$current_term_id = $current_term->term_id;
				$current_taxonomy = $current_term->taxonomy;
				}
				$recent_posts_args = array(
					'post_type' => 'ecm_forum_post', // or your custom post type
					'posts_per_page' => -1, // Number of recent posts to show
					'tax_query' => array(
						array(
							'taxonomy' => $current_taxonomy,
							'field' => 'term_id',
							'terms' => $current_category_id,
						),
					),
				);
				$recent_posts_query = new WP_Query($recent_posts_args);
				// Loop through the posts and display them
				echo '<div id="forums"><div class="category-container">';
				if ($recent_posts_query->have_posts()) {
					while ($recent_posts_query->have_posts()) {
						$recent_posts_query->the_post();
						$replies_count = get_comments_number(get_the_ID());
						echo '<div class="cat-item"><i class="fa fa-pencil-alt" style="font-size:14px;"></i> <b><a href="' . get_permalink() . '">' . get_the_title() . '</a><div class="cat-count">Posted: ' .  ecm_time_ago_format(get_the_date('Y-m-d H:i:s')) . ' </b>(' . $replies_count . ' Replies)</div></div>';
					}
				} else {
					echo '<p>No recent forum posts found.</p>';
				}
					echo '</div></div>';
				?>
				</div>
			</div>
		</div>
	</div>

<?php
}
?>

<?php
get_sidebar();
echo '</div>';
// Footer
get_footer();
?>