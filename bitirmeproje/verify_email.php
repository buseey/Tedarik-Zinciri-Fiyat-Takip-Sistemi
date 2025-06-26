<?php
session_start();
include 'baglanti.php'; // Veritabanı bağlantı dosyanız
mysqli_set_charset($baglanti, "utf8mb4");

// Kullanıcının oturumda olup olmadığını ve doğrulama sürecinde olup olmadığını kontrol et
if (!isset($_SESSION["user_id"]) || !isset($_SESSION["login"]) || !isset($_SESSION['email_verification_pending']) || $_SESSION['email_verification_pending'] !== true) {
    $_SESSION['error'] = "E-posta doğrulama işlemi için yetkisiz erişim veya doğrulama süreci başlatılmadı.";
    header("Location: security.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $verification_code_entered = trim($_POST['verification_code'] ?? '');
    $new_email_from_session = $_SESSION['new_email_for_verification'] ?? ''; // Oturumdan gelen geçici e-posta

    // Girdi Doğrulama
    if (empty($verification_code_entered)) {
        $_SESSION['error'] = "Doğrulama kodu boş bırakılamaz.";
        header("Location: security.php");
        exit();
    }
    // Sadece 6 haneli sayısal kod kabul et
    if (!preg_match("/^\d{6}$/", $verification_code_entered)) {
        $_SESSION['error'] = "Doğrulama kodu 6 haneli sayı olmalıdır.";
        header("Location: security.php");
        exit();
    }

    // Veritabanından kayıtlı kodu, geçerlilik süresini ve new_email_temp'i çek
    $stmt_fetch_code = mysqli_prepare($baglanti, "SELECT verification_code, verification_code_expires_at, new_email_temp FROM users WHERE id = ?");
    if (!$stmt_fetch_code) {
        error_log("Kod çekme sorgusu hazırlanamadı: " . mysqli_error($baglanti));
        $_SESSION['error'] = "Sistem hatası (4). Lütfen tekrar deneyin.";
        header("Location: security.php");
        exit();
    }
    mysqli_stmt_bind_param($stmt_fetch_code, "i", $user_id);
    mysqli_stmt_execute($stmt_fetch_code);
    mysqli_stmt_bind_result($stmt_fetch_code, $db_verification_code, $db_expires_at, $db_new_email_temp);
    mysqli_stmt_fetch($stmt_fetch_code);
    mysqli_stmt_close($stmt_fetch_code);

    // Kodun varlığını, eşleşmesini, geçerlilik süresini ve geçici e-postanın eşleşmesini kontrol et
    if (empty($db_verification_code) || $db_verification_code !== $verification_code_entered || $db_new_email_temp !== $new_email_from_session) {
        $_SESSION['error'] = "Doğrulama kodu yanlış veya geçersiz.";
        header("Location: security.php");
        exit();
    }
    if (strtotime($db_expires_at) < time()) {
        $_SESSION['error'] = "Doğrulama kodu süresi dolmuş. Lütfen yeni bir kod isteyin.";
        // Süresi dolmuş kodu ve geçici e-postayı temizle
        $stmt_clear_code = mysqli_prepare($baglanti, "UPDATE users SET verification_code = NULL, verification_code_expires_at = NULL, new_email_temp = NULL WHERE id = ?");
        mysqli_stmt_bind_param($stmt_clear_code, "i", $user_id);
        mysqli_stmt_execute($stmt_clear_code);
        mysqli_stmt_close($stmt_clear_code);
        unset($_SESSION['email_verification_pending']);
        unset($_SESSION['new_email_for_verification']);
        header("Location: security.php");
        exit();
    }

    // E-postayı güncelle ve doğrulama bilgilerini temizle
    $stmt_update_email = mysqli_prepare($baglanti, "UPDATE users SET email = ?, verification_code = NULL, verification_code_expires_at = NULL, new_email_temp = NULL WHERE id = ?");
    if (!$stmt_update_email) {
        error_log("E-posta güncelleme sorgusu hazırlanamadı: " . mysqli_error($baglanti));
        $_SESSION['error'] = "Sistem hatası (5). Lütfen tekrar deneyin.";
        header("Location: security.php");
        exit();
    }
    // Güncellenecek e-posta olarak veritabanında geçici olarak sakladığımız e-postayı kullanıyoruz
    mysqli_stmt_bind_param($stmt_update_email, "si", $db_new_email_temp, $user_id);
    if (mysqli_stmt_execute($stmt_update_email)) {
        $_SESSION['success'] = "E-posta adresiniz başarıyla güncellendi.";
        session_regenerate_id(true); // Güvenlik için oturum kimliğini yenile
        unset($_SESSION['email_verification_pending']); // Doğrulama sürecini sonlandır
        unset($_SESSION['new_email_for_verification']); // Geçici e-postayı temizle
    } else {
        error_log("E-posta güncellenirken veritabanı hatası oluştu: " . mysqli_error($baglanti));
        $_SESSION['error'] = "E-posta güncellenirken bir hata oluştu.";
    }
    mysqli_stmt_close($stmt_update_email);

} // End of if ($_SERVER["REQUEST_METHOD"] == "POST")

mysqli_close($baglanti);
header("Location: security.php");
exit();
?>