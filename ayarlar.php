<?php
// ayarlar.php
require_once 'db.php';

// Database connection check
if (!isset($conn)) {
    die("Database connection error");
}

// İşlem sonuç mesajları
$message = '';
$messageType = '';

// Kategorileri getir
$categories = [];
$categoriesRes = $conn->query("SELECT DISTINCT Kategori FROM menuurunu ORDER BY Kategori");
if ($categoriesRes && $categoriesRes->num_rows > 0) {
    $categories = $categoriesRes->fetch_all(MYSQLI_ASSOC);
}

// Yeni menü öğesi ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_menu_item'])) {
    $urunAdi = $conn->real_escape_string($_POST['urun_adi']);
    $aciklama = $conn->real_escape_string($_POST['aciklama']);
    $kategori = $conn->real_escape_string($_POST['kategori']);
    $fiyat = (float)$_POST['fiyat'];

    if (!empty($urunAdi) && $fiyat > 0) {
        $stmt = $conn->prepare("INSERT INTO menuurunu (UrunAdi, Aciklama, Kategori, Fiyat) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('sssd', $urunAdi, $aciklama, $kategori, $fiyat);

        if ($stmt->execute()) {
            $message = "Menü öğesi başarıyla eklendi!";
            $messageType = 'success';
        } else {
            $message = "Menü öğesi eklenirken hata oluştu: " . $stmt->error;
            $messageType = 'danger';
        }
        $stmt->close();
    } else {
        $message = "Lütfen geçerli bir ürün adı ve fiyat girin!";
        $messageType = 'danger';
    }
}

// Menü öğesi silme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_menu_item'])) {
    $urunID = (int)$_POST['urun_id'];

    if ($urunID > 0) {
        $stmt = $conn->prepare("DELETE FROM menuurunu WHERE UrunID = ?");
        $stmt->bind_param('i', $urunID);

        if ($stmt->execute()) {
            $message = "Menü öğesi başarıyla silindi!";
            $messageType = 'success';
        } else {
            $message = "Menü öğesi silinirken hata oluştu: " . $stmt->error;
            $messageType = 'danger';
        }
        $stmt->close();
    } else {
        $message = "Geçersiz menü öğesi seçimi!";
        $messageType = 'danger';
    }
}

// Yeni masa ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_table'])) {
    $masaAdi = $conn->real_escape_string($_POST['masa_adi']);

    if (!empty($masaAdi)) {
        $stmt = $conn->prepare("INSERT INTO masalar (MasaAdi) VALUES (?)");
        $stmt->bind_param('s', $masaAdi);

        if ($stmt->execute()) {
            $message = "Masa başarıyla eklendi!";
            $messageType = 'success';
        } else {
            $message = "Masa eklenirken hata oluştu: " . $stmt->error;
            $messageType = 'danger';
        }
        $stmt->close();
    } else {
        $message = "Lütfen geçerli bir masa adı girin!";
        $messageType = 'danger';
    }
}

// Masa silme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_table'])) {
    $masaID = (int)$_POST['masa_id'];

    if ($masaID > 0) {
        $stmt = $conn->prepare("DELETE FROM masalar WHERE MasaID = ?");
        $stmt->bind_param('i', $masaID);

        if ($stmt->execute()) {
            $message = "Masa başarıyla silindi!";
            $messageType = 'success';
        } else {
            $message = "Masa silinirken hata oluştu: " . $stmt->error;
            $messageType = 'danger';
        }
        $stmt->close();
    } else {
        $message = "Geçersiz masa seçimi!";
        $messageType = 'danger';
    }
}

// Fiyat güncelleme (Zam/İndirim)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_price'])) {
    $islemTuru = $_POST['islem_turu']; // 'zam' veya 'indirim'
    $yuzde = (float)$_POST['yuzde'];
    $kategori = $_POST['kategori'];

    if ($yuzde > 0 && $yuzde <= 100) {
        // Zam veya indirim çarpanını hesapla
        $carpan = ($islemTuru === 'zam') ? (1 + ($yuzde / 100)) : (1 - ($yuzde / 100));

        if ($kategori === 'tum') {
            // Tüm ürünler için güncelle
            $stmt = $conn->prepare("UPDATE menuurunu SET Fiyat = Fiyat * ?");
            $stmt->bind_param('d', $carpan);
        } else {
            // Belirli bir kategori için güncelle
            $stmt = $conn->prepare("UPDATE menuurunu SET Fiyat = Fiyat * ? WHERE Kategori = ?");
            $stmt->bind_param('ds', $carpan, $kategori);
        }

        if ($stmt->execute()) {
            $message = "Fiyatlar başarıyla güncellendi!";
            $messageType = 'success';
        } else {
            $message = "Fiyat güncelleme sırasında hata oluştu: " . $stmt->error;
            $messageType = 'danger';
        }
        $stmt->close();
    } else {
        $message = "Lütfen geçerli bir yüzde değeri girin (0-100 arası)!";
        $messageType = 'danger';
    }
}


