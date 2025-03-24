<?php
if (!defined('ABSPATH')) {
    exit;
}

class ReportController {
    public static function render_report_page() {
        include LST_PLUGIN_PATH . 'includes/Views/admin/report-page.php';
    }
    
    public static function render_detailed_report_page() {
        include LST_PLUGIN_PATH . 'includes/Views/admin/detailed-report.php';
    }
}
