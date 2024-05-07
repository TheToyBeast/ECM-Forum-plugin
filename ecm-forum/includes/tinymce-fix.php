<?php

function add_site_url_to_relative_image_links($content) {
    // Get the site URL
    $site_url = site_url();

    // Regex pattern to match relative image links
    $pattern = '/(src=["\'])(\.\.\/wp-content\/uploads\/[^"\']+)(")/i';

    // Replace relative image links with absolute URLs
    $content = preg_replace_callback($pattern, function($matches) use ($site_url) {
        // Remove the '../' part before 'wp-content/uploads/'
        $image_path = preg_replace('/^\.\.\//', '', $matches[2]);
        return $matches[1] . $site_url . '/' . $image_path . $matches[3];
    }, $content);

    return $content;
}
// Hook the function to the_content filter to apply it to post content
add_filter('the_content', 'add_site_url_to_relative_image_links');