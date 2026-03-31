<?php
/**
 * Plugin Name: LearnPress Stats Dashboard
 * Description: Dashboard thống kê LearnPress + shortcode frontend
 * Version: 1.0
 * Author: Student
 */

if (!defined('ABSPATH')) exit;

/*
|--------------------------------------------------------------------------
| Lấy dữ liệu LearnPress
|--------------------------------------------------------------------------
*/
function lp_stats_get_data(){
    global $wpdb;

    // Tổng khóa học
    $total_courses = $wpdb->get_var("
        SELECT COUNT(ID)
        FROM {$wpdb->posts}
        WHERE post_type = 'lp_course'
        AND post_status = 'publish'
    ");

    // Tổng học viên đăng ký
    $total_students = $wpdb->get_var("
        SELECT COUNT(DISTINCT user_id)
        FROM {$wpdb->prefix}learnpress_user_items
        WHERE item_type = 'lp_course'
    ");

    // Khóa học hoàn thành
    $completed_courses = $wpdb->get_var("
        SELECT COUNT(*)
        FROM {$wpdb->prefix}learnpress_user_items
        WHERE item_type = 'lp_course'
        AND status = 'completed'
    ");

    return array(
        'courses'   => $total_courses,
        'students'  => $total_students,
        'completed' => $completed_courses
    );
}

/*
|--------------------------------------------------------------------------
| Dashboard Widget Admin
|--------------------------------------------------------------------------
*/
function lp_stats_dashboard_widget(){

    $stats = lp_stats_get_data();

    echo '<div style="font-size:14px;">';
    echo '<p>📚 Tổng khóa học: <strong>'.$stats['courses'].'</strong></p>';
    echo '<p>👨‍🎓 Tổng học viên: <strong>'.$stats['students'].'</strong></p>';
    echo '<p>✅ Khóa học hoàn thành: <strong>'.$stats['completed'].'</strong></p>';
    echo '</div>';
}

function lp_stats_add_dashboard_widget(){
    wp_add_dashboard_widget(
        'lp_stats_widget',
        'LearnPress Statistics',
        'lp_stats_dashboard_widget'
    );
}

add_action('wp_dashboard_setup', 'lp_stats_add_dashboard_widget');

/*
|--------------------------------------------------------------------------
| Shortcode Frontend
|--------------------------------------------------------------------------
*/
function lp_total_stats_shortcode(){

    $stats = lp_stats_get_data();

    ob_start();
    ?>
    <div style="
        border:1px solid #ddd;
        padding:15px;
        background:#fff;
        max-width:400px;
    ">
        <h3>LearnPress Statistics</h3>
        <p>📚 Tổng khóa học: <strong><?php echo $stats['courses']; ?></strong></p>
        <p>👨‍🎓 Tổng học viên: <strong><?php echo $stats['students']; ?></strong></p>
        <p>✅ Khóa học hoàn thành: <strong><?php echo $stats['completed']; ?></strong></p>
    </div>
    <?php

    return ob_get_clean();
}

add_shortcode('lp_total_stats', 'lp_total_stats_shortcode');