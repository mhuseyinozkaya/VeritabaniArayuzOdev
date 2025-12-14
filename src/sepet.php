<?php
session_start();
include_once "includes/config.php";
include_once "includes/header.php";

if (isset($_GET['sil'])) {
    $silID = (int)$_GET['sil'];
    if (isset($_SESSION['sepet'][$silID])) {
        unset($_SESSION['sepet'][$silID]); // Diziden uçur
    }
    echo "<script>window.location.href='sepet.php';</script>";
    exit;
}

if (isset($_GET['bosalt'])) {
    unset($_SESSION['sepet']);
    echo "<script>window.location.href='Sepet.php';</script>";
    exit;
}

$sepetBos = empty($_SESSION['sepet']);
$genelToplam = 0;
?>

<div class="container mt-5">
    <h2 class="mb-4"><i class="fa-solid fa-cart-shopping"></i> Alışveriş Sepetim</h2>

    <?php if ($sepetBos): ?>

        <div class="alert alert-warning text-center py-5">
            <i class="fa-solid fa-basket-shopping fa-4x mb-3 text-muted"></i>
            <h4>Sepetiniz şu an boş.</h4>
            <p>Hemen alışverişe başlayıp harika ürünleri sepetinize ekleyebilirsiniz.</p>
            <a href="index.php" class="btn btn-primary mt-3">Alışverişe Başla</a>
        </div>

    <?php else: ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="table-responsive">
                    <table class="table table-hover border">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50%">Ürün Adı</th>
                                <th style="width: 15%" class="text-center">Adet</th>
                                <th style="width: 15%" class="text-end">Birim Fiyat</th>
                                <th style="width: 15%" class="text-end">Toplam</th>
                                <th style="width: 5%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['sepet'] as $id => $urun): ?>
                                <?php
                                $satirToplami = $urun['fiyat'] * $urun['adet'];
                                $genelToplam += $satirToplami;
                                ?>
                                <tr>
                                    <td class="align-middle">
                                        <div class="fw-bold"><?= htmlspecialchars($urun['ad']) ?></div>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="badge bg-secondary"><?= $urun['adet'] ?></span>
                                    </td>
                                    <td class="align-middle text-end">
                                        <?= number_format($urun['fiyat'], 2) ?> ₺
                                    </td>
                                    <td class="align-middle text-end fw-bold text-success">
                                        <?= number_format($satirToplami, 2) ?> ₺
                                    </td>
                                    <td class="align-middle text-center">
                                        <a href="sepet.php?sil=<?= $id ?>"
                                            class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('Bu ürünü sepetten çıkarmak istiyor musunuz?')">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between mt-3">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fa fa-arrow-left"></i> Alışverişe Dön
                    </a>
                    <a href="sepet.php?bosalt=1" class="btn btn-outline-danger" onclick="return confirm('Tüm sepeti boşaltmak istediğinize emin misiniz?')">
                        <i class="fa fa-trash-can"></i> Sepeti Boşalt
                    </a>
                </div>
            </div>

            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Sipariş Özeti</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span>Ara Toplam</span>
                            <span><?= number_format($genelToplam, 2) ?> ₺</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 text-success">
                            <span>Kargo</span>
                            <span>Bedava</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <strong>Genel Toplam</strong>
                            <strong class="fs-4 text-primary"><?= number_format($genelToplam, 2) ?> ₺</strong>
                        </div>

                        <?php if (isLogged()): ?>
                            <a href="SiparisOlustur.php" class="btn btn-success w-100 py-3 fw-bold">
                                SEPETİ ONAYLA <i class="fa fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <div class="alert alert-info small">
                                Satın alma işlemini tamamlamak için lütfen giriş yapınız.
                            </div>
                            <a href="giris.php" class="btn btn-warning w-100">
                                Giriş Yap / Kayıt Ol
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php include_once "includes/footer.php"; ?>