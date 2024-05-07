<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package newco_theme
 */

get_header();
$author_id = get_the_author_meta('ID');
// Assuming you're in the loop and have access to the post ID
$post_id = get_the_ID();
$user_id = get_current_user_id();
$like_dislike_counts = ecm_get_like_dislike_counts($post_id);
echo ecm_forum_breadcrumbs();

?>
<div class="g-columns__group">
	<div class="g-columns__item--nine">
		<div class="ecm_forum" style="position:relative;">
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
			<?php if (is_user_logged_in()) {
			$post_id = get_the_ID();
			echo '<button id="save-post-btn" data-postid="' . esc_attr($post_id) . '"><i class="fa-solid fa-bookmark"></i> Save Post</button>';
			} ?>
			<ul class="ecm-tab-list">
				<li class="ecm-tab-item"><a href="<?php echo site_url() ?>/forum">Return to Forums</a></li>
				<?php if (!is_user_logged_in()) : ?>
				<li class="ecm-tab-item"><a href="<?php echo wp_registration_url(); ?>">Register</a></li>
				<li class="ecm-tab-item"><a href="<?php echo wp_login_url(); ?>">Login</a></li>
				<?php endif; ?>
				<?php if (is_user_logged_in()) : ?>
				<li class="ecm-tab-item"><a href="' . site_url('/user-profile/') .'">User Profile</a></li>
				<?php endif; ?>
			</ul>
			</div>
			<!-- AddToAny BEGIN -->
			<div class="a2a_kit a2a_kit_size_32 a2a_default_style">
			<span style="padding-right:10px;width:auto !important">Share : </span>
			<a class="a2a_button_facebook"></a>
			<a class="a2a_button_twitter"></a>
			<a class="a2a_button_linkedin"></a>
			<a class="a2a_button_pinterest"></a>
			<a class="a2a_button_reddit"></a>
			</div>
			<script async src="https://static.addtoany.com/menu/page.js"></script>
			<!-- AddToAny END -->
			<div class="ecm-forum-post-container">
					<?php
					$author_id = get_the_author_meta('ID');
					$nickname = get_the_author_meta('nickname', $author_id);
					$author_posts = count_user_posts($author_id, 'ecm_forum_post');
					$author_role = get_user_meta($author_id, 'ecm_forum_role', true);
					$author_reputation = get_user_meta($author_id, 'ecm_reputation_score', true);
					$join_date = get_the_author_meta('user_registered');
					$gamer_tag = get_user_meta($author_id, 'gamertag', true); // Assuming 'gamer_tag' is a user meta
					$favorite_game = get_user_meta($author_id, 'favorite_game', true); // Assuming 'favorite_game' is a user meta
					$current_user_id = get_current_user_id();
					$user_role = get_user_meta($current_user_id, 'ecm_forum_role', true);
					$flair_icon_url = get_user_meta($author_id, 'selected_flair', true);
					$share_profile = get_user_meta($author_id, 'share_profile', true);
					$profile_link = get_author_profile_link($author_id);
					$tags = wp_get_post_terms(get_the_ID(), 'forum_tag');
					$image_uri = plugins_url('image-upload-handler.php', __FILE__);
				    echo '<div class="author-info" id="author-info" data-img-uri="'. esc_html($image_uri) .'">';
					echo '<div>' . get_avatar($author_id, 96); // Profile Image get_avatar( $current_user->ID, 96 ).
					echo '<p><strong>Role: ' . esc_html($author_role) . '</strong></p></div>';
					echo '<div><p><button title="Add Friend" class="ecm-add-friend" data-author-id=' . esc_attr($author_id) . '">
					<i class="fas fa-user-plus"></i></button>';
					if ($share_profile == 'yes') { echo 'Author: <a href="' . $profile_link . '">'. $nickname .'</a>';
					} else { echo 'Author: ' . $nickname; }
					if ($flair_icon_url) {
					echo ' <img src="' . esc_url($flair_icon_url) . '" alt="User Flair" width="30px"; height="30px" class="user-flair-icon"></p>';
					} else {
					echo '</p>';
					}
					echo '<p><i class="fa fa-trophy"></i> Reputation Points: ' . esc_html($author_reputation) . '</p>';
					echo '<p><i class="fa fa-pencil-alt"></i> Number of Posts: ' . esc_html($author_posts) . '</p>';
					echo '<p><i class="fa fa-calendar-alt"></i> Join Date: ' . date('F j, Y', strtotime($join_date)) . '</p>';
					echo '<p class="_mobile"><i class="fa fa-gamepad"></i> Gamer Tag: ' . esc_html($gamer_tag) . '</p>';
					echo '<p class="_mobile"><i class="fa fa-heart"></i> Favorite Game: ' . esc_html($favorite_game) . '</p>';
					if (!empty($tags)) {
						echo '<div class="tags"><i class="fa-solid fa-tag"></i> ';
						foreach ($tags as $tag) {
							echo esc_html($tag->name) . ', ';
						}
						echo '</div>';
					}
					echo '</div>';
					?>
				</div>
				<div class="post-content">
					<div class="ecm-like-dislike">
						<button class="ecm-like-btn" data-postid="<?php the_ID(); ?>">Like</button>
						vote
						<button class="ecm-dislike-btn" data-postid="<?php the_ID(); ?>">Dislike</button>
					</div>
						<!-- Post content goes here -->
						<?php
						if (have_posts()) {
							while (have_posts()) {
								the_post();
								echo '<article>';
								echo '<h1>' . get_the_title() . '</h1>';
								echo '<div class="post-meta">';
								echo '<span>Published: ' . get_the_date() . '</span>';
								echo '<button class="share-link-btn"><i class="fa-solid fa-share"></i> Share Link</button>';
								echo '</div>';
								the_content();
								echo '<div class="comment-like-dislike-counts" data-postid="' . $post_id . '" >';
								echo '<span class="ecm-like-count">' . $like_dislike_counts['likes'] . ' Likes</span>';
								echo '<span class="ecm-dislike-count">' . $like_dislike_counts['dislikes'] . ' Dislikes</span>';
								echo '</div></article>';
							}
						}
						?>
				</div>
			</div>
			<div class="ecm-forum-comment-container">
				<?php
				if (is_user_logged_in()) {
				// Assuming $user_role, $author_id, $current_user_id, $authorname, $comment_id, and $post_id are defined and contain the correct values

				echo '<div id="commentpostid" data-post-id="' . esc_attr($post_id) .'" >';
				echo '<div id="commentFormActionUrl" data-action-url="' . esc_url(site_url('/wp-comments-post.php')).'">';
				echo '<div class="post_buttons">';

				if ($user_role == 'moderator' && $author_id == $current_user_id) {
					if (comments_open()) { echo '<button class="reply-button" data-comment-id="0"><i class="fas fa-reply"></i> Reply</button>';
					echo '<button class="report-button" data-post-id="' . esc_attr($post_id) . '"><i class="fa-solid fa-ban"></i> Report</button>';
					echo '<button class="delete-post" data-post-id="' . esc_attr($post_id) .'"><i class="fa-solid fa-trash-can"></i> Delete My Post</button>';}
				} elseif ($user_role == 'moderator') {
					if (comments_open()) { echo '<button class="reply-button" data-comment-id="0"><i class="fas fa-reply"></i> Reply</button>';
					echo '<button class="report-button" data-post-id="' . esc_attr($post_id) . '"><i class="fa-solid fa-ban"></i> Report</button>';
					echo '<button class="moderate-post" data-post-id="' . esc_attr($post_id) .'"><i class="fa-solid fa-trash-can"></i> Moderate Post</button>';}
				} elseif ($author_id == $current_user_id) {
					if (comments_open()) { echo '<button class="reply-button" data-comment-id="0"><i class="fas fa-reply"></i> Reply</button>';
					echo '<button class="report-button" data-post-id="' . esc_attr($post_id) . '"><i class="fa-solid fa-ban"></i> Report</button>';
					echo '<button class="delete-post" data-post-id="' . esc_attr($post_id) .'"><i class="fa-solid fa-trash-can"></i> Delete My Post</button>';}
				} else {
					if (comments_open()) { echo '<button class="reply-button" data-comment-id="0"><i class="fas fa-reply"></i> Reply</button>';
					echo '<button class="report-button" data-post-id="' . esc_attr($post_id) . '"><i class="fa-solid fa-ban"></i> Report</button>';}
				}

				echo '</div></div></div>';
				} else {
					echo '<button class="reply-button" data-comment-id="" disabled>Must be logged in to reply</button>';
				}
				?>
			</div>
			<div>
				<?php
				// Check if comments are open
				if (comments_open()) {
					global $withcomments; $withcomments = 1; comments_template();
				} else {
					echo '<p>Comments are closed for this post.</p>';
				}
					?>
			</div>
			<!-- Report Modal -->
			<div id="reportModal" class="modal">
				<div class="modal-content">
					<span class="close">&times;</span>
					<h2>Report Post/Comment</h2>
					<form id="reportForm">
						<div>
							<input type="hidden" id="reportedPostId" name="post_id">
							<input type="hidden" id="reportedCommentId" name="comment_id">
							<label for="reportReason">Reason for Reporting:</label>
							<select id="reportReason" name="report_reason" required>
								<option value="">Select a Reason</option>
								<option value="spam">Spam</option>
								<option value="harassment">Harassment</option>
								<option value="inappropriate_content">Inappropriate Content</option>
								<option value="other">Other</option>
							</select>
							<?php wp_nonce_field('ecm_nonce_action', 'ecm_nonce'); ?>
						</div>
						<div>
							<label for="reportDetails">Additional Details (optional):</label>
							<textarea id="reportDetails" name="report_details"></textarea>
						</div>
						<button type="submit">Submit Report</button>
					</form>
				</div>
			</div>
		</div>
	</div>

