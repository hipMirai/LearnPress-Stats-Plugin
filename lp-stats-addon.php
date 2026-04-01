<?php
/**
 * Plugin Name: LearnPress Stats Dashboard
 * Description: LearnPress nâng cao: stats + course info + notification
 * Version: 2.0.1
 * Author: Student
 * License: GPL2
 */

if (!defined('ABSPATH')) exit;

/*
|--------------------------------------------------------------------------
| YÊU CẦU 1: Notification bar
|--------------------------------------------------------------------------
| Hiển thị thanh thông báo trên trang khóa học
|--------------------------------------------------------------------------
*/
function lp_student_notification_bar(){
    // Hiển thị trên archive, single course, hoặc page có course content
    $current_url = $_SERVER['REQUEST_URI'] ?? '';
    
    // K iểm tra xem đang trên page liên quan đến course không
    $is_course_page = (is_post_type_archive('lp_course') || 
                      is_singular('lp_course') || 
                      strpos($current_url, 'courses') !== false ||
                      strpos($current_url, 'course') !== false);
    
    if(!$is_course_page) return;

    echo '<div class="lp-notification-bar">';

    if(is_user_logged_in()){
        $current_user = wp_get_current_user();
        $display_name = !empty($current_user->display_name) ? $current_user->display_name : $current_user->user_login;
        echo "👋 Chào {$display_name}, bạn đã sẵn sàng bắt đầu bài học hôm nay chưa?";
    }else{
        echo "🔐 Đăng nhập để lưu tiến độ học tập!";
    }

    echo '</div>';
}

// Thêm nhiều hook để đảm bảo notification hiển thị
// Hook LearnPress chính
if(function_exists('is_post_type_archive')){
    add_action('learn-press/archive-course/before-loop', 'lp_student_notification_bar', 5);
    add_action('learn-press/single-course/before-content', 'lp_student_notification_bar', 5);
    add_action('learn-press/before-courses-loop', 'lp_student_notification_bar', 5);
}

// Hook WordPress fallback (nếu LearnPress hook không tồn tại)
add_action('wp_footer', 'lp_student_notification_bar_footer', 999);

function lp_student_notification_bar_footer(){
    // Debug version - chạy từ footer như fallback
    $current_url = $_SERVER['REQUEST_URI'] ?? '';
    
    // Chỉ chạy nếu là course page
    if(strpos($current_url, 'courses') === false && strpos($current_url, 'course') === false){
        return;
    }
    
    // Kiểm tra notification đã được output chưa (để tránh duplicate)
    if(did_action('lp_notification_added')){
        return;
    }
    
    // Nếu footer hook chạy, thêm script để inject notification lên đầu trang
    echo '<script>
    (function(){
        // Tìm notification bar đã tồn tại
        let notification = document.querySelector(".lp-notification-bar");
        
        // Nếu chưa có, thêm nó
        if(!notification){
            let bar = document.createElement("div");
            bar.className = "lp-notification-bar";
            bar.innerHTML = "' . (is_user_logged_in() ? 
                "👋 Chào " . esc_js(wp_get_current_user()->display_name ?: wp_get_current_user()->user_login) . ", bạn đã sẵn sàng bắt đầu bài học hôm nay chưa?" :
                "🔐 Đăng nhập để lưu tiến độ học tập!"
            ) . '";
            
            // Chèn vào đầu body
            if(document.body){
                document.body.insertBefore(bar, document.body.firstChild);
            }
        }
    })();
    </script>';
    
    do_action('lp_notification_added');
}


