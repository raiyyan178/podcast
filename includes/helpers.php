<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue scripts and styles
function gogn_podcast_enqueue_assets() {
    // Enqueue CSS
    wp_enqueue_style(
        'gogn-podcast-css',
        GOGN_PODCAST_URL . 'assets/css/gogn-podcast.css',
        array(),
        '1.8'
    );
    error_log('Gogn Podcast: Enqueued CSS');

    // Enqueue JS
    wp_enqueue_script(
        'gogn-podcast-js',
        GOGN_PODCAST_URL . 'assets/js/gogn-podcast.js',
        array('jquery'),
        '1.8',
        true
    );

    // Localize script
    wp_localize_script(
        'gogn-podcast-js',
        'gognPodcastVars',
        array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gogn_podcast_filter_nonce'),
        )
    );
    error_log('Gogn Podcast: Enqueued JS and localized script');
}
add_action('wp_enqueue_scripts', 'gogn_podcast_enqueue_assets');

// Add custom column for tags in admin
function gogn_podcast_manage_columns($columns) {
    $columns['gogn_podcast_tags'] = 'Tags';
    return $columns;
}
add_filter('manage_gogn_podcast_posts_columns', 'gogn_podcast_manage_columns');

function gogn_podcast_custom_column($column, $post_id) {
    if ($column === 'gogn_podcast_tags') {
        $tags = get_the_terms($post_id, 'gogn_podcast_tag');
        if ($tags && !is_wp_error($tags)) {
            $tag_names = wp_list_pluck($tags, 'name');
            echo esc_html(implode(', ', $tag_names));
        } else {
            echo '—';
        }
    }
}
add_action('manage_gogn_podcast_posts_custom_column', 'gogn_podcast_custom_column', 10, 2);
?>