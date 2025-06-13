<?php
require_once('db.php');
if (!isset($conn)) {
    die("Database connection not established");
}
$tables_result = $conn->query("SELECT * FROM masalar"); // Tablo adı düzeltildi
$tables = $tables_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kafe Menü</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <link rel="icon" href="https://sdmntprwestus3.oaiusercontent.com/files/00000000-ab2c-61fd-9c3c-d520203c1fee/raw?se=2025-06-10T22%3A54%3A09Z&sp=r&sv=2024-08-04&sr=b&scid=4b01ec54-3632-56d7-9814-492465971700&skoid=04233560-0ad7-493e-8bf0-1347c317d021&sktid=a48cca56-e6da-484e-a814-9c849652bcb3&skt=2025-06-10T10%3A07%3A33Z&ske=2025-06-11T10%3A07%3A33Z&sks=b&skv=2024-08-04&sig=Ind43%2BknL0qVh8P83/jEsI42xyx/zA/t6LWsjnluZzk%3D" type="image/png">
    <style>
        body {
            background-color: #f8f4e9;
            font-family: 'Playfair Display', serif;
            color: #4e342e;
            margin: 0;
            padding: 0;
        }

        .menu-header {
            background: linear-gradient(to right, #6d4c41, #a1887f);
            color: white;
            padding: 2rem 0;
            text-align: center;
            position: relative;
            z-index: 10;
        }

        .logo {
            width: 150px;
            height: 150px;
            margin-bottom: 1rem;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #f8f4e9;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .categories-nav {
            background: #5d4037;
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .categories-container {
            display: flex;
            overflow-x: auto;
            gap: 10px;
            padding: 0 15px;
            scrollbar-width: thin;
            scrollbar-color: #a1887f #5d4037;
        }

        .categories-container::-webkit-scrollbar {
            height: 8px;
        }

        .categories-container::-webkit-scrollbar-track {
            background: #5d4037;
        }

        .categories-container::-webkit-scrollbar-thumb {
            background-color: #a1887f;
            border-radius: 4px;
        }

        .category-link {
            background: #a1887f;
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            white-space: nowrap;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .category-link:hover, .category-link.active {
            background: #f8f4e9;
            color: #5d4037;
            transform: translateY(-2px);
        }

        .menu-container {
            padding: 2rem 0;
            max-height: calc(100vh - 340px);
            overflow-y: auto;
        }

        .menu-category {
            margin-bottom: 3rem;
            padding: 1.5rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .category-title {
            border-bottom: 2px solid #d7ccc8;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            color: #5d4037;
            font-size: 1.8rem;
        }

        .menu-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px dashed #d7ccc8;
        }

        .item-info {
            flex: 2;
        }

        .item-name {
            font-weight: 700;
            margin-bottom: 0.25rem;
            font-size: 1.2rem;
        }

        .item-desc {
            font-size: 0.95rem;
            color: #8d6e63;
            font-style: italic;
        }

        .item-price {
            flex: 1;
            text-align: right;
            font-weight: 700;
            color: #5d4037;
            font-size: 1.2rem;
        }

        .order-form {
            display: flex;
            margin-top: 1rem;
            gap: 10px;
        }

        .table-select {
            width: 150px !important;
        }

        .btn-order {
            background: #5d4037;
            color: white;
            border: none;
            padding: 8px 15px;
            transition: background 0.3s;
        }

        .btn-order:hover {
            background: #4e342e;
        }

        .btn-back {
            background: #a1887f;
            color: white;
            margin-top: 2rem;
            padding: 10px 25px;
            transition: background 0.3s;
        }

        .btn-back:hover {
            background: #8d6e63;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const categoryLinks = document.querySelectorAll('.category-link');
            categoryLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href').substring(1);
                    const targetElement = document.getElementById(targetId);

                    categoryLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');

                    targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });

            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const id = entry.target.id;
                        categoryLinks.forEach(link => {
                            link.classList.toggle('active', link.getAttribute('href') === `#${id}`);
                        });
                    }
                });
            }, { threshold: 0.1 });

            document.querySelectorAll('.menu-category').forEach(category => {
                observer.observe(category);
            });
        });
    </script>
