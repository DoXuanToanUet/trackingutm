<?php
if (!defined('ABSPATH')) {
    exit;
}

class ShortLinkController {
    // Đăng ký Meta Box cho CPT
    public static function add_meta_boxes() {
        add_meta_box('lst_meta_box', 'Original URL', array('ShortLinkController', 'meta_box_callback'), 'short_link', 'normal', 'high');
    }
    public static function meta_box_callback($post) {
        $original_url = get_post_meta($post->ID, 'lst_original_url', true);
        echo '<label for="lst_original_url">URL:</label> ';
        echo '<input type="text" id="lst_original_url" name="lst_original_url" value="' . esc_attr($original_url) . '" size="50" />';
    }
    public static function save_post_meta($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (isset($_POST['lst_original_url'])) {
            update_post_meta($post_id, 'lst_original_url', esc_url_raw($_POST['lst_original_url']));
        }
    }
    // Rewrite rules cho URL rút gọn
    public static function add_rewrite_rules() {
        add_rewrite_rule('^l/([^/]+)/?$', 'index.php?lst_short_link=$matches[1]', 'top');
    }
}
add_action('add_meta_boxes', array('ShortLinkController', 'add_meta_boxes'));
add_action('save_post', array('ShortLinkController', 'save_post_meta'));
add_action('init', array('ShortLinkController', 'add_rewrite_rules'));

// Đăng ký query var nếu chưa có
function lst_add_query_vars($vars) {
    $vars[] = 'lst_short_link';
    return $vars;
}
add_filter('query_vars', 'lst_add_query_vars');

// Xử lý chuyển hướng và tracking
function lst_template_redirect() {
    $slug = get_query_var('lst_short_link');
    if ($slug) {
        $args = array(
            'name'           => $slug,
            'post_type'      => 'short_link',
            'posts_per_page' => 1
        );
        $posts = get_posts($args);
        if ($posts) {
            $post = $posts[0];
            $original_url = get_post_meta($post->ID, 'lst_original_url', true);
            
            // Cập nhật số click cho CPT
            $clicks = (int)get_post_meta($post->ID, 'lst_click_count', true);
            update_post_meta($post->ID, 'lst_click_count', $clicks + 1);
            
            // Lấy các tham số UTM từ URL, bao gồm utm_id
            $utm_params = array();
            foreach (array('utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'utm_id') as $utm) {
                if (isset($_GET[$utm])) {
                    $utm_params[$utm] = sanitize_text_field($_GET[$utm]);
                }
            }
            // Ghi log UTM (nếu cần)
            if (!empty($utm_params)) {
                error_log('Short Link [' . $slug . '] UTM: ' . json_encode($utm_params));
            }
            
            // Lưu tracking data qua model TrackingData
            $data = array(
                'short_link_id' => $post->ID,
                'utm_source'    => isset($utm_params['utm_source']) ? $utm_params['utm_source'] : null,
                'utm_medium'    => isset($utm_params['utm_medium']) ? $utm_params['utm_medium'] : null,
                'utm_campaign'  => isset($utm_params['utm_campaign']) ? $utm_params['utm_campaign'] : null,
                'utm_term'      => isset($utm_params['utm_term']) ? $utm_params['utm_term'] : null,
                'utm_content'   => isset($utm_params['utm_content']) ? $utm_params['utm_content'] : null,
                'utm_id'        => isset($utm_params['utm_id']) ? $utm_params['utm_id'] : null,
                'ip_address'    => $_SERVER['REMOTE_ADDR'],
            );
            TrackingData::insert($data);
            
            // Redirect 302 đến URL gốc
            wp_redirect($original_url, 302);
            exit;
        } else {
            status_header(404);
            echo 'Short link not found.';
            exit;
        }
    }
}
add_action('template_redirect', 'lst_template_redirect');
