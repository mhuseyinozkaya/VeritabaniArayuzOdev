<?php
session_start();
include_once "includes/config.php";
require_once "includes/init.php";

$urunler = [];
$conn = connect_database();

if ($conn) {
    $sql = "SELECT * FROM URUN";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $urunler = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include_once "includes/header.php";
?>
<div class="container mt-5">

    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="display-5">Öne Çıkan Ürünler</h1>
            <p class="lead text-muted">En yeni ürünler, en uygun fiyatlarla.</p>
        </div>
    </div>

    <div class="row">
        <?php if (count($urunler) > 0): ?>
            <?php foreach ($urunler as $urun): ?>
                <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
                    <div class="card h-100 shadow-sm">

                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-truncate" title="<?= htmlspecialchars($urun['UrunAdi']) ?>">
                                <?= htmlspecialchars($urun['UrunAdi']) ?>
                            </h5>

                            <p class="card-text small text-muted">
                                <?= htmlspecialchars(mb_substr($urun['Aciklama'] ?? '', 0, 50)) ?>...
                            </p>

                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fs-5 fw-bold text-primary"><?= number_format($urun['Fiyat'], 2) ?> ₺</span>

                                    <?php if ($urun['StokMiktari'] > 0): ?>
                                        <span class="badge bg-success">Stokta Var</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Tükendi</span>
                                    <?php endif; ?>
                                </div>

                                <div class="d-grid gap-2">
                                    <a href="UrunDetay.php?id=<?= $urun['UrunID'] ?>" class="btn btn-outline-dark btn-sm">İncele</a>

                                    <form method="post" action="SepeteEkle.php">
                                        <input type="hidden" name="urun_id" value="<?= $urun['UrunID'] ?>">
                                        <button type="submit" class="btn btn-primary btn-sm w-100" <?= $urun['StokMiktari'] < 1 ? 'disabled' : '' ?>>
                                            <i class="fa-solid fa-cart-plus"></i> Sepete Ekle
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-warning text-center">
                    <h4>Henüz ürün eklenmemiş.</h4>
                    <p>Lütfen veritabanına ürün girişi yapınız.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include_once "includes/footer.php" ?>