</head>
<body>
<div class="menu-header">
    <div class="container">
        <img src="logo.png" alt="Book Cafe" class="logo">
        <h1 class="display-4">Kafe Menüsü</h1>
        <p class="lead">Lezzetli molalar için seçiminizi yapın</p>
    </div>
</div>

<div class="categories-nav">
    <div class="categories-container">
        <a href="#sicak-icecekler" class="category-link active">Sıcak İçecekler</a>
        <a href="#kahveler" class="category-link">Kahveler</a>
        <a href="#sandvicler" class="category-link">Sandviçler</a>
        <a href="#tostlar" class="category-link">Tostlar</a>
        <a href="#makarnalar/pizzalar" class="category-link">Makarnalar / Pizzalar</a>
        <a href="#tatlilar" class="category-link">Tatlilar</a>
        <a href="#akolsuz kokteyiller" class="category-link">Alkolsüz Kokteyller</a>
        <a href="#caylar" class="category-link">Çaylar</a>
    </div>
</div>


    <div class="menu-category" id="sicak-icecekler">
        <h2 class="category-title">Sıcak İçecekler</h2>
        <?php
        $urunler = $conn->query("SELECT * FROM menuurunu WHERE Kategori = 'Sıcak İçecek'"); // Tablo adı düzeltildi
        while ($row = $urunler->fetch_assoc()) {
            echo '<div class="menu-item">';
            echo '<div class="item-info">';
            echo '<div class="item-name">' . $row['UrunAdi'] . '</div>';
            echo '<div class="item-desc">' . $row['Aciklama'] . '</div>';
            echo '</div>';
            echo '<div class="item-price">' . $row['Fiyat'] . ' TL</div>';
            echo '</div>';
            echo '<form method="post" action="ekle_siparis.php" class="order-form">';
            echo '<input type="number" name="adet" value="1" min="1" class="form-control" style="width: 80px;">';
            echo '<select name="masaID" class="form-control table-select">';
            foreach ($tables as $table) {
                echo '<option value="' . $table['MasaID'] . '">' . $table['MasaAdi'] . '</option>';
            }
            echo '</select>';
            echo '<input type="hidden" name="urunID" value="' . $row['UrunID'] . '">';
            echo '<button type="submit" class="btn btn-order">Sipariş Ver</button>';
            echo '</form>';
        }
        ?>
    </div>


        <div class="menu-category" id="kahveler">
            <h2 class="category-title">"Kahveler"</h2>
            <?php
            $urunler = $conn->query("SELECT * FROM menuurunu WHERE Kategori = 'Kahve'"); // Tablo adı düzeltildi
            while ($row = $urunler->fetch_assoc()) {
                echo '<div class="menu-item">';
                echo '<div class="item-info">';
                echo '<div class="item-name">' . $row['UrunAdi'] . '</div>';
                echo '<div class="item-desc">' . $row['Aciklama'] . '</div>';
                echo '</div>';
                echo '<div class="item-price">' . $row['Fiyat'] . ' TL</div>';
                echo '</div>';
                echo '<form method="post" action="ekle_siparis.php" class="order-form">';
                echo '<input type="number" name="adet" value="1" min="1" class="form-control" style="width: 80px;">';
                echo '<select name="masaID" class="form-control table-select">';
                foreach ($tables as $table) {
                    echo '<option value="' . $table['MasaID'] . '">' . $table['MasaAdi'] . '</option>';
                }
                echo '</select>';
                echo '<input type="hidden" name="urunID" value="' . $row['UrunID'] . '">';
                echo '<button type="submit" class="btn btn-order">Sipariş Ver</button>';
                echo '</form>';
            }
            ?>
