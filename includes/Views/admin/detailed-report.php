<?php
if (!defined('ABSPATH')) {
    exit;
}
global $wpdb;
$table_name = $wpdb->prefix . 'short_link_tracking';
$results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY clicked_at DESC", ARRAY_A);
?>
<div class="wrap">
    <h1>Detailed Tracking Report</h1>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Short Link ID</th>
                <th>Clicked At</th>
                <th>UTM Source</th>
                <th>UTM Medium</th>
                <th>UTM Campaign</th>
                <th>UTM Term</th>
                <th>UTM Content</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (!empty($results)) {
                foreach ($results as $row) { ?>
                    <tr>
                        <td><?php echo esc_html($row['id']); ?></td>
                        <td><?php echo esc_html($row['short_link_id']); ?></td>
                        <td><?php echo esc_html($row['clicked_at']); ?></td>
                        <td><?php echo esc_html($row['utm_source']); ?></td>
                        <td><?php echo esc_html($row['utm_medium']); ?></td>
                        <td><?php echo esc_html($row['utm_campaign']); ?></td>
                        <td><?php echo esc_html($row['utm_term']); ?></td>
                        <td><?php echo esc_html($row['utm_content']); ?></td>
                        <td><?php echo esc_html($row['ip_address']); ?></td>
                    </tr>
                <?php }
            } else { ?>
                <tr>
                    <td colspan="9">No tracking data found.</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
