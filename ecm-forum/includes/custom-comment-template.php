<?php
/**
 * Custom Comment Template for 'ecm_forum_post' Post Type
 */
$post_id = get_the_ID();
$current_user_id = get_current_user_id();
$user_role = get_user_meta($current_user_id, 'ecm_forum_role', true);
$googleads = '<ins class="adsbygoogle"
     style="display:block; text-align:center;"
     data-ad-layout="in-article"
     data-ad-format="fluid"
     data-ad-client="ca-pub-6520437489717144"
     data-ad-slot="2177416631"></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>';
// Check if comments are open.
if (have_comments()) :
echo '<h3>Replies</h3>';
    // Loop through each comment.
	$count = 0;
	$RandAd = rand(2,6);
	$RandAd2 = rand(7,11);
	$RandAd3 = rand(12,16);
    foreach ($comments as $comment) :
		
        $comment_id = $comment->comment_ID;
		$like_dislike_counts = ecm_get_comment_like_dislike_counts($comment_id);
		$comment_post_ID = $comment->comment_post_ID;
        $author_id = $comment->user_id;
        $author_role = get_user_meta($author_id, 'ecm_forum_role', true);
        $author_reputation = get_user_meta($author_id, 'ecm_reputation_score', true);
        $join_date = get_the_author_meta('user_registered', $author_id);
        $gamer_tag = get_user_meta($author_id, 'gamertag', true); // Assuming 'gamer_tag' is a user meta
        $favorite_game = get_user_meta($author_id, 'favorite_game', true); // Assuming 'favorite_game' is a user meta
		$flair_icon_url = get_user_meta($author_id, 'selected_flair', true);
		$share_profile = get_user_meta($author_id, 'share_profile', true);
		$profile_link = get_author_profile_link($author_id);
		$comment_date = ecm_time_ago_format(get_comment_date('Y-m-d H:i:s', $comment_id));
		$image_uri = plugins_url('image-upload-handler.php', __FILE__);
		$authorname = get_comment_author($comment_id);
		$nickname = get_the_author_meta('nickname', $author_id);
		if ( $count == $RandAd) {
		echo $googleads;
		}
		elseif ( $count == $RandAd2) {
		echo $googleads;
		}
		elseif ( $count == $RandAd3) {
		echo $googleads;
		}

        echo '<div id="' . $comment_id . '" class="ecm-forum-comment-container">';
        echo '<div class="author-info" id="author-info" data-img-uri="'. esc_html($image_uri) .'">';
        echo '<div>' . get_avatar($author_id, 96); // Profile Image
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
        echo '<p><i class="fa fa-calendar-alt"></i> Join Date: ' . date('F j, Y', strtotime($join_date)) . '</p>';
        echo '<p class="_mobile"><i class="fa fa-gamepad"></i> Gamer Tag: ' . esc_html($gamer_tag) . '</p>';
        echo '<p class="_mobile"><i class="fa fa-heart"></i> Favorite Game: ' . esc_html($favorite_game) . '</p>';
        echo '</div></div>';
        echo '<div class="post-content">';
		echo 'Posted: ' . $comment_date;
		comment_text();
		?>
		<div class="comment-like-dislike-counts" data-commentid="<?php echo $comment_id ?>" >
		<span class="ecm-like-count"><?php echo $like_dislike_counts['likes'] . " Likes"; ?></span>
		<span class="ecm-dislike-count"><?php echo $like_dislike_counts['dislikes'] . " Dislikes"; ?></span>
		</div>
		<?php
        echo '<div class="ecm-like-dislike">';
        echo '<button class="ecm-like-btn" data-commentid="' . esc_attr($comment_id) . '">Like</button>vote';
        echo '<button class="ecm-dislike-btn" data-commentid="' . esc_attr($comment_id) . '">Dislike</button>';
        
        echo '</div></div>';
        
        echo '</div>';
		echo '<div class="ecm-forum-comment-container">';
		if (is_user_logged_in()) {
    // Assuming $user_role, $author_id, $current_user_id, $authorname, $comment_id, and $post_id are defined and contain the correct values

    echo '<div id="commentpostid" data-post-id="' . esc_attr($comment_post_ID) .'" >';
	echo '<div id="commentFormActionUrl" data-action-url="' . esc_url(site_url('/wp-comments-post.php')).'">';
	echo '<div class="post_buttons" data-author-name="'. esc_attr($authorname) .'" >';

    if ($user_role == 'moderator' && $author_id == $current_user_id) {
        echo '<button class="reply-button" data-comment-id="' . esc_attr($comment_id) . '"><i class="fas fa-reply"></i> Reply</button>';
        echo '<button class="report-button" data-post-id="' . esc_attr($post_id) . '" data-comment-id="' . esc_attr($comment_id) . '"><i class="fa-solid fa-ban"></i> Report</button>';
        echo '<button class="delete-comment" data-comment-id="' . esc_attr($comment_id) . '"><i class="fa-solid fa-trash-can"></i> Delete My Comment</button>';
    } elseif ($user_role == 'moderator') {
        echo '<button class="reply-button" data-comment-id="' . esc_attr($comment_id) . '"><i class="fas fa-reply"></i> Reply</button>';
        echo '<button class="report-button" data-post-id="' . esc_attr($post_id) . '" data-comment-id="' . esc_attr($comment_id) . '"><i class="fa-solid fa-ban"></i> Report</button>';
        echo '<button class="moderate-comment" data-comment-id="' . esc_attr($comment_id) . '"><i class="fa-solid fa-trash-can"></i> Moderate Comment</button>';
    } elseif ($author_id == $current_user_id) {
        echo '<button class="reply-button" data-comment-id="' . esc_attr($comment_id) . '"><i class="fas fa-reply"></i> Reply</button>';
        echo '<button class="report-button" data-post-id="' . esc_attr($post_id) . '" data-comment-id="' . esc_attr($comment_id) . '"><i class="fa-solid fa-ban"></i> Report</button>';
        echo '<button class="delete-comment" data-comment-id="' . esc_attr($comment_id) . '"><i class="fa-solid fa-trash-can"></i> Delete My Comment</button>';
    } else {
        echo '<button class="reply-button" data-comment-id="' . esc_attr($comment_id) . '"><i class="fas fa-reply"></i> Reply</button>';
        echo '<button class="report-button" data-post-id="' . esc_attr($post_id) . '" data-comment-id="' . esc_attr($comment_id) . '"><i class="fa-solid fa-ban"></i> Report</button>';
    }

    echo '</div></div></div>';
} else {
    echo '<button class="reply-button" data-comment-id="" disabled>Must be logged in to reply</button>';
}
		echo '</div>';

	$count++;
    endforeach;
endif;


$commenter = wp_get_current_commenter();
$fields = array(
    'author' => '<div class="ecm-comment-form-field">' .
                '<label for="author">' . __( 'Name', 'textdomain' ) . '</label>' .
                '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" />' .
                '</div>',
    'email'  => '<div class="ecm-comment-form-field">' .
                '<label for="email">' . __( 'Email', 'textdomain' ) . '</label>' .
                '<input id="email" name="email" type="text" value="' . esc_attr( $commenter['comment_author_email'] ) . '" />' .
                '</div>',
);

$comments_args = array(
    'fields' => $fields,
    'title_reply' => 'Leave a Comment',
    'comment_notes_after' => '',
);

//include(plugin_dir_path(__FILE__) . 'custom-comment-form.php');
?>