/*
|--------------------------------------------------------------------------
| YÊU CẦU 2: Shortcode chi tiết khóa học
|--------------------------------------------------------------------------
| Cú pháp: [lp_course_info id="123"]
| Hiển thị: Số bài học, thời lượng, trạng thái người dùng
|--------------------------------------------------------------------------
*/
// Shortcode: [lp_course_info id="xxx"]
function lp_course_info_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => ''
    ), $atts);

    $course_id = intval($atts['id']);
    if (!$course_id) return "Course ID không hợp lệ";

    if (!function_exists('learn_press_get_course')) {
        return "LearnPress chưa được kích hoạt";
    }

    $course = learn_press_get_course($course_id);
    if (!$course) return "Không tìm thấy khóa học";

    // Số bài học
    $lessons = $course->get_items('lp_lesson');
    $lesson_count = count($lessons);

    // Duration
    $duration = get_post_meta($course_id, '_lp_duration', true);

    // Trạng thái user
    $status = "Chưa đăng nhập";

    if (is_user_logged_in()) {
        $user = learn_press_get_current_user();
        $course_data = $user->get_course_data($course_id);

        if ($course_data) {
            $status = $course_data->get_status();

            if ($status == 'completed') {
                $status = "Đã hoàn thành";
            } elseif ($status == 'enrolled') {
                $status = "Đã đăng ký";
            } else {
                $status = "Đang học";
            }
        } else {
            $status = "Chưa đăng ký";
        }
    }

    ob_start();
    ?>
    <div class="lp-course-info-box">
        <p>📚 Số bài học: <?php echo $lesson_count; ?></p>
        <p>⏱ Thời lượng: <?php echo esc_html($duration); ?></p>
        <p>👤 Trạng thái: <?php echo esc_html($status); ?></p>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode('lp_course_info', 'lp_course_info_shortcode');


