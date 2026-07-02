<div align="center">
  <img src="logo.png" alt="ITC Logo" width="150"/>
  <h1>Hệ Thống Quản Lý Điểm Rèn Luyện (ITC)</h1>
  <p><i>Một giải pháp toàn diện giúp số hóa, tự động hóa và quản lý điểm rèn luyện sinh viên chuyên nghiệp dành riêng cho Trường Cao Đẳng Công Nghệ Thông Tin TP.HCM (ITC).</i></p>
</div>

---

## 🌟 Giới Thiệu Dự Án

Hệ thống Quản lý Điểm Rèn Luyện được phát triển nhằm mục đích thay thế quy trình chấm điểm rèn luyện thủ công trên giấy, giúp tối ưu hóa thời gian và công sức cho cả Sinh viên, Cố vấn học tập (CVHT), Khoa và Ban giám hiệu nhà trường. 

Với giao diện được thiết kế theo phong cách **Glassmorphism** hiện đại, thân thiện với người dùng trên mọi thiết bị (Responsive Mobile-first), dự án mang đến trải nghiệm mượt mà, trực quan và an toàn.

## 🚀 Các Tính Năng Nổi Bật

### 👨‍🎓 Dành cho Sinh viên
- **Đánh giá tự động:** Sinh viên tự đánh giá điểm rèn luyện, đính kèm hình ảnh minh chứng trực quan.
- **Tra cứu công khai:** Hệ thống tra cứu điểm rèn luyện nhanh chóng qua MSSV không cần đăng nhập.
- **Đăng ký Sự kiện (QR Code):** Tra cứu và đăng ký các sự kiện, hội thảo của trường để tích lũy điểm tự động.
- **Khiếu nại trực tuyến:** Gửi khiếu nại về điểm số trực tiếp trên hệ thống và theo dõi tiến độ xử lý.

### 👩‍🏫 Dành cho Cố vấn học tập (CVHT) & Khoa
- **Duyệt điểm nhanh chóng:** Quản lý danh sách lớp, xem minh chứng và duyệt điểm với thao tác tối giản.
- **Import dữ liệu thông minh:** Tự động đọc file Excel (Điểm danh, Điểm học tập ĐTB) để tự động hóa việc cộng/trừ điểm rèn luyện. Thuật toán xử lý linh hoạt bất kể định dạng file hoặc số lượng cột vượt môn/học lại.
- **Giải quyết khiếu nại:** Xử lý và phản hồi khiếu nại của sinh viên cấp Lớp/Khoa.

### ⚙️ Dành cho Quản trị viên (Admin)
- **Quản lý linh hoạt:** Mở/Đóng các đợt đánh giá, thay đổi cơ cấu bộ Tiêu chí linh hoạt không cần can thiệp code.
- **Thống kê & Báo cáo:** Xuất báo cáo điểm rèn luyện ra file Excel chuẩn format của trường để lưu trữ.
- **Quản lý phân quyền:** Phân quyền người dùng mạnh mẽ.
- **Thông báo Realtime:** Gửi thông báo đến toàn bộ sinh viên hoặc một nhóm cụ thể.

## 🛠 Công Nghệ Sử Dụng

- **Frontend:** HTML5, CSS3 (Vanilla + Glassmorphism UI), JavaScript (ES6+), FontAwesome.
- **Backend:** PHP 8 (Thuần, OOP & Procedural, MySQLi).
- **Database:** MySQL / MariaDB (Relational Database).
- **Thư viện bên thứ 3:** SimpleXLSX (Đọc file Excel), thư viện tạo mã QR, thuật toán Hash MD5 (hoặc Password_hash).
- **Kiến trúc:** Mobile-First Design, MVC Pattern (được tinh gọn).

## 📥 Hướng Dẫn Cài Đặt (Môi trường Local)

1. **Yêu cầu:** Máy tính đã cài đặt [XAMPP](https://www.apachefriends.org/index.html) hoặc WAMP (Yêu cầu PHP >= 7.4).
2. **Clone dự án:**
   Tải mã nguồn về và đặt vào thư mục `htdocs` (nếu dùng XAMPP).
   ```bash
   git clone https://github.com/YourUsername/he-thong-diem-ren-luyen.git
   ```
3. **Cơ sở dữ liệu:**
   - Mở **phpMyAdmin** (`http://localhost/phpmyadmin`).
   - Tạo một Database mới với tên: `itc_diemrenluyen`.
   - Chọn Import và tải lên file `db_deploy.sql` có sẵn trong thư mục gốc của dự án.
4. **Cấu hình:**
   - Mở file `config.php`, kiểm tra và điều chỉnh thông tin database phù hợp:
     ```php
     $db_host = 'localhost';
     $db_user = 'root'; 
     $db_pass = '';     
     $db_name = 'itc_diemrenluyen';
     ```
5. **Chạy ứng dụng:**
   - Truy cập vào trình duyệt: `http://localhost/duandiemrenluyen`

## 👥 Phân Quyền & Tài khoản Demo

Hệ thống có nhiều vai trò (Role). Dưới đây là một số tài khoản Demo mặc định (Mật khẩu chung: `123456`):
- **Admin:** `admin`
- **Khoa (VD: CNTT):** `khoa_cntt`
- **Cố vấn học tập:** `cvht_th1`
- **Sinh viên:** `506240146` (hoặc các MSSV khác có trong DB)

---
*Dự án được xây dựng và phát triển với mục đích phục vụ Đồ án / Quản lý điểm rèn luyện tại ITC. Vui lòng không sử dụng vào mục đích thương mại khi chưa có sự đồng ý.*
