<?php
include 'db_connect.php';

$sn = $_POST['sn'];
$note = isset($_POST['note']) ? $_POST['note'] : '';

// เช็คข้อมูล
$check = $conn->query("SELECT * FROM product_serials WHERE serial_number = '$sn'");
if($check->num_rows == 0) { echo json_encode(['status'=>'error', 'msg'=>'ไม่พบ S/N']); exit; }
$item = $check->fetch_assoc();

if($item['status'] == 'available') { echo json_encode(['status'=>'error', 'msg'=>'สินค้านี้คืนไปแล้ว']); exit; }

$project_id = $item['project_id']; // เก็บไว้ก่อนลบ
$safe_pid = empty($project_id) ? "NULL" : "'$project_id'";
$safe_note = $conn->real_escape_string($note);

// คืนของ
$sql = "UPDATE product_serials SET project_id = NULL, status = 'available' WHERE serial_number = '$sn'";

if($conn->query($sql)) {
    // คืนสต็อก
    $barcode = $item['product_barcode'];
    $conn->query("UPDATE products SET quantity = quantity + 1 WHERE barcode = '$barcode'");
    
    // [สำคัญ!] บันทึกประวัติ (รับคืน)
    $conn->query("INSERT INTO product_history (serial_number, project_id, action_type, note) 
                  VALUES ('$sn', $safe_pid, 'return', '$safe_note')");
    
    echo json_encode(['status'=>'success']);
} else {
    echo json_encode(['status'=>'error', 'msg'=>$conn->error]);
}
?>