/*
|--------------------------------------------------------------------------
| YÊU CẦU 3: Custom CSS - Tùy biến stlye nút và thông báo
|--------------------------------------------------------------------------
| Màu sắc thương hiệu: Cam (#ff9800), Đỏ cam (#ff5722), Xanh lá (#4caf50)
|--------------------------------------------------------------------------
*/
function lp_custom_style(){

    echo '<style>

    /* ==========================================
       YÊU CẦU 1: Notification Bar Styling
       =========================================== */
    .lp-notification-bar {
        background: linear-gradient(135deg, #ff9800 0%, #ff6f00 100%);
        color: #fff;
        padding: 15px 20px;
        text-align: center;
        font-weight: 600;
        font-size: 15px;
        margin: 0;
        border-bottom: 3px solid #ff6f00;
        text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        animation: slideDown 0.3s ease-in-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .lp-notification-bar:hover {
        background: linear-gradient(135deg, #ff6f00 0%, #ff5500 100%);
    }

    /* ==========================================
       YÊU CẦU 2: Course Info Shortcode Styling
       =========================================== */
    .lp-course-info {
        border: 2px solid #ff9800;
        border-radius: 8px;
        padding: 20px;
        background: linear-gradient(135deg, #fff9f5 0%, #fffbf8 100%);
        margin: 20px 0;
        box-shadow: 0 2px 8px rgba(255, 152, 0, 0.15);
        transition: all 0.3s ease;
    }

    .lp-course-info:hover {
        box-shadow: 0 4px 12px rgba(255, 152, 0, 0.25);
        transform: translateY(-2px);
    }

    .lp-course-info-header {
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #ff9800;
    }

    .lp-course-info-title {
        margin: 0;
        color: #ff6f00;
        font-size: 18px;
        font-weight: 700;
    }

    .lp-course-info-content {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .lp-course-info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #ffe0b2;
    }

    .lp-course-info-row:last-child {
        border-bottom: none;
    }

    .lp-course-info-label {
        font-weight: 600;
        color: #333;
        flex: 0 0 40%;
    }

    .lp-course-info-value {
        font-weight: 600;
        color: #ff6f00;
        flex: 0 0 60%;
        text-align: right;
    }

    .lp-course-status-not-enrolled {
        color: #999 !important;
    }

    .lp-course-status-enrolled {
        color: #2196f3 !important;
    }

    .lp-course-status-completed {
        color: #4caf50 !important;
    }

    .lp-course-info-error {
        background: #ffebee;
        color: #c62828;
        padding: 15px 20px;
        border-left: 4px solid #c62828;
        border-radius: 4px;
        margin: 15px 0;
        font-weight: 500;
    }

    /* ==========================================
       YÊU CẦU 3: Enroll & Finish Button Styling
       =========================================== */
    /* Nút Enroll (Ghi danh) - Màu Cam */
    .learn-press-course .lp-button,
    .learn-press-course .button-enroll-course,
    .single-lp_course .button-enroll-course,
    a.button-enroll-course,
    .lp-enroll-button,
    .enroll-course-button {
        background: linear-gradient(135deg, #ff9800 0%, #ff6f00 100%) !important;
        color: #fff !important;
        border: none !important;
        padding: 12px 25px !important;
        font-weight: 600 !important;
        border-radius: 5px !important;
        text-transform: uppercase !important;
        font-size: 14px !important;
        transition: all 0.3s ease !important;
        box-shadow: 0 4px 6px rgba(255, 152, 0, 0.3) !important;
        cursor: pointer !important;
    }

    .learn-press-course .lp-button:hover,
    .learn-press-course .button-enroll-course:hover,
    .single-lp_course .button-enroll-course:hover,
    a.button-enroll-course:hover,
    .lp-enroll-button:hover,
    .enroll-course-button:hover {
        background: linear-gradient(135deg, #ff6f00 0%, #ff5500 100%) !important;
        box-shadow: 0 6px 12px rgba(255, 152, 0, 0.4) !important;
        transform: translateY(-2px) !important;
    }

    /* Nút Finish Course (Hoàn thành khóa) - Màu Xanh lá */
    .learn-press-course .button-finish-course,
    .single-lp_course .button-finish-course,
    a.button-finish-course,
    .lp-finish-button,
    .finish-course-button {
        background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%) !important;
        color: #fff !important;
        border: none !important;
        padding: 12px 25px !important;
        font-weight: 600 !important;
        border-radius: 5px !important;
        text-transform: uppercase !important;
        font-size: 14px !important;
        transition: all 0.3s ease !important;
        box-shadow: 0 4px 6px rgba(76, 175, 80, 0.3) !important;
        cursor: pointer !important;
    }

    .learn-press-course .button-finish-course:hover,
    .single-lp_course .button-finish-course:hover,
    a.button-finish-course:hover,
    .lp-finish-button:hover,
    .finish-course-button:hover {
        background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%) !important;
        box-shadow: 0 6px 12px rgba(76, 175, 80, 0.4) !important;
        transform: translateY(-2px) !important;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .lp-notification-bar {
            padding: 12px 15px;
            font-size: 14px;
        }

        .lp-course-info {
            padding: 15px;
        }

        .lp-course-info-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }

        .lp-course-info-label,
        .lp-course-info-value {
            flex: 100%;
            text-align: left;
        }
    }

    </style>';
}

add_action('wp_head', 'lp_custom_style');


/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS - Các hàm hỗ trợ tiện ích
|--------------------------------------------------------------------------
*/

/**
 * Lấy số lượng bài học trong khóa học
 * 
 * @param int $course_id ID của khóa học
 * @return int Số lượng bài học
 */
function lp_get_course_lessons_count($course_id){
    $lessons = get_posts([
        'post_type' => 'lp_lesson',
        'meta_query' => [
            [
                'key' => '_lp_course_id',
                'value' => $course_id
            ]
        ],
        'numberposts' => -1,
        'post_status' => 'publish'
    ]);
    return count($lessons);
}

/**
 * Lấy thời lượng khóa học
 * 
 * @param int $course_id ID của khóa học
 * @return string Thời lượng (hoặc "Không xác định")
 */
function lp_get_course_duration($course_id){
    $duration = get_post_meta($course_id, '_lp_duration', true);
    return !empty($duration) ? $duration : 'Không xác định';
}

/**
 * Kiểm tra trạng thái đăng ký khóa học của người dùng
 * 
 * @param int $user_id ID của người dùng
 * @param int $course_id ID của khóa học
 * @return string Trạng thái: 'not-enrolled', 'enrolled', 'completed'
 */
function lp_get_user_course_status($user_id, $course_id){
    if(!is_user_logged_in()) return 'not-enrolled';
    
    $enrolled = learn_press_user_enrolled_course($user_id, $course_id);
    
    if(!$enrolled) return 'not-enrolled';
    
    $user = learn_press_get_user($user_id);
    $user_course = $user->get_course_data($course_id);
    
    if($user_course && $user_course->get_status() == 'completed'){
        return 'completed';
    }
    
    return 'enrolled';
}

/**
 * Format lý do không hiển thị thông báo hoặc thông tin
 * Hữu ích cho debugging
 */
function lp_addon_debug_log($message){
    if(WP_DEBUG){
        error_log('LP-Stats-Addon: ' . $message);
    }
}


/*
|--------------------------------------------------------------------------
| ACTIVATION HOOK - Kích hoạt plugin
|--------------------------------------------------------------------------
*/
function lp_stats_addon_activate(){
    // Có thể thêm setup code ở đây nếu cần thiết
    lp_addon_debug_log('LearnPress Stats Addon activated');
}

register_activation_hook(__FILE__, 'lp_stats_addon_activate');


/*
|--------------------------------------------------------------------------
| DOCUMENTATION - Hướng dẫn sử dụng
|--------------------------------------------------------------------------

HƯỚNG DẪN SỬ DỤNG PLUGIN:

1. NOTIFICATION BAR (Thanh Thông Báo)
   ==========================================
   - Tự động hiển thị ở phía trên cùng trang khóa học
   - Nếu đã đăng nhập: "👋 Chào [Tên], bạn đã sẵn sàng bắt đầu bài học hôm nay chưa?"
   - Nếu chưa đăng nhập: "🔐 Đăng nhập để lưu tiến độ học tập!"
   - Hiển thị trên: Course Archive + Single Course Page
   - CSS Class: .lp-notification-bar

2. SHORTCODE [lp_course_info]
   ==========================================
   Cú pháp: [lp_course_info id="123" class="my-custom-class"]
   
   Ví dụ:
   - [lp_course_info id="45"]
   - [lp_course_info id="45" class="featured"]
   
   Thông tin hiển thị:
   - 📖 Số bài học
   - ⏱ Thời lượng
   - 🎯 Trạng thái người dùng
   
   CSS Classes:
   - .lp-course-info (container chính)
   - .lp-course-info-row (mỗi dòng thông tin)
   - .lp-course-info-label (nhãn)
   - .lp-course-info-value (giá trị)
   - .lp-course-status-{status} (trạng thái, có thể là: not-enrolled, enrolled, completed)

3. CUSTOM STYLING (Tùy Biến Màu Sắc)
   ==========================================
   Màu sắc mặc định:
   - Notification Bar: Cam (#ff9800)
   - Button Enroll: Cam (#ff9800)
   - Button Finish: Xanh lá (#4caf50)
   
   Để tùy biến màu sắc, hãy chỉnh sửa giá trị HEX trong lp_custom_style()
   
   Các selector CSS:
   - .lp-notification-bar
   - .button-enroll-course
   - .button-finish-course
   - .lp-course-info

4. HELPER FUNCTIONS (Hàm Hỗ Trợ)
   ==========================================
   Có thể sử dụng các hàm này trong theme hoặc plugin khác:
   
   - lp_get_course_lessons_count($course_id)
     Lấy số bài học
   
   - lp_get_course_duration($course_id)
     Lấy thời lượng khóa học
   
   - lp_get_user_course_status($user_id, $course_id)
     Kiểm tra trạng thái: 'not-enrolled', 'enrolled', 'completed'

|--------------------------------------------------------------------------
*/