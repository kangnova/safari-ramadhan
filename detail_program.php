<?php
require_once 'koneksi.php';
require_once 'hit_counter.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php#program');
    exit();
}

try {
    $query = "SELECT * FROM program WHERE id_program = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$_GET['id']]);
    $program = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$program) {
        header('Location: index.php#program');
        exit();
    }
} catch (PDOException $e) {
    header('Location: index.php#program');
    exit();
}

// Konversi string manfaat menjadi array (asumsikan manfaat dipisahkan dengan baris baru)
$manfaat_array = array_filter(explode("\n", $program['manfaat_kegiatan']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($program['nama_program']) ?> - Safari Ramadhan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .program-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .program-content {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .program-title {
            color: #20B2AA;
            margin-bottom: 1rem;
        }
        
        .program-meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 2rem;
        }
        
        .program-description {
            line-height: 1.8;
            margin-bottom: 2rem;
        }
        
        /* Gaya baru untuk manfaat */
        .manfaat-section {
            margin-bottom: 2rem;
        }
        
        .manfaat-title {
            color: #20B2AA;
            margin-bottom: 2rem;
            text-align: left;
            font-size: 2rem;
        }
        
        .manfaat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .manfaat-item {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 15px;
            position: relative;
            padding-left: 4rem;
        }
        
        .manfaat-number {
            position: absolute;
            left: -10px;
            top: 50%;
            transform: translateY(-50%);
            width: 60px;
            height: 60px;
            background: #20B2AA;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .manfaat-text {
            line-height: 1.6;
        }
        
        .back-button {
            display: inline-block;
            padding: 0.8rem 2rem;
            background: #20B2AA;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            transition: background 0.3s;
        }
        
        .back-button:hover {
            background: #48D1CC;
            color: white;
        }
        
        @media (max-width: 768px) {
            .program-content {
                padding: 1rem;
            }
            
            .program-image {
                max-height: 250px;
            }
            
            .manfaat-grid {
                grid-template-columns: 1fr;
            }
            
            .manfaat-item {
                margin-left: 10px;
            }
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <img src="img/program/<?= htmlspecialchars($program['gambar']) ?>" 
                     alt="<?= htmlspecialchars($program['nama_program']) ?>" 
                     class="program-image mb-4">
                
                <div class="program-content">
                    <h1 class="program-title"><?= htmlspecialchars($program['nama_program']) ?></h1>
                    
                    <div class="program-meta">
                        Terakhir diperbarui: <?= date('d F Y', strtotime($program['tgl_update'])) ?>
                    </div>
                    
                    <div class="program-description">
                        <?= nl2br(htmlspecialchars($program['deskripsi'])) ?>
                    </div>
                    
                    <div class="manfaat-section">
                        <h2 class="manfaat-title">Manfaat Kegiatan</h2>
                        <div class="manfaat-grid">
                            <?php foreach ($manfaat_array as $index => $manfaat): ?>
                                <div class="manfaat-item">
                                    <div class="manfaat-number"><?= $index + 1 ?></div>
                                    <div class="manfaat-text">
                                        <?= htmlspecialchars(trim($manfaat)) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <a href="index.php#program" class="back-button">
                        Kembali ke Program
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>