// Müşteri bilgilerini güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_customer'])) {
    $eskiTelefon = $conn->real_escape_string($_POST['eski_telefon']);
    $yeniTelefon = $conn->real_escape_string($_POST['yeni_telefon']);
    $ad = $conn->real_escape_string($_POST['ad']);
    $soyad = $conn->real_escape_string($_POST['soyad']);
    $eposta = $conn->real_escape_string($_POST['eposta']);

    if (!empty($eskiTelefon) && !empty($yeniTelefon)) {
        // Müşteriyi güncelle
        $stmt = $conn->prepare("UPDATE musteri 
                               SET Telefon = ?, Ad = ?, Soyad = ?, Eposta = ?
                               WHERE Telefon = ?");
        $stmt->bind_param('sssss', $yeniTelefon, $ad, $soyad, $eposta, $eskiTelefon);

        if ($stmt->execute()) {
            $message = "Müşteri bilgileri başarıyla güncellendi!";
            $messageType = 'success';
        } else {
            $message = "Müşteri güncellenirken hata oluştu: " . $stmt->error;
            $messageType = 'danger';
        }
        $stmt->close();
    } else {
        $message = "Lütfen geçerli telefon numaraları girin!";
        $messageType = 'danger';
    }
}

// Menü öğelerini getir (kategorilere göre gruplanmış)
$menuItems = [];
$menuRes = $conn->query("SELECT * FROM menuurunu ORDER BY Kategori, UrunAdi");
if ($menuRes && $menuRes->num_rows > 0) {
    while ($row = $menuRes->fetch_assoc()) {
        $menuItems[$row['Kategori']][] = $row;
    }
}

// Masaları getir
$tables = [];
$tablesRes = $conn->query("SELECT * FROM masalar ORDER BY MasaAdi");
if ($tablesRes && $tablesRes->num_rows > 0) {
    $tables = $tablesRes->fetch_all(MYSQLI_ASSOC);
}

