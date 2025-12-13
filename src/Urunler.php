<?php
session_start();
include_once 'includes/config.php';

if (!isLogged()) {
    redirect_location('giris.php');
}

if ($_SESSION["eposta"] !== "admin@hepsisurada.com") {
    redirect_location();
}

$conn = connect_database();

if (isset($_GET['sil_id'])) {
    $silID = (int)$_GET['sil_id'];
    try {
        $sql = "DELETE FROM URUN WHERE UrunID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$silID]);

        echo "<div class='alert alert-success'>Ürün başarıyla silindi.</div>";
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Silme hatası: Bu ürün siparişlerde kullanılıyor olabilir.</div>";
    }
}

$sql = "SELECT U.*, K.KategoriAdi, M.MarkaAdi 
        FROM URUN U
        LEFT JOIN KATEGORI K ON U.KategoriID = K.KategoriID
        LEFT JOIN MARKA M ON U.MarkaID = M.MarkaID
        ORDER BY U.UrunID DESC";
$stmt = $conn->query($sql);
$urunler = $stmt->fetchAll(PDO::FETCH_ASSOC);

include_once 'includes/header.php';
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Ürün Yönetimi</h2>
        <a href="UrunForm.php" class="btn btn-success"><i class="fa fa-plus"></i> Yeni Ürün Ekle</a>
    </div>

    <table class="table table-bordered table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Ürün Adı</th>
                <th>Marka</th>
                <th>Kategori</th>
                <th>Fiyat</th>
                <th>Stok</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($urunler as $row): ?>
                <tr>
                    <td><?= $row['UrunID'] ?></td>
                    <td><?= htmlspecialchars($row['UrunAdi']) ?></td>
                    <td><?= htmlspecialchars($row['MarkaAdi']) ?></td>
                    <td><?= htmlspecialchars($row['KategoriAdi']) ?></td>
                    <td><?= number_format($row['Fiyat'], 2) ?> ₺</td>
                    <td>
                        <span class="badge <?= $row['StokMiktari'] < 5 ? 'bg-danger' : 'bg-success' ?>">
                            <?= $row['StokMiktari'] ?>
                        </span>
                    </td>
                    <td>
                        <a href="UrunForm.php?id=<?= $row['UrunID'] ?>" class="btn btn-primary btn-sm">
                            <i class="fa fa-edit"></i> Düzenle
                        </a>
                        <a href="Urunler.php?sil_id=<?= $row['UrunID'] ?>"
                            class="btn btn-danger btn-sm"
                            onclick="return confirm('Bu ürünü silmek istediğinize emin misiniz?');">
                            <i class="fa fa-trash"></i> Sil
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include_once "includes/footer.php"; ?>