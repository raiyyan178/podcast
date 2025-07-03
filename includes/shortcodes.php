<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Shortcode for podcast listing with search and category filter
function gogn_podcast_listing_shortcode($atts) {
    $atts = shortcode_atts(array('category' => ''), $atts);
    $selected_category = sanitize_text_field($atts['category'] ?: (isset($_GET['gogn_podcast_category']) ? $_GET['gogn_podcast_category'] : ''));
    $search_query = sanitize_text_field(isset($_GET['gogn_podcast_search']) ? $_GET['gogn_podcast_search'] : '');

    $args = array(
        'post_type' => 'gogn_podcast',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    );
    if ($selected_category) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'gogn_podcast_category',
                'field' => 'slug',
                'terms' => $selected_category,
            ),
        );
    }
    if ($search_query) {
        $args['s'] = $search_query;
        $args['tax_query'] = $args['tax_query'] ?? array();
        $args['tax_query']['relation'] = 'OR';
        $args['tax_query'][] = array(
            'taxonomy' => 'gogn_podcast_tag',
            'field' => 'name',
            'terms' => $search_query,
            'operator' => 'IN',
        );
    }
    error_log('Gogn Podcast: Shortcode query args: ' . print_r($args, true));
    $query = new WP_Query($args);
    $categories = get_terms(array('taxonomy' => 'gogn_podcast_category', 'hide_empty' => false));

    ob_start();
    ?>
    <div class="gogn-podcast-search">
        <input type="text" class="gogn-podcast-search-input" placeholder="Search Podcast " value="<?php echo esc_attr($search_query); ?>">
        <button class="gogn-podcast-search-button">Search</button>
    </div>
    <div class="gogn-podcast-filter">
        <ul class="gogn-podcast-categories">
            <li class="<?php echo !$selected_category ? 'active' : ''; ?>">
                <a href="#" data-category="" class="gogn-podcast-category-link">All</a>
            </li>
            <?php foreach ($categories as $category) : ?>
                <li class="<?php echo $selected_category === $category->slug ? 'active' : ''; ?>">
                    <a href="#" data-category="<?php echo esc_attr($category->slug); ?>" class="gogn-podcast-category-link">
                        <?php echo esc_html($category->name); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="gogn-podcast-listing" style="display: flex; flex-wrap: wrap; gap: 20px;">
        <?php if ($query->have_posts()) : ?>
            <?php while ($query->have_posts()) : $query->the_post(); ?>
                <?php
                $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: '';
                $schedule_utc = get_post_meta(get_the_ID(), '_gogn_podcast_schedule', true);
                $membership_levels = get_post_meta(get_the_ID(), '_gogn_podcast_membership_levels', true);
                $membership_levels = is_array($membership_levels) ? array_map('intval', $membership_levels) : array(2, 3, 4, 5);
                $current_utc = current_time('mysql', 1);
                $is_scheduled = $schedule_utc && strtotime($schedule_utc) > strtotime($current_utc);

                $level_names = array();
                if (function_exists('pmpro_getAllLevels')) {
                    $pmpro_levels = pmpro_getAllLevels(false, true);
                    foreach ($membership_levels as $level_id) {
                        if (isset($pmpro_levels[$level_id])) {
                            $level_names[] = $pmpro_levels[$level_id]->name;
                        }
                    }
                }
                $status_message = $is_scheduled ? 'Scheduled' : 'Requires Membership: ' . (empty($level_names) ? 'Levels 2, 3, 4, 5' : implode(', ', $level_names));
                ?>
                <a href="<?php echo esc_url(get_permalink()); ?>" style="text-decoration: none; width: 33%;">
                    <div style="border: 1px solid #ccc; padding: 10px;">
                        
                        <?php if ($thumbnail) : ?>
                            <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php the_title_attribute(); ?>" style="width: 100%; height: auto;">
						<h3><?php the_title(); ?></h3>
                        <?php endif; ?>
                        <p style="font-size: 0.9em; color: #666;">
                            <?php if ($is_scheduled) : ?>
                                <span class="gogn-podcast-schedule" data-utc-schedule="<?php echo esc_attr($schedule_utc); ?>">
                                    Scheduled (loading local time...)
                                </span>
                            <?php else : ?>
                                <?php echo esc_html($status_message); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </a>
            <?php endwhile; ?>
            <?php wp_reset_postdata(); ?>
        <?php else : ?>
            <p>No podcasts found.</p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('gogn_podcast', 'gogn_podcast_listing_shortcode');

