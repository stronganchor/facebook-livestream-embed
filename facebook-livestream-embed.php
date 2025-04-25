<?php
/*
Plugin Name: Facebook Live Stream Embed
Plugin URI: https://github.com/stronganchor/facebook-livestream-embed/
Description: Embeds a Facebook live stream using a shortcode, with auto-refresh of page access tokens.
Version: 1.0.7
Author: Strong Anchor Tech
Author URI: https://stronganchortech.com/
*/

// ─────────────────────────────────────────────────────────────────────────────
// 1) Settings Page & Registration
// ─────────────────────────────────────────────────────────────────────────────
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

function facebook_live_stream_register_settings() {
    register_setting('facebook_live_stream_settings', 'facebook_live_stream_app_id');
    register_setting('facebook_live_stream_settings', 'facebook_live_stream_app_secret');
    register_setting('facebook_live_stream_settings', 'facebook_live_stream_page_id');
    register_setting('facebook_live_stream_settings', 'facebook_live_stream_access_token');
    register_setting('facebook_live_stream_settings', 'facebook_live_stream_access_token_expires');
    register_setting('facebook_live_stream_settings', 'facebook_live_stream_user_token');

    add_settings_section(
        'facebook_live_stream_section',
        'API Credentials',
        function() {
            echo '<p>Enter your App ID, App Secret, default Page ID, and your long-lived **user** access token below:</p>';
        },
        'facebook-live-stream-settings'
    );

    add_settings_field(
        'facebook_live_stream_app_id',
        'Facebook App ID',
        function() {
            $v = get_option('facebook_live_stream_app_id');
            echo "<input type='text' name='facebook_live_stream_app_id' value='" . esc_attr($v) . "' size='50' />";
        },
        'facebook-live-stream-settings',
        'facebook_live_stream_section'
    );
    add_settings_field(
        'facebook_live_stream_app_secret',
        'Facebook App Secret',
        function() {
            $v = get_option('facebook_live_stream_app_secret');
            echo "<input type='text' name='facebook_live_stream_app_secret' value='" . esc_attr($v) . "' size='50' />";
        },
        'facebook-live-stream-settings',
        'facebook_live_stream_section'
    );
    add_settings_field(
        'facebook_live_stream_page_id',
        'Default Page ID',
        function() {
            $v = get_option('facebook_live_stream_page_id');
            echo "<input type='text' name='facebook_live_stream_page_id' value='" . esc_attr($v) . "' size='50' />";
        },
        'facebook-live-stream-settings',
        'facebook_live_stream_section'
    );
    add_settings_field(
        'facebook_live_stream_access_token',
        'Page Access Token',
        function() {
            $v = get_option('facebook_live_stream_access_token');
            echo "<input type='text' name='facebook_live_stream_access_token' value='" . esc_attr($v) . "' size='50' />";
            echo '<p class="description">Leave blank to use App ID &amp; Secret method.</p>';
        },
        'facebook-live-stream-settings',
        'facebook_live_stream_section'
    );
    add_settings_field(
        'facebook_live_stream_user_token',
        'Long-Lived User Token',
        function() {
            $v = get_option('facebook_live_stream_user_token');
            echo "<input type='text' name='facebook_live_stream_user_token' value='" . esc_attr($v) . "' size='50' />";
            echo '<p class="description">Paste the long-lived **user** access token you generated (step 8 of the docs).</p>';
        },
        'facebook-live-stream-settings',
        'facebook_live_stream_section'
    );
}
add_action('admin_init', 'facebook_live_stream_register_settings');


// ─────────────────────────────────────────────────────────────────────────────
// 2) Expiry Check & Refresh Logic
// ─────────────────────────────────────────────────────────────────────────────
function is_access_token_expired() {
    $expires = get_option('facebook_live_stream_access_token_expires');
    // Treat “no expiry” or any expiry < now+4 days as expired
    if ( ! $expires
      || strtotime($expires) < time() + 4 * DAY_IN_SECONDS
    ) {
        return true;
    }
    return false;
}

