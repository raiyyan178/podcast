<?php
/*
Plugin Name: Gogn Podcast
Description: Manages podcast episodes with scheduling, timezone support, video support, media library integration, per-podcast membership restrictions, enhanced listing status, horizontal category filtering, tags, and AJAX search.
Version: 1.8
Author: Apex Web Studios
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin path
define('GOGN_PODCAST_PATH', plugin_dir_path(__FILE__));
define('GOGN_PODCAST_URL', plugin_dir_url(__FILE__));

// Include plugin files
$include_files = array(
    'includes/post-type.php',
    'includes/metabox.php',
    'includes/shortcodes.php',
    'includes/helpers.php'
);
foreach ($include_files as $file) {
    if (file_exists(GOGN_PODCAST_PATH . $file)) {
        require_once GOGN_PODCAST_PATH . $file;
        error_log('Gogn Podcast: Included ' . $file);
    } else {
        error_log('Gogn Podcast: Failed to include ' . $file);
        add_action('admin_notices', function() use ($file) {
            echo '<div class="notice notice-error"><p>Gogn Podcast: Failed to include ' . esc_html($file) . '. Please check plugin files.</p></div>';
        });
    }
}

// Flush rewrite rules on activation
function gogn_podcast_activate() {
    if (function_exists('gogn_podcast_register_post_type')) {
        gogn_podcast_register_post_type();
        flush_rewrite_rules();
        error_log('Gogn Podcast: Activated and flushed rewrite rules');
    } else {
        error_log('Gogn Podcast: Activation failed - gogn_podcast_register_post_type not defined');
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>Gogn Podcast: Activation failed - post type registration function not found.</p></div>';
        });
    }
}
register_activation_hook(__FILE__, 'gogn_podcast_activate');

// Debug deactivation
function gogn_podcast_deactivate() {
    flush_rewrite_rules();
    error_log('Gogn Podcast: Deactivated and flushed rewrite rules');
}
register_deactivation_hook(__FILE__, 'gogn_podcast_deactivate');

// Debug post type and taxonomy registration
function gogn_podcast_check_registration() {
    if (is_admin() && !post_type_exists('gogn_podcast')) {
        error_log('Gogn Podcast: Post type gogn_podcast not registered');
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>Gogn Podcast: Post type "gogn_podcast" not registered. Please deactivate and reactivate the plugin or check for conflicts.</p></div>';
        });
    }
    if (is_admin() && !taxonomy_exists('gogn_podcast_category')) {
        error_log('Gogn Podcast: Taxonomy gogn_podcast_category not registered');
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>Gogn Podcast: Taxonomy "gogn_podcast_category" not registered. Please deactivate and reactivate the plugin or check for conflicts.</p></div>';
        });
    }
    if (is_admin() && !taxonomy_exists('gogn_podcast_tag')) {
        error_log('Gogn Podcast: Taxonomy gogn_podcast_tag not registered');
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>Gogn Podcast: Taxonomy "gogn_podcast_tag" not registered. Please deactivate and reactivate the plugin or check for conflicts.</p></div>';
        });
    }
    if (is_admin() && taxonomy_exists('gogn_podcast_tag')) {
        $tags = get_terms(array('taxonomy' => 'gogn_podcast_tag', 'hide_empty' => false));
        if (empty($tags)) {
            error_log('Gogn Podcast: No tags found in gogn_podcast_tag');
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning"><p>Gogn Podcast: No tags found. Add tags in Podcasts > Tags to enable tag-based search.</p></div>';
            });
        } else {
            $args = array(
                'post_type' => 'gogn_podcast',
                'posts_per_page' => 1,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'gogn_podcast_tag',
                        'field' => 'slug',
                        'terms' => wp_list_pluck($tags, 'slug'),
                    ),
                ),
            );
            $tagged_posts = new WP_Query($args);
            if (!$tagged_posts->have_posts()) {
                error_log('Gogn Podcast: No podcasts have assigned tags in gogn_podcast_tag');
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-warning"><p>Gogn Podcast: No podcasts have assigned tags. Assign tags to podcasts to enable tag-based search.</p></div>';
                });
            }
        }
    }
}
add_action('admin_init', 'gogn_podcast_check_registration');
?>