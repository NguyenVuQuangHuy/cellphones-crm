<?php
declare(strict_types=1);

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
