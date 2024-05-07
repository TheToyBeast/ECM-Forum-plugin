<?php
/*
Template Name: ECM Forum
*/
include_once(ABSPATH . 'wp-admin/includes/plugin.php');
get_header();
?>
<div class="g-columns__group">
<div class="g-columns__item--nine">
<?php echo ecm_forum_breadcrumbs(); ?>

<div class="forum-container">
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
	<li class="ecm-tab-item"><a href="#forums" class="tab-toggle">Forums</a></li>
	<li class="ecm-tab-item"><a href="#recent-posts" class="tab-toggle">Recent Posts</a></li>
	<?php if (!is_user_logged_in()) : ?>
		<li class="ecm-tab-item"><a href="<?php echo wp_registration_url(); ?>">Register</a></li>
		<li class="ecm-tab-item"><a href="<?php echo wp_login_url(); ?>">Login</a></li>
     <?php endif; ?>
	<?php
	$current_user_id = get_current_user_id();
	$user_role = get_user_meta($current_user_id, 'ecm_forum_role', true);

	if ($user_role == 'moderator') : ?>
				<!-- Other tabs here -->
				<li class="ecm-tab-item _create"><a href="#create-category" class="tab-toggle">Create Category</a></li>
	<?php endif; ?>
	<?php if (is_user_logged_in()) : ?>
	<li class="ecm-tab-item _user"><a href="' . site_url('/user-profile/') .'">User Profile</a></li>
	<?php endif; ?>	
	</ul>
			
			<div class="ecm-tab-content" id="forums">
			<!-- Content for Forums tab -->
				<?php echo do_shortcode( '[ecm_forum_categories]' ); ?>
			</div>
			
			<div class="ecm-tab-content" id="recent-posts">
			<h2>Recent Posts</h2>
			<?php
			$recent_posts_args = array(
				'post_type' => 'ecm_forum_post', // Replace with your actual custom post type
				'posts_per_page' => 20, // Number of posts to show
				'orderby' => 'date', // Order by post date
				'order' => 'DESC' // Show latest posts first
			);

			$recent_posts_query = new WP_Query($recent_posts_args);
			
			echo '<div id="forums">';

			if ($recent_posts_query->have_posts()) {
				echo '<div class="category-container">';
				while ($recent_posts_query->have_posts()) {
					$recent_posts_query->the_post();
					$replies_count = get_comments_number(get_the_ID());
					echo '<div class="cat-item"><i class="fa fa-pencil-alt" style="font-size:14px;"></i> <b><a href="' . get_permalink() . '">' . get_the_title() . '</a><div class="cat-count">Posted: ' .  ecm_time_ago_format(get_the_date('Y-m-d H:i:s')) . ' </b>(' . $replies_count . ' Replies)</div></div>';
				}
				echo '</div>';
			} else {
				echo '<p>No recent forum posts found.</p>';
			}
				echo '</div>';

			// Restore original Post Data
			wp_reset_postdata();
			?>
			</div>
			<div class="ecm-tab-content" id="create-category">
			<h2>Create Category</h2>
			<div class="explain" style="margin-top:0px;">*This tab is only available to moderators</div>
			<!-- Form for creating a new category -->
			<form id="create-category-form" action="<?php echo admin_url('admin-post.php'); ?>" method="post">
				<select name="parent_category" id="parent-category">
					<option value="">Select a Category Separator (optional)</option>
					<?php
					$separators = get_terms(array(
						'taxonomy' => 'forum_category',
						'hide_empty' => false,
						'parent' => 0, // Fetch only top-level terms
					));
					foreach ($separators as $separator) {
						echo '<option value="' . esc_attr($separator->term_id) . '">' . esc_html($separator->name) . '</option>';
					}
					?>
				</select>
				<input type="hidden" name="action" value="ecm_create_new_category">
				<div class="explain">*Choose the header you want your new category to fall under</div>
				<label for="new-category-name">Category Name:</label>
				<input type="text" id="new-category-name" name="new_category_name" required>
				<div class="explain">*Name your new category</div>
				<label for="category-weight">Category Weight:</label>
				<input type="number" id="category-weight" name="category_weight" min="0" required>
				<div class="explain">*A number is required for listing</div>
				 <?php wp_nonce_field('ecm_create_new_category_action', 'ecm_nonce'); ?>
				<input type="submit" value="Create Category">
			</form>
			</div>
		</div>
	</div>
</div>
</div>
</div>
<?php
get_sidebar();
echo '</div>';
get_footer();
?>