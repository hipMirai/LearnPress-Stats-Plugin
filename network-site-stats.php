<?php
/**
 * Plugin Name: Network Site Stats - Binh EPU
 * Description: Hiển thị thống kê các trang web con trong mạng lưới.
 * Version: 1.0
 * Author: Bình Nguyễn
 */

add_action('network_admin_menu', 'nss_add_menu');
function nss_add_menu() {
    add_menu_page('Thống kê mạng', 'Network Stats', 'manage_network', 'network-stats', 'nss_display_page', 'dashicons-chart-bar');
}

function nss_display_page() {
    $sites = get_sites();
    echo '<div class="wrap"><h1>Danh sách các trang web trong mạng lưới</h1>';
    echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>ID</th><th>Tên Trang</th><th>Số bài viết</th></tr></thead><tbody>';
    foreach ($sites as $site) {
        switch_to_blog($site->blog_id);
        echo "<tr><td>{$site->blog_id}</td><td>" . get_bloginfo('name') . "</td><td>" . wp_count_posts()->publish . " bài</td></tr>";
        restore_current_blog();
    }
    echo '</tbody></table></div>';
}