<?php
require_once 'db.php'; // db.php içinde $conn bağlantısı sağlanır

// Mesaj değişkenleri
$enhancement_errors = [];
$enhancement_success = '';

// Rezervasyon ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) && $_POST['action'] === 'ekle'
) {
    $masaID = (int)$_POST['masa'];
    $tarih = $_POST['tarih'];
    $saat = $_POST['saat'];
    $telefon = trim($_POST['telefon']);

    // Telefon kayıtlı mı kontrol et
    $stmt = $conn->prepare("SELECT MusteriID FROM musteri WHERE Telefon = ?");
    $stmt->bind_param("s", $telefon);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $musteriID = $row['MusteriID'];
        $stmt->close();

        // Rezervasyon ekle
        $stmt2 = $conn->prepare(
            "INSERT INTO rezervasyonlar (MusteriID, MasaID, Tarih, Saat) VALUES (?, ?, ?, ?)"
        );
        $stmt2->bind_param("iiss", $musteriID, $masaID, $tarih, $saat);
        if ($stmt2->execute()) {
            $enhancement_success = "Rezervasyon başarıyla eklendi.";
        } else {
            $enhancement_errors[] = "Rezervasyon eklenirken hata oluştu.";
        }
        $stmt2->close();
    } else {
        $enhancement_errors[] = "Girilen telefon numarası kayıtlı değil.";
        $stmt->close();
    }
}

// Rezervasyon silme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) && $_POST['action'] === 'sil'
) {
    $rezID = (int)$_POST['sil_id'];
    $stmt = $conn->prepare("DELETE FROM rezervasyonlar WHERE RezervasyonID = ?");
    $stmt->bind_param("i", $rezID);
    if ($stmt->execute()) {
        $enhancement_success = "Rezervasyon iptal edildi.";
    } else {
        $enhancement_errors[] = "Rezervasyon iptal edilirken hata oluştu.";
    }
    $stmt->close();
}

// Rezervasyonları listeleme
$tarihSecildi = false;
$rezervasyonlar = [];

if (isset($_GET['tarih'])) {
    $tarihSecildi = true;
    $tarih = $_GET['tarih'];

    $stmt = $conn->prepare(
        "SELECT RezervasyonID, MasaID, Saat 
         FROM rezervasyonlar 
         WHERE Tarih = ?
         ORDER BY MasaID, Saat"
    );
    $stmt->bind_param("s", $tarih);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $rezervasyonlar[$row['MasaID']][] = [
            'id' => $row['RezervasyonID'],
            'saat' => $row['Saat'],
        ];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Rezervasyon Sistemi</title>
    <style>
        /* Vintage Bookish Vibe */
        body {
            background: #f5f0e1;
            color: #4a2f2a;
            font-family: 'Georgia', serif;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            padding: 20px 0;
        }

        .header img.logo {
            height: 80px;
        }

        h1, h2 {
            font-family: 'Palatino Linotype', serif;
            margin-bottom: 10px;
        }

        .message {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }

        .success {
            background: #d4edda;
            color: #155724;
        }

        form {
            background: #ffffffee;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #dcd0c0;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);
        }

        label {
            display: inline-block;
            width: 100px;
            font-weight: bold;
        }

        input, select, button {
            padding: 8px;
            border: 1px solid #c4b29a;
            border-radius: 3px;
            font-size: 1rem;
            margin-bottom: 10px;
        }

        button {
            background: #996d5e;
            color: #fff;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background: #7f554b;
        }

        .masalar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        .masa {
            background: #ffffffcc;
            border: 1px solid #dcd0c0;
            border-radius: 5px;
            width: 28%;
            margin-bottom: 20px;
            padding: 15px;
            box-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
        }

        .saat {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <img src="logo.png" alt="Logo" class="logo" style="height: 300px ; width: 300px ">
        <h1>Cair Paravel Kitap Kafe Rezervasyon</h1>
    </div>

    <!-- Mesajlar -->
    <?php foreach ($enhancement_errors as $err): ?>
        <div class="message error"><?= htmlspecialchars($err) ?></div>
    <?php endforeach; ?>
    <?php if ($enhancement_success): ?>
        <div class="message success"><?= htmlspecialchars($enhancement_success) ?></div>
    <?php endif; ?>

    <!-- Rezervasyon Ekleme Formu -->
    <form method="POST">
        <input type="hidden" name="action" value="ekle">
        <label>Masa:</label>
        <select name="masa">
            <?php for ($i = 1; $i <= 10; $i++): ?>
                <option value="<?= $i ?>"><?= $i ?></option>
            <?php endfor; ?>
        </select><br>

        <label>Tarih:</label>
        <input type="date" name="tarih" required><br>

        <label>Saat:</label>
        <input type="time" name="saat" required><br>

        <label>Telefon:</label>
        <input type="text" name="telefon" placeholder="5XXXXXXXXX" required><br>

        <button type="submit">Rezervasyon Yap</button>
    </form>

    <!-- Rezervasyonları Görüntüle -->
    <form method="GET">
        <label>Tarih Seç:</label>
        <input type="date" name="tarih" required>
        <button type="submit">Listele</button>
    </form>

    <?php if ($tarihSecildi): ?>
        <h2><?= htmlspecialchars($tarih) ?> tarihindeki rezervasyonlar</h2>
        <div class="masalar">
            <?php for ($i = 1; $i <= 10; $i++): ?>
                <div class="masa">
                    <h3>Masa <?= $i ?></h3>
                    <?php if (!empty($rezervasyonlar[$i])): ?>
                        <?php foreach ($rezervasyonlar[$i] as $rez): ?>
                            <div class="saat">
                                <span><?= htmlspecialchars($rez['saat']) ?></span>
                                <form method="POST">
                                    <input type="hidden" name="action" value="sil">
                                    <input type="hidden" name="sil_id" value="<?= $rez['id'] ?>">
                                    <button type="submit">İptal</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Rezervasyon yok.</p>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>