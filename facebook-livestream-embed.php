<?php
/*
Plugin Name: Facebook Live Stream Embed
Plugin URI: https://github.com/stronganchor/facebook-livestream-embed/
Description: Embeds a Facebook live stream using a shortcode.
Version: 1.0.1
Author: Strong Anchor Tech
Author URI: https://stronganchortech.com/
*/

// Add settings page
function facebook_live_stream_settings_page() {
    add_options_page(
        'Facebook Live Stream Settings',
        'Facebook Live Stream',
        'manage_options',
        'facebook-live-stream-settings',
        'facebook_live_stream_settings_page_content'
    );
}
add_action('admin_menu', 'facebook_live_stream_settings_page');

// Add settings link to plugin page
function facebook_live_stream_settings_link($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=facebook-live-stream-settings') . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'facebook_live_stream_settings_link');

// Settings page content
function facebook_live_stream_settings_page_content() {
    ?>
    <div class="wrap">
        <h1>Facebook Live Stream Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('facebook_live_stream_settings');
            do_settings_sections('facebook-live-stream-settings');
            submit_button();
            ?>
        </form>
        <h2>Shortcode Usage</h2>
        <p>To embed a Facebook live stream, use the following shortcode:</p>
        <code>[facebook_live_stream page_id="PAGE_ID"]</code>
        
        <p>If you have set the page ID sitewide setting, you can use the shortcode without entering the ID:</p>
        <code>[facebook_live_stream]</code>
        <p>Replace <code>PAGE_ID</code> with the actual Facebook page ID.</p>
        <h3>How to Find the Page ID</h3>
        <ol>
            <li>Go to the videos tab of the Facebook page</li>
            <li>Look at the URL of one of the videos. The page ID is the first string of numbers after the ".com" part of the URL.</li>
            <li>For example, if the URL is <code>https://www.facebook.com/123456789012345/videos/235235235234235</code>, the page ID is <code>123456789012345</code>.</li>
        </ol>
    </div>
    <?php
}

// Register settings
function facebook_live_stream_register_settings() {
    register_setting('facebook_live_stream_settings', 'facebook_live_stream_app_id');
    register_setting('facebook_live_stream_settings', 'facebook_live_stream_app_secret');
    register_setting('facebook_live_stream_settings', 'facebook_live_stream_page_id');
    add_settings_section(
        'facebook_live_stream_section',
        'API Credentials',
        'facebook_live_stream_section_callback',
        'facebook-live-stream-settings'
    );
    add_settings_field(
        'facebook_live_stream_app_id',
        'Facebook App ID',
        'facebook_live_stream_app_id_callback',
        'facebook-live-stream-settings',
        'facebook_live_stream_section'
    );
    add_settings_field(
        'facebook_live_stream_app_secret',
        'Facebook App Secret',
        'facebook_live_stream_app_secret_callback',
        'facebook-live-stream-settings',
        'facebook_live_stream_section'
    );
    add_settings_field(
        'facebook_live_stream_page_id',
        'Default Page ID',
        'facebook_live_stream_page_id_callback',
        'facebook-live-stream-settings',
        'facebook_live_stream_section'
    );
}
add_action('admin_init', 'facebook_live_stream_register_settings');

// Section callback
function facebook_live_stream_section_callback() {
    echo '<p>Enter your Facebook App ID, App Secret, and default page ID below:</p>';
}

// App ID field callback
function facebook_live_stream_app_id_callback() {
    $app_id = get_option('facebook_live_stream_app_id');
    echo '<input type="text" name="facebook_live_stream_app_id" value="' . esc_attr($app_id) . '" size="50" />';
}

// App Secret field callback
function facebook_live_stream_app_secret_callback() {
    $app_secret = get_option('facebook_live_stream_app_secret');
    echo '<input type="text" name="facebook_live_stream_app_secret" value="' . esc_attr($app_secret) . '" size="50" />';
}

// Default page ID field callback
function facebook_live_stream_page_id_callback() {
    $page_id = get_option('facebook_live_stream_page_id');
    echo '<input type="text" name="facebook_live_stream_page_id" value="' . esc_attr($page_id) . '" size="50" />';
}
function fetch_live_video($page_id, $access_token) {
    $live_video_url = "https://graph.facebook.com/$page_id/live_videos?access_token=$access_token";
    $response = wp_remote_get($live_video_url);
    if (is_wp_error($response)) {
        error_log('Live Video Fetch Error: ' . $response->get_error_message());
        return null;
    }
    $body = wp_remote_retrieve_body($response);
    error_log('Live Video Response: ' . $body); // Log the response for debugging
    $data = json_decode($body, true);

    if (isset($data['data']) && !empty($data['data'])) {
        return $data['data'][0]['id'];
    }
    return null;
}

function fetch_recent_video($page_id, $access_token) {
    $recent_video_url = "https://graph.facebook.com/$page_id/videos?access_token=$access_token";
    $response = wp_remote_get($recent_video_url);
    if (is_wp_error($response)) {
        error_log('Recent Video Fetch Error: ' . $response->get_error_message());
        return null;
    }
    $body = wp_remote_retrieve_body($response);
    error_log('Recent Video Response: ' . $body); // Log the response for debugging
    $data = json_decode($body, true);

    if (isset($data['data']) && !empty($data['data'])) {
        return $data['data'][0]['id'];
    }
    return null;
}

function facebook_live_stream_shortcode($atts) {
    $page_id = isset($atts['page_id']) ? $atts['page_id'] : get_option('facebook_live_stream_page_id');
    $app_id = get_option('facebook_live_stream_app_id');
    $app_secret = get_option('facebook_live_stream_app_secret');

    if (empty($page_id)) {
        return '<p>Please provide a page ID or set a default page in the plugin settings.</p>';
    }

    if (empty($app_id) || empty($app_secret)) {
        return '<p>Please provide valid Facebook App credentials in the plugin settings.</p>';
    }

    $access_token = $app_id . '|' . $app_secret;

    // Check for live video first
    $video_id = fetch_live_video($page_id, $access_token);

    // If no live video, check for the most recent video
    if (is_null($video_id)) {
        $video_id = fetch_recent_video($page_id, $access_token);
    }

    if (is_null($video_id)) {
        return '<p>No live stream or recent video found.</p>';
    }

    $embed_code = '<div id="fb-root"></div>';
    $embed_code .= '<script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v10.0&appId=' . esc_attr($app_id) . '&autoLogAppEvents=1" nonce="abcdef"></script>';
    $embed_code .= '<div class="fb-video" data-href="https://www.facebook.com/' . esc_attr($page_id) . '/videos/' . $video_id . '" data-width="auto" data-show-text="false"></div>';

    return $embed_code;
}
add_shortcode('facebook_live_stream', 'facebook_live_stream_shortcode');
