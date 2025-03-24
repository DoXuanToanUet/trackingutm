<?php
/**
 * Plugin Name: Link Shortener & Tracker Plus
 * Description: Plugin tạo link rút gọn, theo dõi số lượt click và ghi nhận UTM parameters, đồng thời lưu log tracking vào bảng riêng và hiển thị báo cáo chi tiết trên Dashboard.
 * Version: 1.1
 * Author: Toanavada
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Định nghĩa hằng số plugin
define('LST_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Load các file cần thiết
require_once LST_PLUGIN_PATH . 'includes/Helpers/functions.php';
require_once LST_PLUGIN_PATH . 'includes/Models/ShortLink.php';
require_once LST_PLUGIN_PATH . 'includes/Models/TrackingData.php';
require_once LST_PLUGIN_PATH . 'includes/Controllers/ShortLinkController.php';
require_once LST_PLUGIN_PATH . 'includes/Controllers/DashboardController.php';
// require_once LST_PLUGIN_PATH . 'includes/Controllers/TrackingController.php';
require_once LST_PLUGIN_PATH . 'includes/Controllers/ReportController.php';

// require_once LST_PLUGIN_PATH . 'includes/Controllers/AdvancedUTMController.php';

// Hooks kích hoạt, hủy kích hoạt
function lst_activate_plugin() {
    
    lst_create_tracking_table();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'lst_activate_plugin');

function lst_deactivate_plugin() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'lst_deactivate_plugin');
