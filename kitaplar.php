<?php
// kitaplar.php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 1) Veritabanı bağlantısı  
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "kitapkafe";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8");
} catch (mysqli_sql_exception $e) {
    die("Bağlantı hatası: " . $e->getMessage());
}

// 2) Yeni Kitap Ekleme İşlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kitap_ekle'])) {
    $baslik = $conn->real_escape_string($_POST['baslik']);
    $yazar = $conn->real_escape_string($_POST['yazar']);
    $yayinevi = $conn->real_escape_string($_POST['yayinevi']);
    $fiyat = (float)$_POST['fiyat'];
    $stok = (int)$_POST['stok'];
    $kategoriID = (int)$_POST['kategoriID'];

    // Validasyon
    if (empty($baslik) || empty($yazar) || $fiyat <= 0 || $stok < 0) {
        $errorBook = "Lütfen geçerli kitap bilgileri girin.";
    } else {
        try {
            if ($kategoriID > 0) {
                $stmt = $conn->prepare("INSERT INTO kitap (Baslik, Yazar, Yayinevi, Fiyat, StokAdedi, KategoriID) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssdii", $baslik, $yazar, $yayinevi, $fiyat, $stok, $kategoriID);
            } else {
                $stmt = $conn->prepare("INSERT INTO kitap (Baslik, Yazar, Yayinevi, Fiyat, StokAdedi) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssdi", $baslik, $yazar, $yayinevi, $fiyat, $stok);
            }
            $stmt->execute();
            $successBook = "Kitap başarıyla eklendi!";
        } catch (mysqli_sql_exception $e) {
            $errorBook = "Kitap ekleme hatası: " . $e->getMessage();
        }
    }
}

// 3) Stok Ekleme İşlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stok_ekle'])) {
    $kitapID = (int)$_POST['kitap'];
    $eklenenStok = (int)$_POST['eklenenStok'];

    if ($kitapID <= 0) {
        $errorStock = "Geçersiz kitap seçimi.";
    } elseif ($eklenenStok <= 0) {
        $errorStock = "Geçersiz stok miktarı.";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE kitap SET StokAdedi = StokAdedi + ? WHERE KitapID = ?");
            $stmt->bind_param("ii", $eklenenStok, $kitapID);
            $stmt->execute();
                $successStock = "Stok başarıyla güncellendi!";
        } catch (mysqli_sql_exception $e) {
            $errorStock = "Stok güncelleme hatası: " . $e->getMessage();
        }
    }
}

// 4) "Satın al" formu gönderimi (stok güncelleme ve sipariş kaydı)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['satin_al'])) {
    $telefon = trim($conn->real_escape_string($_POST['telefon']));
    $adet = (int)$_POST['adet'];
    $kitapID = (int)$_POST['kitapid'];

    // Validasyon
    if (!preg_match('/^[0-9]{10}$/', $telefon)) {
        $error = "Lütfen geçerli bir telefon numarası girin (10 haneli)";
    } elseif ($adet < 1) {
        $error = "Lütfen geçerli bir adet girin.";
    } else {
        $conn->begin_transaction();
        try {
            // Önce stok kontrolü yap
            $stmt = $conn->prepare("SELECT StokAdedi FROM kitap WHERE KitapID = ? FOR UPDATE");
            $stmt->bind_param("i", $kitapID);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception("Kitap bulunamadı!");
            }

            $currentStock = $result->fetch_assoc()['StokAdedi'];
            if ($currentStock < $adet) {
                throw new Exception("Yeterli stok bulunmuyor! Mevcut stok: " . $currentStock);
            }

            // Müşteri kontrolü veya oluşturma
            $stmt = $conn->prepare("SELECT MusteriID FROM musteri WHERE Telefon = ?");
            $stmt->bind_param("s", $telefon);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $musteriID = $row['MusteriID'];
            } else {
                $stmt = $conn->prepare("INSERT INTO musteri (Telefon) VALUES (?)");
                $stmt->bind_param("s", $telefon);
                $stmt->execute();
                $musteriID = $conn->insert_id;
            }

            // Stok güncelleme
            $stmt = $conn->prepare("UPDATE kitap SET StokAdedi = StokAdedi - ? WHERE KitapID = ?");
            $stmt->bind_param("ii", $adet, $kitapID);
            $stmt->execute();

            // Sipariş kaydı
            $stmt = $conn->prepare("INSERT INTO siparisdetay (MusteriID, KitapID, Adet) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $musteriID, $kitapID, $adet);
            $stmt->execute();

            $conn->commit();
            $success = "Satın alma işlemi başarılı!";

        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}
