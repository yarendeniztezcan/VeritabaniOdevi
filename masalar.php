<?php
// masalar.php
require_once 'db.php';

// Database connection check
if (!isset($conn)) {
    die("Database connection error");
}

$selectedDate = date('Y-m-d');
if (!empty($_GET['tarih'])) {
    $inputDate = $_GET['tarih'];
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $inputDate)) {
        $selectedDate = $inputDate;
    }
}
// Clear table orders
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear'], $_POST['masaID'])) {
    $masaID = (int)$_POST['masaID'];

    if ($masaID > 0) {
        $del = $conn->prepare("DELETE FROM kafesiparisdetay 
                              WHERE MasaID = ?");
        $del->bind_param('i', $masaID);
        $del->execute();
        $del->close();
    }
    header("Location: masalar.php?tarih={$selectedDate}");
    exit;
}

// Get tables
$tables = [];
$tablesRes = $conn->query("SELECT * FROM masalar ORDER BY MasaID");
if ($tablesRes) {
    $tables = $tablesRes->fetch_all(MYSQLI_ASSOC);
}
$tableIds = array_column($tables, 'MasaID');

// Get reservations with times
$reservationTimes = [];
if (!empty($tableIds)) {
    $placeholders = implode(',', array_fill(0, count($tableIds), '?'));
    $stmt = $conn->prepare("SELECT MasaID, Saat 
                           FROM rezervasyonlar 
                           WHERE MasaID IN ($placeholders) 
                           AND Tarih = ?");

    $types = str_repeat('i', count($tableIds)) . 's';
    $params = array_merge($tableIds, [$selectedDate]);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $reservationTimes[$row['MasaID']][] = $row['Saat'];
    }
    $stmt->close();
}

