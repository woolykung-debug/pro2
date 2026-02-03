<?php
include 'db_connect.php';

// รับข้อมูล JSON
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (empty($data['items'])) {
    echo json_encode(['status' => 'error', 'msg' => 'ไม่พบรายการที่เลือก']);
    exit;
}

$operator = $data['operator'];
$note = $data['note'];
$items = $data['items']; // รายการ S/N ที่ส่งมาเป็น Array

$success_count = 0;
$errors = [];

foreach ($items as $sn) {
    // 1. ตรวจสอบข้อมูล
    $check = $conn->query("SELECT * FROM product_serials WHERE serial_number = '$sn'");
    if($check->num_rows == 0) {
        $errors[] = "$sn: ไม่พบ S/N";
        continue;
    }
    $item = $check->fetch_assoc();

    // เช็คว่าสถานะต้องไม่ว่าง (ถ้าว่างแปลว่าคืนไปแล้ว หรือยังไม่ได้เบิก)
    if($item['status'] == 'available') {
        $errors[] = "$sn: สถานะว่างอยู่แล้ว (อาจคืนซ้ำ)";
        continue;
    }
    
    $project_id = $item['project_id'];
    $barcode = $item['product_barcode'];

    // 2. อัปเดตตาราง Serial (คืนของ)
    $sql = "UPDATE product_serials SET project_id = NULL, status = 'available' WHERE serial_number = '$sn'";
    
    if($conn->query($sql)) {
        // 3. คืนยอดสต็อก (+1)
        $conn->query("UPDATE products SET quantity = quantity + 1 WHERE barcode = '$barcode'");
        
        // 4. บันทึกประวัติ
        $stmt = $conn->prepare("INSERT INTO product_history (serial_number, project_id, action_type, note, operator) VALUES (?, ?, 'return', ?, ?)");
        $stmt->bind_param("siss", $sn, $project_id, $note, $operator);
        $stmt->execute();
        
        $success_count++;
    } else {
        $errors[] = "$sn: เกิดข้อผิดพลาดในฐานข้อมูล";
    }
}

// สรุปผล
if (count($errors) == 0) {
    echo json_encode(['status' => 'success', 'msg' => "คืนสำเร็จครบ $success_count รายการ"]);
} else {
    echo json_encode([
        'status' => 'partial_error', 
        'msg' => "สำเร็จ $success_count รายการ, มีปัญหา " . count($errors), 
        'errors' => $errors
    ]);
}
?>