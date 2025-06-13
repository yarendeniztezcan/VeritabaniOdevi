<?php
// ekle_siparis.php

// 1) Veritabanı bağlantısı
require_once 'db.php';
if (!isset($conn)) {
    die("Database connection failed");
}

// 2) Yalnızca POST isteği kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: menu.php');
    exit;
}

// 3) POST verilerini al ve doğrula
$masaID = filter_input(INPUT_POST, 'masaID', FILTER_VALIDATE_INT);
$urunID = filter_input(INPUT_POST, 'urunID', FILTER_VALIDATE_INT);
$adet   = filter_input(INPUT_POST, 'adet',   FILTER_VALIDATE_INT);

if (!$masaID || !$urunID || !$adet) {
    // Geçersiz veri → Menüye geri dön
    header('Location: menu.php');
    exit;
}

// 4) MüşteriID için varsayılan değer (anonim siparişler için)
$musteriID = 1; // Default customer for anonymous orders

// 5) Siparişi kafesiparisdetay tablosuna ekle
$stmt = $conn->prepare("
    INSERT INTO kafesiparisdetay (MusteriID, MasaID, UrunID, Adet)
    VALUES (?, ?, ?, ?)
");
if (!$stmt) {
    // Hazırlama hatası
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("iiii", $musteriID, $masaID, $urunID, $adet);

if (!$stmt->execute()) {
    // Çalıştırma hatası
    die("Execute failed: " . $stmt->error);
}

// 6) Temizlik
$stmt->close();
$conn->close();

// 7) Masalar sayfasına yönlendir
header('Location: masalar.php?tarih=' . date('Y-m-d'));
exit;