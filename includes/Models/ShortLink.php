<?php
if (!defined('ABSPATH')) {
    exit;
}

class ShortLink {
    public static function register_cpt() {
        $labels = array(
            'name'               => 'Short Links',
            'singular_name'      => 'Short Link',
            'menu_name'          => 'Short Links',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Short Link',
            'edit_item'          => 'Edit Short Link',
            'view_item'          => 'View Short Link',
            'all_items'          => 'All Short Links',
            'search_items'       => 'Search Short Links',
            'not_found'          => 'No short links found.',
            'not_found_in_trash' => 'No short links found in Trash.'
        );
        
        $args = array(
            'labels'       => $labels,
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => true,
            'menu_icon'    => 'dashicons-admin-links',
            'supports'     => array('title'),
            'rewrite'      => false,
        );
        
        register_post_type('short_link', $args);
    }
}
add_action('init', array('ShortLink', 'register_cpt'));