function refresh_access_token() {
    $app_id     = get_option('facebook_live_stream_app_id');
    $app_secret = get_option('facebook_live_stream_app_secret');
    $user_token = get_option('facebook_live_stream_user_token');
    $page_id    = get_option('facebook_live_stream_page_id');

    if ( empty($app_id) || empty($app_secret) || empty($user_token) || empty($page_id) ) {
        return;
    }

    // 1) Exchange short-lived user token for new long-lived user token
    $url1 = "https://graph.facebook.com/oauth/access_token"
          . "?grant_type=fb_exchange_token"
          . "&client_id={$app_id}"
          . "&client_secret={$app_secret}"
          . "&fb_exchange_token={$user_token}";
    $resp1 = wp_remote_get($url1);
    if ( is_wp_error($resp1) ) {
        wp_mail(
            get_option('admin_email'),
            'FB Token Refresh Error',
            'Error fetching long-lived user token: ' . $resp1->get_error_message()
        );
        return;
    }
    $data1 = json_decode(wp_remote_retrieve_body($resp1), true);
    if ( empty($data1['access_token']) ) {
        wp_mail(
            get_option('admin_email'),
            'FB Token Refresh Error',
            'No user token returned. Raw response: ' . wp_remote_retrieve_body($resp1)
        );
        return;
    }
    $new_user_token = $data1['access_token'];
    $expires_in     = ! empty($data1['expires_in'])
                      ? intval($data1['expires_in'])
                      : 60 * DAY_IN_SECONDS; // fallback to 60 days
    update_option('facebook_live_stream_user_token', $new_user_token);

    // 2) Fetch a fresh page token using that new user token
    $url2 = "https://graph.facebook.com/{$page_id}"
          . "?fields=access_token"
          . "&access_token={$new_user_token}";
    $resp2 = wp_remote_get($url2);
    if ( is_wp_error($resp2) ) {
        wp_mail(
            get_option('admin_email'),
            'FB Token Refresh Error',
            'Error fetching page token: ' . $resp2->get_error_message()
        );
        return;
    }
    $data2 = json_decode(wp_remote_retrieve_body($resp2), true);
    if ( ! empty($data2['access_token']) ) {
        update_option('facebook_live_stream_access_token', $data2['access_token']);
        // store new expiry timestamp
        $ts = time() + $expires_in;
        update_option('facebook_live_stream_access_token_expires', date('Y-m-d H:i:s', $ts));

        // ensure our daily hook is scheduled
        if ( ! wp_next_scheduled('facebook_live_stream_check_token') ) {
            // schedule first run at ~expires_in - 3 days, then every 24h
            wp_schedule_event(time() + ($expires_in - 3 * DAY_IN_SECONDS), 'daily', 'facebook_live_stream_check_token');
        }
    } else {
        wp_mail(
            get_option('admin_email'),
            'FB Token Refresh Error',
            'No page token returned. Raw response: ' . wp_remote_retrieve_body($resp2)
        );
    }
}

add_action('facebook_live_stream_check_token', 'check_and_refresh_access_token');
function check_and_refresh_access_token() {
    if ( is_access_token_expired() ) {
        refresh_access_token();
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 3) Activation / Deactivation Hooks
// ─────────────────────────────────────────────────────────────────────────────
function facebook_live_stream_activate() {
    if ( ! wp_next_scheduled('facebook_live_stream_check_token') ) {
        wp_schedule_event(time(), 'daily', 'facebook_live_stream_check_token');
    }
}
register_activation_hook(__FILE__, 'facebook_live_stream_activate');

function facebook_live_stream_deactivate() {
    wp_clear_scheduled_hook('facebook_live_stream_check_token');
}
register_deactivation_hook(__FILE__, 'facebook_live_stream_deactivate');


// ─────────────────────────────────────────────────────────────────────────────
// 4) Fetch & Shortcode
// ─────────────────────────────────────────────────────────────────────────────
function fetch_live_video($page_id, $access_token) {
    $url      = "https://graph.facebook.com/{$page_id}/live_videos?access_token={$access_token}";
    $response = wp_remote_get($url);
    if ( is_wp_error($response) ) {
        return 'Live Video Fetch Error: ' . $response->get_error_message();
    }
    return wp_remote_retrieve_body($response);
}

function fetch_recent_video($page_id, $access_token) {
    $url      = "https://graph.facebook.com/{$page_id}/videos?access_token={$access_token}";
    $response = wp_remote_get($url);
    if ( is_wp_error($response) ) {
        return 'Recent Video Fetch Error: ' . $response->get_error_message();
    }
    return wp_remote_retrieve_body($response);
}

function facebook_live_stream_shortcode($atts) {
    $page_id = $atts['page_id'] ?? get_option('facebook_live_stream_page_id');
    $token   = get_option('facebook_live_stream_access_token');
    if ( ! $page_id ) {
        return '<p>Please set a default Page ID or pass one via shortcode.</p>';
    }
    if ( ! $token ) {
        $app_id     = get_option('facebook_live_stream_app_id');
        $app_secret = get_option('facebook_live_stream_app_secret');
        if ( ! $app_id || ! $app_secret ) {
            return '<p>Please configure your App ID &amp; Secret in settings.</p>';
        }
        $token = "{$app_id}|{$app_secret}";
    }

    // ** auto-refresh on each shortcode render **
    check_and_refresh_access_token();

    // try live
    $live_raw   = fetch_live_video($page_id, $token);
    $live_data  = json_decode($live_raw, true);
    if ( ! empty($live_data['data']) ) {
        $vid = $live_data['data'][0];
        return $vid['embed_html'] ?? '';
    }

    // fallback to recent
    $recent_raw  = fetch_recent_video($page_id, $token);
    $recent_data = json_decode($recent_raw, true);
    if ( ! empty($recent_data['data']) ) {
        $vid = $recent_data['data'][0];
        return $vid['embed_html'] ?? '';
    }

    return '<p>No live or recent video found.</p>';
}
add_shortcode('facebook_live_stream', 'facebook_live_stream_shortcode');
