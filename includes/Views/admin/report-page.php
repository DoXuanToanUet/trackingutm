<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_tracking = $wpdb->prefix . 'short_link_tracking';

// Kiểm tra nếu có short_link_id (chi tiết)
$detail_id = isset($_GET['short_link_id']) ? absint($_GET['short_link_id']) : 0;

if ($detail_id > 0) {
    // 1. Lấy thông tin Short Link
    $post = get_post($detail_id);
    if (!$post || $post->post_type !== 'short_link') {
        echo '<div class="wrap"><h1>Short Link not found.</h1></div>';
        return;
    }
    
    $original_url = get_post_meta($post->ID, 'lst_original_url', true);
    $short_url    = home_url('/l/' . $post->post_name);
    
    // 2. Lấy tổng số click của short link
    $total_clicks = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_tracking WHERE short_link_id = %d",
            $post->ID
        )
    );
    
    // 3. Lấy dữ liệu cho Donut Chart: nhóm theo UTM combination
    $utm_combinations = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT utm_source, utm_medium, utm_campaign, utm_id, utm_content, COUNT(*) as click_count
             FROM $table_tracking
             WHERE short_link_id = %d
             GROUP BY utm_source, utm_medium, utm_campaign, utm_id, utm_content
             ORDER BY click_count DESC",
            $post->ID
        ),
        ARRAY_A
    );
    
    // Xây dựng dữ liệu cho Donut Chart tổng hợp UTM
    $donut_labels = array();
    $donut_data   = array();
    if ($utm_combinations) {
        foreach ($utm_combinations as $combo) {
            $label = '';
            foreach (array('utm_source','utm_medium','utm_campaign','utm_id','utm_content') as $field) {
                if (!empty($combo[$field])) {
                    $label .= $combo[$field] . ' ';
                }
            }
            $label = trim($label);
            if (!$label) {
                $label = 'Unknown';
            }
            $donut_labels[] = $label;
            $donut_data[]   = intval($combo['click_count']);
        }
    }
    
    // 4. Lấy dữ liệu cho Line Chart tổng hợp: thống kê click theo ngày cho short link
    $line_chart_data = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT DATE(clicked_at) as click_date, COUNT(*) as clicks 
             FROM $table_tracking 
             WHERE short_link_id = %d 
             GROUP BY DATE(clicked_at)
             ORDER BY click_date ASC",
            $post->ID
        ),
        ARRAY_A
    );
    $line_dates = array();
    $line_clicks = array();
    if ($line_chart_data) {
        foreach ($line_chart_data as $row) {
            $line_dates[] = $row['click_date'];
            $line_clicks[] = intval($row['clicks']);
        }
    }
    
    // Chuyển dữ liệu sang JSON cho Chart.js
    $donut_labels_json = json_encode($donut_labels);
    $donut_data_json   = json_encode($donut_data);
    $line_dates_json   = json_encode($line_dates);
    $line_clicks_json  = json_encode($line_clicks);
    ?>
    
    <!-- CSS cho giao diện detail -->
    <style>
        .lst-detail-container {
            max-width: 1000px;
            margin: 20px auto;
            font-family: Arial, sans-serif;
            color: #333;
        }
        .lst-detail-header {
            margin-bottom: 20px;
        }
        .lst-detail-header h1 {
            margin: 0;
            font-size: 28px;
        }
        .lst-info-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .lst-info-card p {
            margin: 5px 0;
        }
        .lst-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        .lst-summary .summary-box {
            flex: 1 1 300px;
            background: #f1f1f1;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }
        .lst-summary .summary-box h3 {
            margin: 0 0 10px;
            font-size: 18px;
        }
        .lst-summary .summary-box p {
            font-size: 22px;
            font-weight: bold;
            margin: 0;
        }
        .lst-chart-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .lst-chart-box {
            flex: 1 1 300px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
        }
        .lst-chart-box h3 {
            margin-top: 0;
            font-size: 18px;
            text-align: center;
        }
        /* Layout cho UTM Combination cards: 3 per row */
        .lst-utm-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .lst-utm-card {
            background: #f7f7f7;
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 15px;
            flex: 1 1 calc(33.333% - 20px);
            box-sizing: border-box;
        }
        .lst-utm-card h3 {
            margin-top: 0;
            font-size: 18px;
        }
        .lst-utm-link {
            font-size: 13px;
            word-break: break-all;
            margin-bottom: 5px;
            display: block;
            color: #0073aa;
            text-decoration: none;
        }
        .lst-utm-link:hover {
            text-decoration: underline;
        }
        .lst-utm-stats {
            font-size: 15px;
            font-weight: bold;
        }
    </style>
    
    <div class="lst-detail-container">
        <!-- Header & Info -->
        <div class="lst-detail-header">
            <h1>Chi tiết Short Link</h1>
        </div>
        <div class="lst-info-card">
            <p><strong>ID:</strong> <?php echo esc_html($post->ID); ?></p>
            <p><strong>Title:</strong> <?php echo esc_html($post->post_title ?: 'No title'); ?></p>
            <p><strong>Original URL:</strong> <a href="<?php echo esc_url($original_url); ?>" target="_blank"><?php echo esc_html($original_url); ?></a></p>
            <p><strong>Short URL:</strong> <a href="<?php echo esc_url($short_url); ?>" target="_blank"><?php echo esc_html($short_url); ?></a></p>
            <p><a class="button" href="?page=lst-report">&laquo; Back to list</a></p>
        </div>
        
        <!-- Summary Section -->
        <div class="lst-summary">
            <div class="summary-box">
                <h3>Total Clicks</h3>
                <p><?php echo intval($total_clicks); ?></p>
            </div>
            <div class="summary-box">
                <h3>Total UTM Combinations</h3>
                <p><?php echo count($utm_combinations); ?></p>
            </div>
        </div>
        <?php
// Tính thống kê UTM Combination
$max_click = 0;
$min_click = PHP_INT_MAX;
$max_index = $min_index = 0;
$total_combo_clicks = 0;
if ($utm_combinations) {
    foreach ($utm_combinations as $index => $combo) {
        $clicks = intval($combo['click_count']);
        $total_combo_clicks += $clicks;
        if ($clicks > $max_click) {
            $max_click = $clicks;
            $max_index = $index;
        }
        if ($clicks < $min_click) {
            $min_click = $clicks;
            $min_index = $index;
        }
    }
}
$avg_clicks = ($utm_combinations && $total_combo_clicks > 0) ? round($total_combo_clicks / count($utm_combinations), 2) : 0;

// Gợi ý AI
$ai_message = '';
if ($max_click > 1000) {
    $ai_message = 'Chiến dịch hàng đầu đạt hiệu suất rất cao!';
} elseif ($avg_clicks < 50) {
    $ai_message = 'Trung bình click của các UTM khá thấp, cân nhắc tối ưu lại chiến dịch.';
} else {
    $ai_message = 'Hiệu suất chiến dịch ở mức trung bình. Có thể cải thiện thêm.';
}
?>
<div class="lst-ai-message" style="background:#e7f3fe; border:1px solid #b3d4fc; padding:10px; margin-bottom:20px;">
    <strong>AI Analysis:</strong> <?php echo esc_html($ai_message); ?>
</div>
<div class="lst-summary-stats">
    <p><strong>Combination nhiều nhất:</strong> Combination <?php echo ($max_index + 1); ?> với <?php echo $max_click; ?> clicks.</p>
    <p><strong>Combination ít nhất:</strong> Combination <?php echo ($min_index + 1); ?> với <?php echo $min_click; ?> clicks.</p>
    <p><strong>Trung bình mỗi combination:</strong> <?php echo $avg_clicks; ?> clicks.</p>
</div>

        <!-- Chart Row: Donut Chart & Overall Line Chart -->
        <div class="lst-chart-row">
            <div class="lst-chart-box">
                <h3>Click Distribution (Donut)</h3>
                <canvas id="donutChart" style="max-width:100%; min-height:200px;"></canvas>
            </div>
            <div class="lst-chart-box">
                <h3>Clicks Over Time (Line)</h3>
                <canvas id="lineChart" style="max-width:100%; min-height:200px;"></canvas>
            </div>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Donut Chart
            var ctxDonut = document.getElementById('donutChart').getContext('2d');
            new Chart(ctxDonut, {
                type: 'doughnut',
                data: {
                    labels: <?php echo $donut_labels_json; ?>,
                    datasets: [{
                        data: <?php echo $donut_data_json; ?>,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.6)',
                            'rgba(54, 162, 235, 0.6)',
                            'rgba(255, 206, 86, 0.6)',
                            'rgba(75, 192, 192, 0.6)',
                            'rgba(153, 102, 255, 0.6)',
                            'rgba(255, 159, 64, 0.6)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            
            // Overall Line Chart (Click over time)
            var ctxLine = document.getElementById('lineChart').getContext('2d');
            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: <?php echo $line_dates_json; ?>,
                    datasets: [{
                        label: 'Clicks',
                        data: <?php echo $line_clicks_json; ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.3)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        });
        </script>
        
        <!-- Dashboard UTM Combinations: Hiển thị theo lưới 3 card/1 hàng -->
        <h2>UTM Combinations</h2>
        <div class="lst-utm-grid">
            <?php if ($utm_combinations) : ?>
                <?php foreach ($utm_combinations as $index => $utm) : 
                    // Xây dựng mảng tham số cho UTM
                    $utm_args = array(
                        'utm_source'   => $utm['utm_source'],
                        'utm_medium'   => $utm['utm_medium'],
                        'utm_campaign' => $utm['utm_campaign'],
                        'utm_id'       => $utm['utm_id'],
                        'utm_content'  => $utm['utm_content']
                    );
                    $utm_args = array_filter($utm_args, function($v) {
                        return ($v !== null && $v !== '');
                    });
                    $full_utm_link = $short_url . '?' . http_build_query($utm_args);
                    ?>
                    <div class="lst-utm-card">
                        <h3>Combination <?php echo ($index + 1); ?></h3>
                        <a class="lst-utm-link" href="<?php echo esc_url($full_utm_link); ?>" target="_blank">
                            <?php echo esc_html($full_utm_link); ?>
                        </a>
                        <div class="lst-utm-stats">
                            Clicks: <?php echo intval($utm['click_count']); ?>
                        </div>
                        <?php
                        // Xây dựng điều kiện động cho từng trường cho biểu đồ riêng của combination
                        $conditions = array();
                        $params = array($post->ID);
                        $conditions[] = "short_link_id = %d";
                        $fields = array('utm_source', 'utm_medium', 'utm_campaign', 'utm_id', 'utm_content');
                        foreach ($fields as $field) {
                            if (!empty($utm[$field])) {
                                $conditions[] = "$field = %s";
                                $params[] = $utm[$field];
                            } else {
                                $conditions[] = "$field IS NULL";
                            }
                        }
                        $query = "SELECT DATE(clicked_at) as click_date, COUNT(*) as clicks 
                                  FROM $table_tracking 
                                  WHERE " . implode(" AND ", $conditions) . " 
                                  GROUP BY DATE(clicked_at) 
                                  ORDER BY click_date ASC";
                        $chart_data = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);
                        $dates = array();
                        $clicks = array();
                        if ($chart_data) {
                            foreach ($chart_data as $row) {
                                $dates[] = $row['click_date'];
                                $clicks[] = intval($row['clicks']);
                            }
                        }
                        $dates_json = json_encode($dates);
                        $clicks_json = json_encode($clicks);
                        ?>
                        <?php if (!empty($dates) && !empty($clicks)) : ?>
                            <div class="lst-chart-container">
                                <canvas id="chart-<?php echo $index; ?>" style="max-width:100%; min-height:150px;"></canvas>
                            </div>
                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var ctx = document.getElementById('chart-<?php echo $index; ?>').getContext('2d');
                                new Chart(ctx, {
                                    type: 'line', // Sử dụng line chart cho từng UTM combination
                                    data: {
                                        labels: <?php echo $dates_json; ?>,
                                        datasets: [{
                                            label: 'Clicks',
                                            data: <?php echo $clicks_json; ?>,
                                            backgroundColor: 'rgba(75, 192, 192, 0.3)',
                                            borderColor: 'rgba(75, 192, 192, 1)',
                                            fill: true,
                                            tension: 0.3,
                                            borderWidth: 2
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        scales: {
                                            y: { beginAtZero: true }
                                        },
                                        plugins: {
                                            legend: { display: false }
                                        }
                                    }
                                });
                            });
                            </script>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p>No UTM data found for this short link.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Nhúng Chart.js (nếu chưa có) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <?php
} else {
    // =========================
    // HIỂN THỊ DANH SÁCH SHORT LINKS (dashboard list)
    // =========================
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
                    
                    // Lấy breakdown UTM (theo utm_source)
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
                echo '<tr><td colspan="5">No short links found.</td></tr>';
            }
            ?>
            </tbody>
        </table>
    </div>
    <?php
}
