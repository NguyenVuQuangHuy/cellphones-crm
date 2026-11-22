# Typography và CSS nền

## Chọn phông chữ

| Nhu cầu | Font stack hoặc cách nạp |
| --- | --- |
| Cảm giác bản địa trên từng hệ điều hành | `system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif`. Trình duyệt dùng font giao diện của hệ điều hành. Trên nền tảng Apple, cách này dùng San Francisco khi hệ thống chọn font đó. |
| Giữ chất chữ đồng nhất giữa macOS, Windows và Android | `"Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif`. Nạp Inter bằng cơ chế font sẵn có của dự án và giữ bộ ký tự Vietnamese. |
| Dự án đã có phông thương hiệu | Giữ phông thương hiệu khi được yêu cầu. Dùng scale, weight và tracking bên dưới để tạo phân cấp kiểu Apple. |
| Giá tiền, KPI, thông số và đoạn mã | Dùng `font-variant-numeric: tabular-nums` cho cột số cần căn hàng, căn phải các giá trị so sánh. Dùng `ui-monospace, monospace` cho mã. |

- Sử dụng `system-ui` để tiếp cận font hệ thống Apple. Tránh đóng gói hoặc hotlink tệp tải xuống của Apple như SF Pro, SF Mono và New York cho web công khai khi chưa có quyền phân phối tương ứng. Dùng Inter khi cần webfont. Giữ thông báo bản quyền và giấy phép OFL khi phân phối tệp Inter.
- Dùng một nguồn tải font. Với Next.js, ưu tiên cơ chế `next/font` mà dự án đang dùng và bảo đảm cấu hình có tập ký tự tiếng Việt. Với Vite hoặc CSS thuần, ưu tiên gói font sẵn có hoặc WOFF2 tự lưu trữ, có `font-display: swap`, kiểu font và dải weight khớp với tệp. Kiểm tra độ phủ glyph của tệp thực tế trước khi khai báo subset. Chỉ preload tệp có ích cho nội dung đầu màn hình.
- Dùng `lang="vi"` cho trang tiếng Việt. Thử chuỗi **“Điều chỉnh trải nghiệm, giữ đúng tiến độ. Tổng cộng 1.250.000 ₫.”** Để dấu và ký tự xuống dòng tự nhiên. Kiểm tra dấu khi thu hẹp màn hình và khi ô nhập hoặc heading có nhiều dòng.
- Đặt chữ thân bài khoảng 16–18 px với line-height khoảng 1.5–1.65. Cho tiêu đề hero line-height khoảng 1.1–1.2, weight 650–700 khi font hỗ trợ và tracking nhẹ. Giữ tracking mặc định cho thân bài. Với font tĩnh, chọn weight có sẵn để tránh mức đậm giả. Dùng heading theo cấu trúc nội dung, áp dụng cỡ chữ bằng class hoặc token.
- Dùng nhãn và chú thích khoảng 13–14 px với tương phản phù hợp. Form tiếng Việt nên dùng chữ đầu câu, để chỗ cho nhãn dài và tránh cắt nội dung bằng chiều cao cố định.

## CSS khởi điểm

Dùng các giá trị này khi dự án chưa có token tương ứng. Điều chỉnh màu theo thương hiệu. Nếu nạp Inter theo cơ chế hiện có của dự án, đặt family thực tế lên đầu `--font-ui`. Không cần nạp webfont cho lựa chọn font hệ thống.

```css
:root {
  --font-ui: system-ui, -apple-system, BlinkMacSystemFont,
    "Segoe UI", Arial, sans-serif;
  --text-body: clamp(1rem, 0.97rem + 0.15vw, 1.125rem);
  --text-display: clamp(2.25rem, 1.55rem + 3.4vw, 4.5rem);
  --text-section: clamp(1.5rem, 1.25rem + 1.25vw, 2.5rem);
  --text-app-title: clamp(1.5rem, 1.35rem + 0.75vw, 2rem);

  --page: #fff;
  --surface: #f5f5f7;
  --text: #1d1d1f;
  --text-muted: #55555d;
  --line-subtle: #dedee3;
  --accent: #005ec9;
  --on-accent: #fff;
  --focus: #005ec9;

  --content-max: 75rem;
  --gutter: clamp(1rem, 4vw, 3rem);
  --section-gap: clamp(3rem, 8vw, 7rem);
  --radius-panel: 1rem;
  --radius-media: 1.5rem;
  --motion-fast: 180ms;
  --motion-ease: cubic-bezier(0.2, 0.7, 0.2, 1);
}

body {
  background-color: var(--page);
  color: var(--text);
  font-family: var(--font-ui);
  font-size: var(--text-body);
  line-height: 1.55;
}

button,
input,
select,
textarea {
  font: inherit;
}

.display-title {
  font-size: var(--text-display);
  font-weight: 700;
  letter-spacing: -0.025em;
  line-height: 1.15;
  text-wrap: balance;
}

:focus-visible {
  outline: 2px solid var(--focus);
  outline-offset: 3px;
}
```

Giới hạn độ dài đoạn văn khoảng 55–70 ký tự mỗi dòng khi có đủ không gian. Dùng lưới responsive và token khoảng cách để căn hero, section và cột nội dung. Tắt transition hoặc animation trang trí trên từng component trong media query `@media (prefers-reduced-motion: reduce)`. Kiểm tra rằng trạng thái cuối vẫn xuất hiện ngay cả khi animation bị tắt.

## Tài liệu đối chiếu

- [Apple Human Interface Guidelines — Typography](https://developer.apple.com/design/human-interface-guidelines/typography)
- [Apple Developer — Managing the fonts for your app](https://developer.apple.com/documentation/technologyoverviews/managing-the-fonts-for-your-app)
- [Apple Developer — Fonts](https://developer.apple.com/fonts/)
- [Inter — Ngôn ngữ và glyph](https://rsms.me/inter/)
- [Inter — SIL Open Font License](https://github.com/rsms/inter/blob/master/LICENSE.txt)
