<?php
session_start();
include_once "includes/config.php";

$urunid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$conn = null; // Başlangıç değeri

if (function_exists('connect_database')) {
    $conn = connect_database();
}

$urun = null;
$urunYorumlar = [];

if ($conn && $urunid > 0) {
    try {
        // 1. ÜRÜN BİLGİLERİNİ ÇEK
        $sql_get_product = "SELECT * FROM URUN WHERE UrunID = ?";
        $stmt = $conn->prepare($sql_get_product);
        $stmt->execute([$urunid]);
        $urun = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$urun) {
            redirect_location();
            exit;
        }

        $sql_get_product_review = "SELECT UY.*, M.Ad, M.Soyad 
                                   FROM URUN_YORUM UY 
                                   JOIN MUSTERI M ON UY.MusteriID = M.MusteriID 
                                   WHERE UY.UrunID = ? 
                                   ORDER BY UY.YorumTarihi DESC";
        $stmt = $conn->prepare($sql_get_product_review);
        $stmt->execute([$urunid]);
        $urunYorumlar = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        echo "Hata: " . $e->getMessage();
        exit;
    }
} else {
    redirect_location();
    exit;
}

$pageTitle = $urun['UrunAdi'];
include_once "includes/header.php";
?>

<div class="container mt-5 mb-5">
    
    <div class="row">

        <div class="col-md-6">
            <h1 class="display-5 fw-bold"><?= htmlspecialchars($urun['UrunAdi']) ?></h1>
            
            <div class="mb-3">
                <span class="fs-2 text-primary fw-bold"><?= number_format($urun['Fiyat'], 2) ?> TL</span>
            </div>

            <div class="mb-4">
                <?php if ($urun['StokMiktari'] > 0): ?>
                    <span class="badge bg-success fs-6"><i class="fa-solid fa-check"></i> Stokta Var</span>
                    <span class="text-muted ms-2">(<?= $urun['StokMiktari'] ?> adet kaldı)</span>
                <?php else: ?>
                    <span class="badge bg-danger fs-6"><i class="fa-solid fa-xmark"></i> Tükendi</span>
                <?php endif; ?>
            </div>

            <p class="lead text-muted">
                <?= htmlspecialchars($urun['Aciklama']) ?>
            </p>

            <hr class="my-4">

            <form method="post" action="SepeteEkle.php" class="d-flex align-items-center gap-3">
                <input type="hidden" name="urun_id" value="<?= $urun['UrunID'] ?>">
                
                <div class="input-group" style="width: 130px;">
                    <span class="input-group-text">Adet</span>
                    <input type="number" name="adet" class="form-control text-center" value="1" min="1" max="<?= $urun['StokMiktari'] ?>">
                </div>

                <button type="submit" class="btn btn-primary btn-lg flex-grow-1" <?= $urun['StokMiktari'] < 1 ? 'disabled' : '' ?>>
                    <i class="fa-solid fa-cart-plus me-2"></i> Sepete Ekle
                </button>
            </form>
            
            <div class="row mt-4 text-center text-muted small">
                <div class="col-4">
                    <i class="fa-solid fa-truck fa-2x mb-2"></i><br>Hızlı Kargo
                </div>
                <div class="col-4">
                    <i class="fa-solid fa-shield-halved fa-2x mb-2"></i><br>Güvenli Ödeme
                </div>
                <div class="col-4">
                    <i class="fa-solid fa-rotate-left fa-2x mb-2"></i><br>İade Garantisi
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-5">
        <div class="col-12">
            <h3 class="border-bottom pb-2 mb-4">
                Ürün Yorumları <span class="badge bg-secondary rounded-pill"><?= count($urunYorumlar) ?></span>
            </h3>

            <?php if (!empty($urunYorumlar)): ?>
                <?php foreach ($urunYorumlar as $yorum): ?>
                    <div class="card mb-3 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <h6 class="fw-bold mb-0">
                                        <?= htmlspecialchars($yorum['Ad'] . " " . mb_substr($yorum['Soyad'], 0, 1) . ".") ?>
                                    </h6>
                                    <div class="text-warning small">
                                        <?php 
                                        $puan = isset($yorum['Puan']) ? $yorum['Puan'] : 5;
                                        for($i=0; $i<$puan; $i++) echo '<i class="fa-solid fa-star"></i>';
                                        for($i=$puan; $i<5; $i++) echo '<i class="fa-regular fa-star"></i>';
                                        ?>
                                    </div>
                                </div>
                                <span class="text-muted small">
                                    <?= date("d.m.Y", strtotime($yorum['YorumTarihi'])) ?>
                                </span>
                            </div>
                            <p class="card-text text-secondary">
                                <?= htmlspecialchars($yorum['YorumMetni']) ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-light text-center py-4 border">
                    <i class="fa-regular fa-comment-dots fa-3x text-muted mb-3"></i>
                    <p class="mb-0">Bu ürün için henüz yorum yapılmamış.</p>
                </div>
            <?php endif; ?>
            
        </div>
    </div>

</div>

<?php include_once "includes/footer.php"; ?>