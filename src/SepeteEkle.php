<?php
session_start();
include_once "includes/config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$urunID = isset($_POST['urun_id']) ? (int)$_POST['urun_id'] : 0;
$adet   = isset($_POST['adet']) ? (int)$_POST['adet'] : 1;

if ($urunID <= 0) {
    die("Geçersiz ürün ID");
}

$conn = connect_database();
$stmt = $conn->prepare("SELECT UrunID, UrunAdi, Fiyat, StokMiktari FROM URUN WHERE UrunID = ?");
$stmt->execute([$urunID]);
$urun = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$urun) {
    die("Ürün bulunamadı.");
}

if ($urun['StokMiktari'] < $adet) {
    // Basit bir JS uyarısı ile geri gönder
    echo "<script>alert('Yetersiz Stok! Maksimum alabileceğiniz adet: " . $urun['StokMiktari'] . "'); window.history.back();</script>";
    exit;
}

//SEPET SESSION OLUŞTURMA
if (!isset($_SESSION['sepet'])) {
    $_SESSION['sepet'] = [];
}

if (isset($_SESSION['sepet'][$urunID])) {
    // Varsa üzerine ekle
    $mevcutAdet = $_SESSION['sepet'][$urunID]['adet'];
    $yeniAdet = $mevcutAdet + $adet;

    // Toplam miktar stoğu geçmesin
    if ($yeniAdet > $urun['StokMiktari']) {
        $yeniAdet = $urun['StokMiktari'];
    }
    
    $_SESSION['sepet'][$urunID]['adet'] = $yeniAdet;
} else {
    // Yoksa yeni ekle
    $_SESSION['sepet'][$urunID] = [
        'id'    => $urun['UrunID'],
        'ad'    => $urun['UrunAdi'],
        'fiyat' => $urun['Fiyat'],
        'adet'  => $adet,
        'stok'  => $urun['StokMiktari'] // İleride sepet sayfasında kontrol için lazım olabilir
    ];
}

if (isset($_SERVER['HTTP_REFERER'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
} else {
    header("Location: sepet.php");
}
exit;
?>