// AJAX handler for category filtering
function gogn_podcast_filter_ajax() {
    check_ajax_referer('gogn_podcast_filter_nonce', 'nonce');

    $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
    $args = array(
        'post_type' => 'gogn_podcast',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    );
    if ($category) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'gogn_podcast_category',
                'field' => 'slug',
                'terms' => $category,
            ),
        );
    }
    error_log('Gogn Podcast: Category filter args: ' . print_r($args, true));
    $query = new WP_Query($args);

    ob_start();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: '';
            $schedule_utc = get_post_meta(get_the_ID(), '_gogn_podcast_schedule', true);
            $membership_levels = get_post_meta(get_the_ID(), '_gogn_podcast_membership_levels', true);
            $membership_levels = is_array($membership_levels) ? array_map('intval', $membership_levels) : array(2, 3, 4, 5);
            $current_utc = current_time('mysql', 1);
            $is_scheduled = $schedule_utc && strtotime($schedule_utc) > strtotime($current_utc);

            $level_names = array();
            if (function_exists('pmpro_getAllLevels')) {
                $pmpro_levels = pmpro_getAllLevels(false, true);
                foreach ($membership_levels as $level_id) {
                    if (isset($pmpro_levels[$level_id])) {
                        $level_names[] = $pmpro_levels[$level_id]->name;
                    }
                }
            }
            $status_message = $is_scheduled ? 'Scheduled' : 'Requires Membership: ' . (empty($level_names) ? 'Levels 2, 3, 4, 5' : implode(', ', $level_names));
            ?>
            <a href="<?php echo esc_url(get_permalink()); ?>" style="text-decoration: none; width: 200px;">
                <div style="border: 1px solid #ccc; padding: 10px;">
                    <h3><?php the_title(); ?></h3>
                    <?php if ($thumbnail) : ?>
                        <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php the_title_attribute(); ?>" style="width: 100%; height: auto;">
                    <?php endif; ?>
                    <p style="font-size: 0.9em; color: #666;">
                        <?php if ($is_scheduled) : ?>
                            <span class="gogn-podcast-schedule" data-utc-schedule="<?php echo esc_attr($schedule_utc); ?>">
                                Scheduled (loading local time...)
                            </span>
                        <?php else : ?>
                            <?php echo esc_html($status_message); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </a>
            <?php
        }
        wp_reset_postdata();
    } else {
        echo '<p>No podcasts found.</p>';
    }
    wp_send_json_success(ob_get_clean());
}
add_action('wp_ajax_gogn_podcast_filter', 'gogn_podcast_filter_ajax');
add_action('wp_ajax_nopriv_gogn_podcast_filter', 'gogn_podcast_filter_ajax');

// AJAX handler for search
function gogn_podcast_search_ajax() {
    check_ajax_referer('gogn_podcast_filter_nonce', 'nonce');

    $search_query = isset($_POST['search']) ? sanitize_text_field(trim($_POST['search'])) : '';
    error_log('Gogn Podcast: Search query received: "' . $search_query . '"');

    $post_ids = array();

    if ($search_query) {
        // Title search
        $title_args = array(
            'post_type' => 'gogn_podcast',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            's' => $search_query,
        );
        $title_query = new WP_Query($title_args);
        error_log('Gogn Podcast: Title search found ' . $title_query->found_posts . ' posts');
        while ($title_query->have_posts()) {
            $title_query->the_post();
            $post_ids[] = get_the_ID();
            error_log('Gogn Podcast: Title match: ' . get_the_title() . ' (ID: ' . get_the_ID() . ')');
        }
        wp_reset_postdata();

        // Tag search
        $tag_args = array(
            'post_type' => 'gogn_podcast',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => 'gogn_podcast_tag',
                    'field' => 'name',
                    'terms' => $search_query,
                    'operator' => 'IN',
                ),
            ),
        );
        $tag_query = new WP_Query($tag_args);
        error_log('Gogn Podcast: Tag search found ' . $tag_query->found_posts . ' posts');
        while ($tag_query->have_posts()) {
            $tag_query->the_post();
            $post_ids[] = get_the_ID();
            error_log('Gogn Podcast: Tag match: ' . get_the_title() . ' (ID: ' . get_the_ID() . ')');
        }
        wp_reset_postdata();
    } else {
        // No search query, return all posts
        $args = array(
            'post_type' => 'gogn_podcast',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        );
        $query = new WP_Query($args);
        while ($query->have_posts()) {
            $query->the_post();
            $post_ids[] = get_the_ID();
        }
        wp_reset_postdata();
    }

    // Combine and deduplicate results
    $post_ids = array_unique($post_ids);
    error_log('Gogn Podcast: Combined unique post IDs: ' . count($post_ids));

    // Final query
    $args = array(
        'post_type' => 'gogn_podcast',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    );
    if (!empty($post_ids)) {
        $args['post__in'] = $post_ids;
        $args['orderby'] = 'post__in';
    } else if ($search_query) {
        $args['post__in'] = array(0); // No results
    }
    error_log('Gogn Podcast: Final query args: ' . print_r($args, true));
    $query = new WP_Query($args);
    error_log('Gogn Podcast: Found ' . $query->found_posts . ' podcasts');

    ob_start();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            error_log('Gogn Podcast: Rendering post: ' . get_the_title() . ' (ID: ' . get_the_ID() . ')');
            $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: '';
            $schedule_utc = get_post_meta(get_the_ID(), '_gogn_podcast_schedule', true);
            $membership_levels = get_post_meta(get_the_ID(), '_gogn_podcast_membership_levels', true);
            $membership_levels = is_array($membership_levels) ? array_map('intval', $membership_levels) : array(2, 3, 4, 5);
            $current_utc = current_time('mysql', 1);
            $is_scheduled = $schedule_utc && strtotime($schedule_utc) > strtotime($current_utc);

            $level_names = array();
            if (function_exists('pmpro_getAllLevels')) {
                $pmpro_levels = pmpro_getAllLevels(false, true);
                foreach ($membership_levels as $level_id) {
                    if (isset($pmpro_levels[$level_id])) {
                        $level_names[] = $pmpro_levels[$level_id]->name;
                    }
                }
            }
            $status_message = $is_scheduled ? 'Scheduled' : 'Requires Membership: ' . (empty($level_names) ? 'Levels 2, 3, 4, 5' : implode(', ', $level_names));
            ?>
            <a href="<?php echo esc_url(get_permalink()); ?>" style="text-decoration: none; width: 200px;">
                <div style="border: 1px solid #ccc; padding: 10px;">
                    <h3><?php the_title(); ?></h3>
                    <?php if ($thumbnail) : ?>
                        <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php the_title_attribute(); ?>" style="width: 100%; height: auto;">
                    <?php endif; ?>
                    <p style="font-size: 0.9em; color: #666;">
                        <?php if ($is_scheduled) : ?>
                            <span class="gogn-podcast-schedule" data-utc-schedule="<?php echo esc_attr($schedule_utc); ?>">
                                Scheduled (loading local time...)
                            </span>
                        <?php else : ?>
                            <?php echo esc_html($status_message); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </a>
            <?php
        }
        wp_reset_postdata();
    } else {
        echo '<p>No podcasts found.</p>';
    }
    wp_send_json_success(ob_get_clean());
}
add_action('wp_ajax_gogn_podcast_search', 'gogn_podcast_search_ajax');
add_action('wp_ajax_nopriv_gogn_podcast_search', 'gogn_podcast_search_ajax');

