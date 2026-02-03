<?php include 'db_connect.php'; 

// รับค่าจากตัวกรอง (ถ้ามี)
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ประวัติการทำรายการ - Stock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <style>
        :root { --primary: #0f172a; --accent: #3b82f6; --bg: #f1f5f9; }
        body { font-family: 'Sarabun', sans-serif; background-color: var(--bg); color: #334155; }
        .sidebar { background: var(--primary); min-height: 100vh; width: 260px; position: fixed; top: 0; left: 0; padding-top: 20px; z-index: 1000; }
        .nav-link { color: #94a3b8; padding: 12px 25px; margin: 4px 16px; border-radius: 8px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.1); color: #fff; }
        .nav-link.active { background: var(--accent); }
        .main-content { margin-left: 260px; padding: 30px; }
        .card-custom { border: none; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); background: #fff; overflow: hidden; }
        .btn-edit-note { cursor: pointer; color: #fbbf24; transition: 0.2s; }
        .btn-edit-note:hover { color: #d97706; }
        
        /* สไตล์สำหรับ Select ในหัวตาราง */
        .header-select {
            background-color: transparent;
            border: none;
            font-weight: bold; /* ให้ตัวหนาเหมือนหัวข้ออื่น */
            text-align: center;
            cursor: pointer;
            color: var(--bs-table-color); /* สีเดียวกับตัวหนังสือหัวตาราง */
            width: 100%;
            padding: 0;
        }
        .header-select:focus { outline: none; box-shadow: none; }
        .header-select option { color: #000; font-weight: normal; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold text-secondary m-0"><i class="fas fa-history me-2"></i>ประวัติการรับเข้า / เบิกออก (Log)</h4>
    </div>

    <div class="card card-custom">
        <div class="card-body">
            <table id="historyTable" class="table table-hover w-100 align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="15%">วัน/เวลา</th>
                        
                        <th width="12%" class="text-center p-0 align-middle">
                            <select class="form-select form-select-sm header-select" onchange="location.href='?type='+this.value">
                                <option value="" <?php echo $filter_type == '' ? 'selected' : ''; ?>>▼ ทุกประเภท</option>
                                <option value="import" <?php echo $filter_type == 'import' ? 'selected' : ''; ?>>🔵 รับเข้า</option>
                                <option value="export" <?php echo $filter_type == 'export' ? 'selected' : ''; ?>>🔴 เบิกออก</option>
                                <option value="return" <?php echo $filter_type == 'return' ? 'selected' : ''; ?>>🟡 รับคืน</option>
                            </select>
                        </th>

                        <th width="15%">ผู้ทำรายการ</th>
                        <th width="15%">S/N</th>
                        <th width="20%">สินค้า / โครงการ</th>
                        <th>หมายเหตุ (แก้ไขได้)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sql = "SELECT h.*, p.project_name, 
                                   pr.name as product_name 
                            FROM product_history h 
                            LEFT JOIN projects p ON h.project_id = p.id 
                            LEFT JOIN product_serials ps ON h.serial_number = ps.serial_number
                            LEFT JOIN products pr ON ps.product_barcode = pr.barcode";
                    
                    if($filter_type != '') {
                        $sql .= " WHERE h.action_type = '$filter_type'";
                    }

                    $sql .= " ORDER BY h.id DESC"; 
                    
                    $result = $conn->query($sql);
                    
                    while($row = $result->fetch_assoc()): 
                        $badge = '';
                        if($row['action_type'] == 'import') $badge = '<span class="badge bg-success rounded-pill px-3">รับเข้า</span>';
                        elseif($row['action_type'] == 'export') $badge = '<span class="badge bg-danger rounded-pill px-3">เบิกออก</span>';
                        elseif($row['action_type'] == 'return') $badge = '<span class="badge bg-warning text-dark rounded-pill px-3">รับคืน</span>';
                        
                        $pro_name = $row['product_name'] ? $row['product_name'] : '<span class="text-muted small">-</span>';
                        $location = $row['project_name'] ? '<i class="fas fa-folder text-warning"></i> '.$row['project_name'] : $pro_name;
                        $operator = $row['operator'] ? '<i class="fas fa-user-circle text-secondary me-1"></i> '.$row['operator'] : '-';
                    ?>
                    <tr>
                        <td class="small"><?php echo date('d/m/Y H:i', strtotime($row['action_date'])); ?></td>
                        <td class="text-center"><?php echo $badge; ?></td>
                        <td><?php echo $operator; ?></td>
                        <td class="fw-bold text-primary"><?php echo $row['serial_number']; ?></td>
                        <td><small><?php echo $location; ?></small></td>
                        <td>
                            <div class="d-flex justify-content-between align-items-center">
                                <span id="note_<?php echo $row['id']; ?>" class="text-muted small fst-italic"><?php echo $row['note']; ?></span>
                                <i class="fas fa-pen btn-edit-note ms-2" onclick="editNote(<?php echo $row['id']; ?>, '<?php echo $row['note']; ?>')"></i>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function(){
        $('#historyTable').DataTable({
            "language": { "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json" },
            "order": [[ 0, "desc" ]], 
            "columnDefs": [
                { "orderable": false, "targets": [1, 2, 3, 4, 5] } // ห้ามเรียงลำดับ column 1,2,3,4,5
            ]
        });
    });

    function editNote(id, oldNote) {
        Swal.fire({
            title: 'แก้ไขหมายเหตุ',
            input: 'text',
            inputValue: oldNote,
            showCancelButton: true,
            confirmButtonText: 'บันทึก',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post("api_update_history.php", { id: id, note: result.value }, function(res){
                    if(res.trim() == 'success') {
                        $('#note_' + id).text(result.value);
                        Swal.fire({ icon: 'success', title: 'บันทึกแล้ว', toast: true, position: 'top-end', showConfirmButton: false, timer: 1500 });
                    } else {
                        Swal.fire('Error', res, 'error');
                    }
                });
            }
        });
    }
</script>

</body>
</html>