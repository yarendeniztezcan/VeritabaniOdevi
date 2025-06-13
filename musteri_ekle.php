<?php
include('db.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and sanitize form data
    $ad = trim($_POST['ad']);
    $soyad = trim($_POST['soyad']);
    $telefon = trim($_POST['telefon']);
    $eposta = trim($_POST['eposta']);

    // Validate required fields
    if (empty($ad) || empty($soyad) || empty($telefon)) {
        $error = 'Ad, Soyad ve Telefon alanları zorunludur!';
    } else {
        // Sanitize data
        $ad = $conn->real_escape_string($ad);
        $soyad = $conn->real_escape_string($soyad);
        $telefon = $conn->real_escape_string($telefon);
        $eposta = $conn->real_escape_string($eposta);

        // Validate email format if provided
        if (!empty($eposta)) {
            if (!filter_var($eposta, FILTER_VALIDATE_EMAIL)) {
                $error = 'Geçersiz e-posta formatı!';
            }
        }

        // Validate phone number 
        $telefon = preg_replace('/[^0-9]/', '', $telefon);
        if (strlen($telefon) < 9) {
            $error = 'Telefon numarası en az 9 karakter olmalıdır!';
        }

        if (empty($error)) {
            // Insert into database with prepared statement
            $stmt = $conn->prepare("INSERT INTO musteri (Ad, Soyad, Telefon, Eposta) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $ad, $soyad, $telefon, $eposta);

            if ($stmt->execute()) {
                $success = 'Müşteri başarıyla eklendi!';
                // Clear form
                $ad = $soyad = $telefon = $eposta = '';
            } else {
                $error = 'Müşteri ekleme hatası: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müşteri Ekle</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: #574031;
            --primary-dark: #271914;
            --secondary: #010d15;
            --light: #827268;
            --dark: #5a5c69;
            --success: #1cc88a;
            --warning: #471e03;
            --danger: #351d0e;
            --border-radius: 10px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            background: linear-gradient(135deg, #695b4a 0%, #594741 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: var(--dark);
            line-height: 1.6;
        }

        .logo {
            align-items: center ;
            width: 240px;
            height: 240px;
            background: var(--primary);
            border-radius: 100%;
            color: white;
            font-size: 10px;
            box-shadow: var(--box-shadow);
            margin-bottom: 15px;
            transition: var(--transition);
        }


        /* Card Styles */
        .card {
            background: #172037;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            width: 100%;
            max-width: 600px;
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }


        .card-header h2 {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .card-body {
            padding: 30px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label i {
            color: var(--primary);
            font-size: 18px;
        }

        .form-control {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 1px solid #e3e6f0;
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
            background-color: #f8f9fc;
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
            background-color: white;
        }


        /* Button Styles */
        .btn {
            display: block;
            width: 100%;
            padding: 14px;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            margin-bottom: 15px;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #254ec7 100%);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--light);
            color: var(--secondary);
            border: 1px solid #e3e6f0;
        }

        .btn-secondary:hover {
            background: #eef0f7;
            transform: translateY(-2px);
        }

        /* Alert Styles */
        .alert {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            font-weight: 500;
        }

        .alert-danger {
            background-color: #fcebec;
            color: var(--danger);
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #e6f4ee;
            color: var(--success);
            border: 1px solid #c3e6cb;
        }


        /* Footer */
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: var(--secondary);
        }

        .footer a {
            color: var(--primary);
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 576px) {
            .card-body {
                padding: 25px 20px;
            }

            .card-header {
                padding: 20px 15px;
            }

            .card-header h2 {
                font-size: 20px;
            }

            .form-control {
                padding: 12px 15px 12px 40px;
            }

            .input-icon {
                top: 37px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                    <div class="container">
                        <img src="logo.png" alt="Book Cafe" class="logo">
                    <h4 class="mb-0">Yeni Müşteri Ekle</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Ad*</label>
                            <input type="text" name="ad" class="form-control"
                                   value="<?= isset($ad) ? htmlspecialchars($ad) : '' ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Soyad*</label>
                            <input type="text" name="soyad" class="form-control"
                                   value="<?= isset($soyad) ? htmlspecialchars($soyad) : '' ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Telefon*</label>
                            <input type="tel" name="telefon" class="form-control"
                                   value="<?= isset($telefon) ? htmlspecialchars($telefon) : '' ?>"
                                   placeholder="5XXXXXXXXX" required>
                            <small class="form-text text-muted">Başında 0 olmadan giriniz</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">E-posta</label>
                            <input type="email" name="eposta" class="form-control"
                                   value="<?= isset($eposta) ? htmlspecialchars($eposta) : '' ?>"
                                   placeholder="ornek@mail.com">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-person-plus"></i> Müşteri Ekle
                            </button>
                            <a href="index.html" class="btn btn-secondary">
                                <i class="bi bi-house-door"></i> Ana Sayfa
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</body>
</html>


