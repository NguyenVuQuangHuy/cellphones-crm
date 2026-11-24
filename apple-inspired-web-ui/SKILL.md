---
name: apple-inspired-web-ui
description: >-
  Build, redesign, or review an original web UI inspired by Apple design, Apple.com
  product storytelling, or macOS interface conventions. Use for Apple-style landing
  pages, portfolios, product pages, dashboards, and web apps, including requests
  for Apple-style typography, San Francisco, SF Pro, system-ui, Inter, or Vietnamese
  fonts. Apply the project's existing frontend stack and brand.
---

# Giao diện web lấy cảm hứng từ Apple

## Bắt đầu từ tác vụ của người dùng

1. Đọc yêu cầu, cấu trúc dự án, các màn hình hiện có, hệ thống component và CSS toàn cục. Xác định hành động chính, nội dung cần ưu tiên và các trạng thái cần xử lý.
2. Chọn bố cục theo loại màn hình. Giữ thương hiệu, nội dung, route, API và hành vi hiện tại trong phạm vi yêu cầu. Với Next.js, React/Vite, Tailwind hay HTML/CSS thuần, dùng kiến trúc và công cụ đã có trong dự án.
3. Dùng hệ thống chữ, màu và khoảng cách thống nhất. Đọc [typography-and-tokens.md](references/typography-and-tokens.md) để chọn phông chữ và lấy CSS khởi điểm khi dự án thiếu design token.
4. Triển khai component và tương tác thật qua các điểm vào sẵn có. Kiểm tra bằng trình duyệt và chạy lệnh build, lint hoặc test phù hợp với dự án. Với yêu cầu review, ưu tiên chỉ ra lỗi quan sát được và vị trí sửa.

## Bố cục theo loại giao diện

- **Landing page, portfolio, trang sản phẩm.** Dùng headline ngắn nêu giá trị, một CTA chính, một CTA phụ khi cần và một ảnh hoặc đoạn minh họa có mục đích. Dùng ảnh lớn và khoảng trắng để tạo nhịp giữa các phần. Chỉ dùng bố cục bento khi có nhiều nhóm nội dung độc lập cần đặt cạnh nhau. Cho người dùng thấy nội dung và hành động quan trọng ngay khi tải trang.
- **Dashboard, CRM, web app.** Đặt tác vụ chính và dữ liệu cần dùng ở đầu màn hình. Dùng thanh điều hướng, bộ lọc, danh sách hoặc bảng theo luồng thực tế. Ưu tiên căn cột rõ, mật độ phù hợp và trạng thái tải, trống, lỗi, thành công. Dùng heading nhỏ hơn và khoảng cách gọn hơn trang giới thiệu.
- **Mọi loại trang.** Tạo bố cục, bảng màu, nội dung và hình ảnh phù hợp với thương hiệu của dự án. Nếu có trang tham chiếu cụ thể, rút ra nguyên tắc về phân cấp chữ, khoảng trắng và nhịp thị giác để làm một thiết kế riêng. Dùng tài nguyên thuộc dự án hoặc có quyền sử dụng.

## Phân cấp thị giác và tương tác

- Dùng nền trung tính, nét phân cách mảnh và một màu nhấn cho liên kết hoặc thao tác chính. Lấy light mode làm mặc định cho dự án mới. Với dự án đang chạy, theo theme và yêu cầu hiện có. Chỉ thêm theme switcher khi được yêu cầu.
- Gom nội dung liên quan bằng khoảng cách và căn lề. Dùng card khi nội dung cần ranh giới hoặc có hành động riêng. Mỗi màn hình nên có một điểm nhấn thị giác rõ, với bo góc và bóng đổ dùng có chủ đích.
- Dùng bề mặt bán trong suốt có chọn lọc cho thanh điều hướng nổi hoặc lớp phủ. Cung cấp nền đặc làm fallback, kiểm tra tương phản trên nội dung phía sau và tránh blur trên vùng cuộn lớn.
- Dùng motion ngắn để phản hồi thao tác hoặc giải thích thay đổi trạng thái. Ưu tiên opacity và transform, bảo đảm nội dung đọc được ngay khi tải và tắt motion trang trí với `prefers-reduced-motion`. Giữ cuộn trang tự nhiên.
- Dùng HTML có ngữ nghĩa, thứ tự tiêu đề hợp lý, nhãn cho form và nút biểu tượng, focus rõ với `:focus-visible` và vùng chạm đủ lớn. Bảo đảm chức năng dùng được bằng bàn phím và thông tin trạng thái có cả chữ hoặc biểu tượng. Khi dùng dialog, giữ focus trong dialog và trả focus về phần tử mở sau khi đóng.
- Giữ ảnh hero đúng tỷ lệ và có kích thước khai báo. Dùng ảnh responsive, tối ưu ảnh đầu trang và tải chậm ảnh ngoài màn hình. Giữ animation và thư viện bổ sung trong mức cần thiết.

## Kiểm tra trước khi bàn giao

- Xem ở chiều rộng khoảng 360, 768 và 1440 CSS px, hoặc các kích thước tương đương trong dự án. Kiểm tra zoom 200%, nội dung dài và cuộn ngang ngoài ý muốn.
- Kiểm tra font thực tế trên Apple và ít nhất một nền tảng khác khi có sẵn. Thử tiêu đề có dấu, đoạn văn, nút, input và bảng số. Kiểm tra trang còn đọc được khi webfont không tải.
- Dùng bàn phím qua các luồng chính, menu và dialog. Thử CTA, form và các trạng thái tải, trống, lỗi khi màn hình có các trạng thái này.
- Kiểm tra độ tương phản WCAG AA, cả trên nền mờ hoặc ảnh. Kiểm tra reduced motion và console. Báo đúng những gì đã kiểm tra và những nền tảng chưa chạy được.