</div>


            <div class="menu-category" id="sandvicler">
                <h2 class="category-title">Sandvicler</h2>
                <?php
                $urunler = $conn->query("SELECT * FROM menuurunu WHERE Kategori = 'Sandviç'"); // Tablo adı düzeltildi
                while ($row = $urunler->fetch_assoc()) {
                    echo '<div class="menu-item">';
                    echo '<div class="item-info">';
                    echo '<div class="item-name">' . $row['UrunAdi'] . '</div>';
                    echo '<div class="item-desc">' . $row['Aciklama'] . '</div>';
                    echo '</div>';
                    echo '<div class="item-price">' . $row['Fiyat'] . ' TL</div>';
                    echo '</div>';
                    echo '<form method="post" action="ekle_siparis.php" class="order-form">';
                    echo '<input type="number" name="adet" value="1" min="1" class="form-control" style="width: 80px;">';
                    echo '<select name="masaID" class="form-control table-select">';
                    foreach ($tables as $table) {
                        echo '<option value="' . $table['MasaID'] . '">' . $table['MasaAdi'] . '</option>';
                    }
                    echo '</select>';
                    echo '<input type="hidden" name="urunID" value="' . $row['UrunID'] . '">';
                    echo '<button type="submit" class="btn btn-order">Sipariş Ver</button>';
                    echo '</form>';
                }
                ?>
            </div>

                <div class="menu-category" id="tostlar">
                    <h2 class="category-title">Tostlar</h2>
                    <?php
                    $urunler = $conn->query("SELECT * FROM menuurunu WHERE Kategori = 'Tost'"); // Tablo adı düzeltildi
                    while ($row = $urunler->fetch_assoc()) {
                        echo '<div class="menu-item">';
                        echo '<div class="item-info">';
                        echo '<div class="item-name">' . $row['UrunAdi'] . '</div>';
                        echo '<div class="item-desc">' . $row['Aciklama'] . '</div>';
                        echo '</div>';
                        echo '<div class="item-price">' . $row['Fiyat'] . ' TL</div>';
                        echo '</div>';
                        echo '<form method="post" action="ekle_siparis.php" class="order-form">';
                        echo '<input type="number" name="adet" value="1" min="1" class="form-control" style="width: 80px;">';
                        echo '<select name="masaID" class="form-control table-select">';
                        foreach ($tables as $table) {
                            echo '<option value="' . $table['MasaID'] . '">' . $table['MasaAdi'] . '</option>';
                        }
                        echo '</select>';
                        echo '<input type="hidden" name="urunID" value="' . $row['UrunID'] . '">';
                        echo '<button type="submit" class="btn btn-order">Sipariş Ver</button>';
                        echo '</form>';
                    }
                    ?>
                </div>


                    <div class="menu-category" id="makarnalar/pizzalar">
                        <h2 class="category-title">"Makarnalar / Pizzalar"</h2>
                        <?php
                        $urunler = $conn->query("SELECT * FROM menuurunu WHERE Kategori = 'Makarna / Pizza'"); // Tablo adı düzeltildi
                        while ($row = $urunler->fetch_assoc()) {
                            echo '<div class="menu-item">';
                            echo '<div class="item-info">';
                            echo '<div class="item-name">' . $row['UrunAdi'] . '</div>';
                            echo '<div class="item-desc">' . $row['Aciklama'] . '</div>';
                            echo '</div>';
                            echo '<div class="item-price">' . $row['Fiyat'] . ' TL</div>';
                            echo '</div>';
                            echo '<form method="post" action="ekle_siparis.php" class="order-form">';
                            echo '<input type="number" name="adet" value="1" min="1" class="form-control" style="width: 80px;">';
                            echo '<select name="masaID" class="form-control table-select">';
                            foreach ($tables as $table) {
                                echo '<option value="' . $table['MasaID'] . '">' . $table['MasaAdi'] . '</option>';
                            }
                            echo '</select>';
                            echo '<input type="hidden" name="urunID" value="' . $row['UrunID'] . '">';
                            echo '<button type="submit" class="btn btn-order">Sipariş Ver</button>';
                            echo '</form>';
                        }
                        ?>
                    </div>


                        <div class="menu-category" id="tatlilar">
                            <h2 class="category-title">Tatlilar</h2>
                            <?php
                            $urunler = $conn->query("SELECT * FROM menuurunu WHERE Kategori = 'Tatli'"); // Tablo adı düzeltildi
                            while ($row = $urunler->fetch_assoc()) {
                                echo '<div class="menu-item">';
                                echo '<div class="item-info">';
                                echo '<div class="item-name">' . $row['UrunAdi'] . '</div>';
                                echo '<div class="item-desc">' . $row['Aciklama'] . '</div>';
                                echo '</div>';
                                echo '<div class="item-price">' . $row['Fiyat'] . ' TL</div>';
                                echo '</div>';
                                echo '<form method="post" action="ekle_siparis.php" class="order-form">';
                                echo '<input type="number" name="adet" value="1" min="1" class="form-control" style="width: 80px;">';
                                echo '<select name="masaID" class="form-control table-select">';
                                foreach ($tables as $table) {
                                    echo '<option value="' . $table['MasaID'] . '">' . $table['MasaAdi'] . '</option>';
                                }
                                echo '</select>';
                                echo '<input type="hidden" name="urunID" value="' . $row['UrunID'] . '">';
                                echo '<button type="submit" class="btn btn-order">Sipariş Ver</button>';
                                echo '</form>';
                            }
                            ?>
                        </div>


                            <div class="menu-category" id="akolsuz kokteyiller">
                                <h2 class="category-title">Alkolsuz Kokteyiller</h2>
                                <?php
                                $urunler = $conn->query("SELECT * FROM menuurunu WHERE Kategori = 'Alkolsüz Kokteyl'"); // Tablo adı düzeltildi
                                while ($row = $urunler->fetch_assoc()) {
                                    echo '<div class="menu-item">';
                                    echo '<div class="item-info">';
                                    echo '<div class="item-name">' . $row['UrunAdi'] . '</div>';
                                    echo '<div class="item-desc">' . $row['Aciklama'] . '</div>';
                                    echo '</div>';
                                    echo '<div class="item-price">' . $row['Fiyat'] . ' TL</div>';
                                    echo '</div>';
                                    echo '<form method="post" action="ekle_siparis.php" class="order-form">';
                                    echo '<input type="number" name="adet" value="1" min="1" class="form-control" style="width: 80px;">';
                                    echo '<select name="masaID" class="form-control table-select">';
                                    foreach ($tables as $table) {
                                        echo '<option value="' . $table['MasaID'] . '">' . $table['MasaAdi'] . '</option>';
                                    }
                                    echo '</select>';
                                    echo '<input type="hidden" name="urunID" value="' . $row['UrunID'] . '">';
                                    echo '<button type="submit" class="btn btn-order">Sipariş Ver</button>';
                                    echo '</form>';
                                }
                                ?>
                            </div>

                <div class="menu-category" id="caylar">
                    <h2 class="category-title">Caylar</h2>
                    <?php
                    $urunler = $conn->query("SELECT * FROM menuurunu WHERE Kategori = 'Cay'"); // Tablo adı düzeltildi
                    while ($row = $urunler->fetch_assoc()) {
                        echo '<div class="menu-item">';
                        echo '<div class="item-info">';
                        echo '<div class="item-name">' . $row['UrunAdi'] . '</div>';
                        echo '<div class="item-desc">' . $row['Aciklama'] . '</div>';
                        echo '</div>';
                        echo '<div class="item-price">' . $row['Fiyat'] . ' TL</div>';
                        echo '</div>';
                        echo '<form method="post" action="ekle_siparis.php" class="order-form">';
                        echo '<input type="number" name="adet" value="1" min="1" class="form-control" style="width: 80px;">';
                        echo '<select name="masaID" class="form-control table-select">';
                        foreach ($tables as $table) {
                            echo '<option value="' . $table['MasaID'] . '">' . $table['MasaAdi'] . '</option>';
                        }
                        echo '</select>';
                        echo '<input type="hidden" name="urunID" value="' . $row['UrunID'] . '">';
                        echo '<button type="submit" class="btn btn-order">Sipariş Ver</button>';
                        echo '</form>';
                    }
                    ?>
                </div>
<div class="container">
    <div class="text-center py-4">
        <a href="index.html" class="btn btn-back">Ana Sayfaya Dön</a>
    </div>

</body>
</html>