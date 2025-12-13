<?php
include_once "config.php";
try {
    $conn = connect_database();
    $check = $conn->query("SELECT TOP 1 * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'MUSTERI'");
    
    if (!$check->fetch()) {
        $conn->exec("CREATE DATABASE test_PROJEODEV;");
        echo "Veritabanı oluşturuldu!";

        // Dosyayı tamamen oku
        $sql = file_get_contents(__DIR__ . '/../database/database.sql');
        
        // Tek hamlede çalıştır
        $conn->exec($sql);
        echo "Tablolar oluşturuldu!";
    }
} catch (PDOException $e) {
    die("SQL Hatası: " . $e->getMessage());
}