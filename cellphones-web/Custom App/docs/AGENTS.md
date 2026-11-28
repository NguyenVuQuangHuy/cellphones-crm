# AGENTS.md

## 1. Project Context

Dự án CellphoneS CRM là đồ án sinh viên, mở rộng website WordPress hiện có bằng Custom App IMEI, bảo hành và Gemini Copilot.

Mục tiêu:
- Hoàn thành các chức năng trong PRD.
- Ưu tiên đơn giản, dễ hiểu, dễ kiểm thử và dễ demo.
- Giúp người phát triển học và kiểm soát được mã nguồn.

## 2. Source of Truth

Trước khi thực hiện công việc, đọc:
- `docs/PRD.md`
- `docs/ARCHITECTURE.md`
- `docs/TASKS.md` nếu có.
- Source code hiện tại liên quan đến task.

Sử dụng PRD v4.0 (Student MVP) và Architecture v2.0 (Student MVP), hoặc bản mới hơn đã được người dùng phê duyệt.
Nếu tài liệu và source code mâu thuẫn, báo cáo cho người dùng trước khi quyết định. Không tự sửa tài liệu để hợp thức hóa code.

## 3. Development Workflow

Mỗi task phải tuân theo quy trình:

1. Explore: Khảo sát source code, chưa chỉnh sửa file.
2. Plan: Đề xuất phương án thực hiện, luồng dữ liệu, danh sách file cần sửa và tiêu chí kiểm thử.
3. Approval: Chờ người dùng duyệt kế hoạch trước khi sửa code.
4. Implement: Lập trình theo kế hoạch đã duyệt.
5. Verify: Chạy kiểm thử.
6. Review: Giải thích và báo cáo thay đổi, cung cấp hướng dẫn xem Git Diff.

Không chuyển sang task khác khi task hiện tại chưa được nghiệm thu. Nếu cần mở rộng phạm vi đã duyệt, báo cáo và xin phép trước.

## 4. Coding Principles

- Ưu tiên giải pháp đơn giản nhất đáp ứng yêu cầu.
- Sử dụng tên biến và tên hàm dễ hiểu.
- Mỗi file có trách nhiệm rõ ràng.
- Tái sử dụng các hàm và thành phần hiện có.
- Không tạo lớp trừu tượng không cần thiết.
- Không refactor toàn bộ repository khi chỉ sửa một chức năng.
- Không thêm dependency nếu chưa được chấp thuận.
- Chỉ tạo file khi task thực sự cần đến file đó.

## 5. Architecture Constraints

- Sử dụng WordPress và PHP hiện có; mở rộng plugin trong repository `cellphones-crm`.
- Không triển khai FastAPI hoặc backend độc lập.
- Odoo quản lý chứng từ nghiệp vụ gốc; MariaDB quản lý hồ sơ bảo hành và dữ liệu vận hành WordPress.
- Thời hạn bảo hành mặc định là một năm kể từ ngày mua; theo quy ước demo hiện tại, ngày giao máy thành công được xem là ngày mua.
- Không sao chép toàn bộ dữ liệu CRM vào MariaDB; ưu tiên ID tham chiếu đến chứng từ Odoo.
- Gemini chỉ được truy vấn dữ liệu qua các hàm đọc được cấp phép. Không dùng AI để quyết định kết quả bảo hành.
- Giữ xử lý webhook đơn giản theo Architecture MVP; xác thực nguồn gọi và chống tạo trùng hồ sơ.
- Không tự thêm Queue, Worker, Redis hoặc các cơ chế nâng cao ngoài phạm vi MVP.
- Không làm hỏng chức năng website khách hàng.
- Không tự ý thay đổi kiến trúc hoặc quy tắc nghiệp vụ.

## 6. Safety Rules

- Không đưa API key, mật khẩu hoặc token vào Git, frontend hoặc log.
- Không tự suy đoán tên trường, model hoặc ID trong Odoo; kiểm tra dữ liệu thực tế hoặc báo rõ chưa xác minh.
- Kiểm tra quyền ở phía backend đối với chức năng nhân viên; không chỉ ẩn giao diện.
- Không xóa dữ liệu hoặc file ngoài phạm vi task.
- Không ghi dữ liệu lên hệ thống thật khi chưa được cho phép.
- Không tự ý deploy, commit hoặc push code lên remote.
- Không ghi đè hoặc hoàn tác thay đổi của người dùng và thành viên khác khi chưa được phép.
- Không dùng dữ liệu giả để báo cáo chức năng đã chạy thành công trên dữ liệu thật.

## 7. Testing

Sau khi thực hiện một task:
- Kiểm tra cú pháp của các file đã sửa.
- Kiểm tra chức năng chính và đầu ra theo tiêu chí nghiệm thu.
- Kiểm tra trường hợp dữ liệu không hợp lệ và lỗi API nếu liên quan.
- Kiểm tra ảnh hưởng đến chức năng hiện có.
- Báo cáo chính xác test đã chạy, kết quả pass/fail và test chưa chạy.

Ưu tiên kiểm thử local với dữ liệu thử. Không tuyên bố hoàn thành nếu chưa xác minh các tiêu chí nghiệm thu bắt buộc.

## 8. Completion Report

Sau mỗi task, cung cấp:

1. Công việc đã hoàn thành.
2. Danh sách file đã tạo hoặc chỉnh sửa.
3. Lý do thay đổi.
4. Luồng hoạt động của chức năng.
5. Hướng dẫn kiểm thử thủ công.
6. Kết quả kiểm thử.
7. Những vấn đề còn tồn tại hoặc quyết định cần người dùng xác nhận.

Giải thích bằng tiếng Việt, sử dụng thuật ngữ kỹ thuật tiếng Anh khi cần.
Không tự chuyển sang task tiếp theo. Để người dùng review Git Diff, nghiệm thu và quyết định commit.

## 9. Learning Mode

Người dùng là sinh viên đang học phát triển phần mềm với AI.

Khi giới thiệu một thành phần kỹ thuật mới, giải thích:
- Thành phần đó là gì?
- Tại sao dự án cần nó?
- Nó giao tiếp với thành phần nào?
- Điều gì xảy ra nếu nó gặp lỗi?

Ưu tiên giúp người dùng hiểu mục đích của file, đầu vào, đầu ra và luồng dữ liệu. Không yêu cầu người dùng phải hiểu mọi chi tiết ngay lập tức. Ưu tiên giúp người dùng hiểu và kiểm soát hệ thống thay vì chỉ tạo ra nhiều code.
