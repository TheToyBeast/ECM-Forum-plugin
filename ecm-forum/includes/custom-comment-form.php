<div class="custom-comment-form">
	<p>This is the new form</p>
    <form id="custom-comment-form" action="<?php echo esc_url( site_url( '/wp-comments-post.php' ) ); ?>" method="post">
        <!-- Your custom comment fields go here -->
        <label for="comment">Comment</label>
        <textarea id="comment" name="comment" rows="4"></textarea>
        <!-- Add any additional fields or buttons as needed -->
        <input type="submit" name="submit" value="Submit Comment" />
		<?php wp_nonce_field('comment_form_nonce', 'comment_nonce_field'); ?>
        <?php comment_id_fields(); ?>
        <?php do_action('comment_form', $post->ID); ?>
    </form>
</div>
<script>
	<?php $image_upload_url = plugins_url('image-upload-handler.php', __FILE__); ?>
    tinymce.init({
        selector: "textarea",
        width: 700,
        height: 400,
        plugins: [
            "link", "lists", "emoticons", "blockquote", "textcolor", "fontsize", "image", "code"
        ],
        toolbar: [
            "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify",
            "bullist numlist outdent indent | link emoticons blockquote | forecolor backcolor | fontsize | image | code"
        ],
		images_upload_url: " <?php echo $image_upload_url?>",
        menubar: false,
        statusbar: false,
		extended_valid_elements: "img[src|id|width|height|align|hspace|vspace],",

    });

    document.addEventListener("DOMContentLoaded", function() {
        document.body.classList.add("forum-post-form");
    });
</script>