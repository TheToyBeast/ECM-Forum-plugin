<?php

function ecm_time_ago_format($datetime) {
    // Create a DateTime object for the post datetime
    $timezone = new DateTimeZone(get_option('timezone_string'));
    $date = new DateTime($datetime, $timezone);

    // Get the current time using WordPress settings
    $now = new DateTime(current_time('mysql'), $timezone);

    // Calculate the difference
    $interval = $date->diff($now);

    // Now format this difference
    if ($interval->y > 0) {
        return $interval->y . ($interval->y == 1 ? " year" : " years") . " ago";
    } elseif ($interval->m > 0) {
        return $interval->m . ($interval->m == 1 ? " month" : " months") . " ago";
    } elseif ($interval->d > 0) {
        return $interval->d . ($interval->d == 1 ? " day" : " days") . " ago";
    } elseif ($interval->h > 0) {
        return $interval->h . ($interval->h == 1 ? " hour" : " hours") . " ago";
    } elseif ($interval->i > 0) {
        return $interval->i . ($interval->i == 1 ? " minute" : " minutes") . " ago";
    } else {
        return "Just now";
    }
}