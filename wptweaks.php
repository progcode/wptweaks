<?php
/*
Plugin Name: WP Tweaks
Plugin URI: https://icoders.co
Description: Add tweaks to WP
Author: Kovacs Daniel Akos
Version: 1.0.1
*/

/**
 * Remove auto-update for git
 */
function remove_core_updates() {
    global $wp_version;return(object) array('last_checked'=> time(),'version_checked'=> $wp_version,);
}
add_filter('pre_site_transient_update_core','remove_core_updates');
add_filter('pre_site_transient_update_plugins','remove_core_updates');
add_filter('pre_site_transient_update_themes','remove_core_updates');

/**
 * Add OWA analytics to head
 */
function add_owa_analytics() {
  $owaUrl = getenv('OWA_URL');
  $owaTracking = getenv('OWA_TRACKING');

  echo '<script type="text/javascript">
  //<![CDATA[
  var owa_baseUrl = \''.$owaUrl.'\';
  var owa_cmds = owa_cmds || [];
  owa_cmds.push([\'setSiteId\', \''.$owaTracking.'\']);
  owa_cmds.push([\'trackPageView\']);
  owa_cmds.push([\'trackClicks\']);

  (function() {
      var _owa = document.createElement(\'script\'); _owa.type = \'text/javascript\'; _owa.async = true;
      owa_baseUrl = (\'https:\' == document.location.protocol ? window.owa_baseSecUrl || owa_baseUrl.replace(/http:/, \'https:\') : owa_baseUrl );
      _owa.src = owa_baseUrl + \'modules/base/js/owa.tracker-combined-min.js\';
      var _owa_s = document.getElementsByTagName(\'script\')[0]; _owa_s.parentNode.insertBefore(_owa, _owa_s);
  }());
  //]]>
  </script>';
}
add_action( 'wp_head', 'add_owa_analytics' );

/**
 * Enable features from Soil when plugin is activated
 * @link https://roots.io/plugins/soil/
 */
add_theme_support('soil', [
    'clean-up',
    'disable-rest-api',
    'disable-asset-versioning',
    'disable-trackbacks',
    'js-to-footer',
    'nice-search',
]);

/**
 * Alter dns-prefetch links in <head>
 */
add_filter('wp_resource_hints', function (array $urls, string $relation): array {
    // If the relation is different than dns-prefetch, leave the URLs intact
    if ($relation !== 'dns-prefetch') {
        return $urls;
    }

    // Remove s.w.org entry
    $urls = array_filter($urls, function (string $url): bool {
        return strpos($url, 's.w.org') === false;
    });

    // List of domains to prefetch:
    $dnsPrefetchUrls = [];
    return array_merge($urls, $dnsPrefetchUrls);
}, 10, 2);

/**
 * Disable RSS feeds by redirecting their URLs to homepage
 */
foreach (['do_feed_rss2', 'do_feed_rss2_comments'] as $feedAction) {
    add_action($feedAction, function (): void {
        // Redirect permanently to homepage
        wp_redirect(home_url(), 301);
        exit;
    }, 1);
}

/**
 * Remove the feed links from <head>
 */
remove_action('wp_head', 'feed_links', 2);

/**
 * Remove emoji script and styles from <head>
 */
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');

/**
 * Disable REST-API for all users except of admin
 */
add_filter('rest_authentication_errors', function ($access) {
    if (!current_user_can('administrator')) {
        return new WP_Error('rest_cannot_access', 'Only authenticated users can access the REST API.', ['status' => rest_authorization_required_code()]);
    }
    return $access;
});

/**
 * Remove REST-AI link from <head>
 */
remove_action('wp_head', 'rest_output_link_wp_head');

/**
 * Disable XML-RPC
 */
add_filter('xmlrpc_enabled', function (): bool {
    return false;
});

/**
 * Remove XML-RPC link from <head>
 */
remove_action('wp_head', 'rsd_link');

/**
 * Remove Windows Live Writer manifest from <head>
 */
remove_action('wp_head', 'wlwmanifest_link');

/**
 * Remove info about WordPress version from <head>
 */
remove_action('wp_head', 'wp_generator');

/**
 * Remove Gutenberg default styles
 */
add_action('wp_print_styles', function (): void {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
});

/**
 * Remove unnecessary attributes from style tags
 */
add_filter('style_loader_tag', function (string $tag, string $handle): string {
    // Remove ID attribute
    $tag = str_replace("id='${handle}-css'", '', $tag);

    // Remove type attribute
    $tag = str_replace(" type='text/css'", '', $tag);

    // Change ' to " in attributes:
    $tag = str_replace('\'', '"', $tag);

    // Remove trailing slash
    $tag = str_replace(' />', '>', $tag);

    // Remove double spaces
    return str_replace('  ', '', $tag);
}, 10, 2);

/**
 * Hide WP version strings from scripts and styles
 *
 * @param string $src The source URL of the enqueued style
 * @return string $src
 *
 * @link https://developer.wordpress.org/reference/hooks/style_loader_src/
 * @link https://developer.wordpress.org/reference/hooks/script_loader_src/
 */
function remove_script_style_wp_ver( $src ) {
    global $wp_version;

    // parses query variables into string
    $query_string = parse_url( $src, PHP_URL_QUERY );

    // store variables into $query array
    parse_str( $query_string, $query_vars );

    // check if script version is equal to WP version
    if ( !empty($query_vars['ver']) && $query_vars['ver'] === $wp_version ) {
        $src = remove_query_arg( 'ver', $src );
    }
    return $src;
}
add_filter( 'style_loader_src', 'remove_script_style_wp_ver' );
add_filter( 'script_loader_src', 'remove_script_style_wp_ver' );
