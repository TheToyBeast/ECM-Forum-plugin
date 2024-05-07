<?php

add_action('forum_category_add_form_fields', 'ecm_add_category_separator_field');
function ecm_add_category_separator_field() {
    ?>
    <!-- HTML for add form field -->
    <div class="form-field">
        <label for="ecm-category-separator-title">Category Separator Title</label>
        <input type="text" name="ecm_category_separator_title" id="ecm-category-separator-title">
        <p class="description">Enter a title for the category separator.</p>
    </div>
    <?php }

add_action('forum_category_edit_form_fields', 'ecm_edit_category_separator_field');

function ecm_edit_category_separator_field($term) {
    $title = get_term_meta($term->term_id, 'ecm_category_separator_title', true);
    ?>
    <!-- HTML for edit form field -->
    <tr class="form-field">
        <th scope="row" valign="top"><label for="ecm-category-separator-title">Category Separator Title</label></th>
        <td>
            <input type="text" name="ecm_category_separator_title" id="ecm-category-separator-title" value="<?php echo esc_attr($title); ?>">
            <p class="description">Enter a title for the category separator.</p>
        </td>
    </tr>
    <?php
}

function ecm_handle_new_post_creation() {
	
    if (!isset($_POST['ecm_nonce']) || !wp_verify_nonce($_POST['ecm_nonce'], 'ecm_create_new_post_action')) {
        wp_die('Security check failed');
    }
	
	// Define an array of allowed HTML tags and attributes
$allowed_html = array(
    'a' => array(
        'href' => true,
        'title' => true
    ),
    'img' => array(
        'src' => true,
        'alt' => true,
        'width' => true,  // Allow width attribute
        'height' => true  // Allow height attribute
    ),
    // Add more tags and attributes as needed
);

    $post_title = sanitize_text_field($_POST['title']);
    $post_content = wp_kses($_POST['content'], $allowed_html);
    $parent_category_id = intval($_POST['parent_category']);
    $tags = sanitize_text_field($_POST['tags']);
    $is_private = isset($_POST['private']) ? true : false;
	$current_user_id = get_current_user_id();
	$user_role = get_user_meta($current_user_id, 'ecm_forum_role', true);
	$sticky = isset($_POST['sticky']) && $_POST['sticky'] === 'sticky';

    $new_post = array(
        'post_title'    => $post_title,
        'post_content'  => $post_content,
        'post_status'   => $is_private ? 'private' : 'publish',
        'post_type'     => 'ecm_forum_post', 
        'post_author'   => get_current_user_id()
    );

    $post_id = wp_insert_post($new_post);

    if (!is_wp_error($post_id)) {
        wp_set_post_terms($post_id, array($parent_category_id), 'forum_category');
        
        if (!empty($tags)) {
			$tag_names = explode(',', $tags); // Split the comma-separated tag names into an array

			// Remove leading and trailing whitespace from each tag name
			$tag_names = array_map('trim', $tag_names);

			// Set the tags for the custom post type 'ecm_forum_post'
			wp_set_post_terms($post_id, $tag_names, 'forum_tag', true); // Replace 'forum_tag' with your custom taxonomy name
		}

        // Handle sticky post for moderators
       if ($sticky && $user_role == 'moderator') {
            stick_post($post_id);
        }

        // Handle subscription logic here if needed

        wp_redirect(get_permalink($post_id));
        exit;
    } else {
        wp_die('Failed to create post.');
    }
}
add_action('admin_post_ecm_handle_new_post_creation', 'ecm_handle_new_post_creation');


//custom forum form

function create_forum_post_form_page() {
    // Create a new Forum Post Form page
    $page = array(
        'post_title'   => 'Create Forum Post',
        'post_content' => '[insert_forum_post_form]', // Shortcode to insert the form
        'post_status'  => 'publish',
        'post_type'    => 'page',
    );

    // Insert the page into the database
    $page_id = wp_insert_post($page);

    // Save the page ID as an option
    update_option('forum_post_form_page_id', $page_id);
}

function forum_post_form_shortcode() {
    $current_user_id = get_current_user_id();
	$user_role = get_user_meta($current_user_id, 'ecm_forum_role', true);
	$parent_category_id = isset($_GET['parent_category']) ? intval($_GET['parent_category']) : '';
	$topic_url = get_term_link($parent_category_id, 'forum_category');
	$image_upload_url = plugins_url('image-upload-handler.php', __FILE__);
	 if (isset($_GET['parent_category']) && !empty($_GET['parent_category'])) {
		$topic_url = get_term_link($parent_category_id, 'forum_category'); 
	 } else {
		 $topic_url = site_url().'/forum';
	 }
	
    ob_start();

    // Forum Post Form content
    echo '<div class="_forum-form">
		<a href="' . esc_url($topic_url) . '">Return to Topic</a>';
	?>
	 <ins class="adsbygoogle"
     style="display:block; text-align:center;"
     data-ad-layout="in-article"
     data-ad-format="fluid"
     data-ad-client="ca-pub-6520437489717144"
     data-ad-slot="2177416631"></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>
<?php echo '<p style="font-size:12px; text-align:center;">Once posted, the post cannot be edited.</p>
        <form id="forumForm" method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php?action=ecm_handle_new_post_creation')) . '">';

    if ($user_role == 'moderator') {
        echo '<div style="margin-top:20px" id="forum_post-sticky">
            <input type="checkbox" name="sticky" id="sticky" value="sticky">
            <label for="sticky">Make this post sticky</label>
        </div>';
    }

    echo '<div id="forum_post-title">
        <label for="title">Title:</label>
        <input type="text" name="title" id="title" required>
    </div>
    
    <div id="forum_post-content">
        <label for="content">Content:</label>
        <textarea name="content" rows="5"></textarea>
    </div>
	
	<div id="forum_post-tags">
        <label for="tags">Topic Tags (Separate tags using a comma)</label><br>
        <input type="text" name="tags" id="tags" >
    </div>
    
    
    ' . wp_nonce_field('ecm_create_new_post_action', 'ecm_nonce') . '
	<input type="hidden" name="parent_category" value="' . esc_attr($parent_category_id) . '">
    <input style="margin-top:20px;" type="submit" name="submit" value="Submit">
    </form>
	
   <script>
    tinymce.init({
        selector: "textarea",
        width: "100%",
        height: 400,
        plugins: [
            "link", "lists", "emoticons", "blockquote", "textcolor", "fontsize", "image", "code"
        ],
        toolbar: [
            "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify",
            "bullist numlist outdent indent | link emoticons blockquote | forecolor backcolor | fontsize | image | code"
        ],
		images_upload_url: " '. $image_upload_url . '",
        menubar: false,
        statusbar: false,
		extended_valid_elements: "img[src|id|width|height|align|hspace|vspace],",

    });

    document.addEventListener("DOMContentLoaded", function() {
        document.body.classList.add("forum-post-form");
    });
</script>
    </div>';

    return ob_get_clean();
}
add_shortcode('insert_forum_post_form', 'forum_post_form_shortcode');