<style>
.modal {
    display: none; /* Hidden by default */
    position: fixed; /* Stay in place */
    z-index: 1; /* Sit on top */
    left: 0;
    top: 0;
    width: 100%; /* Full width */
    height: 100%; /* Full height */
    overflow: auto; /* Enable scroll if needed */
    background-color: rgb(0,0,0); /* Fallback color */
    background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto; /* 15% from the top and centered */
    padding: 20px;
    border: 1px solid #888;
    width: 500px; /* Could be more or less, depending on screen size */
	max-width:100%;
}
.modal-content label{
		display: block;
    margin-bottom: 5px;
    color: #333;
    font-family: "Oswald", sans-serif;
	}
	
.modal-content select{
		width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
    font-size: 17px;
	}
textarea#reportDetails {
    width: 100%;
    padding: 10px;
    font-size: 20px;
    border-radius: 5px;
}
	
.modal-content button{
	display: inline-block;
    border-radius: 10px;
    padding: 10px 15px;
    outline: none;
    border: none;
    white-space: nowrap;
    -webkit-user-select: none;
    cursor: pointer;
    font-size: 14px;
    font-family: sans-serif;
    background: #2c0032;
    color: white;
    margin-top: 20px;
	}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
}

.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

</style>
<script>

// Get the modal
var modal = document.getElementById("reportModal");

