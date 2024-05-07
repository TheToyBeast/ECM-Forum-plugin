<?php

// Header Ticker recent forum posts
function toybeast_get_recent_forum_posts() {
    $args = array(
        'post_type'      => 'ecm_forum_post',
        'posts_per_page' => 10,
        'orderby'        => 'date',
        'order'          => 'DESC'
    );

    $recent_posts = new WP_Query($args);
    return $recent_posts;
};