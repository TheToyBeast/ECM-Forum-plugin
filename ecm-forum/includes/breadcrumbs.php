<?php

function ecm_forum_breadcrumbs() {
    // Start the breadcrumb with a link to your forum's main page
    $breadcrumb = '<div class="_breadcrumb"><a href="' . site_url('/forum/') . '"><i class="fas fa-home"></i></a>';

    if (is_tax('forum_category') || is_singular('ecm_forum_post')) {
        // Get the current term or the post's primary category
        $current_term = is_tax('forum_category') ? get_queried_object() : get_the_terms(get_the_ID(), 'forum_category')[0];

        if ($current_term && !is_wp_error($current_term)) {
            // Get parent term if exists
            $parent_term = ($current_term->parent != 0) ? get_term($current_term->parent, 'forum_category') : null;

            // Add parent term to breadcrumb if it exists
            if ($parent_term) {
                $breadcrumb .= ' &raquo; <a href="' . get_term_link($parent_term) . '">' . $parent_term->name . '</a>';
            }

            // Check if current view is a term archive or a single post
            if (is_tax('forum_category')) {
                // For term archives, display the term name without a link
                $breadcrumb .= ' &raquo; ' . $current_term->name;
            } else {
                // For single posts, link to the term and add the post title
                $breadcrumb .= ' &raquo; <a href="' . get_term_link($current_term) . '">' . $current_term->name . '</a>';
                $breadcrumb .= ' &raquo; <span class="breadcrumb-post">' . get_the_title() . '</span>';
            }
        }
		$breadcrumb .= '</div>';
    }

    echo $breadcrumb;
}