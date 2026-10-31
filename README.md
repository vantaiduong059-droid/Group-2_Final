# EduManager - Hệ thống điểm danh & quản lý chuyên cần sinh viên

Ứng dụng web quản lý lớp học phần, điểm danh và theo dõi mức độ tham gia của sinh viên. Hệ thống có 3 vai trò: **Admin**, **Giảng viên** và **Sinh viên**, hỗ trợ điểm danh bằng **QR**, **mã code** hoặc **thủ công**, tự động **cảnh báo** sinh viên vắng nhiều hoặc tham gia thấp, kèm quiz, thảo luận và thống kê.

## Tính năng chính

### Điểm danh
- Giảng viên mở phiên điểm danh cho từng buổi học bằng **QR token**, **mã code** hoặc điểm danh **thủ công**; phiên có thời hạn và tự đóng khi hết hạn.
- Sinh viên điểm danh trong thời gian cho phép, hệ thống ghi nhận trạng thái có mặt / đi muộn / vắng.
- Giảng viên chỉnh sửa kết quả điểm danh, mọi thay đổi được lưu **nhật ký chỉnh sửa**.
- Sinh viên gửi **khiếu nại** điểm danh, giảng viên xử lý; có chức năng **đơn xin nghỉ**.

### Cảnh báo học tập
- Tự động cảnh báo khi sinh viên **vắng vượt ngưỡng** hoặc chỉ số tham gia (CPI) **thấp hơn ngưỡng**.
- Ngưỡng cấu hình riêng cho từng học phần, nếu không có thì dùng mặc định của hệ thống.
- Cảnh báo được gửi đến sinh viên, giảng viên và admin; có cơ chế chống gửi trùng khi cảnh báo cũ chưa đọc.

### Điểm tham gia (CPI)
- Quy tắc điểm cấu hình theo học phần: điểm cho có mặt, đi muộn, vắng, tương tác, và trọng số giữa chuyên cần với quiz.
- Quiz trực tuyến, thảo luận trên lớp (có phản hồi), ghi nhận tương tác của sinh viên.

### Quản trị và thống kê
- Admin quản lý học phần, lịch học, giảng viên, sinh viên, buổi học, cảnh báo và thông báo.
- Kiểm tra xung đột lịch học khi tạo buổi học.
- Dashboard biểu đồ (Chart.js) cho từng vai trò, hệ thống thông báo trong ứng dụng.
- Hỗ trợ cài đặt dạng PWA (có manifest và service worker).

## Công nghệ

- **Backend:** PHP thuần theo mô hình MVC tự xây dựng (Router, Controller, Model, Repository), PDO
- **Cơ sở dữ liệu:** MySQL, 20 bảng, khóa ngoại, dữ liệu mẫu và script `migrate.php`
- **Frontend:** HTML, CSS, JavaScript (fetch API), Bootstrap 5, Chart.js
- **API:** hơn 90 endpoint REST trả về JSON
- **Mẫu thiết kế:** Strategy (các hình thức điểm danh QR / Code / Manual), Observer (kích hoạt cảnh báo sau khi điểm danh), Repository, Singleton (kết nối CSDL)
- **Công cụ:** Git, GitHub

## Cấu trúc thư mục

```
app/
  Controllers/Web, Api   Controller giao diện và API
  Models, Repositories   Truy cập dữ liệu
  Helpers                AlertEngine, thống kê chuyên cần, trạng thái phiên điểm danh
  Patterns/              Strategy và Observer
  Views/                 Giao diện admin, teacher, student
config/                  Cấu hình ứng dụng và kết nối CSDL
core/                    Router, Controller, Model nền
database/database.sql    Cấu trúc và dữ liệu mẫu
public/                  Điểm vào, CSS, JS, PWA, trang chạy kiểm thử
migrate.php              Cập nhật cấu trúc CSDL
Ke_hoach_He_thong_Diem_danh_Sinh_vien.pdf   Kế hoạch xây dựng hệ thống
```

## Cài đặt và chạy

1. Cài PHP 8+ và MySQL (XAMPP, Laragon hoặc tương tự), bật Apache với `mod_rewrite`.
2. Đặt dự án vào thư mục web (ví dụ `htdocs`).
3. Tạo CSDL `attendance_system` và nhập file `database/database.sql`.
4. Chỉnh `config/config.php`: thông tin kết nối MySQL (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`) và `BASE_URL` trỏ đến thư mục `public`.
5. Nếu cần cập nhật cấu trúc mới nhất, chạy `migrate.php`.
6. Mở trình duyệt tại `BASE_URL` và đăng nhập theo vai trò đã chọn.

## Kiểm thử

- `public/test_runner.php` chạy kiểm thử tự động cho các bảng CSDL, endpoint và logic quan trọng.
- File `testdata_implementation...` mô tả các trường hợp kiểm thử về sinh viên (đi học đều, đi muộn, vắng nhiều...) để kiểm tra logic điểm danh và cảnh báo.

## Ảnh chụp màn hình

[Thêm 3-4 ảnh: trang giảng viên mở điểm danh, trang sinh viên điểm danh, trang cảnh báo, dashboard]

## Đóng góp

- **Tài Dương Văn:** thiết kế và xây dựng toàn bộ giao diện, logic điểm danh và cảnh báo điểm danh, cơ sở dữ liệu.
- **Hà Đức Mạnh, Dương Trung Nguyên:** chia và đăng tải phần giảng viên và sinh viên lên repo.
