<?php
session_start();
include_once "includes/config.php";

if (!isLogged()) {
    redirect_location('giris.php');
    exit;
}

$adminEmail = "admin@hepsisurada.com";

if ($_SESSION['eposta'] !== $adminEmail) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Bu sayfaya erişim yetkiniz yok!</div></div>";
    exit;
}

$conn = connect_database();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['durum_guncelle'])) {
    $siparisID = $_POST['siparis_id'];
    $yeniDurum = $_POST['yeni_durum'];

    if (!empty($siparisID) && !empty($yeniDurum)) {
        try {
            $sqlEkle = "INSERT INTO SIPARIS_DURUM_LOGU (SiparisID, DurumAciklamasi, DurumTarihi) VALUES (?, ?, GETDATE())";
            $stmtEkle = $conn->prepare($sqlEkle);
            $stmtEkle->execute([$siparisID, $yeniDurum]);
            $mesaj = "Sipariş #$siparisID durumu güncellendi.";
        } catch (PDOException $e) {
            $hata = "Hata oluştu: " . $e->getMessage();
        }
    }
}

$aramaSiparisID = isset($_GET['s']) ? $_GET['s'] : "";

// Sorguyu Hazırla: Loglar + Müşteri Adı + Sipariş Tutarı
$sql = "
    SELECT 
        L.LogID, 
        L.SiparisID, 
        L.DurumAciklamasi, 
        L.DurumTarihi,
        M.Ad, 
        M.Soyad,
        S.ToplamTutar
    FROM SIPARIS_DURUM_LOGU L
    JOIN SIPARIS S ON L.SiparisID = S.SiparisID
    JOIN MUSTERI M ON S.MusteriID = M.MusteriID
";

// Eğer arama yapıldıysa filtrele
if ($aramaSiparisID != "") {
    $sql .= " WHERE L.SiparisID = :sid";
}

$sql .= " ORDER BY L.DurumTarihi DESC"; // En yeniden eskiye

$stmt = $conn->prepare($sql);

if ($aramaSiparisID != "") {
    $stmt->execute(['sid' => $aramaSiparisID]);
} else {
    $stmt->execute();
}

$loglar = $stmt->fetchAll(PDO::FETCH_ASSOC);

include_once "includes/header.php";
?>

<div class="container mt-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-danger"><i class="fa-solid fa-user-shield"></i> Yönetici Paneli</h2>
            <p class="text-muted">Sipariş Durum Geçmişi ve Güncelleme Ekranı</p>
        </div>
        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#durumGuncelleModal">
                <i class="fa-solid fa-pen-to-square"></i> Hızlı Durum Güncelle
            </button>
        </div>
    </div>

    <?php if (isset($mesaj)): ?>
        <div class="alert alert-success"><?= $mesaj ?></div>
    <?php endif; ?>
    <?php if (isset($hata)): ?>
        <div class="alert alert-danger"><?= $hata ?></div>
    <?php endif; ?>

    <div class="card mb-4 bg-light">
        <div class="card-body">
            <form method="get" class="row g-2 align-items-center">
                <div class="col-auto">
                    <label for="searchID" class="col-form-label fw-bold">Sipariş No İle Ara:</label>
                </div>
                <div class="col-auto">
                    <input type="number" name="s" id="searchID" class="form-control" placeholder="Örn: 102" value="<?= htmlspecialchars($aramaSiparisID) ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-secondary">Filtrele</button>
                    <?php if ($aramaSiparisID): ?>
                        <a href="AdminSiparisLoglari.php" class="btn btn-outline-secondary">Temizle</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <i class="fa-solid fa-list"></i> Hareket Kayıtları
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-secondary">
                    <tr>
                        <th>Tarih</th>
                        <th>Sipariş No</th>
                        <th>Müşteri</th>
                        <th>Durum Açıklaması</th>
                        <th class="text-end">Sipariş Tutarı</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($loglar) > 0): ?>
                        <?php foreach ($loglar as $log): ?>
                            <?php
                            // Renklendirme Mantığı
                            $durum = $log['DurumAciklamasi'];
                            $renk = "secondary";
                            $ikon = "fa-circle-info";

                            if (stripos($durum, 'Teslim') !== false) {
                                $renk = "success";
                                $ikon = "fa-check-circle";
                            } elseif (stripos($durum, 'Kargo') !== false) {
                                $renk = "info text-dark";
                                $ikon = "fa-truck";
                            } elseif (stripos($durum, 'İptal') !== false) {
                                $renk = "danger";
                                $ikon = "fa-times-circle";
                            } elseif (stripos($durum, 'Ödeme') !== false) {
                                $renk = "primary";
                                $ikon = "fa-credit-card";
                            } elseif (stripos($durum, 'Hazır') !== false) {
                                $renk = "warning text-dark";
                                $ikon = "fa-box";
                            }
                            ?>
                            <tr>
                                <td style="width: 180px;">
                                    <i class="fa-regular fa-clock text-muted"></i>
                                    <?= date("d.m.Y H:i", strtotime($log['DurumTarihi'])) ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border border-secondary">#<?= $log['SiparisID'] ?></span>
                                </td>
                                <td>
                                    <i class="fa-regular fa-user"></i>
                                    <?= htmlspecialchars($log['Ad'] . " " . $log['Soyad']) ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $renk ?> p-2">
                                        <i class="fa-solid <?= $ikon ?>"></i> <?= htmlspecialchars($durum) ?>
                                    </span>
                                </td>
                                <td class="text-end fw-bold">
                                    <?= number_format($log['ToplamTutar'], 2) ?> ₺
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Kayıt bulunamadı.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="durumGuncelleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Sipariş Durumu Güncelle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Sipariş Numarası (ID):</label>
                        <input type="number" name="siparis_id" class="form-control" required placeholder="Örn: 1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Yeni Durum:</label>
                        <select name="yeni_durum" class="form-select" required>
                            <option value="Sipariş Hazırlanıyor">📦 Sipariş Hazırlanıyor</option>
                            <option value="Kargoya Verildi">🚛 Kargoya Verildi</option>
                            <option value="Teslim Edildi">✅ Teslim Edildi</option>
                            <option value="İptal Edildi">❌ İptal Edildi</option>
                            <option value="İade Süreci Başladı">🔄 İade Süreci Başladı</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    <button type="submit" name="durum_guncelle" class="btn btn-primary">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once "includes/footer.php"; ?>