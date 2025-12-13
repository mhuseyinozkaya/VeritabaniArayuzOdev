<?php
session_start();
include_once "includes/config.php";

if (isLogged()) {
    redirect_location();
    exit;
}
function assign_session($user)
{
    session_regenerate_id(true);
    $_SESSION['id'] = $user['MusteriID'];
    $_SESSION['eposta'] = $user['Eposta'];
    $_SESSION['isim'] = $user['Ad'];
    $_SESSION['soyisim'] = $user['Soyad'];
}
function retrive_user(&$conn, $email)
{
    $sql = "SELECT MusteriID, Ad, Soyad, Eposta, SifreHash FROM MUSTERI WHERE Eposta = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$conn = connect_database();

if ($conn) {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        if (empty($email) || empty($password)) { ?>
            <script>
                alert('Lütfen boş alan bırakmayın!');
                window.location.href = "giris.php";
            </script>
<?php
            exit;
        }
        $user = retrive_user($conn, $email);
        if ($user && password_verify($password, $user['SifreHash'])) {
            assign_session($user);
            if ($user['Eposta'] === "admin@hepsisurada.com") {
                redirect_location("Urunler.php");
            } else {
                redirect_location();
            }
            exit;
        } else {
            $hataMesaji = "Girdiğiniz e-posta ve şifreye ait kayıt bulunmuyor.";
        }
    }
}

include_once "includes/header.php";
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0 rounded-3">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h3 class="mb-0">Giriş Yap</h3>
                </div>
                <div class="card-body p-4">

                    <?php if (!empty($hataMesaji)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo $hataMesaji; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" name="email" id="emailInput" placeholder="name@example.com" required>
                            <label for="emailInput">E-posta Adresi</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" name="password" id="passInput" placeholder="Şifre" required>
                            <label for="passInput">Şifre</label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Giriş Yap</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center py-3 bg-light">
                    <span class="text-muted">Hesabınız yok mu?</span>
                    <a href="kayit.php" class="text-decoration-none fw-bold">Hemen Kayıt Ol</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "includes/footer.php"; ?>