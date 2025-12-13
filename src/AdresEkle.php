<?php
session_start();
include_once "includes/config.php";

if (!isLogged()) {
    redirect_location('giris.php');
    exit;
}

$conn = connect_database();
$mesaj = "";
$hata = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $baslik = trim($_POST['baslik']);
    $sehir = trim($_POST['sehir']);
    $adresMetni = trim($_POST['adres_metni']);
    $adresTipi = $_POST['adres_tipi']; // Teslimat veya Fatura
    $musteriID = $_SESSION['id']; // Oturumdaki kullanıcı ID'si

    // Boş alan kontrolü
    if (empty($baslik) || empty($sehir) || empty($adresMetni)) {
        $hata = "Lütfen tüm alanları doldurunuz.";
    } else {
        try {
            $sql = "INSERT INTO ADRES (MusteriID, AdresBaslik, Sehir, AdresMetni, AdresTipi) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$musteriID, $baslik, $sehir, $adresMetni, $adresTipi]);

            if (isset($_GET['redirect']) && $_GET['redirect'] == 'siparis') {
                header("Location: SiparisOlustur.php");
            } else {
                header("Location: profil.php?durum=adres_eklendi");
            }
            exit;

        } catch (PDOException $e) {
            $hata = "Adres eklenirken bir hata oluştu: " . $e->getMessage();
        }
    }
}

include_once "includes/header.php";
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fa-solid fa-map-location-dot"></i> Yeni Adres Ekle</h4>
                </div>
                <div class="card-body">

                    <?php if (!empty($hata)): ?>
                        <div class="alert alert-danger">
                            <i class="fa-solid fa-circle-exclamation"></i> <?= $hata ?>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Adres Başlığı</label>
                            <input type="text" name="baslik" class="form-control" placeholder="Örn: Evim, İşyeri" required>
                            <div class="form-text text-muted">Adresinizi hatırlamanız için kısa bir isim verin.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Şehir</label>
                            <select name="sehir" class="form-select" required>
                                <option value="">Şehir Seçiniz...</option>
                                <option value="İstanbul">İstanbul</option>
                                <option value="Ankara">Ankara</option>
                                <option value="İzmir">İzmir</option>
                                <option value="Bursa">Bursa</option>
                                <option value="Antalya">Antalya</option>
                                <option value="Konya">Konya</option>
                                <option value="Adana">Adana</option>
                                <option value="Gaziantep">Gaziantep</option>
                                <option value="Şanlıurfa">Şanlıurfa</option>
                                <option value="Kocaeli">Kocaeli</option>
                                <option value="Diğer">Diğer</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Adres Tipi</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="adres_tipi" id="tip1" value="Teslimat" checked>
                                    <label class="form-check-label" for="tip1">Teslimat Adresi</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="adres_tipi" id="tip2" value="Fatura">
                                    <label class="form-check-label" for="tip2">Fatura Adresi</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Açık Adres</label>
                            <textarea name="adres_metni" class="form-control" rows="3" placeholder="Mahalle, sokak, bina no, kapı no..." required></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">Kaydet</button>
                            <a href="profil.php" class="btn btn-secondary">İptal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "includes/footer.php"; ?>