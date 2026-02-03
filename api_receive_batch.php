<?php
include 'db_connect.php';

$json_data = file_get_contents('php://input');
$items = json_decode($json_data, true);

if (empty($items)) {
    echo json_encode(['status' => 'error', 'msg' => 'ไม่พบข้อมูล']);
    exit;
}

$success_count = 0;
$errors = [];

foreach ($items as $item) {
    $barcode = $item['barcode'];
    $sn = $item['sn'];
    $operator = $item['operator']; // <--- รับชื่อคนทำรายการ

    // 1. เช็คซ้ำ
    $check = $conn->query("SELECT * FROM product_serials WHERE serial_number = '$sn'");
    if ($check->num_rows > 0) {
        $errors[] = "$sn มีแล้ว";
        continue;
    }

    // 2. บันทึก S/N
    $insert = $conn->query("INSERT INTO product_serials (product_barcode, serial_number, status) VALUES ('$barcode', '$sn', 'available')");

    if ($insert) {
        // 3. เพิ่มสต็อก
        $conn->query("UPDATE products SET quantity = quantity + 1 WHERE barcode = '$barcode'");
        
        // 4. บันทึกประวัติ (พร้อมชื่อผู้ทำรายการ)
        $sql_hist = "INSERT INTO product_history (serial_number, action_type, note, operator) 
                     VALUES ('$sn', 'import', 'รับสินค้าเข้าใหม่', '$operator')";
        $conn->query($sql_hist);
        
        $success_count++;
    } else {
        $errors[] = "Error $sn: " . $conn->error;
    }
}

if (count($errors) == 0) {
    echo json_encode(['status' => 'success', 'msg' => "บันทึกสำเร็จ $success_count รายการ"]);
} else {
    echo json_encode(['status' => 'partial_error', 'msg' => "สำเร็จ $success_count, พลาด " . count($errors), 'errors' => $errors]);
}
?>