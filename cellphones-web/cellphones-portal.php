<?php

/**
 * Plugin Name: CellphoneS CRM Portal
 * Description: Customer portal for the CellphoneS CRM course project.
 * Version: 1.0.0
 * Requires PHP: 8.1
 */

defined('ABSPATH') || exit;

// These APIs are provided by WordPress; the editor may not have its stubs indexed.
// @intelephense-ignore-start
require_once __DIR__ . '/custom-app/admin.php';

function cellphones_portal_assets(): void 
{
    $base = plugin_dir_url(__FILE__);
    wp_enqueue_style('cellphones-portal', $base . 'assets/css/style.css', [], '1.2.5');
    wp_enqueue_script('cellphones-portal', $base . 'assets/js/app.js', [], '1.0.3', true);
    wp_add_inline_script(
        'cellphones-portal',
        'window.CELLPHONES_API_URL = ' . wp_json_encode($base . 'api.php') . ';',
        'before'
    );
}

function cellphones_portal_shortcode(): string
{
    $html = file_get_contents(__DIR__ . '/index.html');
    if ($html === false || !preg_match('/<body[^>]*>(.*?)<\/body>/si', $html, $matches)) {
        return '<p>Không tải được cổng khách hàng. Vui lòng thử lại.</p>';
    }

    cellphones_portal_assets();
    $content = preg_replace('/\s*<script[^>]*src="assets\/js\/app\.js"[^>]*><\/script>/i', '', $matches[1]);
    $content = str_replace(
        ['assets/tech-hero.webp', 'assets/cellphones-mark.svg'],
        [plugin_dir_url(__FILE__) . 'assets/tech-hero.webp', plugin_dir_url(__FILE__) . 'assets/cellphones-mark.svg'],
        $content
    );
    $configPath = __DIR__ . '/config.php';
    require_once is_file($configPath) ? $configPath : __DIR__ . '/config.example.php';
    $demoNotice = defined('MOCK_MODE') && MOCK_MODE
        ? 'Đang dùng chế độ mô phỏng: biểu mẫu không tạo hồ sơ thực trên Odoo. Không nhập họ tên, số điện thoại, email, địa chỉ, IMEI hoặc dữ liệu cá nhân thật. Nếu chuyển sang Odoo thật, cần công bố trước mục đích, phạm vi, thời hạn xử lý dữ liệu và đầu mối liên hệ của đơn vị vận hành.'
        : 'CẢNH BÁO: Odoo thật đang bật; dữ liệu biểu mẫu có thể được gửi vào hệ thống Odoo. Không nhập dữ liệu thật trước khi đơn vị vận hành công bố đầy đủ mục đích, phạm vi, thời hạn xử lý và thông tin liên hệ.';
    $content = str_replace('<!-- MODE-NOTICE -->', esc_html($demoNotice), $content);
    $commerceNotice = defined('MOCK_MODE') && MOCK_MODE
        ? 'Danh mục, giá, tồn kho và trạng thái là dữ liệu trình diễn; không phải báo giá hoặc cam kết cung cấp hàng. Giỏ hàng và đặt COD chỉ mô phỏng, không xác lập giao dịch. Website không phải kênh bán hàng chính thức và không đại diện CellphoneS.'
        : 'CẢNH BÁO: Website đang kết nối hệ thống thật. Trước khi công khai nhận giao dịch, đơn vị vận hành cần bổ sung thông tin pháp nhân và liên hệ chính xác, điều kiện giao dịch và chính sách áp dụng; footer này không thay thế thủ tục pháp lý cần thiết.';
    $content = str_replace('<!-- COMMERCE-NOTICE -->', esc_html($commerceNotice), $content);
    return $content;
}

add_shortcode('cellphones_crm_portal', 'cellphones_portal_shortcode');

function cellphones_portal_create_page(): void
{
    if (get_option('cellphones_portal_page_created')) {
        return;
    }

    $page_id = wp_insert_post([
        'post_title' => 'CellphoneS CRM Portal',
        'post_name' => 'cellphones-crm',
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_content' => '[cellphones_crm_portal]',
    ]);

    if (!is_wp_error($page_id)) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $page_id);
        update_option('cellphones_portal_page_created', $page_id);
    }
}

register_activation_hook(__FILE__, 'cellphones_portal_create_page');
// @intelephense-ignore-end
