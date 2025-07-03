<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add metabox for video link, schedule, timezone, and membership levels
function gogn_podcast_add_metabox() {
    add_meta_box(
        'gogn_podcast_details',
        'Podcast Details',
        'gogn_podcast_metabox_callback',
        'gogn_podcast',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'gogn_podcast_add_metabox');

function gogn_podcast_metabox_callback($post) {
    wp_nonce_field('gogn_podcast_save_meta', 'gogn_podcast_nonce');
    $video_link = get_post_meta($post->ID, '_gogn_podcast_video_link', true);
    $schedule = get_post_meta($post->ID, '_gogn_podcast_schedule', true);
    $timezone = get_post_meta($post->ID, '_gogn_podcast_timezone', true);
    $membership_levels = get_post_meta($post->ID, '_gogn_podcast_membership_levels', true);
    $membership_levels = is_array($membership_levels) ? $membership_levels : array();
    $timezones = timezone_identifiers_list();
    $pmpro_levels = function_exists('pmpro_getAllLevels') ? pmpro_getAllLevels(false, true) : array();
    ?>
    <p>
        <label for="gogn_podcast_video_link">Video URL (YouTube or Media Library MP4):</label><br>
        <input type="url" id="gogn_podcast_video_link" name="gogn_podcast_video_link" value="<?php echo esc_attr($video_link); ?>" style="width: 80%;">
        <button type="button" id="gogn_podcast_upload_button" class="button">Select from Media</button>
    </p>
    <p>
        <label for="gogn_podcast_schedule">Release Schedule (YYYY-MM-DD HH:MM):</label><br>
        <input type="datetime-local" id="gogn_podcast_schedule" name="gogn_podcast_schedule" value="<?php echo esc_attr($schedule); ?>">
    </p>
    <p>
        <label for="gogn_podcast_timezone">Timezone for Schedule:</label><br>
        <select id="gogn_podcast_timezone" name="gogn_podcast_timezone">
            <?php foreach ($timezones as $tz) : ?>
                <option value="<?php echo esc_attr($tz); ?>" <?php selected($timezone, $tz); ?>><?php echo esc_html($tz); ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <label for="gogn_podcast_membership_levels">Required Membership Levels (leave empty for default: 2, 3, 4, 5):</label><br>
        <select id="gogn_podcast_membership_levels" name="gogn_podcast_membership_levels[]" multiple style="width: 80%;">
            <?php if (!empty($pmpro_levels)) : ?>
                <?php foreach ($pmpro_levels as $level) : ?>
                    <option value="<?php echo esc_attr($level->id); ?>" <?php echo in_array($level->id, $membership_levels) ? 'selected' : ''; ?>>
                        <?php echo esc_html($level->name); ?>
                    </option>
                <?php endforeach; ?>
            <?php else : ?>
                <option value="" disabled>No membership levels found. Ensure Paid Memberships Pro is active.</option>
            <?php endif; ?>
        </select>
    </p>
    <script>
        jQuery(document).ready(function($) {
            var mediaUploader;
            $('#gogn_podcast_upload_button').click(function(e) {
                e.preventDefault();
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                mediaUploader = wp.media({
                    title: 'Select Podcast Video',
                    button: { text: 'Select Video' },
                    multiple: false,
                    library: { type: 'video/mp4' }
                });
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#gogn_podcast_video_link').val(attachment.url);
                });
                mediaUploader.open();
            });
        });
    </script>
    <?php
}

function gogn_podcast_save_meta($post_id) {
    if (!isset($_POST['gogn_podcast_nonce']) || !wp_verify_nonce($_POST['gogn_podcast_nonce'], 'gogn_podcast_save_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (isset($_POST['gogn_podcast_video_link'])) {
        update_post_meta($post_id, '_gogn_podcast_video_link', esc_url_raw($_POST['gogn_podcast_video_link']));
    }
    if (isset($_POST['gogn_podcast_schedule']) && isset($_POST['gogn_podcast_timezone'])) {
        $schedule = sanitize_text_field($_POST['gogn_podcast_schedule']);
        $timezone = sanitize_text_field($_POST['gogn_podcast_timezone']);
        try {
            $datetime = new DateTime($schedule, new DateTimeZone($timezone));
            $datetime->setTimezone(new DateTimeZone('UTC'));
            update_post_meta($post_id, '_gogn_podcast_schedule', $datetime->format('Y-m-d H:i'));
            update_post_meta($post_id, '_gogn_podcast_timezone', $timezone);
        } catch (Exception $e) {
            update_post_meta($post_id, '_gogn_podcast_schedule', '');
            update_post_meta($post_id, '_gogn_podcast_timezone', '');
        }
    }
    if (isset($_POST['gogn_podcast_membership_levels'])) {
        $membership_levels = array_map('intval', $_POST['gogn_podcast_membership_levels']);
        update_post_meta($post_id, '_gogn_podcast_membership_levels', $membership_levels);
    } else {
        delete_post_meta($post_id, '_gogn_podcast_membership_levels');
    }
}
add_action('save_post', 'gogn_podcast_save_meta');

// Enqueue WordPress media scripts
function gogn_podcast_enqueue_admin_scripts($hook) {
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }
    global $post_type;
    if ($post_type === 'gogn_podcast') {
        wp_enqueue_media();
    }
}
add_action('admin_enqueue_scripts', 'gogn_podcast_enqueue_admin_scripts');
?>