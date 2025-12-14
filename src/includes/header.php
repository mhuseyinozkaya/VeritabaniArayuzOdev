<?php
include_once "includes/config.php";
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
$kategori = [];
if (isset($conn)) {
  try {
    $sql_cat = "SELECT * FROM KATEGORI";
    $stmt = $conn->prepare($sql_cat);
    $stmt->execute();
    $kategori = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    echo $e->getMessage();
  }
} else {
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
  <meta charset="UTF-16">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle ?? "hepsişurada.com") ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      font-family: Arial, Helvetica, sans-serif;
    }

    .dropdown {
      float: left;
    }

    .dropdown .dropbtn {
      font-size: 16px;
      border: none;
      outline: none;
      color: white;
      padding: 14px 16px;
      background-color: inherit;
      font-family: inherit;
      margin: 0;
    }

    .dropdown-content {
      display: none;
      position: absolute;
      background-color: #f9f9f9;
      min-width: 160px;
      box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
      z-index: 10000;
    }

    .dropdown-content a {
      float: none;
      color: black;
      padding: 12px 16px;
      text-decoration: none;
      display: block;
      text-align: left;
    }

    .dropdown-content a:hover {
      background-color: #ddd;
    }

    .dropdown:hover .dropdown-content {
      display: block;
    }
  </style>
</head>

<body>

  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="index.php">hepsişurada.com</a>
      <div class="dropdown">
        <button class="dropbtn">
          Kategoriler <i class="fa fa-caret-down"></i> </button>

        <div class="dropdown-content">
          <?php if (!empty($kategori)): ?>
            <?php foreach ($kategori as $kat): ?>
              <a href="Kategori.php?id=<?= $kat['KategoriID'] ?>">
                <?= htmlspecialchars($kat['KategoriAdi']) ?>
              </a>
            <?php endforeach; ?>
          <?php else: ?>
            <a href="#">Kategori Bulunamadı</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="ms-auto">
        <?php if (isLogged()): ?>
          <?php if (isset($_SESSION['eposta']) && $_SESSION['eposta'] === 'admin@hepsisurada.com'): ?>
            <a href="Urunler.php" class="btn btn-warning btn-sm me-2 fw-bold">
              <i class="fa-solid fa-screwdriver-wrench"></i> Admin Paneli
              <a href="SiparisLoglari.php" class="btn btn-warning btn-sm">Sipariş Logları</a>
            </a>
          <?php endif; ?>
          <a href="profil.php" class="text-white text-decoration-none me-2 fw-bold">
            <i class="fa-solid fa-user"></i> <?= htmlspecialchars($_SESSION['isim']) ?>
          </a>
          <a href="sepet.php" class="btn btn-outline-light me-2 position-relative">
            <i class="fa-solid fa-cart-shopping"></i> Sepetim

            <?php if (isset($_SESSION['sepet']) && count($_SESSION['sepet']) > 0): ?>
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?= count($_SESSION['sepet']) ?>
              </span>
            <?php endif; ?>
          </a>
          <a href="cikisyap.php" class="btn btn-danger btn-sm">Çıkış Yap</a>
        <?php elseif ($current_page === 'giris.php'): ?>
          <a href="kayit.php" class="btn btn-primary btn-sm">Kayıt ol</a>
        <?php elseif ($current_page === 'kayit.php'): ?>
          <a href="giris.php" class="btn btn-primary btn-sm">Giriş Yap</a>
        <?php else: ?>
          <a href="giris.php" class="btn btn-primary btn-sm">Giriş Yap</a>
          <a href="kayit.php" class="btn btn-primary btn-sm">Kayıt ol</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>