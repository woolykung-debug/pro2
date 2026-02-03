<?php
include 'db_connect.php';

// รับข้อมูล JSON ที่ส่งมา
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['status' => 'error', 'msg' => 'ไม่พบข้อมูลส่งมา']);
    exit;
}

$project_id = $data['project_id'];
$items = $data['items']; // รายการสินค้า [{sn, operator}, ...]

$success_count = 0;
$errors = [];

foreach ($items as $item) {
    $sn = $conn->real_escape_string($item['sn']);
    $operator = $conn->real_escape_string($item['operator']);

    // 1. ตรวจสอบสินค้า
    $check = $conn->query("SELECT * FROM product_serials WHERE serial_number = '$sn'");
    if ($check->num_rows == 0) {
        $errors[] = "$sn: ไม่พบ S/N นี้ในระบบ";
        continue;
    }
    
    $row = $check->fetch_assoc();
    if ($row['status'] != 'available') {
        $errors[] = "$sn: ไม่สามารถเบิกได้ (สถานะ: {$row['status']})";
        continue;
    }

    // 2. ทำรายการเบิก (อัปเดตสถานะ + ผูกโปรเจกต์)
    $sql = "UPDATE product_serials SET project_id = '$project_id', status = 'sold', date_added = NOW() WHERE serial_number = '$sn'";
    
    if ($conn->query($sql)) {
        // ตัดสต็อกสินค้าหลัก
        $p_barcode = $row['product_barcode'];
        $conn->query("UPDATE products SET quantity = quantity - 1 WHERE barcode = '$p_barcode'");

        // บันทึกประวัติ
        $stmt = $conn->prepare("INSERT INTO product_history (serial_number, project_id, action_type, operator) VALUES (?, ?, 'export', ?)");
        $stmt->bind_param("sis", $sn, $project_id, $operator);
        $stmt->execute();

        $success_count++;
    } else {
        $errors[] = "$sn: Database Error";
    }
}

// สรุปผลลัพธ์
if (count($errors) == 0) {
    echo json_encode(['status' => 'success', 'msg' => "เบิกสำเร็จครบ $success_count รายการ"]);
} else {
    echo json_encode([
        'status' => 'partial_error', 
        'msg' => "สำเร็จ $success_count รายการ, มีข้อผิดพลาด " . count($errors), 
        'errors' => $errors
    ]);
}
?>