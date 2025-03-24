<?php
function lst_add_admin_menu() {
    add_menu_page('Short Links Report', 'Short Links Report', 'manage_options', 'lst-report', array('ReportController', 'render_report_page'), 'dashicons-chart-bar', 20);
    add_submenu_page('lst-report', 'Detailed Tracking Report', 'Detailed Tracking', 'manage_options', 'lst-detailed-report', array('ReportController', 'render_detailed_report_page'));
}
add_action('admin_menu', 'lst_add_admin_menu');

function lst_add_advanced_utm_menu() {
    add_submenu_page(
        'lst-report',
        'Advanced UTM Report',
        'Advanced UTM Report',
        'manage_options',
        'lst-advanced-utm-report',
        array('AdvancedUTMController', 'render_advanced_report_page')
    );
}
add_action('admin_menu', 'lst_add_advanced_utm_menu');
