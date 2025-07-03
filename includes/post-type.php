<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Register custom post type and taxonomies
function gogn_podcast_register_post_type() {
    $post_type_args = array(
        'public' => true,
        'label' => 'Podcasts',
        'labels' => array(
            'name' => 'Podcasts',
            'singular_name' => 'Podcast',
            'menu_name' => 'Podcasts',
            'all_items' => 'All Podcasts',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Podcast',
            'edit_item' => 'Edit Podcast',
            'new_item' => 'New Podcast',
            'view_item' => 'View Podcast',
            'search_items' => 'Search Podcasts',
        ),
        'supports' => array('title', 'editor', 'thumbnail'),
        'has_archive' => true,
        'rewrite' => array('slug' => 'podcast'),
        'show_in_rest' => true,
        'show_in_menu' => true,
        'menu_position' => 20,
        'menu_icon' => 'dashicons-video-alt3',
        'capability_type' => 'post',
        'map_meta_cap' => true,
    );
    register_post_type('gogn_podcast', $post_type_args);
    error_log('Gogn Podcast: Registered post type gogn_podcast');

    // Register category taxonomy
    $category_args = array(
        'hierarchical' => true,
        'labels' => array(
            'name' => 'Podcast Categories',
            'singular_name' => 'Podcast Category',
            'menu_name' => 'Categories',
            'all_items' => 'All Podcast Categories',
            'edit_item' => 'Edit Podcast Category',
            'view_item' => 'View Podcast Category',
            'update_item' => 'Update Podcast Category',
            'add_new_item' => 'Add New Podcast Category',
            'new_item_name' => 'New Podcast Category Name',
            'search_items' => 'Search Podcast Categories',
        ),
        'public' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'rewrite' => array('slug' => 'podcast-category'),
    );
    register_taxonomy('gogn_podcast_category', 'gogn_podcast', $category_args);
    error_log('Gogn Podcast: Registered taxonomy gogn_podcast_category');

    // Register tag taxonomy
    $tag_args = array(
        'hierarchical' => false,
        'labels' => array(
            'name' => 'Podcast Tags',
            'singular_name' => 'Podcast Tag',
            'menu_name' => 'Tags',
            'all_items' => 'All Podcast Tags',
            'edit_item' => 'Edit Podcast Tag',
            'view_item' => 'View Podcast Tag',
            'update_item' => 'Update Podcast Tag',
            'add_new_item' => 'Add New Podcast Tag',
            'new_item_name' => 'New Podcast Tag Name',
            'search_items' => 'Search Podcast Tags',
        ),
        'public' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'rewrite' => array('slug' => 'podcast-tag'),
    );
    register_taxonomy('gogn_podcast_tag', 'gogn_podcast', $tag_args);
    error_log('Gogn Podcast: Registered taxonomy gogn_podcast_tag');
}
add_action('init', 'gogn_podcast_register_post_type', 0);
?>