# CellphoneS CRM Customer Portal

Cổng khách hàng phục vụ đồ án CRM, xây dựng bằng WordPress và plugin PHP riêng. Website minh họa danh mục sản phẩm, tư vấn mua hàng, đặt COD, tiếp nhận bảo hành và tra cứu tiến độ. Backend nghiệp vụ có thể chạy bằng dữ liệu mô phỏng hoặc kết nối Odoo JSON-2.

> Đây là bản mô phỏng học thuật, không phải website chính thức hay kênh bán hàng của CellphoneS. Không dùng để nhận đơn hàng thật nếu chưa được đơn vị sở hữu hệ thống cho phép và chưa hoàn thiện các thông tin, chính sách cần thiết.

## Tính năng

- Danh mục 45 sản phẩm, tìm kiếm, lọc theo nhóm và tồn kho.
- Luồng tư vấn mua hàng, giỏ hàng/đặt COD mô phỏng hoặc gửi vào Odoo khi bật chế độ live.
- Tạo yêu cầu bảo hành và tra cứu trạng thái sửa chữa.
- Giao diện responsive, nội dung tiếng Việt, chạy trong WordPress qua shortcode.
- Có dữ liệu dự phòng để trình diễn khi chưa cấu hình Odoo hoặc Odoo tạm thời không truy cập được.

Danh mục Odoo hiện chưa có ảnh sản phẩm; giao diện sẽ cần bộ ảnh bổ sung nếu muốn hiển thị ảnh thật.

## Công nghệ và kiến trúc

- WordPress trên Apache/PHP 8.3, chạy bằng Docker Compose.
- Plugin `cellphones-web/cellphones-portal.php` đọc phần nội dung của `index.html`, đăng ký shortcode `[cellphones_crm_portal]` và nạp CSS/JavaScript.
- `cellphones-web/api.php` là PHP backend cùng origin. Trình duyệt chỉ gọi các action được cho phép; khóa Odoo ở cấu hình PHP phía máy chủ.
- Khi chạy live, backend gọi Odoo JSON-2 qua HTTPS. MariaDB lưu dữ liệu lõi WordPress; dữ liệu CRM/nghiệp vụ thuộc Odoo.

```text
Browser (HTML/CSS/Vanilla JS)
              │ same-origin JSON
              ▼
WordPress + CellphoneS CRM Portal plugin
              │ PHP cURL + server-side API key
              ▼
Odoo JSON-2 API
```

Đặc tả kiến trúc, từ điển dữ liệu, quy trình và hợp đồng API nằm trong [`DAC_TA_WEB_PHP_ODOO_CRM.md`](./DAC_TA_WEB_PHP_ODOO_CRM.md). Tệp skill thiết kế giao diện tùy chỉnh nằm trong [`apple-inspired-web-ui/`](./apple-inspired-web-ui/); đây là tài liệu phát triển, không phải dependency của website.

## Chạy local bằng Docker

Yêu cầu: Docker Desktop đang chạy và Docker Compose v2.

Trong PowerShell, từ thư mục gốc repo:

```powershell
Copy-Item .env.example .env
Copy-Item .\cellphones-web\config.example.php .\cellphones-web\config.php
docker compose up -d
```

Trước lần chạy đầu, mở `.env` và thay hai giá trị mật khẩu mẫu bằng mật khẩu riêng. Mở <http://127.0.0.1:8080> để hoàn tất cài đặt WordPress, rồi vào **Plugins → Installed Plugins** và kích hoạt **CellphoneS CRM Portal**. Plugin sẽ tạo trang chủ cổng khách hàng. Thông tin đăng nhập quản trị do bạn tự tạo khi cài đặt.

Lệnh vận hành:

```powershell
docker compose ps
docker compose logs -f wordpress
docker compose stop
docker compose start
docker compose down
```

`docker compose down` giữ lại các named volume. Chỉ dùng `docker compose down -v` khi muốn xóa hoàn toàn dữ liệu WordPress và MariaDB local.

