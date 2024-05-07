<?php

// Check if the user is logged in and has the capability to post
if (!is_user_logged_in() || !current_user_can('publish_posts')) {
    echo 'You must be logged in and have sufficient permissions to post.';
    return;
}

// Process form submission
if (isset($_POST['ecm-submitted'])) {
    // Validate nonce
    if (!isset($_POST['ecm_forum_post_nonce']) || !wp_verify_nonce($_POST['ecm_forum_post_nonce'], 'ecm_forum_post_nonce_action')) {
        echo '<div class="error">Security check failed.</div>';
        return;
    }

    // Sanitize and validate input data
    $title = sanitize_text_field($_POST['ecm-title']);
    $content = sanitize_textarea_field($_POST['ecm-content']);

    $errors = array();
    if (empty($title)) {
        $errors[] = 'Title is required.';
    }
    if (empty($content)) {
        $errors[] = 'Content is required.';
    }

    // If there are no errors, proceed with post creation
    if (count($errors) == 0) {
        $new_post = array(
            'post_title'    => $title,
            'post_content'  => $content,
            'post_status'   => 'pending', // or 'publish'
            'post_type'     => 'ecm_forum_post' // your custom post type
        );
        $post_id = wp_insert_post($new_post);

        if ($post_id) {
            // Success message or redirect
            echo '<div class="success">Post submitted successfully!</div>';
        } else {
            echo '<div class="error">There was an error in submitting your post.</div>';
        }
    } else {
        // Display errors to the user
        foreach ($errors as $error) {
            echo '<div class="error">' . esc_html($error) . '</div>';
        }
    }
}

?>

<form id="ecm-forum-post-form" action="<?php echo admin_url('admin-post.php'); ?>" method="post" enctype="multipart/form-data">
    <div>
        <label for="ecm-title">Title:</label>
        <input type="text" id="ecm-title" name="ecm-title" required>
    </div>

    <div>
        <label for="ecm-content">Content:</label>
        <textarea id="ecm-content" name="ecm-content" rows="5" required></textarea>
    </div>

    <!-- Include other fields as needed -->

    <?php wp_nonce_field('ecm_create_new_post_action', 'ecm_forum_post_nonce'); ?>
	<script>
        document.addEventListener("DOMContentLoaded", function() {
    tinymce.init({
        selector: "textarea",
        width: 700,
        height: 400,
        setup: function (editor) {
            editor.on('change', function () {
                tinymce.triggerSave(); // Save the TinyMCE content to the textarea
            });
        }
    });

    // Handle form submission
    var form = document.getElementById("ecm-forum-post-form"); // Replace with your form's ID
    if(form){
        form.addEventListener("submit", function() {
            tinymce.triggerSave(); // Save TinyMCE content to textarea before submitting the form
        });
    }
});
</script>

    <div>
        <input type="submit" name="ecm-submitted" value="Submit Post">
    </div>
</form>