// Shortcode for podcast video
function gogn_podcast_video_shortcode() {
    if (!is_singular('gogn_podcast')) {
        return '';
    }
    $post_id = get_the_ID();
    $video_link = get_post_meta($post_id, '_gogn_podcast_video_link', true);
    $schedule_utc = get_post_meta($post_id, '_gogn_podcast_schedule', true);
    $membership_levels = get_post_meta($post_id, '_gogn_podcast_membership_levels', true);
    $membership_levels = is_array($membership_levels) ? array_map('intval', $membership_levels) : array(2, 3, 4, 5);
    
    $current_utc = current_time('mysql', 1);
    $is_scheduled = $schedule_utc && strtotime($schedule_utc) > strtotime($current_utc);
    
    ob_start();
    if ($is_scheduled) {
        ?>
        <p>This podcast is scheduled for release on <span class="gogn-podcast-schedule" data-utc-schedule="<?php echo esc_attr($schedule_utc); ?>">Scheduled (loading local time...)</span>.</p>
        <?php
    } elseif (!is_user_logged_in()) {
        echo '<p>Please log in to view this podcast.</p>';
    } elseif (function_exists('pmpro_hasMembershipLevel') && !pmpro_hasMembershipLevel($membership_levels)) {
        $level_names = array();
        if (function_exists('pmpro_getAllLevels')) {
            $pmpro_levels = pmpro_getAllLevels(false, true);
            foreach ($membership_levels as $level_id) {
                if (isset($pmpro_levels[$level_id])) {
                    $level_names[] = $pmpro_levels[$level_id]->name;
                }
            }
        }
        $level_text = empty($level_names) ? 'Levels 2, 3, 4, 5' : implode(', ', $level_names);
        echo '<p>You need an active membership (' . esc_html($level_text) . ') to view this podcast.</p>';
    } elseif ($video_link) {
        if (strpos($video_link, 'youtube.com') !== false || strpos($video_link, 'youtu.be') !== false) {
            $video_id = gogn_podcast_get_youtube_id($video_link);
            ?>
            <iframe width="560" height="560" src="https://www.youtube.com/embed/<?php echo esc_attr($video_id); ?>" frameborder="0" allowfullscreen></iframe>
            <?php
        } else {
            ?>
            <video controls style="max-width: 100%;">
                <source src="<?php echo esc_url($video_link); ?>" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            <?php
        }
    } else {
        echo '<p>No video available for this podcast.</p>';
    }
    return ob_get_clean();
}
add_shortcode('gogn_podcast_video', 'gogn_podcast_video_shortcode');

// Helper function to extract YouTube video ID
function gogn_podcast_get_youtube_id($url) {
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
    preg_match($pattern, $url, $matches);
    return isset($matches[1]) ? $matches[1] : '';
}
?>