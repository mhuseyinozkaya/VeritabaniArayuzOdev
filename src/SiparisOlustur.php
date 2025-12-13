<?php
session_start();
include_once "includes/config.php";

if (!isLogged()) {
    redirect_location('giris.php');
    exit;
}

// Sepet Boşsa Anasayfaya At
if (!isset($_SESSION['sepet']) || count($_SESSION['sepet']) == 0) {
    header("Location: index.php");
    exit;
}

$conn = connect_database();
$musteriID = $_SESSION['id'];
$hata = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['siparis_tamamla'])) {
    
    $adresID = $_POST['teslimat_adres_id'] ?? null;
    $odemeTipi = $_POST['odeme_tipi'] ?? null;
    
    // Sepet Toplam Tutarı Hesapla
    $toplamTutar = 0;
    foreach ($_SESSION['sepet'] as $urun) {
        $toplamTutar += $urun['fiyat'] * $urun['adet'];
    }

    if (!$adresID) {
        $hata = "Lütfen bir teslimat adresi seçiniz.";
    } elseif (!$odemeTipi) {
        $hata = "Lütfen bir ödeme yöntemi seçiniz.";
    } else {
        try {
            // TRANSACTION BAŞLAT
            $conn->beginTransaction();

            // 1. ADIM: SIPARIS Tablosuna Ekle
            $sqlSiparis = "INSERT INTO SIPARIS (MusteriID, SiparisTarihi, ToplamTutar, TeslimatAdresID) 
                           VALUES (?, GETDATE(), ?, ?)";
            $stmtSiparis = $conn->prepare($sqlSiparis);
            $stmtSiparis->execute([$musteriID, $toplamTutar, $adresID]);
            
            // Oluşan Siparişin ID'sini Al
            $siparisID = $conn->lastInsertId();

            $islemDurumu = ($odemeTipi == 'Kredi Kartı') ? 'Basarili' : 'Beklemede';
            
            $sqlOdeme = "INSERT INTO ODEME (SiparisID, OdemeTipi, Tutar, IslemDurumu) 
                         VALUES (?, ?, ?, ?)";
            $stmtOdeme = $conn->prepare($sqlOdeme);
            $stmtOdeme->execute([$siparisID, $odemeTipi, $toplamTutar, $islemDurumu]);

            // Sipariş kalemi ekleme sorgusu
            $sqlKalem = "INSERT INTO SIPARIS_KALEMI (SiparisID, UrunID, Miktar, BirimFiyat) VALUES (?, ?, ?, ?)";
            $stmtKalem = $conn->prepare($sqlKalem);

            // Stok düşme sorgusu
            $sqlStok = "UPDATE URUN SET StokMiktari = StokMiktari - ? WHERE UrunID = ?";
            $stmtStok = $conn->prepare($sqlStok);

            foreach ($_SESSION['sepet'] as $id => $item) {
                // A) Sipariş Kalemini Ekle
                $stmtKalem->execute([$siparisID, $item['id'], $item['adet'], $item['fiyat']]);
                
                // B) Ürünün Stoğunu Güncelle (Satılan adet kadar düş)
                $stmtStok->execute([$item['adet'], $item['id']]);
            }

            $sqlLog = "INSERT INTO SIPARIS_DURUM_LOGU (SiparisID, DurumAciklamasi, DurumTarihi) VALUES (?, 'Sipariş Alındı', GETDATE())";
            $stmtLog = $conn->prepare($sqlLog);
            $stmtLog->execute([$siparisID]);

            $conn->commit();

            // Sepeti Boşalt
            unset($_SESSION['sepet']);

            // Profil sayfasına yönlendir
            header("Location: profil.php?durum=siparis_basarili");
            exit;

        } catch (PDOException $e) {
            $conn->rollBack(); // Hata varsa işlemleri geri al
            $hata = "Sipariş oluşturulurken hata oluştu: " . $e->getMessage();
        }
    }
}

// Kullanıcının Adreslerini Çek (Dropdown için)
$stmtAdres = $conn->prepare("SELECT * FROM ADRES WHERE MusteriID = ?");
$stmtAdres->execute([$musteriID]);
$adresler = $stmtAdres->fetchAll(PDO::FETCH_ASSOC);

include_once "includes/header.php";
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fa-solid fa-check-circle"></i> Siparişi Tamamla</h4>
                </div>
                <div class="card-body">
                    
                    <?php if ($hata): ?>
                        <div class="alert alert-danger"><?= $hata ?></div>
                    <?php endif; ?>

                    <form method="post">
                        
                        <h5 class="text-secondary border-bottom pb-2 mb-3">1. Teslimat Adresi Seçin</h5>
                        <?php if (count($adresler) > 0): ?>
                            <div class="mb-4">
                                <?php foreach ($adresler as $adr): ?>
                                    <div class="form-check mb-2 p-3 border rounded">
                                        <input class="form-check-input" type="radio" name="teslimat_adres_id" id="adr<?= $adr['AdresID'] ?>" value="<?= $adr['AdresID'] ?>" required>
                                        <label class="form-check-label w-100" for="adr<?= $adr['AdresID'] ?>">
                                            <strong><?= htmlspecialchars($adr['AdresBaslik']) ?></strong>
                                            <span class="badge bg-light text-dark float-end"><?= htmlspecialchars($adr['AdresTipi']) ?></span>
                                            <br>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($adr['AdresMetni']) ?> - <?= htmlspecialchars($adr['Sehir']) ?>
                                            </small>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                Kayıtlı adresiniz yok. <a href="AdresEkle.php" class="fw-bold">Buraya tıklayarak adres ekleyin.</a>
                            </div>
                        <?php endif; ?>

                        <h5 class="text-secondary border-bottom pb-2 mb-3 mt-4">2. Ödeme Yöntemi</h5>
                        <div class="mb-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check p-3 border rounded h-100">
                                        <input class="form-check-input" type="radio" name="odeme_tipi" id="kredi_karti" value="Kredi Kartı" required>
                                        <label class="form-check-label fw-bold" for="kredi_karti">
                                            <i class="fa-regular fa-credit-card text-primary fa-lg mb-2 d-block"></i>
                                            Kredi Kartı
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check p-3 border rounded h-100">
                                        <input class="form-check-input" type="radio" name="odeme_tipi" id="havale" value="Havale/EFT">
                                        <label class="form-check-label fw-bold" for="havale">
                                            <i class="fa-solid fa-money-bill-transfer text-success fa-lg mb-2 d-block"></i>
                                            Havale / EFT
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check p-3 border rounded h-100">
                                        <input class="form-check-input" type="radio" name="odeme_tipi" id="kapida" value="Kapıda Ödeme">
                                        <label class="form-check-label fw-bold" for="kapida">
                                            <i class="fa-solid fa-truck text-warning fa-lg mb-2 d-block"></i>
                                            Kapıda Ödeme
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-light border">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Sepetinizdeki Ürün Sayısı:</span>
                                <strong><?= count($_SESSION['sepet']) ?> Adet</strong>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center fs-5">
                                <span>Toplam Tutar:</span>
                                <?php 
                                    $toplam = 0;
                                    foreach($_SESSION['sepet'] as $u) $toplam += $u['fiyat'] * $u['adet'];
                                ?>
                                <strong class="text-success"><?= number_format($toplam, 2) ?> ₺</strong>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="siparis_tamamla" class="btn btn-success btn-lg" <?= (count($adresler) == 0) ? 'disabled' : '' ?>>
                                <i class="fa-solid fa-check"></i> Siparişi Onayla ve Bitir
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary">Alışverişe Dön</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "includes/footer.php"; ?>