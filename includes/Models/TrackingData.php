<?php
if (!defined('ABSPATH')) {
    exit;
}

class TrackingData {
    public static function insert($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'short_link_tracking';
        return $wpdb->insert($table_name, $data);
    }
    
    public static function delete_by_short_link($post_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'short_link_tracking';
        $wpdb->delete($table_name, array('short_link_id' => $post_id));
    }
}
