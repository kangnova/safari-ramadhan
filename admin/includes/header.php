<?php
// Get current page filename
$current_page = basename($_SERVER['PHP_SELF']);

// Function to set active class
function isActive($page) {
    global $current_page;
    return ($current_page === $page) ? 'active' : '';
}

// Function to set active class for dropdown
function isActiveDropdown($pages) {
    global $current_page;
    return in_array($current_page, $pages) ? 'active' : '';
}

// Struktur menu dengan pengelompokan yang lebih baik
$menuGroups = [
    'main' => [
        [
            'title' => 'Dashboard',
            'icon' => 'house-door',
            'link' => 'index.php'
        ],
        [
            'title' => 'Profile',
            'icon' => 'person-circle',
            'link' => 'profile.php'
        ]
    ],
    'content' => [
        'title' => 'Konten',
        'icon' => 'file-earmark-text',
        'items' => [
            [
                'title' => 'Berita',
                'icon' => 'newspaper',
                'link' => 'berita.php'
            ],
            [
                'title' => 'Program',
                'icon' => 'calendar-event',
                'link' => 'program.php'
            ],
            [
                'title' => 'Gallery',
                'icon' => 'images',
                'link' => 'gallery.php'
            ],
            [
                'title' => 'Slide Utama',
                'icon' => 'images',
                'link' => 'kelola_slide.php'
            ]
        ]
    ],
    'pendaftar' => [
        'title' => 'Pendaftar',
        'icon' => 'people',
        'items' => [
            [
                'title' => 'Pendaftar Safari',
                'icon' => 'person-lines-fill',
                'link' => 'pendaftar.php'
            ],
            [
                'title' => 'Pendaftar Ifthar',
                'icon' => 'person-plus-fill',
                'link' => 'pendaftar_ifthar.php'
            ],
            [
                'title' => 'Duta GNB',
                'icon' => 'star',
                'link' => 'duta_gnb.php'
            ]
        ]
    ],
    'kegiatan' => [
        'title' => 'Kegiatan',
        'icon' => 'calendar-check',
        'items' => [
            [
                'title' => 'Jadwal',
                'icon' => 'calendar-date',
                'link' => 'jadwal.php'
            ],
            [
                'title' => 'Pengisi',
                'icon' => 'person-video3',
                'link' => 'pengisi.php'
            ]
        ]
    ],
    'keuangan' => [
        'title' => 'Keuangan',
        'icon' => 'wallet2',
        'items' => [
            [
                'title' => 'Dashboard Donasi',
                'icon' => 'graph-up',
                'link' => 'donasi.php'
            ],
            [
                'title' => 'Manajemen Donasi',
                'icon' => 'list-check',
                'link' => 'managementdonasi.php'
            ],
            [
                'title' => 'Pengeluaran',
                'icon' => 'cash-stack',
                'link' => 'pengeluaran.php'
            ],
            [
                'title' => 'Laporan',
                'icon' => 'file-text',
                'link' => 'laporan.php'
            ],
            [
                'title' => 'Paket Donasi',
                'icon' => 'box',
                'link' => 'profil_donasi.php'
            ],
            [
                'title' => 'Nominal Donasi',
                'icon' => 'currency-dollar',
                'link' => 'nominal_donasi.php'
            ],
            [
                'title' => 'Target Donasi',
                'icon' => 'bullseye',
                'link' => 'target_donasi.php'
            ],
            [
                'title' => 'Logo Bank',
                'icon' => 'bank',
                'link' => 'logo_bank.php'
            ],
            [
                'title' => 'Slide Donasi',
                'icon' => 'images',
                'link' => 'slide.php'
            ]
        ]
    ],
    'dukungan' => [
        'title' => 'Dukungan',
        'icon' => 'hand-thumbs-up',
        'items' => [
            [
                'title' => 'Sponsor',
                'icon' => 'building',
                'link' => 'sponsor.php'
            ]
        ]
    ]
];
?>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-star-fill me-2"></i>
            Safari Ramadhan Admin
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <?php foreach ($menuGroups['main'] as $item): ?>
                <li class="nav-item">
                    <a class="nav-link <?= isActive($item['link']) ?>" href="<?= $item['link'] ?>">
                        <i class="bi bi-<?= $item['icon'] ?>"></i> <?= $item['title'] ?>
                    </a>
                </li>
                <?php endforeach; ?>
                
                <?php
                // Generate dropdowns for grouped menus
                $groupKeys = ['content', 'pendaftar', 'kegiatan', 'keuangan', 'dukungan'];
                foreach ($groupKeys as $group):
                    $menuGroup = $menuGroups[$group];
                    $groupPages = array_map(function($item) { return $item['link']; }, $menuGroup['items']);
                ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= isActiveDropdown($groupPages) ?>" 
                       href="#" 
                       id="navbarDropdown<?= ucfirst($group) ?>" 
                       role="button" 
                       data-bs-toggle="dropdown" 
                       aria-expanded="false">
                        <i class="bi bi-<?= $menuGroup['icon'] ?>"></i> <?= $menuGroup['title'] ?>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdown<?= ucfirst($group) ?>">
                        <?php foreach ($menuGroup['items'] as $item): ?>
                        <li>
                            <a class="dropdown-item <?= isActive($item['link']) ?>" href="<?= $item['link'] ?>">
                                <i class="bi bi-<?= $item['icon'] ?>"></i> <?= $item['title'] ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php endforeach; ?>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>