// Müşterileri getir
$customers = [];
$customersRes = $conn->query("SELECT * FROM musteri ORDER BY Ad, Soyad");
if ($customersRes && $customersRes->num_rows > 0) {
    $customers = $customersRes->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ayarlar – Kitap Kafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Text:wght@400;700&family=Old+Standard+TT:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Favicon -->
    <link rel="icon" href="https://sdmntprwestus2.oaiusercontent.com/files/00000000-87bc-61f8-9ff9-b00a0f16ce96/raw?se=2025-06-10T22%3A51%3A35Z&sp=r&sv=2024-08-04&sr=b&scid=3d34b33d-b676-5d18-862f-7ce0d2a7afa0&skoid=04233560-0ad7-493e-8bf0-1347c317d021&sktid=a48cca56-e6da-484e-a814-9c849652bcb3&skt=2025-06-10T10%3A50%3A27Z&ske=2025-06-11T10%3A50%3A27Z&sks=b&skv=2024-08-04&sig=YFhCA9e9Dyx4kzgdCWXUBV2CsjKjSkuwE/Dc9SSUXbY%3D" type="image/png">
    <style>
        :root {
            --vintage-brown: #8B4513;
            --old-paper: #F5F1E6;
            --book-cover: #A67C52;
            --gold-accent: #D4AF37;
            --ink-black: #2C2416;
            --success-green: #28a745;
            --info-blue: #17a2b8;
            --warning-yellow: #ffc107;
            --danger-red: #dc3545;
        }

        body {
            background-color: var(--old-paper);
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23d4af3799' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
            font-family: 'Crimson Text', serif;
            color: var(--ink-black);
            padding: 20px 0;
        }

        .container {
            max-width: 1200px;
        }

        .header {
            text-align: center;
            padding: 1rem 0 2rem;
            margin-bottom: 2rem;
        }

        .vintage-logo {
            width: 120px;
            border: 3px solid var(--book-cover);
            border-radius: 50%;
            box-shadow: 0 4px 10px rgba(139, 69, 19, 0.2);
        }

        h1 {
            font-family: 'Old Standard TT', serif;
            color: var(--vintage-brown);
            margin-top: 1rem;
            font-weight: 700;
            letter-spacing: 1px;
            position: relative;
        }

        h1::after {
            content: "";
            position: absolute;
            bottom: -10px;
            left: 35%;
            right: 35%;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--book-cover), transparent);
        }

        .card {
            background: var(--old-paper);
            border: 1px solid var(--book-cover);
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(139, 69, 19, 0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            position: relative;
            height: 100%;
            margin-bottom: 25px;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(139, 69, 19, 0.2);
        }

        .card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--gold-accent), var(--book-cover), var(--gold-accent));
        }

        .card-header {
            background: linear-gradient(to bottom, var(--book-cover), var(--vintage-brown));
            color: var(--old-paper);
            font-family: 'Old Standard TT', serif;
            font-weight: 700;
            padding: 0.75rem 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .badge {
            font-family: 'Crimson Text', serif;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .form-control, .btn {
            border: 1px solid var(--book-cover);
        }

        .btn-primary {
            background: var(--vintage-brown);
            color: var(--old-paper);
            font-weight: 700;
            transition: all 0.3s;
            border: 1px solid var(--vintage-brown);
        }

        .btn-primary:hover {
            background: var(--book-cover);
            transform: scale(1.02);
            border: 1px solid var(--vintage-brown);
        }

        .btn-success {
            background: var(--success-green);
            border: 1px solid var(--success-green);
            font-weight: 700;
        }

        .btn-danger {
            background: var(--danger-red);
            border: 1px solid var(--danger-red);
            font-weight: 700;
        }

        .btn-warning {
            background: var(--warning-yellow);
            border: 1px solid var(--warning-yellow);
            font-weight: 700;
            color: #000;
        }

        .btn-info {
            background: var(--info-blue);
            border: 1px solid var(--info-blue);
            font-weight: 700;
        }

        .btn:hover {
            transform: scale(1.02);
        }

        .footer {
            text-align: center;
            margin-top: 3rem;
            padding-top: 1rem;
            border-top: 1px solid var(--book-cover);
            color: var(--vintage-brown);
            font-family: 'Old Standard TT', serif;
        }

        .input-group-text {
            background: var(--book-cover);
            color: var(--old-paper);
            border: 1px solid var(--book-cover);
            font-family: 'Old Standard TT', serif;
        }

        .admin-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-family: 'Old Standard TT', serif;
            color: var(--vintage-brown);
            border-bottom: 2px dashed var(--book-cover);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .vintage-label {
            font-family: 'Old Standard TT', serif;
            font-size: 0.9rem;
            color: var(--vintage-brown);
            font-weight: 700;
            margin-bottom: 5px;
        }

        .form-control:focus {
            border-color: var(--vintage-brown);
            box-shadow: 0 0 0 0.25rem rgba(166, 124, 82, 0.25);
        }

        .alert {
            border-left: 4px solid;
            font-family: 'Crimson Text', serif;
        }

        .alert-success {
            border-left-color: var(--success-green);
            background-color: rgba(40, 167, 69, 0.1);
        }

        .alert-danger {
            border-left-color: var(--danger-red);
            background-color: rgba(220, 53, 69, 0.1);
        }

        .alert-info {
            border-left-color: var(--info-blue);
            background-color: rgba(23, 162, 184, 0.1);
        }

        .action-card {
            padding: 20px;
            border-radius: 8px;
            background: rgba(245, 241, 230, 0.7);
            box-shadow: 0 3px 10px rgba(139, 69, 19, 0.1);
            margin-bottom: 20px;
            height: 100%;
        }

        .icon-box {
            width: 60px;
            height: 60px;
            background: var(--vintage-brown);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 24px;
        }

        .price-operation {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .price-option {
            flex: 1;
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .price-option.selected {
            border-color: var(--vintage-brown);
            background: rgba(166, 124, 82, 0.1);
        }

        .price-option.zam {
            background: rgba(255, 193, 7, 0.2);
        }

        .price-option.indirim {
            background: rgba(40, 167, 69, 0.2);
        }

        .price-option h5 {
            margin-top: 0;
            font-family: 'Old Standard TT', serif;
        }

        .category-selector {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        .category-btn {
            flex: 1;
            min-width: 120px;
            padding: 10px;
            border-radius: 8px;
            background: rgba(166, 124, 82, 0.1);
            border: 1px solid var(--book-cover);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .category-btn.selected {
            background: var(--vintage-brown);
            color: white;
        }

        .price-change-box {
            background: rgba(245, 241, 230, 0.7);
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            border: 1px solid var(--book-cover);
        }

        .category-header {
            background: rgba(166, 124, 82, 0.1);
            padding: 10px 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-family: 'Old Standard TT', serif;
            font-weight: 700;
            color: var(--vintage-brown);
        }

        .customer-card {
            background: rgba(245, 241, 230, 0.7);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid var(--book-cover);
        }

        .customer-icon {
            width: 50px;
            height: 50px;
            background: var(--vintage-brown);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-right: 15px;
        }

        .customer-info {
            flex: 1;
        }

        .customer-row {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px dashed var(--book-cover);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <img src="logo.png" alt="Kitap Kafe Logo" class="vintage-logo">
        <h1>Ayarlar ve Yönetim Paneli</h1>
    </div>

    <!-- Mesajlar -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $messageType ?> mb-4">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <!-- Menü Yönetimi -->
    <div class="admin-section">
        <h3 class="section-title">Menü Yönetimi</h3>
        <div class="row">
            <!-- Menü Öğesi Ekle -->
            <div class="col-md-6">
                <div class="action-card">
                    <div class="icon-box">
                        <i class="bi bi-plus-circle"></i>
                    </div>
                    <h4 class="text-center mb-4" style="color: var(--vintage-brown);">Menü Öğesi Ekle</h4>
                    <form method="post">
                        <div class="mb-3">
                            <div class="vintage-label">Ürün Adı</div>
                            <input type="text" name="urun_adi" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <div class="vintage-label">Açıklama</div>
                            <textarea name="aciklama" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="vintage-label">Kategori</div>
                                <select name="kategori" class="form-select" required>
                                    <option value="">Kategori seçin</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= htmlspecialchars($category['Kategori']) ?>">
                                            <?= htmlspecialchars($category['Kategori']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="vintage-label">Fiyat (TL)</div>
                                <input type="number" name="fiyat" class="form-control" step="0.01" min="0.01" required>
                            </div>
                        </div>
                        <button type="submit" name="add_menu_item" class="btn btn-success w-100">
                            <i class="bi bi-plus-lg me-2"></i>Menü Öğesi Ekle
                        </button>
                    </form>
                </div>
            </div>

            <!-- Menü Öğesi Sil -->
            <div class="col-md-6">
                <div class="action-card">
                    <div class="icon-box">
                        <i class="bi bi-trash"></i>
                    </div>
                    <h4 class="text-center mb-4" style="color: var(--vintage-brown);">Menü Öğesi Sil</h4>
                    <form method="post">
                        <div class="mb-3">
                            <div class="vintage-label">Menü Öğesi Seçin</div>
                            <select name="urun_id" class="form-select" required>
                                <option value="">Ürün seçin</option>
                                <?php foreach ($menuItems as $kategori => $items): ?>
                                    <optgroup label="<?= htmlspecialchars($kategori) ?>">
                                        <?php foreach ($items as $item): ?>
                                            <option value="<?= $item['UrunID'] ?>">
                                                <?= htmlspecialchars($item['UrunAdi']) ?> (<?= number_format($item['Fiyat'], 2) ?> TL)
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="delete_menu_item" class="btn btn-danger w-100">
                            <i class="bi bi-trash me-2"></i>Menü Öğesini Sil
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Masa Yönetimi -->
    <div class="admin-section">
        <h3 class="section-title">Masa Yönetimi</h3>
        <div class="row">
            <!-- Masa Ekle -->
            <div class="col-md-6">
                <div class="action-card">
                    <div class="icon-box">
                        <i class="bi bi-plus-square"></i>
                    </div>
                    <h4 class="text-center mb-4" style="color: var(--vintage-brown);">Masa Ekle</h4>
                    <form method="post">
                        <div class="mb-3">
                            <div class="vintage-label">Masa Adı</div>
                            <input type="text" name="masa_adi" class="form-control" required>
                        </div>
                        <button type="submit" name="add_table" class="btn btn-success w-100">
                            <i class="bi bi-plus-lg me-2"></i>Masa Ekle
                        </button>
                    </form>
                </div>
            </div>

            <!-- Masa Sil -->
            <div class="col-md-6">
                <div class="action-card">
                    <div class="icon-box">
                        <i class="bi bi-dash-square"></i>
                    </div>
                    <h4 class="text-center mb-4" style="color: var(--vintage-brown);">Masa Sil</h4>
                    <form method="post">
                        <div class="mb-3">
                            <div class="vintage-label">Masa Seçin</div>
                            <select name="masa_id" class="form-select" required>
                                <option value="">Masa seçin</option>
                                <?php foreach ($tables as $table): ?>
                                    <option value="<?= $table['MasaID'] ?>">
                                        <?= htmlspecialchars($table['MasaAdi']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="delete_table" class="btn btn-danger w-100">
                            <i class="bi bi-trash me-2"></i>Masayı Sil
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Fiyat Güncelleme -->
    <div class="admin-section">
        <h3 class="section-title">Fiyat Güncelleme</h3>
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="action-card">
                    <div class="icon-box">
                        <i class="bi bi-currency-exchange"></i>
                    </div>
                    <h4 class="text-center mb-4" style="color: var(--vintage-brown);">Toplu Fiyat Güncelleme</h4>
                    <form method="post">
                        <!-- İşlem Türü Seçimi -->
                        <div class="price-operation mb-4">
                            <div class="price-option zam selected" data-type="zam">
                                <i class="bi bi-arrow-up-circle fs-1"></i>
                                <h5>Zam Yap</h5>
                                <p>Ürün fiyatlarını artır</p>
                            </div>
                            <div class="price-option indirim" data-type="indirim">
                                <i class="bi bi-arrow-down-circle fs-1"></i>
                                <h5>İndirim Yap</h5>
                                <p>Ürün fiyatlarını azalt</p>
                            </div>
                            <input type="hidden" name="islem_turu" id="islem_turu" value="zam">
                        </div>

                        <!-- Yüzde Değeri -->
                        <div class="mb-4">
                            <div class="vintage-label">Yüzde Değeri</div>
                            <div class="input-group">
                                <input type="number" name="yuzde" class="form-control" min="1" max="100" value="10" required>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <!-- Kategori Seçimi -->
                        <div class="mb-4">
                            <div class="vintage-label">Kategori Seçimi</div>
                            <div class="category-selector">
                                <div class="category-btn selected" data-category="tum">Tüm Ürünler</div>
                                <?php foreach ($categories as $category): ?>
                                    <div class="category-btn" data-category="<?= htmlspecialchars($category['Kategori']) ?>">
                                        <?= htmlspecialchars($category['Kategori']) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="kategori" id="selected_category" value="tum">
                        </div>

                        <button type="submit" name="update_price" class="btn btn-warning w-100">
                            <i class="bi bi-arrow-repeat me-2"></i>Fiyatları Güncelle
                        </button>
                    </form>

                    <!-- Bilgi Kutusu -->
                    <div class="price-change-box mt-4">
                        <h5><i class="bi bi-info-circle me-2"></i>Nasıl Çalışır?</h5>
                        <p class="mb-0">
                            Seçtiğiniz kategori için fiyatları belirlediğiniz yüzde oranında
                            <span id="operation-text">artıracaksınız</span>.
                            Örneğin; %10 zam yapıldığında 20 TL olan bir ürün 22 TL olacaktır.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Müşteri Yönetimi -->
    <div class="admin-section">
        <h3 class="section-title">Müşteri Yönetimi</h3>
        <div class="row">
            <!-- Müşteri Bilgilerini Güncelle -->
            <div class="col-md-6">
                <div class="action-card">
                    <div class="icon-box">
                        <i class="bi bi-person-gear"></i>
                    </div>
                    <h4 class="text-center mb-4" style="color: var(--vintage-brown);">Müşteri Bilgilerini Güncelle</h4>
                    <form method="post">
                        <div class="mb-3">
                            <div class="vintage-label">Mevcut Telefon Numarası</div>
                            <input type="tel" name="eski_telefon" class="form-control"
                                   placeholder="5xx xxx xxxx" pattern="[0-9]{10}"
                                   title="10 haneli telefon numarası" required>
                        </div>
                        <div class="mb-3">
                            <div class="vintage-label">Yeni Telefon Numarası</div>
                            <input type="tel" name="yeni_telefon" class="form-control"
                                   placeholder="5xx xxx xxxx" pattern="[0-9]{10}"
                                   title="10 haneli telefon numarası" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="vintage-label">Ad</div>
                                <input type="text" name="ad" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="vintage-label">Soyad</div>
                                <input type="text" name="soyad" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="vintage-label">E-posta</div>
                            <input type="email" name="eposta" class="form-control">
                        </div>
                        <button type="submit" name="update_customer" class="btn btn-info w-100">
                            <i class="bi bi-arrow-repeat me-2"></i>Bilgileri Güncelle
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Mevcut Veriler -->
    <div class="admin-section">
        <h3 class="section-title">Mevcut Veriler</h3>
        <div class="row">
            <!-- Mevcut Menü Öğeleri -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        Mevcut Menü Öğeleri
                        <span class="badge bg-success"><?= count($menuItems, COUNT_RECURSIVE) - count($menuItems) ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($menuItems)): ?>
                            <?php foreach ($menuItems as $kategori => $items): ?>
                                <div class="category-header">
                                    <?= htmlspecialchars($kategori) ?>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                        <tr>
                                            <th>Ürün Adı</th>
                                            <th>Açıklama</th>
                                            <th>Fiyat</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($items as $item): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($item['UrunAdi']) ?></td>
                                                <td><?= htmlspecialchars($item['Aciklama']) ?></td>
                                                <td><?= number_format($item['Fiyat'], 2) ?> TL</td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                Henüz menü öğesi eklenmemiş
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Mevcut Masalar -->
            <div class="col-12 mt-4">
                <div class="card">
                    <div class="card-header">
                        Mevcut Masalar
                        <span class="badge bg-success"><?= count($tables) ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($tables)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                    <tr>
                                        <th>Masa ID</th>
                                        <th>Masa Adı</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($tables as $table): ?>
                                        <tr>
                                            <td><?= $table['MasaID'] ?></td>
                                            <td><?= htmlspecialchars($table['MasaAdi']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                Henüz masa eklenmemiş
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Mevcut Müşteriler -->
            <div class="col-12 mt-4">
                <div class="card">
                    <div class="card-header">
                        Mevcut Müşteriler
                        <span class="badge bg-success"><?= count($customers) ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($customers)): ?>
                            <div class="customer-list">
                                <?php foreach ($customers as $customer): ?>
                                    <div class="customer-row">
                                        <div class="customer-icon">
                                            <i class="bi bi-person"></i>
                                        </div>
                                        <div class="customer-info">
                                            <h5><?= htmlspecialchars($customer['Ad'] ?? '') ?> <?= htmlspecialchars($customer['Soyad'] ?? '') ?></h5>
                                            <div class="d-flex flex-wrap">
                                                <div class="me-4"><strong>Telefon:</strong> <?= htmlspecialchars($customer['Telefon']) ?></div>
                                                <?php if (!empty($customer['Eposta'])): ?>
                                                    <div><strong>E-posta:</strong> <?= htmlspecialchars($customer['Eposta']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                Henüz müşteri kaydı bulunmamaktadır
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="footer">
    <a href="index.html" class="btn btn-outline-secondary mb-2">
        <i class="bi bi-house-door me-1"></i>Ana Sayfa
    </a>
    <p>© 2025 Kitap Kafe • Hayatın her anı bir kitap gibi</p>
</div>

<script>
    // Zam/İndirim seçimi
    document.querySelectorAll('.price-option').forEach(option => {
        option.addEventListener('click', function() {
            // Tüm seçeneklerden selected sınıfını kaldır
            document.querySelectorAll('.price-option').forEach(opt => {
                opt.classList.remove('selected');
            });

            // Tıklanana selected sınıfı ekle
            this.classList.add('selected');

            // İşlem türünü güncelle
            const operationType = this.getAttribute('data-type');
            document.getElementById('islem_turu').value = operationType;

            // Bilgi metnini güncelle
            const operationText = operationType === 'zam' ? 'artıracaksınız' : 'azaltacaksınız';
            document.getElementById('operation-text').textContent = operationText;
        });
    });

    // Kategori seçimi
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Tüm seçeneklerden selected sınıfını kaldır
            document.querySelectorAll('.category-btn').forEach(b => {
                b.classList.remove('selected');
            });

            // Tıklanana selected sınıfı ekle
            this.classList.add('selected');

            // Kategori değerini güncelle
            const category = this.getAttribute('data-category');
            document.getElementById('selected_category').value = category;
        });
    });
    
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>