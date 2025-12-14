<?php
session_start();
include_once "includes/config.php";

if (!isLogged()) {
    redirect_location('giris.php');
    exit;
}

$conn = connect_database();
$musteriID = $_SESSION['id']; 

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['yorum_yap'])) {
    $urunID = (int)$_POST['urun_id'];
    $puan = (int)$_POST['puan'];
    $yorumMetni = trim($_POST['yorum_metni']);

    if ($puan < 1 || $puan > 5) {
        $hata = "Lütfen 1 ile 5 arasında bir puan seçiniz.";
    } elseif (empty($yorumMetni)) {
        $hata = "Lütfen bir yorum metni yazınız.";
    } else {
        try {
            // URUN_YORUM Tablosuna Ekleme
            $sqlYorum = "INSERT INTO URUN_YORUM (MusteriID, UrunID, Puan, YorumMetni, YorumTarihi) 
                         VALUES (?, ?, ?, ?, GETDATE())";

            $stmt = $conn->prepare($sqlYorum);
            $stmt->execute([$musteriID, $urunID, $puan, $yorumMetni]);
            
            header("Location: profil.php?durum=yorum_basarili");
            exit;
        } catch (PDOException $e) {
            $hata = "Yorum eklenirken bir hata oluştu: " . $e->getMessage();
        }
    }
}

if (isset($_GET['sil_adres_id'])) {
    $silID = (int)$_GET['sil_adres_id'];
    try {
        $sqlSil = "DELETE FROM ADRES WHERE AdresID = ? AND MusteriID = ?";
        $stmtSil = $conn->prepare($sqlSil);
        $stmtSil->execute([$silID, $musteriID]);
        header("Location: profil.php?durum=adres_silindi");
        exit;
    } catch (PDOException $e) {
        $hata = "Bu adres silinemez çünkü bir siparişte kullanılıyor.";
    }
}

$stmtUser = $conn->prepare("SELECT * FROM MUSTERI WHERE MusteriID = ?");
$stmtUser->execute([$musteriID]);
$kullanici = $stmtUser->fetch(PDO::FETCH_ASSOC);

// Siparişleri Çek
$sqlSiparis = "
    SELECT S.SiparisID, S.SiparisTarihi, S.ToplamTutar, 
           A.AdresBaslik, A.Sehir, A.AdresMetni,
        (SELECT TOP 1 DurumAciklamasi FROM SIPARIS_DURUM_LOGU L 
         WHERE L.SiparisID = S.SiparisID ORDER BY L.DurumTarihi DESC) AS SonDurum
    FROM SIPARIS S
    LEFT JOIN ADRES A ON S.TeslimatAdresID = A.AdresID
    WHERE S.MusteriID = ?
    ORDER BY S.SiparisTarihi DESC
";
$stmtSiparis = $conn->prepare($sqlSiparis);
$stmtSiparis->execute([$musteriID]);
$siparisler = $stmtSiparis->fetchAll(PDO::FETCH_ASSOC);

// Adresleri Çek
$sqlAdres = "SELECT * FROM ADRES WHERE MusteriID = ? ORDER BY AdresID DESC";
$stmtAdres = $conn->prepare($sqlAdres);
$stmtAdres->execute([$musteriID]);
$adresler = $stmtAdres->fetchAll(PDO::FETCH_ASSOC);

include_once "includes/header.php";
?>

