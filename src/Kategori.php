<?php
session_start();
include_once "includes/config.php";

$kategoriID = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$kategoriAdi = "Kategori Bulunamadı";
$urunler = [];

$conn = connect_database();

if ($conn && $kategoriID > 0) {
    // 1. Kategori Adını Çek (Sayfa başlığı için)
    try {
        $stmtKat = $conn->prepare("SELECT KategoriAdi FROM KATEGORI WHERE KategoriID = ?");
        $stmtKat->execute([$kategoriID]);
        $katSonuc = $stmtKat->fetch(PDO::FETCH_ASSOC);
        
        if ($katSonuc) {
            $kategoriAdi = $katSonuc['KategoriAdi'];
            
            // 2. O Kategoriye Ait Ürünleri Çek
            $sqlUrun = "SELECT * FROM URUN WHERE KategoriID = ? ORDER BY UrunID DESC";
            $stmtUrun = $conn->prepare($sqlUrun);
            $stmtUrun->execute([$kategoriID]);
            $urunler = $stmtUrun->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        echo "Hata: " . $e->getMessage();
    }
}

include_once "includes/header.php";
?>

<div class="container mt-5">
    <div class="row mb-4">
        <div class="col-12 border-bottom pb-2">
            <h3><i class="fa-solid fa-list"></i> <?= htmlspecialchars($kategoriAdi) ?></h3>
            <span class="text-muted"><?= count($urunler) ?> ürün listeleniyor</span>
        </div>
    </div>

    <div class="row">
        <?php if (!empty($urunler)): ?>
            <?php foreach ($urunler as $urun): ?>
                <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="bg-light text-center p-4 text-muted">
                            <i class="fa-solid fa-box-open fa-4x"></i>
                        </div>
                        
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-truncate">
                                <?= htmlspecialchars($urun['UrunAdi']) ?>
                            </h5>
                            
                            <p class="card-text text-success fw-bold">
                                <?= number_format($urun['Fiyat'], 2) ?> ₺
                            </p>
                            
                            <div class="mb-3">
                                <?php if ($urun['StokMiktari'] > 0): ?>
                                    <span class="badge bg-success">Stokta Var</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Tükendi</span>
                                <?php endif; ?>
                            </div>

                            <div class="mt-auto d-grid gap-2">
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
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-warning text-center">
                    <i class="fa-solid fa-circle-exclamation fa-2x mb-3"></i>
                    <h4>Bu kategoride henüz ürün bulunmuyor.</h4>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once "includes/footer.php"; ?>