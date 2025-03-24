<?php
if (!defined('ABSPATH')) {
    exit;
}

function lst_create_tracking_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'short_link_tracking';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        short_link_id bigint(20) NOT NULL,
        clicked_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        utm_source varchar(100) DEFAULT NULL,
        utm_medium varchar(100) DEFAULT NULL,
        utm_campaign varchar(100) DEFAULT NULL,
        utm_term varchar(100) DEFAULT NULL,
        utm_content varchar(100) DEFAULT NULL,
        utm_id varchar(100) DEFAULT NULL,
        ip_address varchar(100) DEFAULT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// function lst_add_query_vars($vars) {
//     $vars[] = 'lst_short_link';
//     return $vars;
// }
// add_filter('query_vars', 'lst_add_query_vars');

function lst_delete_tracking_data( $post_id ) {
    // Kiểm tra xem post này có phải là short_link không
    if ( 'short_link' === get_post_type( $post_id ) ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'short_link_tracking';
        $wpdb->delete( $table_name, array( 'short_link_id' => $post_id ) );
    }
}
add_action( 'before_delete_post', 'lst_delete_tracking_data' );