<div class="container mt-5">
    <div class="row">
        
        <div class="col-md-3 mb-4">
            <div class="card shadow-sm text-center p-3">
                <div class="mb-3">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px; font-size: 24px;">
                        <?= strtoupper(substr($kullanici['Ad'], 0, 1) . substr($kullanici['Soyad'], 0, 1)) ?>
                    </div>
                </div>
                <h5><?= htmlspecialchars($kullanici['Ad'] . " " . $kullanici['Soyad']) ?></h5>
                <p class="text-muted small"><?= htmlspecialchars($kullanici['Eposta']) ?></p>
                <hr>
                <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
                    <button class="nav-link active text-start" data-bs-toggle="pill" data-bs-target="#siparislerim">
                        <i class="fa-solid fa-box-open me-2"></i> Siparişlerim
                    </button>
                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#adreslerim">
                        <i class="fa-solid fa-map-location-dot me-2"></i> Adreslerim
                    </button>
                    <a href="cikisyap.php" class="nav-link text-start text-danger">
                        <i class="fa-solid fa-right-from-bracket me-2"></i> Çıkış Yap
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            
            <?php if (isset($hata)): ?>
                <div class="alert alert-danger"><?= $hata ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['durum'])): ?>
                <?php if ($_GET['durum'] == 'yorum_basarili'): ?>
                    <div class="alert alert-success"><i class="fa-solid fa-check"></i> Yorumunuz başarıyla kaydedildi!</div>
                <?php elseif ($_GET['durum'] == 'adres_silindi'): ?>
                    <div class="alert alert-success">Adres başarıyla silindi.</div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="tab-content" id="v-pills-tabContent">
                
                <div class="tab-pane fade show active" id="siparislerim">
                    <h4 class="mb-4">Sipariş Geçmişim</h4>

                    <?php if (count($siparisler) > 0): ?>
                        <div class="accordion" id="siparisAkordiyon">
                            <?php foreach ($siparisler as $index => $sip): ?>
                                <?php 
                                    $durumMetni = $sip['SonDurum'] ?? 'İşleme Alındı';
                                    $badgeClass = stripos($durumMetni, 'Teslim') !== false ? 'bg-success' : 'bg-secondary';
                                    
                                    // SİPARİŞ KALEMLERİNİ ÇEK
                                    $sqlKalem = "SELECT SK.Miktar, SK.BirimFiyat, U.UrunAdi, U.UrunID 
                                                 FROM [dbo].[SIPARIS_KALEMI] SK 
                                                 JOIN URUN U ON SK.UrunID = U.UrunID 
                                                 WHERE SK.SiparisID = ?";
                                    $stmtKalem = $conn->prepare($sqlKalem);
                                    $stmtKalem->execute([$sip['SiparisID']]);
                                    $kalemler = $stmtKalem->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                
                                <div class="accordion-item shadow-sm mb-3 border">
                                    <h2 class="accordion-header" id="heading<?= $sip['SiparisID'] ?>">
                                        <button class="accordion-button <?= $index !== 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $sip['SiparisID'] ?>">
                                            <div class="d-flex justify-content-between w-100 pe-3">
                                                <span><span class="fw-bold">#<?= $sip['SiparisID'] ?></span> - <?= date("d.m.Y", strtotime($sip['SiparisTarihi'])) ?></span>
                                                <span><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($durumMetni) ?></span> <span class="fw-bold text-success ms-2"><?= number_format($sip['ToplamTutar'], 2) ?> ₺</span></span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapse<?= $sip['SiparisID'] ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#siparisAkordiyon">
                                        <div class="accordion-body bg-light">
                                            <div class="table-responsive bg-white rounded border">
                                                <table class="table table-sm mb-0 align-middle">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Ürün Adı</th>
                                                            <th class="text-center">Adet</th>
                                                            <th class="text-end">Tutar</th>
                                                            <th class="text-center">İşlem</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($kalemler as $kalem): ?>
                                                            <tr>
                                                                <td>
                                                                    <a href="UrunDetay.php?id=<?= $kalem['UrunID'] ?>" class="text-decoration-none text-dark">
                                                                        <?= htmlspecialchars($kalem['UrunAdi']) ?>
                                                                    </a>
                                                                </td>
                                                                <td class="text-center"><?= $kalem['Miktar'] ?></td>
                                                                <td class="text-end fw-bold"><?= number_format($kalem['Miktar'] * $kalem['BirimFiyat'], 2) ?> ₺</td>
                                                                <td class="text-center">
                                                                    <button type="button" class="btn btn-warning btn-sm text-dark btn-yorum-yap" 
                                                                            data-bs-toggle="modal" 
                                                                            data-bs-target="#yorumModal"
                                                                            data-urunid="<?= $kalem['UrunID'] ?>"
                                                                            data-urunadi="<?= htmlspecialchars($kalem['UrunAdi']) ?>">
                                                                        <i class="fa-regular fa-star"></i> Yorum Yap
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light border text-center py-5">
                            <p>Henüz siparişiniz bulunmuyor.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="adreslerim">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>Adreslerim</h4>
                        <a href="AdresEkle.php" class="btn btn-success btn-sm"><i class="fa fa-plus"></i> Yeni Adres</a>
                    </div>
                    <div class="row">
                        <?php foreach ($adresler as $adr): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100 shadow-sm border-start border-4 border-info">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="fw-bold"><?= htmlspecialchars($adr['AdresBaslik']) ?></h6>
                                            <a href="profil.php?sil_adres_id=<?= $adr['AdresID'] ?>" class="text-danger" onclick="return confirm('Silmek istediğinize emin misiniz?')"><i class="fa fa-trash"></i></a>
                                        </div>
                                        <p class="card-text text-muted small mt-2">
                                            <?= htmlspecialchars($adr['AdresMetni']) ?> <br>
                                            <strong><?= htmlspecialchars($adr['Sehir']) ?></strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="yorumModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fa-solid fa-star"></i> Ürünü Değerlendir</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="urun_id" id="modalUrunID">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Ürün:</label>
                        <input type="text" class="form-control-plaintext" id="modalUrunAdi" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Puanınız:</label>
                        <select name="puan" class="form-select" required>
                            <option value="5" selected>⭐⭐⭐⭐⭐ (5 - Çok İyi)</option>
                            <option value="4">⭐⭐⭐⭐ (4 - İyi)</option>
                            <option value="3">⭐⭐⭐ (3 - Orta)</option>
                            <option value="2">⭐⭐ (2 - Kötü)</option>
                            <option value="1">⭐ (1 - Çok Kötü)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Yorumunuz:</label>
                        <textarea name="yorum_metni" class="form-control" rows="4" placeholder="Ürün hakkında düşüncelerinizi yazın..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="yorum_yap" class="btn btn-primary">Yorumu Gönder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var yorumButtons = document.querySelectorAll('.btn-yorum-yap');
        var modalUrunID = document.getElementById('modalUrunID');
        var modalUrunAdi = document.getElementById('modalUrunAdi');

        yorumButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var uID = this.getAttribute('data-urunid');
                var uAdi = this.getAttribute('data-urunadi');
                
                modalUrunID.value = uID;
                modalUrunAdi.value = uAdi;
            });
        });
    });
</script>

<?php include_once "includes/footer.php"; ?>