<?php
session_start();
include_once "includes/config.php";

if (isLogged()) {
    redirect_location();
    exit;
}

$hataMesaji = "";
$name = "";
$surname = "";
$email = "";
$passwd = "";
$passwd_again = "";

function is_empty_field_leaved(
    &$name,
    &$surname,
    &$email,
    &$passwd,
    &$passwd_again
) {
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $email = trim($_POST['email']);
    $passwd = trim($_POST['password']);
    $passwd_again = trim($_POST['password_again']);
    // If any field was empty then return true for print an error
    if (empty($name) || empty($surname) || empty($email) || empty($passwd) || empty($passwd_again)) {
        return true;
    }
    return false;
}

function is_passwds_match($p1, $p2)
{
    return $p1 === $p2;
}

function min_passwd_len_met($passwd, $len = 6)
{
    return strlen($passwd) >= $len;
}

function insert_user(&$conn, $email, $name, $surname, $passwd, &$hataMesaji)
{
    $hashed_passwd = password_hash($passwd, PASSWORD_DEFAULT);
    $sql = "INSERT INTO MUSTERI (Ad, Soyad, Eposta, SifreHash) VALUES (?,?,?,?)";
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute([$name, $surname, $email, $hashed_passwd]);
        return true;
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $hataMesaji = "Bu e-posta adresi zaten kayıtlı!";
        } else {
            echo "Kayıt hatası: " . $e->getMessage();
        }
        return false;
    }
}

$conn = connect_database();

if ($conn) {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        if (is_empty_field_leaved(
            $name,
            $surname,
            $email,
            $passwd,
            $passwd_again
        )) {
            $hataMesaji = "Lütfen boş alan bırakmayın!";
        } else {
            if (!is_passwds_match($passwd, $passwd_again)) {
                $hataMesaji = "Parolalar eşleşmiyor!";
            } else {
                if (!min_passwd_len_met($passwd)) {
                    $hataMesaji = "Parola uzunluğu en az 6 olmalı!";
                } else {
                    if (insert_user($conn, $email, $name, $surname, $passwd, $hataMesaji)) { ?>
                        <script>
                            alert('Başarıyla kayıt olundu! Giriş sayfasına yönlendiriliyorsunuz.');
                            window.location.href = "giris.php";
                        </script>
<?php
                        exit;
                    }
                }
            }
        }
    }
}
include_once "includes/header.php";
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow border-0 rounded-3">
                <div class="card-header bg-success text-white text-center py-3">
                    <h3 class="mb-0">Aramıza Katıl</h3>
                </div>
                <div class="card-body p-4">

                    <?php if (!empty($basariMesaji)): ?>
                        <div class="alert alert-success text-center">
                            <i class="fa-solid fa-check-circle fa-2x mb-2"></i><br>
                            <?php echo $basariMesaji; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($hataMesaji)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fa-solid fa-circle-xmark me-2"></i> <?php echo $hataMesaji; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($basariMesaji)): ?>
                        <form method="post">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Ad</label>
                                    <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($name); ?>" placeholder="Adınız" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Soyad</label>
                                    <input type="text" class="form-control" name="surname" value="<?php echo htmlspecialchars($surname); ?>" placeholder="Soyadınız" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">E-posta Adresi</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="ornek@mail.com" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Şifre</label>
                                <input type="password" class="form-control" name="password" placeholder="En az 6 karakter" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Şifre Tekrar</label>
                                <input type="password" class="form-control" name="password_again" placeholder="Şifrenizi doğrulayın" required>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-success btn-lg">Kayıt Ol</button>
                            </div>
                        </form>
                    <?php endif; ?>

                </div>
                <div class="card-footer text-center py-3 bg-light">
                    <span class="text-muted">Zaten hesabınız var mı?</span>
                    <a href="giris.php" class="text-decoration-none fw-bold">Giriş Yap</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "includes/footer.php"; ?>