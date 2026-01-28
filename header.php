<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safari Ramadhan 1446 H/2025</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
    <style>
        :root {
            --primary-color: #20B2AA;
        }
        .bg-primary {
            background-color: var(--primary-color) !important;
        }
        .text-primary {
            color: var(--primary-color) !important;
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: #1a8f89;
            border-color: #1a8f89;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="img/logo.png" alt="Logo" height="30">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="berkisah.php">Berkisah</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="motivasi.php">Motivasi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kajian.php">Kajian</a>
                    </li>
                </ul>
                <a href="form.html" class="btn btn-primary ms-3">Daftar</a>
            </div>
        </div>
    </nav>