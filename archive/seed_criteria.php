<?php
require_once 'config.php';

echo "Bắt đầu cập nhật Bộ Tiêu Chí theo mẫu chuẩn ITC...<br>";

// Xóa dữ liệu cũ
$conn->query("DELETE FROM tieu_chi");
$conn->query("DELETE FROM nhom_tieu_chi");

$groups = [
    [
        'ten_nhom' => '1. Đánh giá về ý thức tham gia học tập',
        'diem_toi_da' => 20,
        'thu_tu' => 1,
        'tieu_chi' => [
            ['noi_dung' => 'a. Ý thức thái độ trong học tập (Chuyên cần, đúng giờ, làm bài tập đầy đủ)', 'diem' => 10],
            ['noi_dung' => 'b. Tham gia các câu lạc bộ học thuật, ngoại ngữ, tin học...', 'diem' => 2],
            ['noi_dung' => 'c. Tham gia các cuộc thi học thuật cấp Khoa, Trường (VD: Olympic, Tay nghề)', 'diem' => 3],
            ['noi_dung' => 'd. Đạt điểm TBC học tập trong học kỳ cao (Giỏi/Xuất sắc cộng thêm điểm)', 'diem' => 5],
        ]
    ],
    [
        'ten_nhom' => '2. Đánh giá về ý thức chấp hành nội quy, quy chế',
        'diem_toi_da' => 25,
        'thu_tu' => 2,
        'tieu_chi' => [
            ['noi_dung' => 'a. Chấp hành tốt quy chế đào tạo, quy chế thi và kiểm tra', 'diem' => 10],
            ['noi_dung' => 'b. Chấp hành tốt nội quy nhà trường (Đeo thẻ sinh viên, trang phục đúng quy định)', 'diem' => 10],
            ['noi_dung' => 'c. Chấp hành quy định về đóng học phí, tham gia bảo hiểm y tế đầy đủ', 'diem' => 5],
        ]
    ],
    [
        'ten_nhom' => '3. Đánh giá về ý thức tham gia các hoạt động CT-XH, VH-VN, TT',
        'diem_toi_da' => 20,
        'thu_tu' => 3,
        'tieu_chi' => [
            ['noi_dung' => 'a. Tham gia các hoạt động sinh hoạt lớp, sinh hoạt chính trị, Tuần sinh hoạt công dân', 'diem' => 10],
            ['noi_dung' => 'b. Tham gia các hoạt động Văn hóa, Văn nghệ, Thể dục thể thao cấp Trường/Khoa', 'diem' => 5],
            ['noi_dung' => 'c. Tham gia các hoạt động tình nguyện, chiến dịch Mùa hè xanh, Xuân tình nguyện', 'diem' => 5],
        ]
    ],
    [
        'ten_nhom' => '4. Đánh giá phẩm chất công dân và quan hệ với cộng đồng',
        'diem_toi_da' => 25,
        'thu_tu' => 4,
        'tieu_chi' => [
            ['noi_dung' => 'a. Chấp hành tốt đường lối của Đảng, chính sách pháp luật của Nhà nước', 'diem' => 10],
            ['noi_dung' => 'b. Tham gia các hoạt động giữ gìn an ninh trật tự, an toàn giao thông, bảo vệ môi trường', 'diem' => 10],
            ['noi_dung' => 'c. Có tinh thần chia sẻ, giúp đỡ bạn bè gặp khó khăn, sinh viên khuyết tật', 'diem' => 5],
        ]
    ],
    [
        'ten_nhom' => '5. Đánh giá ý thức và kết quả tham gia công tác cán bộ lớp, đoàn thể',
        'diem_toi_da' => 10,
        'thu_tu' => 5,
        'tieu_chi' => [
            ['noi_dung' => 'a. Là Ban cán sự lớp, BCH Đoàn, Hội sinh viên hoàn thành tốt nhiệm vụ', 'diem' => 10],
        ]
    ]
];

foreach ($groups as $g) {
    $sql = "INSERT INTO nhom_tieu_chi (ten_nhom, diem_toi_da, thu_tu) VALUES ('{$g['ten_nhom']}', {$g['diem_toi_da']}, {$g['thu_tu']})";
    if ($conn->query($sql)) {
        $nhom_id = $conn->insert_id;
        foreach ($g['tieu_chi'] as $tc) {
            $sql_tc = "INSERT INTO tieu_chi (nhom_id, noi_dung, diem_toi_da, diem_mac_dinh) VALUES ($nhom_id, '{$tc['noi_dung']}', {$tc['diem']}, 0)";
            $conn->query($sql_tc);
        }
    }
}

echo "Hoàn tất việc nạp Bộ Tiêu Chí Đánh Giá Mẫu!";
?>
