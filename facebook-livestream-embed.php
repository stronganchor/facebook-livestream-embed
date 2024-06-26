<?php
/*
Plugin Name: Facebook Live Stream Embed
Plugin URI: https://github.com/stronganchor/facebook-livestream-embed/
Description: Embeds a Facebook live stream using a shortcode.
Version: 1.0.6
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
        <h3>How to Get the Access Token</h3>
        <p>Note: You must be an admin of the Facebook page to generate an access token.</p>
        <ol>
            <li>Go to the <a href="https://developers.facebook.com/tools/explorer/" target="_blank">Facebook Graph API Explorer</a>.</li>
            <li>In the top-left corner, select your app from the dropdown menu.</li>
            <li>Click on the "Get Token" button and select "Get User Access Token".</li>
            <li>In the permissions window, select the following permissions: <code>pages_show_list</code>, <code>pages_read_engagement</code>, <code>pages_read_user_content</code>.</li>
            <li>Click "Generate Access Token" and allow any prompts that appear.</li>
            <li>Copy the short-lived token generated.</li>
            <li>Exchange the short-lived token for a long-lived token by pasting the following URL into your browser’s address bar:</li>
            <code>https://graph.facebook.com/v10.0/oauth/access_token?grant_type=fb_exchange_token&client_id=YOUR_APP_ID&client_secret=YOUR_APP_SECRET&fb_exchange_token=SHORT_LIVED_TOKEN</code>
            <li>Replace <code>YOUR_APP_ID</code>, <code>YOUR_APP_SECRET</code>, and <code>SHORT_LIVED_TOKEN</code> with your actual values and submit the request.</li>
            <li>This will return a long-lived user access token.</li>
            <li>Use the long-lived user access token to get a page access token by pasting the following URL into your browser’s address bar:</li>
            <code>https://graph.facebook.com/PAGE_ID?fields=access_token&access_token=LONG_LIVED_USER_ACCESS_TOKEN</code>
            <li>Replace <code>PAGE_ID</code> and <code>LONG_LIVED_USER_ACCESS_TOKEN</code> with your actual values and submit the request.</li>
            <li>Copy the generated long-lived page access token and paste it into the "Access Token" field below.</li>
        </ol>
    </div>
    <?php
}

// Register settings
function facebook_live_stream_register_settings() {
    register_setting('facebook_live_stream_settings', 'facebook_live_stream_app_id');
    register_setting('facebook_live_stream_settings', 'facebook_live_stream_app_secret');
    register_setting('facebook_live_stream_settings', 'facebook_live_stream_page_id');
    register_setting('facebook_live_stream_settings', 'facebook_live_stream_access_token');
    register_setting('facebook_live_stream_settings', 'facebook_live_stream_access_token_expires');
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
    add_settings_field(
        'facebook_live_stream_access_token',
        'Access Token',
        'facebook_live_stream_access_token_callback',
        'facebook-live-stream-settings',
        'facebook_live_stream_section'
    );
    // Hide the access token expiry field from the settings page
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

// Access Token field callback
function facebook_live_stream_access_token_callback() {
    $access_token = get_option('facebook_live_stream_access_token');
    echo '<input type="text" name="facebook_live_stream_access_token" value="' . esc_attr($access_token) . '" size="50" />';
    echo '<p class="description">Leave this field blank to use the App ID and App Secret method.</p>';
}

// Check if token is expired
function is_access_token_expired() {
    $expires = get_option('facebook_live_stream_access_token_expires');
    if ($expires && strtotime($expires) < strtotime('+4 days')) {
        return true;
    }
    return false;
}

// Refresh token
function refresh_access_token() {
    $app_id = get_option('facebook_live_stream_app_id');
    $app_secret = get_option('facebook_live_stream_app_secret');
    $access_token = get_option('facebook_live_stream_access_token');
    
    if (empty($app_id) || empty($app_secret) || empty($access_token)) {
        return;
    }

    $url = "https://graph.facebook.com/v10.0/oauth/access_token?grant_type=fb_exchange_token&client_id=$app_id&client_secret=$app_secret&fb_exchange_token=$access_token";
    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        wp_mail(
            get_option('admin_email'), 
            'Facebook Access Token Refresh Failed', 
            "The access token refresh failed with the following error: $error_message.\n\nPlease try to manually refresh the token using the following URL:\n$url"
        );
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['access_token'])) {
        update_option('facebook_live_stream_access_token', $data['access_token']);
        update_option('facebook_live_stream_access_token_expires', date('Y-m-d H:i:s', time() + $data['expires_in']));

        // Schedule the next token check
        if (!wp_next_scheduled('facebook_live_stream_check_token')) {
            wp_schedule_event(time() + ($data['expires_in'] - (3 * 24 * 60 * 60)), 'daily', 'facebook_live_stream_check_token');
        }
    } else {
        wp_mail(get_option('admin_email'), 'Facebook Access Token Refresh Failed', 'The access token refresh failed. Please update the token manually.');
    }
}

// Check and refresh token if needed
function check_and_refresh_access_token() {
    if (is_access_token_expired()) {
        refresh_access_token();
    }
}
add_action('facebook_live_stream_check_token', 'check_and_refresh_access_token');

// Schedule the token check event on plugin activation
function facebook_live_stream_activate() {
    if (!wp_next_scheduled('facebook_live_stream_check_token')) {
        wp_schedule_event(time(), 'daily', 'facebook_live_stream_check_token');
    }
}
register_activation_hook(__FILE__, 'facebook_live_stream_activate');

// Clear the scheduled event on plugin deactivation
function facebook_live_stream_deactivate() {
    wp_clear_scheduled_hook('facebook_live_stream_check_token');
}
register_deactivation_hook(__FILE__, 'facebook_live_stream_deactivate');

function fetch_live_video($page_id, $access_token) {
    $live_video_url = "https://graph.facebook.com/$page_id/live_videos?access_token=$access_token";
    $response = wp_remote_get($live_video_url);
    if (is_wp_error($response)) {
        return 'Live Video Fetch Error: ' . $response->get_error_message();
    }
    $body = wp_remote_retrieve_body($response);
    return 'Live Video Response: ' . $body; // Display the response for debugging
}

function fetch_recent_video($page_id, $access_token) {
    $recent_video_url = "https://graph.facebook.com/$page_id/videos?access_token=$access_token";
    $response = wp_remote_get($recent_video_url);
    if (is_wp_error($response)) {
        return 'Recent Video Fetch Error: ' . $response->get_error_message();
    }
    $body = wp_remote_retrieve_body($response);
    return 'Recent Video Response: ' . $body; // Display the response for debugging
}

function facebook_live_stream_shortcode($atts) {
    $page_id = isset($atts['page_id']) ? $atts['page_id'] : get_option('facebook_live_stream_page_id');
    $access_token = get_option('facebook_live_stream_access_token');

    if (empty($page_id)) {
        return '<p>Please provide a page ID or set a default page in the plugin settings.</p>';
    }

    if (empty($access_token)) {
        $app_id = get_option('facebook_live_stream_app_id');
        $app_secret = get_option('facebook_live_stream_app_secret');

        if (empty($app_id) || empty($app_secret)) {
            return '<p>Please provide valid Facebook App credentials in the plugin settings.</p>';
        }

        $access_token = $app_id . '|' . $app_secret;
    }

    // Check and refresh the token
    check_and_refresh_access_token();

    // Check for live video first
    $live_video_response = fetch_live_video($page_id, $access_token);
    if (strpos($live_video_response, 'Live Video Response:') === 0) {
        $video_data = json_decode(substr($live_video_response, strlen('Live Video Response: ')), true);
        if (isset($video_data['data']) && !empty($video_data['data'])) {
            $video_id = $video_data['data'][0]['id'];
            $embed_html = $video_data['data'][0]['embed_html'];
        } else {
            $video_id = null;
        }
    } else {
        $video_id = null;
    }

    // If no live video, check for the most recent video
    if (is_null($video_id)) {
        $recent_video_response = fetch_recent_video($page_id, $access_token);
        if (strpos($recent_video_response, 'Recent Video Response:') === 0) {
            $video_data = json_decode(substr($recent_video_response, strlen('Recent Video Response: ')), true);
            if (isset($video_data['data']) && !empty($video_data['data'])) {
                $video_id = $video_data['data'][0]['id'];
                $embed_html = $video_data['data'][0]['embed_html'];
            } else {
                $video_id = null;
            }
        } else {
            $video_id = null;
        }
    }

    if (is_null($video_id)) {
        return '<p>No live stream or recent video found.</p>' . '<br>' . $live_video_response . '<br>' . $recent_video_response;
    }

    return $embed_html;
}
add_shortcode('facebook_live_stream', 'facebook_live_stream_shortcode');
