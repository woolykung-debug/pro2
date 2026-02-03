<?php
include 'db_connect.php';

$sn = $_POST['sn'];
$pid = $_POST['project_id'];

// เช็คความถูกต้อง
$check_item = $conn->query("SELECT status FROM product_serials WHERE serial_number = '$sn'");
if ($check_item->num_rows == 0) { echo json_encode(['status'=>'error', 'msg'=>'ไม่พบ S/N']); exit; }
$item = $check_item->fetch_assoc();

if ($item['status'] != 'available') { echo json_encode(['status'=>'error', 'msg'=>'สินค้านี้ไม่ว่าง']); exit; }

// ย้ายของ
$sql = "UPDATE product_serials SET project_id = $pid, status = 'sold', date_added = NOW() WHERE serial_number = '$sn'";

if($conn->query($sql)) {
    // ตัดสต็อก
    $get_bc = $conn->query("SELECT product_barcode FROM product_serials WHERE serial_number = '$sn'")->fetch_assoc();
    $barcode = $get_bc['product_barcode'];
    $conn->query("UPDATE products SET quantity = quantity - 1 WHERE barcode = '$barcode'");

    // [สำคัญ!] บันทึกประวัติ (เบิกออก)
    $conn->query("INSERT INTO product_history (serial_number, project_id, action_type) VALUES ('$sn', $pid, 'export')");

    echo json_encode(['status'=>'success']);
} else {
    echo json_encode(['status'=>'error', 'msg'=>$conn->error]);
}
?>