Nếu cổng `8080` đang được sử dụng, đổi vế trái của mục `ports` trong `docker-compose.yml` (ví dụ `127.0.0.1:8081:80`) rồi mở cổng tương ứng. Website chỉ bind vào loopback mặc định, không công khai ra mạng LAN.

### Lưu ý với volume WordPress/MariaDB đã có

`.env` không được đưa lên GitHub. Nếu bạn đã khởi tạo MariaDB trước đó, giữ các giá trị database trong `.env` khớp với volume hiện tại; thay các giá trị này không tự đổi mật khẩu bên trong database đã tồn tại. `.env.example` chỉ là mẫu cho lần cài đặt mới.

## Cấu hình Odoo

Mặc định `config.example.php` bật `MOCK_MODE`. Để kết nối thật:

1. Tạo bản local từ `cellphones-web/config.example.php` thành `cellphones-web/config.php`.
2. Trong `config.php`, đặt `MOCK_MODE` thành `false`, điền `ODOO_USER_API_KEY` cùng URL và database phù hợp.
3. Tạo khóa Odoo phù hợp cho API JSON-2/RPC. Không dùng khóa MCP-only. Không dán khóa vào README, JavaScript, HTML hoặc GitHub.
4. Khởi động lại WordPress: `docker compose restart wordpress`.

Odoo JSON-2 yêu cầu bearer API key; quyền truy cập còn tùy tài khoản và gói Odoo. Xem [tài liệu API JSON-2](https://www.odoo.com/documentation/20.0/vi/developer/reference/external_api.html). Kiểm tra nhãn nguồn dữ liệu trên trang: `Dữ liệu Odoo`/`Dữ liệu cache Odoo` cho kết quả đã lấy từ Odoo; fallback cho biết backend đang dùng bộ dữ liệu dự phòng.

> Chế độ live có thể tạo hồ sơ CRM, đơn bán hàng và phiếu bảo hành thật. Chỉ gửi biểu mẫu thử bằng dữ liệu được phép dùng trong môi trường của nhóm.

## Cấu hình local cần giữ ngoài Git

- `cellphones-web/config.php`: khóa Odoo/Gemini và cờ chế độ chạy.
- `.env`: thông tin kết nối MariaDB/WordPress.
- `cellphones-web/cache/*.json`: cache sản phẩm, có thể tái tạo.

Các tệp trên đã được loại trừ trong `.gitignore`. Chỉ commit `config.example.php` và `.env.example`, không commit bản cấu hình thật. Nếu một khóa từng được chia sẻ hoặc đẩy lên GitHub, hãy thu hồi khóa đó ở dịch vụ tương ứng rồi tạo khóa mới.

## Cấu trúc repo

```text
.
├── apple-inspired-web-ui/       # Skill thiết kế giao diện
├── cellphones-web/               # WordPress plugin và PHP backend
│   ├── api.php
│   ├── assets/
│   │   ├── css/style.css
│   │   ├── js/app.js
│   │   ├── cellphones-mark.svg
│   │   └── tech-hero.webp
│   ├── cache/                     # Cache local, không commit
│   ├── data/products_fallback.json
│   ├── config.example.php
│   ├── index.html
│   └── start.ps1                  # Máy có PHP CLI; cổng mặc định 8000
├── DAC_TA_WEB_PHP_ODOO_CRM.md     # Đặc tả nghiệp vụ và API
├── docker-compose.yml
├── .env.example
└── README.md
```

## Trước khi đưa repo lên public GitHub

Đặc tả đồ án chứa một số thông tin định danh và dữ liệu liên hệ dùng làm ví dụ. Hãy rà soát hoặc ẩn danh các thông tin đó nếu repo công khai. Xác nhận `config.php`, `.env`, cache và mọi khóa API không xuất hiện trong danh sách file chuẩn bị commit.
