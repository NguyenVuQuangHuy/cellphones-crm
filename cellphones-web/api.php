<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$configFile = __DIR__ . '/config.php';
require is_file($configFile) ? $configFile : __DIR__ . '/config.example.php';

const ACTIONS = ['get_products', 'create_lead', 'create_order', 'create_ticket', 'check_ticket'];
const BRANCHES = [
    'CH01' => [
        'team_id' => 4,
        'tag_id' => 43,
        'label' => 'CH01 - 136 Nguyễn Thái Học',
        'ticket_key' => 'CH01 - CellphoneS 136 Nguyễn Thái Học'
    ],
    'CH02' => [
        'team_id' => 5,
        'tag_id' => 56,
        'label' => 'CH02 - 21A Hàng Bài',
        'ticket_key' => 'CH01 - CellphoneS 136 Nguyễn Thái Học, CH02 - CellphoneS 21A Hàng Bài'
    ],
];
const STAGES = [
    1 => 'Mới tiếp nhận',
    2 => 'Đang xác minh',
    11 => 'Chờ khách / Chờ linh kiện',
    12 => 'Đang xử lý',
    13 => 'Đã giải quyết',
    6 => 'Đóng'
];

function respond(bool $ok, string $code, string $message, mixed $data = null, int $status = 200): never {
    http_response_code($status);
    echo json_encode(compact('ok', 'code', 'message', 'data'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fail(string $message, array $fields = []): never {
    respond(false, 'VALIDATION_ERROR', $message, ['fields' => $fields]);
}

function clean_text(mixed $value, int $max = 1000): string {
    $value = trim((string) $value);
    return function_exists('mb_substr') ? mb_substr($value, 0, $max, 'UTF-8') : substr($value, 0, $max);
}

function safe_html(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function normalize_phone(mixed $value): string {
    $digits = preg_replace('/\D+/', '', (string) $value) ?? '';
    if (str_starts_with($digits, '84') && strlen($digits) === 11) $digits = '0' . substr($digits, 2);
    return $digits;
}

function valid_phone(string $phone): bool {
    return preg_match('/^0[35789][0-9]{8}$/', $phone) === 1;
}

function valid_email(string $email): bool {
    return $email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function odoo_call(string $model, string $method, array $payload): array {
    if (!function_exists('curl_init')) throw new RuntimeException('PHP cURL chưa được bật.');
    $url = rtrim(ODOO_BASE_URL, '/') . "/json/2/{$model}/{$method}";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . ODOO_USER_API_KEY,
            'X-Odoo-Database: ' . ODOO_DB,
            'Content-Type: application/json; charset=utf-8',
            'Accept: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($raw === false || $error !== '') throw new RuntimeException('Không thể kết nối Odoo.');
    $decoded = json_decode($raw, true);
    if ($status < 200 || $status >= 300) {
        $detail = is_array($decoded) ? (string) ($decoded['message'] ?? $decoded['error']['message'] ?? '') : '';
        throw new RuntimeException($detail !== '' ? $detail : "Odoo trả lỗi HTTP {$status}");
    }
    if (json_last_error() !== JSON_ERROR_NONE) throw new RuntimeException('Phản hồi Odoo không hợp lệ.');
    return is_array($decoded) ? $decoded : ['result' => $decoded];
}

function result_rows(array $response): array {
    $value = $response['result'] ?? $response;
    return is_array($value) ? $value : [];
}

function created_id(array $response): int {
    $value = $response['result'] ?? $response;
    if (is_int($value)) return $value;
    if (is_array($value)) {
        if (isset($value[0]) && is_numeric($value[0])) return (int) $value[0];
        if (isset($value['id'])) return (int) $value['id'];
    }
    throw new RuntimeException('Odoo không trả về ID bản ghi mới.');
}

function products(): array {
    $fallback = __DIR__ . '/data/products_fallback.json';
    $cache = __DIR__ . '/cache/products.json';
    if (MOCK_MODE) return [
        'items' => json_decode((string)file_get_contents($fallback), true),
        'source' => 'mock_fallback'
    ];
    if (is_file($cache) && time() - filemtime($cache) < CACHE_TTL_SECONDS) return [
        'items' => json_decode((string)file_get_contents($cache), true),
        'source' => 'cache'
    ];
    try {
        $rows = result_rows(odoo_call('product.product', 'search_read', [
            'domain' => [['default_code', 'ilike', 'SP-']],
            'fields' => ['id', 'default_code', 'name', 'list_price', 'qty_available', 'description_sale'],
            'limit' => 100
        ]));
        foreach ($rows as &$row) $row['category'] = product_category((string) ($row['default_code'] ?? ''));
        unset($row);
        file_put_contents($cache, json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
        return ['items' => $rows, 'source' => 'odoo_live'];
    } catch (Throwable $e) {
        error_log($e->getMessage());
        return [
            'items' => json_decode((string)file_get_contents($fallback), true),
            'source' => 'fallback_on_error'
        ];
    }
}

function product_category(string $code): string {
    return match (true) {
        str_starts_with($code, 'SP-LAP-'), str_starts_with($code, 'SP-MAC-') => 'Laptop',
        str_starts_with($code, 'SP-TAB-') => 'Tablet', str_starts_with($code, 'SP-SW-') => 'Đồng hồ thông minh',
        str_starts_with($code, 'SP-AU-') => 'Âm thanh', str_starts_with($code, 'SP-PK-') => 'Phụ kiện',
        default => 'Điện thoại'
    };
}

function product_map(): array {
    $list = json_decode((string)file_get_contents(__DIR__ . '/data/products_fallback.json'), true) ?: [];
    $out = [];
    foreach ($list as $item) $out[(int) $item['id']] = $item;
    return $out;
}

function partner_for(string $name, string $phone, string $email, string $branch, string $segment = '', string $address = ''): array {
    if (MOCK_MODE) return ['id' => 9001, 'existing' => false];
    $phone84 = '+84' . substr($phone, 1);
    $found = result_rows(odoo_call('res.partner', 'search_read', [
        'domain' => [
            '|',
            '|',
            ['phone_sanitized', '=', $phone84],
            ['phone', '=', $phone],
            ['phone', '=', $phone84]
        ],
        'fields' => ['id', 'street'],
        'limit' => 1
    ]));
    $segmentTag = $segment === 'student' ? 55 : ($segment === 'teacher' ? 49 : 50);
    $tags = [93, BRANCHES[$branch]['tag_id'], $segmentTag];
    if (!$found) {
        $vals = [
            'name' => $name,
            'phone' => $phone,
            'category_id' => [[6, 0, array_values(array_unique($tags))]]
        ];
        if ($email !== '') $vals['email'] = $email;
        if ($address !== '') $vals['street'] = $address;
        return [
            'id' => created_id(odoo_call('res.partner', 'create', ['vals_list' => [$vals]])),
            'existing' => false
        ];
    }
    $id = (int) $found[0]['id'];
    $vals = ['category_id' => array_map(fn($id) => [4, $id, 0], array_values(array_unique($tags)))];
    if ($address !== '' && empty($found[0]['street'])) $vals['street'] = $address;
    odoo_call('res.partner', 'write', ['ids' => [$id], 'vals' => $vals]);
    return ['id' => $id, 'existing' => true];
}

function mock_ref(string $prefix, int $pad = 0): string {
    $seed = (int) (microtime(true) * 1000) % 99999;
    return $prefix . ($pad ? str_pad((string) $seed, $pad, '0', STR_PAD_LEFT) : $seed);
}

function gemini_classify(string $note): array {
    $fallback = [
        'category' => 'Tư vấn mua hàng',
        'summary' => '',
        'is_ambiguous' => false,
        'ai_used' => false,
        'ai_fallback' => false
    ];
    if (MOCK_MODE || trim(GEMINI_API_KEY) === '') return $fallback;
    try {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode(GEMINI_MODEL) . ':generateContent?key=' . rawurlencode(GEMINI_API_KEY);
        $body = [
            'contents' => [['parts' => [['text' => "Phân loại nội dung sau thành Tư vấn mua hàng, Bảo hành hoặc Khiếu nại; tóm tắt một câu: {$note}"]]]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'category' => ['type' => 'STRING', 'enum' => ['Tư vấn mua hàng', 'Bảo hành', 'Khiếu nại']],
                        'summary' => ['type' => 'STRING'],
                        'is_ambiguous' => ['type' => 'BOOLEAN']
                    ],
                    'required' => ['category', 'summary', 'is_ambiguous']
                ]
            ]
        ];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE)
        ]);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false || $status < 200 || $status >= 300) throw new RuntimeException('Gemini unavailable');
        $outer = json_decode($raw, true);
        $text = $outer['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $value = json_decode($text, true);
        if (!is_array($value) || !empty($value['is_ambiguous'])) return $fallback + ['ai_fallback' => true];
        return [
            'category' => (string) $value['category'],
            'summary' => clean_text($value['summary'] ?? '', 300),
            'is_ambiguous' => false,
            'ai_used' => true,
            'ai_fallback' => false
        ];
    } catch (Throwable $e) {
        error_log($e->getMessage());
        return $fallback + ['ai_fallback' => true];
    }
}

function create_lead(array $d): never {
    $name = clean_text($d['contact_name'] ?? '', 120);
    $phone = normalize_phone($d['phone'] ?? '');
    $email = clean_text($d['email_from'] ?? '', 160);
    $branch = (string) ($d['branch'] ?? '');
    $note = clean_text($d['note'] ?? '', 2000);
    if ($name === ''
        || !valid_phone($phone)
        || !isset(BRANCHES[$branch])
        || $note === ''
        || !valid_email($email))
        fail('Vui lòng kiểm tra họ tên, số điện thoại, email, chi nhánh và nội dung tư vấn.');
    $productId = (int) ($d['product_id'] ?? 0);
    $catalog = product_map();
    $product = $catalog[$productId] ?? null;
    $productName = clean_text($d['product_name'] ?? ($product['name'] ?? 'Nhu cầu mua hàng'), 200);
    $partner = partner_for($name, $phone, $email, $branch, (string) ($d['customer_segment'] ?? ''));
    $ai = gemini_classify($note);
    if ($ai['category'] === 'Bảo hành' && !$ai['is_ambiguous']) {
        create_ticket([
            'partner_name' => $name,
            'partner_phone' => $phone,
            'partner_email' => $email,
            'branch' => $branch,
            'loai_yeu_cau' => 'Bảo hành sửa chữa',
            'product_id' => $productId,
            'ten_thiet_bi' => $productName,
            'subject' => 'Yêu cầu bảo hành từ Web',
            'description' => $note
        ]);
    }
    if (MOCK_MODE) respond(true, 'OPPORTUNITY_CREATED', 'Đã tiếp nhận yêu cầu tư vấn ở chế độ mô phỏng.', [
        'routed_to' => 'crm',
        'record_id' => 28,
        'partner_id' => $partner['id'],
        'is_existing_customer' => $partner['existing'],
        'team_label' => BRANCHES[$branch]['label'],
        'ai_category' => $ai['category'],
        'ai_summary' => $ai['summary'],
        'ai_used' => $ai['ai_used'],
        'idempotent_hit' => false
    ]);
    $recent = result_rows(odoo_call('crm.lead', 'search_read', [
        'domain' => [
            ['partner_id', '=', $partner['id']],
            ['type', '=', 'opportunity'],
            ['active', '=', true],
            ['stage_id', '=', 1],
            ['create_date', '>=', gmdate('Y-m-d H:i:s', time() - 600)]
        ],
        'fields' => ['id'],
        'limit' => 1
    ]));
    if ($recent) respond(true, 'OPPORTUNITY_EXISTS', 'Yêu cầu vừa gửi đã được tiếp nhận trước đó.', [
        'routed_to' => 'crm',
        'record_id' => (int) $recent[0]['id'],
        'partner_id' => $partner['id'],
        'idempotent_hit' => true,
        'ai_category' => $ai['category']
    ]);
    $desc = '<p><strong>[Nguồn Web PHP - QT01 TO-BE]</strong></p><p><strong>AI Phân loại:</strong> '
        . safe_html($ai['category'])
        . ($ai['summary'] !== '' ? ' | <strong>Tóm tắt:</strong> ' . safe_html($ai['summary']) : '')
        . '</p><p><strong>Nội dung gốc:</strong> ' . safe_html($note) . '</p>';
    $vals = [
        'name' => DEMO_PREFIX . ' ' . $name . ' - ' . $productName,
        'type' => 'opportunity',
        'stage_id' => 1,
        'partner_id' => $partner['id'],
        'contact_name' => $name,
        'phone' => $phone,
        'team_id' => BRANCHES[$branch]['team_id'],
        'source_id' => 15,
        'expected_revenue' => (float) ($product['list_price'] ?? 0),
        'description' => $desc
    ];
    if ($email !== '') $vals['email_from'] = $email;
    $id = created_id(odoo_call('crm.lead', 'create', ['vals_list' => [$vals]]));
    respond(true, 'OPPORTUNITY_CREATED', 'Đã tiếp nhận yêu cầu tư vấn và chuyển tới đội kinh doanh ' . $branch . '.', [
        'routed_to' => 'crm',
        'record_id' => $id,
        'partner_id' => $partner['id'],
        'is_existing_customer' => $partner['existing'],
        'team_label' => BRANCHES[$branch]['label'],
        'ai_category' => $ai['category'],
        'ai_summary' => $ai['summary'],
        'ai_used' => $ai['ai_used'],
        'idempotent_hit' => false
    ]);
}

function create_order(array $d): never {
    $name = clean_text($d['customer_name'] ?? '', 120);
    $phone = normalize_phone($d['phone'] ?? '');
    $email = clean_text($d['email'] ?? '', 160);
    $branch = (string) ($d['branch'] ?? '');
    $address = clean_text($d['address'] ?? '', 300);
    $items = $d['items'] ?? [];
    if ($name === ''
        || !valid_phone($phone)
        || !isset(BRANCHES[$branch])
        || !valid_email($email)
        || !is_array($items)
        || !$items)
        fail('Vui lòng nhập đủ thông tin nhận hàng và chọn ít nhất một sản phẩm.');
    $catalog = product_map();
    $lines = [];
    $mockTotal = 0.0;
    foreach ($items as $item) {
        $id = (int) ($item['product_id'] ?? 0);
        $qty = (int) ($item['qty'] ?? 0);
        if (!isset($catalog[$id]) || $qty < 1 || $qty > 99) fail('Giỏ hàng chứa sản phẩm hoặc số lượng không hợp lệ.');
        $lines[] = [0, 0, ['product_id' => $id, 'product_uom_qty' => $qty]];
        $mockTotal+=(float) $catalog[$id]['list_price'] * $qty;
    }
    $partner = partner_for($name, $phone, $email, $branch, '', $address);
    $ref = 'WP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
    $note = clean_text($d['note'] ?? '', 1000);
    if (MOCK_MODE) respond(true, 'ORDER_CREATED', 'Đặt hàng mô phỏng thành công! Nhân viên sẽ gọi xác nhận.', [
        'order_id' => 47,
        'order_name' => 'S' . str_pad((string) (time() % 100000), 5, '0', STR_PAD_LEFT),
        'client_order_ref' => $ref,
        'amount_total' => $mockTotal * 1.1,
        'state' => 'draft',
        'team_label' => BRANCHES[$branch]['label']
    ]);
    $html = '<p><strong>[WP-DEMO - Đơn hàng Web COD]</strong></p><p>Địa chỉ giao: ' . safe_html($address) . '</p><p>Ghi chú khách: ' . safe_html($note) . '</p>';
    $id = created_id(odoo_call(
        'sale.order',
        'create',
        ['vals_list' => [[
            'partner_id' => $partner['id'],
            'team_id' => BRANCHES[$branch]['team_id'],
            'warehouse_id' => 1,
            'client_order_ref' => $ref,
            'note' => $html,
            'order_line' => $lines
        ]]]
    ));
    $rows = result_rows(odoo_call('sale.order', 'search_read', [
        'domain' => [['id', '=', $id]],
        'fields' => ['name', 'amount_total', 'state'],
        'limit' => 1
    ]));
    $row = $rows[0] ?? [];
    respond(true, 'ORDER_CREATED', 'Đặt hàng thành công! Mã báo giá ' . ($row['name'] ?? $id) . '.', [
        'order_id' => $id,
        'order_name' => $row['name'] ?? (string) $id,
        'client_order_ref' => $ref,
        'amount_total' => (float) ($row['amount_total'] ?? 0),
        'state' => $row['state'] ?? 'draft',
        'team_label' => BRANCHES[$branch]['label']
    ]);
}

function create_ticket(array $d): never {
    $name = clean_text($d['partner_name'] ?? '', 120);
    $phone = normalize_phone($d['partner_phone'] ?? '');
    $email = clean_text($d['partner_email'] ?? '', 160);
    $branch = (string) ($d['branch'] ?? '');
    $type = clean_text($d['loai_yeu_cau'] ?? '', 80);
    $device = clean_text($d['ten_thiet_bi'] ?? '', 200);
    $subject = clean_text($d['subject'] ?? '', 200);
    $description = clean_text($d['description'] ?? '', 2000);
    $allowed = ['Bảo hành sửa chữa', 'Đổi trả sản phẩm', 'Khiếu nại / Tư vấn hậu mãi'];
    if ($name === ''
        || !valid_phone($phone)
        || !valid_email($email)
        || !isset(BRANCHES[$branch])
        || !in_array($type, $allowed, true)
        || $device === ''
        || $subject === ''
        || $description === '')
        fail('Vui lòng kiểm tra đầy đủ thông tin phiếu bảo hành.');
    $partner = partner_for($name, $phone, $email, $branch);
    $ref = str_pad((string) (time() % 100000), 5, '0', STR_PAD_LEFT);
    if (MOCK_MODE) respond(true, 'TICKET_CREATED', 'Đã tạo phiếu bảo hành mô phỏng #' . $ref . '. SLA 72h đang tạm dừng chờ mang máy tới quầy.', [
        'ticket_id' => (int) $ref,
        'ticket_ref' => $ref,
        'stage_id' => 11,
        'stage_name' => STAGES[11],
        'sla_paused' => true,
        'branch_label' => '[' . $branch . '] ' . ($branch === 'CH01' ? '136 Nguyễn Thái Học' : '21A Hàng Bài')
    ]);
    $vals = [
        'name' => DEMO_PREFIX . ' ' . $subject,
        'team_id' => 2,
        'stage_id' => 11,
        'partner_id' => $partner['id'],
        'partner_name' => $name,
        'partner_phone' => $phone,
        'x_studio_sdt_tra_cuu' => $phone,
        'x_studio_loai_yeu_cau' => $type,
        'x_studio_cua_hang_tiep_nhan' => BRANCHES[$branch]['ticket_key'],
        'x_studio_ten_thiet_bi_ghi_nhan' => $device,
        'x_studio_nhan_giu_thiet_bi_tai_quay' => 'Không nhận máy',
        'x_studio_ly_do_cho' => 'Chờ khách hàng phản hồi',
        'x_studio_han_theo_doi_tiep_theo' => gmdate('Y-m-d H:i:s', time() + 48 * 3600),
        'description' => '<p><strong>[Nguồn Web PHP - QT03 TO-BE]</strong></p><p>' . safe_html($description) . '</p>'
    ];
    if ($email !== '') $vals['partner_email'] = $email;
    if ((int) ($d['product_id'] ?? 0) > 0) $vals['x_studio_san_pham_danh_muc'] = (int) $d['product_id'];
    $serial = clean_text($d['so_imei_serial'] ?? '', 120);
    if ($serial !== '') $vals['x_studio_so_imei_serial'] = $serial;
    $id = created_id(odoo_call('helpdesk.ticket', 'create', ['vals_list' => [$vals]]));
    $rows = result_rows(odoo_call('helpdesk.ticket', 'search_read', [
        'domain' => [['id', '=', $id]],
        'fields' => ['ticket_ref'],
        'limit' => 1
    ]));
    $ref = (string) ($rows[0]['ticket_ref'] ?? str_pad((string) $id, 5, '0', STR_PAD_LEFT));
    respond(true, 'TICKET_CREATED', 'Đã tạo phiếu bảo hành #' . $ref . '! SLA 72h tạm dừng tới khi quầy nhận máy.', [
        'ticket_id' => $id,
        'ticket_ref' => $ref,
        'stage_id' => 11,
        'stage_name' => STAGES[11],
        'sla_paused' => true,
        'branch_label' => '[' . $branch . '] ' . ($branch === 'CH01' ? '136 Nguyễn Thái Học' : '21A Hàng Bài')
    ]);
}

function last9(mixed $phone): string {
    $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';
    return substr($digits, -9);
}

function m2o_id(mixed $value): int {
    return is_array($value) ? (int) ($value[0] ?? 0) : (int) $value;
}

function check_ticket(array $d): never {
    $phone = normalize_phone($d['phone'] ?? '');
    if (!valid_phone($phone)) {
        fail('Vui lòng nhập số điện thoại hợp lệ.');
    }

    $closedStage = 6;
    $repairs = [];

    if (MOCK_MODE) {
        $rows = $phone === '0909112233' ? [
            [
                'ticket_ref' => '00042',
                'name' => 'Máy sạc pin không vào điện',
                'x_studio_ten_thiet_bi_ghi_nhan' => 'Điện thoại mẫu',
                'x_studio_cua_hang_tiep_nhan' => 'CH02',
                'stage_id' => 12,
                'write_date' => date('Y-m-d H:i:s'),
                'repair_ids' => [1],
            ],
            ['x_studio_ten_thiet_bi_ghi_nhan' => 'Thiết bị mẫu đã đóng', 'stage_id' => $closedStage],
        ] : [];
        $repairs = [['id' => 1, 'name' => 'WH/RO/00003', 'state' => 'confirmed', 'under_warranty' => true]];
    } else {
        $internationalPhone = '84' . substr($phone, 1);
        $phoneVariants = [$phone, '+' . $internationalPhone, $internationalPhone];

        $rows = result_rows(odoo_call('helpdesk.ticket', 'search_read', [
            'domain' => [
                '&', '&',
                ['team_id', '=', 2],
                ['stage_id', '!=', $closedStage],
                '|', '|', '|',
                ['partner_phone', 'in', $phoneVariants],
                ['x_studio_sdt_tra_cuu', 'in', $phoneVariants],
                ['partner_id.phone', 'in', $phoneVariants],
                ['partner_id.phone_sanitized', 'in', $phoneVariants],
            ],
            'fields' => [
                'ticket_ref', 'name', 'x_studio_ten_thiet_bi_ghi_nhan',
                'x_studio_cua_hang_tiep_nhan', 'stage_id', 'write_date', 'repair_ids',
            ],
            'order' => 'write_date desc, id desc',
        ]));
    }

    $rows = array_filter($rows, fn(array $row) => m2o_id($row['stage_id'] ?? 0) !== $closedStage);
    if (!MOCK_MODE) {
        $repairIds = [];
        foreach ($rows as $row) {
            foreach ($row['repair_ids'] ?? [] as $id) {
                $repairIds[(int) $id] = (int) $id;
            }
        }
        if ($repairIds) {
            $repairs = result_rows(odoo_call('repair.order', 'search_read', [
                'domain' => [['id', 'in', array_values($repairIds)]],
                'fields' => ['id', 'name', 'state', 'under_warranty'],
            ]));
        }
    }

    $repairLabels = [
        'draft' => 'Mới tạo lệnh kỹ thuật',
        'confirmed' => 'Đang sửa chữa',
        'done' => 'Hoàn tất sửa chữa',
        'cancel' => 'Đã hủy lệnh',
    ];
    $repairsById = [];
    foreach ($repairs as $repair) {
        $repairsById[(int) $repair['id']] = [
            'name' => (string) $repair['name'],
            'state_label' => $repairLabels[$repair['state']] ?? 'Không xác định',
            'under_warranty' => (bool) ($repair['under_warranty'] ?? false),
        ];
    }

    $tickets = [];
    foreach ($rows as $row) {
        $stage = m2o_id($row['stage_id'] ?? 0);
        $ticketRepairs = [];
        foreach ($row['repair_ids'] ?? [] as $id) {
            if (isset($repairsById[(int) $id])) {
                $ticketRepairs[] = $repairsById[(int) $id];
            }
        }
        $branch = (string) ($row['x_studio_cua_hang_tiep_nhan'] ?? '');
        $branchLabel = match (true) {
            str_contains($branch, 'CH02') => '[CH02] 21A Hàng Bài',
            str_contains($branch, 'CH01') => '[CH01] 136 Nguyễn Thái Học',
            default => 'Chưa rõ chi nhánh',
        };

        $tickets[] = [
            'ticket_ref' => ltrim(trim((string) ($row['ticket_ref'] ?? '')), '#'),
            'subject' => trim((string) ($row['name'] ?? '')),
            'device_name' => trim((string) ($row['x_studio_ten_thiet_bi_ghi_nhan'] ?? '')),
            'stage_id' => $stage,
            'stage_name' => STAGES[$stage] ?? 'Đang cập nhật',
            'sla_paused' => $stage === 11,
            'branch_label' => $branchLabel,
            'updated_at' => (string) ($row['write_date'] ?? ''),
            'repairs' => $ticketRepairs,
        ];
    }

    $message = $tickets
        ? 'Tìm thấy ' . count($tickets) . ' phiếu bảo hành chưa đóng.'
        : 'Không tìm thấy phiếu bảo hành chưa đóng cho số điện thoại này.';

    respond(true, 'TICKETS_LOADED', $message, ['tickets' => $tickets]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(false, 'METHOD_NOT_ALLOWED', 'Chỉ chấp nhận yêu cầu POST.', null, 405);
$body = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($body)) respond(false, 'INVALID_JSON', 'Dữ liệu JSON không hợp lệ.', null, 400);
$action = (string) ($body['action'] ?? '');
if (!in_array($action, ACTIONS, true)) respond(false, 'INVALID_ACTION', 'Hành động không được phép.', null, 400);
$data = is_array($body['data'] ?? null) ? $body['data'] : [];
try {
    match ($action) {
        'get_products' => respond(true, 'PRODUCTS_LOADED', 'Đã tải danh mục sản phẩm.', products()),
        'create_lead' => create_lead($data),
        'create_order' => create_order($data),
        'create_ticket' => create_ticket($data),
        'check_ticket' => check_ticket($data)
    };
} catch (Throwable $e) {
    error_log($e->getMessage());
    respond(false, 'SERVER_ERROR', 'Hệ thống đang bận hoặc chưa kết nối được Odoo. Vui lòng thử lại sau.');
}