// 5) Kategorileri çek
$kategoriler = $conn->query("SELECT KategoriID, KategoriAdi FROM kategori ORDER BY KategoriAdi");

// 6) Tüm kitapları çek
$allBooks = $conn->query("SELECT KitapID, Baslik FROM kitap ORDER BY Baslik");

// 7) Seçilen kategori
$selectedCategoryID = isset($_GET['kategoriID']) ? (int)$_GET['kategoriID'] : 0;

// 8) Kitapları çek
$sql = "SELECT k.KitapID, k.Baslik, k.Yazar, k.Yayinevi, k.Fiyat, k.StokAdedi,
               COALESCE(c.KategoriAdi, '—') AS KategoriAdi
          FROM kitap k
     LEFT JOIN kategori c ON k.KategoriID = c.KategoriID";

$params = [];
$types = "";

if ($selectedCategoryID > 0) {
    $sql .= " WHERE k.KategoriID = ?";
    $types = "i";
    $params[] = &$selectedCategoryID;
}

$sql .= " ORDER BY k.Baslik";

$stmt = $conn->prepare($sql);
if ($selectedCategoryID > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$kitaplar = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kitap Kafe – Kitaplar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Text:wght@400;700&family=Old+Standard+TT:wght@400;700&display=swap" rel="stylesheet">
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
        }

        body {
            background-color: var(--old-paper);
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23d4af3799' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
            font-family: 'Crimson Text', serif;
            color: var(--ink-black);
        }

        .container {
            max-width: 1200px;
        }

        h1 {
            font-family: 'Old Standard TT', serif;
            color: var(--vintage-brown);
            border-bottom: 3px double var(--book-cover);
            padding-bottom: 15px;
            text-align: center;
            margin-top: 20px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .card {
            background: var(--old-paper);
            border: 1px solid var(--book-cover);
            border-radius: 4px;
            box-shadow: 0 5px 15px rgba(139, 69, 19, 0.1);
            transition: all 0.3s ease;
            height: 100%;
            overflow: hidden;
            position: relative;
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

        .card-title {
            font-family: 'Old Standard TT', serif;
            font-weight: 700;
            color: var(--vintage-brown);
            border-bottom: 1px dashed var(--book-cover);
            padding-bottom: 10px;
        }

        .card-text {
            font-size: 1.05rem;
            line-height: 1.6;
        }

        .btn-primary {
            background-color: var(--book-cover);
            border-color: var(--vintage-brown);
            color: white;
            font-weight: 700;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background-color: var(--vintage-brown);
            border-color: var(--book-cover);
            transform: scale(1.02);
        }

        .btn-success {
            background-color: var(--vintage-brown);
            border-color: var(--book-cover);
            font-weight: 700;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }

        .btn-success:hover {
            background-color: var(--book-cover);
            transform: scale(1.02);
        }

        .btn-info {
            background-color: var(--info-blue);
            border-color: var(--info-blue);
            font-weight: 700;
        }

        .btn-add {
            background-color: var(--success-green);
            border-color: var(--success-green);
            font-weight: 700;
        }

        .form-select, .form-control {
            background-color: rgba(245, 241, 230, 0.7);
            border: 1px solid var(--book-cover);
            color: var(--ink-black);
            font-family: 'Crimson Text', serif;
        }

        .form-select:focus, .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(166, 124, 82, 0.25);
            border-color: var(--vintage-brown);
        }

        .alert {
            border-left: 4px solid var(--vintage-brown);
            background: rgba(245, 241, 230, 0.9);
            color: var(--ink-black);
            font-family: 'Crimson Text', serif;
        }

        .alert-danger {
            border-left-color: #8B0000;
        }

        .alert-success {
            border-left-color: #556B2F;
        }

        .alert-info {
            border-left-color: var(--info-blue);
        }

        .page-header {
            text-align: center;
            padding-bottom: 20px;
            margin-bottom: 30px;
            position: relative;
        }

        .page-header::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 10%;
            right: 10%;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--book-cover), transparent);
        }

        .vintage-label {
            font-family: 'Old Standard TT', serif;
            font-size: 0.9rem;
            color: var(--vintage-brown);
            font-weight: 700;
            margin-bottom: 3px;
        }

        .book-meta {
            background: rgba(166, 124, 82, 0.08);
            border-radius: 4px;
            padding: 10px 15px;
            margin-bottom: 15px;
            border-left: 3px solid var(--book-cover);
        }

        .card-header {
            font-family: 'Old Standard TT', serif;
            font-weight: 700;
        }

        .admin-section {
            margin-bottom: 30px;
        }

    </style>
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="page-header">
        <img src="logo.png" alt="Book Cafe" class="logo" style="width: 300px; height: 300px;">
        <h1>Kitap Kafe – Kitaplar</h1>
    </div>

    <!-- Yönetim Paneli -->
    <div class="admin-section">
        <h2 class="text-center mb-4" style="font-family: 'Old Standard TT', serif; color: var(--vintage-brown);">Kitap Yönetimi</h2>

        <div class="row">
            <!-- Yeni Kitap Ekle Formu -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-book-cover text-white">
                        <h5 class="mb-0">Yeni Kitap Ekle</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="mb-3">
                                <div class="vintage-label">Kitap Adı</div>
                                <input type="text" name="baslik" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <div class="vintage-label">Yazar</div>
                                <input type="text" name="yazar" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <div class="vintage-label">Yayınevi</div>
                                <input type="text" name="yayinevi" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <div class="vintage-label">Fiyat (TL)</div>
                                <input type="number" name="fiyat" class="form-control" step="0.01" min="0" required>
                            </div>

                            <div class="mb-3">
                                <div class="vintage-label">Stok Adedi</div>
                                <input type="number" name="stok" class="form-control" min="0" required>
                            </div>

                            <div class="mb-3">
                                <div class="vintage-label">Kategori</div>
                                <select name="kategoriID" class="form-select">
                                    <option value="0">Kategori Seçin</option>
                                    <?php
                                    $kategoriler->data_seek(0); // Reset pointer
                                    while ($kat = $kategoriler->fetch_assoc()): ?>
                                        <option value="<?= $kat['KategoriID'] ?>">
                                            <?= htmlspecialchars($kat['KategoriAdi']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <button type="submit" name="kitap_ekle" class="btn btn-add w-100">Kitap Ekle</button>
                        </form>

                        <?php if (!empty($errorBook)): ?>
                            <div class="alert alert-danger mt-3"><?= htmlspecialchars($errorBook) ?></div>
                        <?php elseif (!empty($successBook)): ?>
                            <div class="alert alert-success mt-3"><?= htmlspecialchars($successBook) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Stok Ekleme Formu -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-vintage-brown text-white">
                        <h5 class="mb-0">Stok Ekle</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="mb-3">
                                <div class="vintage-label">Kitap Seçin</div>
                                <select name="kitap" class="form-select" required>
                                    <option value="">Kitap Seçin</option>
                                    <?php while ($book = $allBooks->fetch_assoc()): ?>
                                        <option value="<?= $book['KitapID'] ?>">
                                            <?= htmlspecialchars($book['Baslik']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <div class="vintage-label">Eklenecek Stok Miktarı</div>
                                <input type="number" name="eklenenStok" class="form-control" min="1" required>
                            </div>

                            <button type="submit" name="stok_ekle" class="btn btn-info w-100">Stok Ekle</button>
                        </form>

                        <?php if (!empty($errorStock)): ?>
                            <div class="alert alert-danger mt-3"><?= htmlspecialchars($errorStock) ?></div>
                        <?php elseif (!empty($successStock)): ?>
                            <div class="alert alert-success mt-3"><?= htmlspecialchars($successStock) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kategoriye Göre Filtrele -->
    <form method="get" class="row g-2 mb-4">
        <div class="col-md-4">
            <div class="vintage-label">Kategori Seçin</div>
            <select name="kategoriID" class="form-select">
                <option value="0">Tüm Kategoriler</option>
                <?php
                $kategoriler->data_seek(0); // Reset pointer
                while ($kat = $kategoriler->fetch_assoc()): ?>
                    <option
                            value="<?= $kat['KategoriID'] ?>"
                        <?= $kat['KategoriID'] === $selectedCategoryID ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($kat['KategoriAdi']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Filtrele</button>
        </div>
    </form>

    <!-- Başarı / Hata Mesajları -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div>
    <?php elseif (!empty($success)): ?>
        <div class="alert alert-success mb-4"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Kitap Kartları veya Boş Mesajı -->
    <?php if ($kitaplar->num_rows === 0): ?>
        <div class="alert alert-info text-center py-4">
            <h4 class="mt-3">Henüz sisteme eklenmiş kitap yok</h4>
            <p class="mb-0">Lütfen daha sonra tekrar kontrol edin veya yeni kitap ekleyin</p>
        </div>
    <?php else: ?>
        <div class="row">
            <?php while ($row = $kitaplar->fetch_assoc()): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($row['Baslik']) ?></h5>

                            <div class="book-meta">
                                <p class="card-text mb-1"><strong>Yazar:</strong> <?= htmlspecialchars($row['Yazar']) ?></p>
                                <p class="card-text mb-1"><strong>Yayınevi:</strong> <?= htmlspecialchars($row['Yayinevi']) ?></p>
                                <p class="card-text mb-1"><strong>Kategori:</strong> <?= htmlspecialchars($row['KategoriAdi']) ?></p>
                            </div>

                            <div class="d-flex justify-content-between mb-3">
                                <div>
                                    <div class="vintage-label">Fiyat</div>
                                    <p class="card-text"><strong><?= number_format($row['Fiyat'],2) ?> TL</strong></p>
                                </div>
                                <div>
                                    <div class="vintage-label">Stok</div>
                                    <p class="card-text"><strong><?= (int)$row['StokAdedi'] ?></strong></p>
                                </div>
                            </div>

                            <!-- Satın Al Formu -->
                            <form method="post" class="mt-auto">
                                <input type="hidden" name="kitapid" value="<?= $row['KitapID'] ?>">

                                <div class="mb-2">
                                    <div class="vintage-label">Telefon</div>
                                    <input
                                            type="tel"
                                            name="telefon"
                                            class="form-control"
                                            placeholder="5xx xxx xxxx"
                                            pattern="[0-9]{10}"
                                            title="10 haneli telefon numarası"
                                            required
                                    >
                                </div>

                                <div class="mb-3">
                                    <div class="vintage-label">Adet</div>
                                    <input
                                            type="number"
                                            name="adet"
                                            class="form-control"
                                            min="1"
                                            max="<?= max(1, (int)$row['StokAdedi']) ?>"
                                            value="1"
                                            required
                                    >
                                </div>

                                <button
                                        type="submit"
                                        name="satin_al"
                                        class="btn btn-success w-100"
                                    <?= (int)$row['StokAdedi'] < 1 ? 'disabled' : '' ?>
                                >
                                    <?= (int)$row['StokAdedi'] > 0 ? 'Satın Al' : 'Stokta Yok' ?>
                                </button>
                            </form>

                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>