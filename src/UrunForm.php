<?php
session_start();
include_once 'includes/config.php';
if (!isLogged()) { redirect_location('giris.php'); }

$conn = connect_database();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;

// Form Gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ad = $_POST['UrunAdi'];
    $aciklama = $_POST['Aciklama'];
    $fiyat = $_POST['Fiyat'];
    $stok = $_POST['StokMiktari'];
    $kategoriID = $_POST['KategoriID'];
    $markaID = $_POST['MarkaID'];

    try {
        if ($isEdit) {
            // GÜNCELLEME (UPDATE)
            $sql = "UPDATE URUN SET UrunAdi=?, Aciklama=?, Fiyat=?, StokMiktari=?, KategoriID=?, MarkaID=? WHERE UrunID=?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$ad, $aciklama, $fiyat, $stok, $kategoriID, $markaID, $id]);
        } else {
            // EKLEME (CREATE)
            $sql = "INSERT INTO URUN (UrunAdi, Aciklama, Fiyat, StokMiktari, KategoriID, MarkaID) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$ad, $aciklama, $fiyat, $stok, $kategoriID, $markaID]);
        }
        redirect_location('Urunler.php');
    } catch (PDOException $e) {
        $error = "Hata: " . $e->getMessage();
    }
}

// Düzenleme ise verileri çek
$urun = ['UrunAdi'=>'', 'Aciklama'=>'', 'Fiyat'=>'', 'StokMiktari'=>'', 'KategoriID'=>'', 'MarkaID'=>''];
if ($isEdit) {
    $stmt = $conn->prepare("SELECT * FROM URUN WHERE UrunID = ?");
    $stmt->execute([$id]);
    $urun = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Dropdownlar için verileri çek
$kategoriler = $conn->query("SELECT * FROM KATEGORI")->fetchAll(PDO::FETCH_ASSOC);
$markalar = $conn->query("SELECT * FROM MARKA")->fetchAll(PDO::FETCH_ASSOC);
include_once 'includes/header.php';
?>

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4><?= $isEdit ? 'Ürün Güncelle' : 'Yeni Ürün Ekle' ?></h4>
        </div>
        <div class="card-body">
            <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
            
            <form method="post">
                <div class="mb-3">
                    <label>Ürün Adı</label>
                    <input type="text" name="UrunAdi" class="form-control" value="<?= htmlspecialchars($urun['UrunAdi']) ?>" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Fiyat</label>
                        <input type="number" step="0.01" name="Fiyat" class="form-control" value="<?= $urun['Fiyat'] ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Stok Miktarı</label>
                        <input type="number" name="StokMiktari" class="form-control" value="<?= $urun['StokMiktari'] ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Kategori</label>
                        <select name="KategoriID" class="form-select" required>
                            <option value="">Seçiniz...</option>
                            <?php foreach($kategoriler as $kat): ?>
                                <option value="<?= $kat['KategoriID'] ?>" <?= $kat['KategoriID'] == $urun['KategoriID'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kat['KategoriAdi']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Marka</label>
                        <select name="MarkaID" class="form-select" required>
                            <option value="">Seçiniz...</option>
                            <?php foreach($markalar as $m): ?>
                                <option value="<?= $m['MarkaID'] ?>" <?= $m['MarkaID'] == $urun['MarkaID'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['MarkaAdi']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label>Açıklama</label>
                    <textarea name="Aciklama" class="form-control" rows="4"><?= htmlspecialchars($urun['Aciklama']) ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100"><?= $isEdit ? 'Güncelle' : 'Kaydet' ?></button>
            </form>
        </div>
    </div>
</div>
<?php include_once "includes/footer.php"; ?>