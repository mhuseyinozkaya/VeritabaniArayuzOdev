<?php
include_once "config.php";

try {
    $conn = connect_database("master");
    // ADIM 2: Veritabanı var mı diye 'sys.databases' tablosundan kontrol ediyoruz.
    $sqlCheck = "SELECT name FROM master.sys.databases WHERE name = 'test_PROJEODEV'";
    $stmt = $conn->query($sqlCheck);

    // Eğer veritabanı YOKSA (fetch false dönerse)
    if (!$stmt->fetch()) {
        // Veritabanını oluştur
        $conn->exec("CREATE DATABASE test_PROJEODEV");
        echo "Veritabanı başarıyla oluşturuldu!<br>";

        // ADIM 3: Şimdi yeni oluşturduğumuz veritabanının içine girmeliyiz.
        // Bağlantıyı koparmadan 'USE' komutuyla veritabanını seçiyoruz.
        $conn->exec("USE test_PROJEODEV");

        // Dosyayı oku
        $sqlFile = __DIR__ . '/../../data/database.sql';
        if (file_exists($sqlFile)) {
            $sqlContent = file_get_contents($sqlFile);
            $conn->exec($sqlContent);
            echo "Tablolar ve veriler başarıyla içeri aktarıldı!";
        } else {
            echo "Hata: SQL dosyası bulunamadı ($sqlFile)";
        }
    } else {
        echo "Veritabanı zaten mevcut, işlem yapılmadı.";
    } ?>
    <a href="../index.php">Anasayfaya dön</a>
    <?php exit;
} catch (PDOException $e) {
    die("İşlem Hatası: " . $e->getMessage());
}
?>