// Get the button that opens the modal
var reportBtns = document.querySelectorAll(".report-button");

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

// When the user clicks the button, open the modal 
reportBtns.forEach(function(btn) {
    btn.onclick = function() {
        modal.style.display = "block";
        var postId = this.getAttribute("data-post-id"); // Get post ID from button data attribute
        var commentId = this.getAttribute("data-comment-id"); // Get comment ID if available

        // Check if the elements exist before setting their values
        var reportedPostIdElement = document.getElementById("reportedPostId");
        var reportedCommentIdElement = document.getElementById("reportedCommentId");

        if (reportedPostIdElement) {
            reportedPostIdElement.value = postId;
        }

        if (reportedCommentIdElement) {
            reportedCommentIdElement.value = commentId;
        }
		document.getElementById("reportForm").reset();
    }
});
// When the user clicks on <span> (x), close the modal
span.onclick = function() {
    modal.style.display = "none";
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

// Handle form submission
document.getElementById("reportForm").onsubmit = function(event) {
    event.preventDefault();
    // AJAX call to submit the form data
    var formData = new FormData(this);
    // You can add AJAX code here to send the form data to your server
}

document.getElementById("reportForm").addEventListener("submit", function(event) {
    event.preventDefault();

    // Prepare FormData from the form
    var formData = new FormData(this);
    formData.append('action', 'submit_report'); // Action hook for WordPress

    // Append the nonce to the FormData
    var nonce = document.getElementById('ecm_nonce').value;
    formData.append('ecm_nonce', nonce);

    // Check if comment ID is available
    var commentId = document.getElementById("reportedCommentId").value;
    if (commentId) {
        // If comment ID is available, add it to the FormData
        formData.append('comment_id', commentId);
    } else {
        // If comment ID is not available, get post ID from button data attribute
        var postId = document.getElementById("reportedPostId").value;
        formData.append('post_id', postId);
    }

    // AJAX request using ecm_ajax_object.ecm_ajax_url
    var xhr = new XMLHttpRequest();
    xhr.open('POST', ecm_ajax_object.ecm_ajax_url, true); // Use localized AJAX URL
    xhr.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            // Handle the response from the server
            var response = JSON.parse(this.responseText);
            if (response.success) {
                alert("Report submitted successfully!");
                // Close the modal
                modal.style.display = "none";
            } else {
                alert("Error: " + response.data);
            }
        }
    };
    xhr.send(formData);
});
	
	var url = window.location.href;
var commentIdMatch = url.match(/#(\d+)/); // Assuming the comment ID is in the URL as "#50" for example

if (commentIdMatch) {
    var commentId = commentIdMatch[1]; // Extract the comment ID from the match
    var commentContainer = document.getElementById(commentId);

    if (commentContainer) {
        // Add CSS styling to highlight the comment
        commentContainer.style.backgroundColor = '#e9f7ff'; // Change to your desired highlighting style
    }
}
</script>

<?php
get_sidebar();
echo '</div>';
get_footer();
