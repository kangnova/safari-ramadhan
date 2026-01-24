<?php
require_once 'koneksi.php';
require_once 'hit_counter.php';

try {
    // Query untuk mengambil jadwal safari yang sudah disetujui
    $query = "SELECT js.*, l.nama_lembaga, l.alamat, l.kecamatan 
              FROM jadwal_safari js
              JOIN lembaga l ON js.lembaga_id = l.id 
              WHERE YEAR(js.tanggal) = YEAR(NOW())
              ORDER BY js.tanggal ASC, js.jam ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $jadwal_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Safari Ramadhan 1446 H/2025</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .content-wrapper {
            margin-top: 80px;
            padding: 20px;
        }
        
        .page-header {
            color: #333;
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 600;
        }
        
        .table-wrapper {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 
                    0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            white-space: nowrap;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .status-pending {
            background-color: #ffeeba;
            color: #856404;
        }
        
        .status-terlaksana {
            background-color: #c3e6cb;
            color: #155724;
        }
        
        .status-batal {
            background-color: #f5c6cb;
            color: #721c24;
        }
        
        .dataTables_wrapper {
            padding: 10px;
        }
        
        .dataTables_filter input {
            border-radius: 4px;
            border: 1px solid #ddd;
            padding: 5px 10px;
        }
        
        .dataTables_length select {
            border-radius: 4px;
            border: 1px solid #ddd;
            padding: 5px 10px;
        }
        
        @media (max-width: 768px) {
            .content-wrapper {
                margin-top: 60px;
                padding: 10px;
            }
            
            .table-wrapper {
                padding: 10px;
                margin: 0 -10px;
                border-radius: 0;
            }
            
            .table thead {
                display: none;
            }
            
            .table, 
            .table tbody, 
            .table tr, 
            .table td {
                display: block;
                width: 100%;
            }
            
            .table tr {
                margin-bottom: 20px;
                border: 1px solid #ddd;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                background: #fff;
                padding: 10px;
            }
            
            .table td {
                display: flex;
                padding: 8px 0;
                border: none;
                position: relative;
            }
            
            .table td:before {
                content: attr(data-label);
                font-weight: bold;
                width: 120px;
                min-width: 120px;
            }
            
            .page-title {
                font-size: 20px;
                text-align: center;
            }
            
            .dataTables_length,
            .dataTables_filter {
                width: 100%;
                margin-bottom: 10px;
                text-align: left;
            }
            
            .dataTables_filter input {
                width: 100%;
                margin-left: 0;
            }
        }
        
        .password-modal {
            background: rgba(0,0,0,0.8);
        }
        
        .password-modal .modal-content {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .password-modal .modal-header {
            border-bottom: none;
            padding: 20px 20px 0;
        }
        
        .password-modal .modal-body {
            padding: 20px;
        }
        
        .password-error {
            color: #dc3545;
            margin-top: 10px;
            display: none;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>



    <!-- Content -->
    <div class="content-wrapper">
        <div class="container">
            <div class="table-wrapper">
                <div class="table-responsive">
                    <table class="table table-hover" id="jadwalTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Hari</th>
                                <th>Tanggal</th>
                                <th>Jam</th>
                                <th>Lembaga</th>
                                <th>Pengisi</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (!empty($jadwal_list)):
                                $no = 1;
                                foreach($jadwal_list as $jadwal): 
                                    $hari = date('l', strtotime($jadwal['tanggal']));
                                    $hari_id = [
                                        'Sunday' => 'Minggu',
                                        'Monday' => 'Senin',
                                        'Tuesday' => 'Selasa',
                                        'Wednesday' => 'Rabu',
                                        'Thursday' => 'Kamis',
                                        'Friday' => 'Jumat',
                                        'Saturday' => 'Sabtu'
                                    ];
                            ?>
                            <tr>
                                <td data-label="No"><?= $no++ ?></td>
                                <td data-label="Hari"><?= $hari_id[$hari] ?></td>
                                <td data-label="Tanggal"><?= date('d/m/Y', strtotime($jadwal['tanggal'])) ?></td>
                                <td data-label="Jam"><?= date('H:i', strtotime($jadwal['jam'])) ?> WIB</td>
                                <td data-label="Lembaga"><?= htmlspecialchars($jadwal['nama_lembaga']) ?></td>
                                <td data-label="Pengisi"><?= htmlspecialchars($jadwal['pengisi']) ?></td>
                                <td data-label="Status">
                                    <span class="status-badge status-<?= $jadwal['status'] ?>">
                                        <?= ucfirst($jadwal['status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php 
                                endforeach;
                            endif; 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize DataTable directly
        initializeDataTable();
        
        function initializeDataTable() {
            $('#jadwalTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
                },
                order: [[2, 'asc']], // Sort by tanggal
                pageLength: 10,
                responsive: true,
                drawCallback: function() {
                    if(window.innerWidth <= 768) {
                        $('.dataTables_length, .dataTables_filter')
                            .addClass('container-fluid');
                    }
                }
            });
        }
        
        // Responsive handler
        $(window).resize(function() {
            if(window.innerWidth <= 768) {
                $('.dataTables_length, .dataTables_filter')
                    .addClass('container-fluid');
            } else {
                $('.dataTables_length, .dataTables_filter')
                    .removeClass('container-fluid');
            }
        });
    });
    </script>
</body>
</html>