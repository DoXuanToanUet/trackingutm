<?php
if (!defined('ABSPATH')) {
    exit;
}

// Lấy short_link_id từ URL (nếu có)
$detail_id = isset($_GET['short_link_id']) ? absint($_GET['short_link_id']) : 0;

global $wpdb;
$table_tracking = $wpdb->prefix . 'short_link_tracking';

if ($detail_id > 0) {
    // =========================
    // HIỂN THỊ CHI TIẾT SHORT LINK
    // =========================
    // 1. Lấy thông tin Short Link
    $post = get_post($detail_id);
    if (!$post || $post->post_type !== 'short_link') {
        echo '<div class="wrap"><h1>Short Link not found.</h1></div>';
        return;
    }
    
    $original_url = get_post_meta($post->ID, 'lst_original_url', true);
    
    // 2. Lấy danh sách click (nếu muốn hiển thị chi tiết)
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT utm_source, utm_medium, utm_campaign, utm_term, utm_content, ip_address, clicked_at
             FROM $table_tracking
             WHERE short_link_id = %d
             ORDER BY clicked_at DESC",
            $post->ID
        ),
        ARRAY_A
    );
    
    // 3. Lấy dữ liệu cho biểu đồ (đếm click theo utm_source)
    $utm_data = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT utm_source, COUNT(*) AS click_count
             FROM $table_tracking
             WHERE short_link_id = %d
             GROUP BY utm_source",
            $post->ID
        ),
        ARRAY_A
    );
    // Tạo mảng để vẽ biểu đồ
    $chart_labels = array();
    $chart_counts = array();
    if ($utm_data) {
        foreach ($utm_data as $row) {
            $src = $row['utm_source'] ? $row['utm_source'] : 'Unknown';
            $chart_labels[] = $src;
            $chart_counts[] = intval($row['click_count']);
        }
    }
    // Chuyển sang JSON
    $chart_labels_json = json_encode($chart_labels);
    $chart_counts_json = json_encode($chart_counts);
    
    ?>
    <div class="wrap">
        <h1>Chi tiết Short Link</h1>
        <p><strong>Short Link ID:</strong> <?php echo esc_html($post->ID); ?></p>
        <p><strong>Short Link Title:</strong> <?php echo esc_html($post->post_title); ?></p>
        <p><strong>Original URL:</strong> <?php echo esc_html($original_url); ?></p>
        
        <!-- Nút trở về trang danh sách -->
        <p><a class="button" href="?page=lst-report">&laquo; Back to list</a></p>
        
        <hr>
        
        <h2>Biểu đồ Click theo UTM Source</h2>
        <canvas id="myUtmChart" style="max-width:600px;"></canvas>
        
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var ctx = document.getElementById('myUtmChart').getContext('2d');
            var chartLabels = <?php echo $chart_labels_json; ?>;
            var chartCounts = <?php echo $chart_counts_json; ?>;
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Clicks',
                        data: chartCounts,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
        </script>
        
        <hr>
        
        <h2>Chi tiết các lượt click</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
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
                <?php if ($results) : ?>
                    <?php foreach ($results as $row) : ?>
                        <tr>
                            <td><?php echo esc_html($row['clicked_at']); ?></td>
                            <td><?php echo esc_html($row['utm_source']); ?></td>
                            <td><?php echo esc_html($row['utm_medium']); ?></td>
                            <td><?php echo esc_html($row['utm_campaign']); ?></td>
                            <td><?php echo esc_html($row['utm_term']); ?></td>
                            <td><?php echo esc_html($row['utm_content']); ?></td>
                            <td><?php echo esc_html($row['ip_address']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="7">No clicks found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
} else {
    // =========================
    // HIỂN THỊ DANH SÁCH SHORT LINKS
    // =========================
    // Bạn có thể giữ code danh sách cũ, hoặc viết lại gọn hơn
    global $wpdb;
    $time_range = isset($_GET['time_range']) ? sanitize_text_field($_GET['time_range']) : 'all';
    $date_condition = '';
    if ($time_range === 'current') {
        $start_date = date('Y-m-01 00:00:00');
        $date_condition = $wpdb->prepare(" AND clicked_at >= %s", $start_date);
    } elseif ($time_range === 'previous') {
        $start_date = date('Y-m-01 00:00:00', strtotime('first day of last month'));
        $end_date = date('Y-m-01 00:00:00');
        $date_condition = $wpdb->prepare(" AND clicked_at >= %s AND clicked_at < %s", $start_date, $end_date);
    } elseif ($time_range === '3') {
        $start_date = date('Y-m-d H:i:s', strtotime('-3 months'));
        $date_condition = $wpdb->prepare(" AND clicked_at >= %s", $start_date);
    } elseif ($time_range === '6') {
        $start_date = date('Y-m-d H:i:s', strtotime('-6 months'));
        $date_condition = $wpdb->prepare(" AND clicked_at >= %s", $start_date);
    }

    ?>
    <div class="wrap">
        <h1>Short Links Report</h1>
        <form method="get" style="margin-bottom:20px;">
            <input type="hidden" name="page" value="lst-report">
            <label for="time_range">Lọc theo khoảng thời gian:</label>
            <select name="time_range" id="time_range">
                <option value="all" <?php selected($time_range, 'all'); ?>>Toàn thời gian</option>
                <option value="current" <?php selected($time_range, 'current'); ?>>Tháng này</option>
                <option value="previous" <?php selected($time_range, 'previous'); ?>>Tháng trước</option>
                <option value="3" <?php selected($time_range, '3'); ?>>3 tháng</option>
                <option value="6" <?php selected($time_range, '6'); ?>>6 tháng</option>
            </select>
            <input type="submit" class="button button-primary" value="Lọc">
        </form>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Short URL</th>
                    <th>Original URL</th>
                    <th>Total Clicks</th>
                    <th>UTM Breakdown</th>
                    <th></th> <!-- Cột xem chi tiết -->
                </tr>
            </thead>
            <tbody>
            <?php
            // Lấy danh sách short links
            $args = array(
                'post_type'      => 'short_link',
                'posts_per_page' => -1,
            );
            $short_links = get_posts($args);
            if ($short_links) {
                foreach ($short_links as $post) {
                    $short_url   = home_url('/l/' . $post->post_name);
                    $original_url = get_post_meta($post->ID, 'lst_original_url', true);
                    
                    // Lấy tổng số click
                    $query = "SELECT COUNT(*) FROM {$wpdb->prefix}short_link_tracking WHERE short_link_id = %d" . $date_condition;
                    $click_count = $wpdb->get_var($wpdb->prepare($query, $post->ID));
                    
                    // Lấy breakdown UTM
                    $query2 = "SELECT utm_source, COUNT(*) as click_count FROM {$wpdb->prefix}short_link_tracking WHERE short_link_id = %d" . $date_condition . " GROUP BY utm_source";
                    $utm_breakdown = $wpdb->get_results($wpdb->prepare($query2, $post->ID), ARRAY_A);
                    
                    $utm_summary = array();
                    if ($utm_breakdown) {
                        foreach ($utm_breakdown as $utm) {
                            $source = $utm['utm_source'] ? $utm['utm_source'] : 'Unknown';
                            $utm_summary[] = $source . ': ' . $utm['click_count'];
                        }
                        $utm_summary = implode(', ', $utm_summary);
                    } else {
                        $utm_summary = 'N/A';
                    }
                    ?>
                    <tr>
                        <td><a href="<?php echo esc_url($short_url); ?>" target="_blank"><?php echo esc_html($short_url); ?></a></td>
                        <td><?php echo esc_html($original_url); ?></td>
                        <td><?php echo intval($click_count); ?></td>
                        <td><?php echo esc_html($utm_summary); ?></td>
                        <td>
                            <a class="button" href="?page=lst-report&short_link_id=<?php echo $post->ID; ?>">View Detail</a>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                ?>
                <tr>
                    <td colspan="5">No short links found.</td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
    </div>
    <?php
}
