<?php

// reputation update on like and dislike

function ecm_update_reputation_on_like_dislike($post_id, $action) {
    $post_author_id = get_post_field('post_author', $post_id);
    $current_reputation = (int) get_user_meta($post_author_id, 'ecm_reputation_score', true);

    // Define the points for like and dislike
    $like_points = 1;    // 1 point for every 100 likes
    $dislike_points = -1; // -1 point for every 100 dislikes

    // Calculate the new reputation score
    $new_reputation = $current_reputation + (($action === 'like') ? $like_points : $dislike_points);

    update_user_meta($post_author_id, 'ecm_reputation_score', $new_reputation);
}

// initialize Reputation Score

function ecm_initialize_user_reputation($user_id) {
    update_user_meta($user_id, 'ecm_reputation_score', 0);
}

add_action('user_register', 'ecm_initialize_user_reputation');
