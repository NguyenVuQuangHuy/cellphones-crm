<?php
declare(strict_types=1);

$settings = parse_ini_file(
    dirname(__DIR__) . '/.env',
    false,
    INI_SCANNER_RAW
);

$get = static function (string $name) use ($settings): string {
    if (!array_key_exists($name, $settings)) {
        throw new RuntimeException("Thiếu biến {$name} trong file .env.");
    }

    return (string) $settings[$name];
};

$mockMode = filter_var(
    $get('MOCK_MODE'),
    FILTER_VALIDATE_BOOLEAN,
    FILTER_NULL_ON_FAILURE
);
if ($mockMode === null) {
    throw new RuntimeException('MOCK_MODE phải là true hoặc false.');
}

$cacheTtl = filter_var($get('CACHE_TTL_SECONDS'), FILTER_VALIDATE_INT);
if (!is_int($cacheTtl) || $cacheTtl < 0) {
    throw new RuntimeException('CACHE_TTL_SECONDS phải là số nguyên không âm.');
}
// Bật true để phát triển và kiểm thử giao diện mà không cần khóa API.
define('MOCK_MODE', true);
define('ODOO_BASE_URL', 'https://edu-cellphones.odoo.com');
define('ODOO_DB', 'edu-cellphones');
define('ODOO_USER_API_KEY', '<ODOO_USER_API_KEY>');

// Tùy chọn QT01: để trống để dùng luồng fallback CRM an toàn.
define('GEMINI_API_KEY', '');
define('GEMINI_MODEL', 'gemini-2.5-flash');

define('CACHE_TTL_SECONDS', 300);
define('DEMO_PREFIX', '[WP-DEMO]');