// Get orders - corrected without date references
$ordersByTable = [];
if (!empty($tableIds)) {
    $placeholders = implode(',', array_fill(0, count($tableIds), '?'));
    $stmt = $conn->prepare("SELECT s.MasaID, m.UrunAdi, s.Adet, m.Fiyat
                           FROM kafesiparisdetay s
                           INNER JOIN menuurunu m ON s.UrunID = m.UrunID
                           WHERE s.MasaID IN ($placeholders)
                           ORDER BY s.UrunID ASC");

    $types = str_repeat('i', count($tableIds));
    $stmt->bind_param($types, ...$tableIds);
    $stmt->execute();

    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $ordersByTable[$row['MasaID']][] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masalar ve Siparişler – Kitap Kafe</title>
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
            --reserve-blue: #4169E1;
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
            border-bottom: 2px solid var(--book-cover);
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
        }

        .card {
            background: var(--old-paper);
            border: 1px solid var(--book-cover);
            border-radius: 4px;
            box-shadow: 0 5px 15px rgba(139, 69, 19, 0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            position: relative;
            height: 100%;
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

        .badge-reserve {
            background-color: var(--reserve-blue);
        }

        .list-group-item {
            background: transparent;
            border: none;
            border-bottom: 1px dashed rgba(166, 124, 82, 0.3);
            padding: 0.5rem 0;
            display: flex;
            justify-content: space-between;
        }

        .form-control, .btn {
            border: 1px solid var(--book-cover);
        }

        .btn-vintage {
            background: var(--vintage-brown);
            color: var(--old-paper);
            font-weight: 700;
            transition: all 0.3s;
        }

        .btn-vintage:hover {
            background: var(--book-cover);
            transform: scale(1.02);
        }

        .btn-vintage:disabled {
            opacity: 0.6;
            cursor: not-allowed;
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

        .page-title {
            font-family: 'Old Standard TT', serif;
            font-size: 2.2rem;
            color: var(--vintage-brown);
            text-align: center;
            margin: 1rem 0 2rem;
            position: relative;
        }

        .page-title::after {
            content: "";
            position: absolute;
            bottom: -10px;
            left: 25%;
            right: 25%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--book-cover), transparent);
        }

        .date-filter {
            max-width: 500px;
            margin: 0 auto 2rem;
        }

        .reservation-times {
            background: rgba(65, 105, 225, 0.08);
            border-radius: 4px;
            padding: 10px 15px;
            margin: 15px 0;
            border-left: 3px solid var(--reserve-blue);
        }

        .reservation-header {
            font-family: 'Old Standard TT', serif;
            font-weight: 700;
            color: var(--reserve-blue);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }

        .reservation-header i {
            margin-right: 8px;
        }

        .time-badge {
            background: rgba(65, 105, 225, 0.15);
            border: 1px solid var(--reserve-blue);
            color: var(--reserve-blue);
            border-radius: 12px;
            padding: 3px 10px;
            font-size: 0.85rem;
            margin: 3px;
            display: inline-block;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <img src="logo.png" alt="Kitap Kafe Logo" class="vintage-logo">
        <h1 class="page-title">Masalar ve Siparişler</h1>
    </div>

    <!-- Date Filter - REMOVED max attribute to allow future dates -->
    <form method="get" class="mb-4 date-filter">
        <div class="input-group">
            <span class="input-group-text">Tarih</span>
            <input
                    type="date"
                    name="tarih"
                    value="<?= htmlspecialchars($selectedDate) ?>"
                    class="form-control"
            >
            <button class="btn btn-vintage" type="submit">Filtrele</button>
        </div>
    </form>

    <div class="row">
        <?php foreach ($tables as $table):
            $masaID = $table['MasaID'];
            $tableReservations = $reservationTimes[$masaID] ?? [];
            $hasReservations = !empty($tableReservations);
            $orders = $ordersByTable[$masaID] ?? [];
            $total = 0;
            ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <?= htmlspecialchars($table['MasaAdi']) ?>
                        <?php if ($hasReservations): ?>
                            <span class="badge badge-reserve">Rezerveli</span>
                        <?php endif; ?>
                    </div>

                    <div class="card-body">
                        <?php if (!empty($orders)): ?>
                            <ul class="list-group mb-3">
                                <?php foreach ($orders as $row):
                                    $subtotal = $row['Adet'] * $row['Fiyat'];
                                    $total += $subtotal;
                                    ?>
                                    <li class="list-group-item">
                                        <span><?= htmlspecialchars($row['UrunAdi']) ?> × <?= $row['Adet'] ?></span>
                                        <span><?= number_format($subtotal, 2) ?> TL</span>
                                    </li>
                                <?php endforeach; ?>
                                <li class="list-group-item d-flex justify-content-between fw-bold pt-3">
                                    <span>Toplam</span>
                                    <span><?= number_format($total, 2) ?> TL</span>
                                </li>
                            </ul>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <p class="text-muted fst-italic">Sipariş bulunamadı</p>
                            </div>
                        <?php endif; ?>

                        <!-- Rezervasyon Saatleri -->
                        <?php if ($hasReservations): ?>
                            <div class="reservation-times">
                                <div class="reservation-header">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clock" viewBox="0 0 16 16">
                                        <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/>
                                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/>
                                    </svg>
                                    Rezervasyon Saatleri
                                </div>
                                <div>
                                    <?php foreach ($tableReservations as $time): ?>
                                        <span class="time-badge"><?= htmlspecialchars($time) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Clear Button -->
                        <form
                                method="post"
                                action="masalar.php?tarih=<?= htmlspecialchars($selectedDate) ?>"
                                onsubmit="return confirm('Bu masanın siparişleri silinecek. Emin misiniz?');"
                        >
                            <input type="hidden" name="masaID" value="<?= $masaID ?>">
                            <button
                                    type="submit"
                                    name="clear"
                                    class="btn btn-vintage w-100"
                                <?= empty($orders) ? 'disabled' : '' ?>
                            >
                                Masayı Temizle
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="footer">
    <a href="index.html" class="btn btn-sm btn-outline-secondary mb-2">Ana Sayfa</a>
    <p>© 2025 Kitap Kafe • Hayatın her anı bir kitap gibi</p>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>