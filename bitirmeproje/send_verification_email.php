<?php
session_start();
include 'baglanti.php'; 
mysqli_set_charset($baglanti, "utf8mb4");

// PHPMailer dosyaları
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


if (!isset($_SESSION["user_id"]) || !isset($_SESSION["login"])) {
    $_SESSION['error'] = "E-posta adresinizi güncellemek için lütfen giriş yapınız.";
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $new_email_raw = trim($_POST['new_email'] ?? '');
    $current_password_raw = trim($_POST['current_password'] ?? '');

    // Girdi Doğrulama
    if (empty($new_email_raw) || empty($current_password_raw)) {
        $_SESSION['error'] = "Yeni e-posta adresi ve mevcut şifrenizi boş bırakamazsınız.";
        header("Location: security.php");
        exit();
    }
    if (!filter_var($new_email_raw, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Geçerli bir e-posta adresi giriniz.";
        header("Location: security.php");
        exit();
    }

    // Mevcut şifreyi veritabanından çek
    $stmt_fetch_password = mysqli_prepare($baglanti, "SELECT password FROM users WHERE id = ?");
    if (!$stmt_fetch_password) {
        error_log("Şifre çekme sorgusu hazırlanamadı: " . mysqli_error($baglanti));
        $_SESSION['error'] = "Sistem hatası (1). Lütfen tekrar deneyin.";
        header("Location: security.php");
        exit();
    }
    mysqli_stmt_bind_param($stmt_fetch_password, "i", $user_id);
    mysqli_stmt_execute($stmt_fetch_password);
    mysqli_stmt_bind_result($stmt_fetch_password, $db_password_hash);
    mysqli_stmt_fetch($stmt_fetch_password);
    mysqli_stmt_close($stmt_fetch_password);

    if (empty($db_password_hash)) {
        $_SESSION['error'] = "Kullanıcı bilgileri bulunamadı. Lütfen tekrar giriş yapın.";
        header("Location: login.php");
        exit();
    }

    // Girilen şifreyi doğrula
    if (!password_verify($current_password_raw, $db_password_hash)) {
        $_SESSION['error'] = "Mevcut şifreniz yanlış.";
        header("Location: security.php");
        exit();
    }

    // Yeni e-posta adresinin zaten başka bir kullanıcı tarafından kullanılıp kullanılmadığını kontrol et
    $stmt_check_email = mysqli_prepare($baglanti, "SELECT id FROM users WHERE email = ? AND id != ?");
    if (!$stmt_check_email) {
        error_log("E-posta kontrol sorgusu hazırlanamadı: " . mysqli_error($baglanti));
        $_SESSION['error'] = "Sistem hatası (2). Lütfen tekrar deneyin.";
        header("Location: security.php");
        exit();
    }
    mysqli_stmt_bind_param($stmt_check_email, "si", $new_email_raw, $user_id);
    mysqli_stmt_execute($stmt_check_email);
    mysqli_stmt_store_result($stmt_check_email);
    if (mysqli_stmt_num_rows($stmt_check_email) > 0) {
        $_SESSION['error'] = "Bu e-posta adresi zaten başka bir kullanıcı tarafından kullanılıyor.";
        mysqli_stmt_close($stmt_check_email);
        header("Location: security.php");
        exit();
    }
    mysqli_stmt_close($stmt_check_email);

    // Doğrulama kodu oluştur ve geçerlilik süresini ayarla
    $verification_code = rand(100000, 999999); 
    $expires_at = date('Y-m-d H:i:s', strtotime('+2 minutes')); 

    // Kodu, geçerlilik süresini ve geçici e-postayı veritabanına kaydet
    $stmt_save_code = mysqli_prepare($baglanti, "UPDATE users SET verification_code = ?, verification_code_expires_at = ?, new_email_temp = ? WHERE id = ?");
    if (!$stmt_save_code) {
        error_log("Kod kaydetme sorgusu hazırlanamadı: " . mysqli_error($baglanti));
        $_SESSION['error'] = "Sistem hatası (3). Lütfen tekrar deneyin.";
        header("Location: security.php");
        exit();
    }
    mysqli_stmt_bind_param($stmt_save_code, "sssi", $verification_code, $expires_at, $new_email_raw, $user_id);
    if (!mysqli_stmt_execute($stmt_save_code)) {
        error_log("Kod ve geçici e-posta kaydedilirken hata oluştu: " . mysqli_error($baglanti));
        $_SESSION['error'] = "Doğrulama kodu gönderilirken bir sorun oluştu.";
        header("Location: security.php");
        exit();
    }
    mysqli_stmt_close($stmt_save_code);

    // E-posta gönderme (PHPMailer kullanılarak)
    $mail = new PHPMailer(true);
    try {
      
        $mail->isSMTP();
        $mail->Host = 'mail.foodtrackingsystem.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'foodtrac@foodtrackingsystem.com';  // e-posta adresin
        $mail->Password = 'fiyattakip+00';                     
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;        
        $mail->Port = 465;

        $mail->setFrom('mainaccount@foodtrackingsystem.com', 'QFiyat Destek');
        $mail->addAddress($new_email_raw);
        $mail->isHTML(true);
        $mail->Subject = 'E-posta Adresi Dogrulama Kodunuz';
        $mail->Body    = 'Merhaba,<br><br>'
                       . 'E-posta adresinizi güncellemek için doğrulama kodunuz: <b>' . $verification_code . '</b><br><br>'
                       . 'Bu kod 15 dakika içinde sona erecektir.<br><br>'
                       . 'Saygılarımızla,<br>QFiyat Destek Ekibi';
        $mail->AltBody = 'E-posta adresinizi güncellemek için doğrulama kodunuz: ' . $verification_code . ' Bu kod 15 dakika içinde sona erecektir.';

        $mail->send();

        $_SESSION['success'] = "Doğrulama kodu yeni e-posta adresinize gönderildi. Lütfen gelen kutunuzu kontrol edin.";
        $_SESSION['email_verification_pending'] = true; // Doğrulama beklendiğini oturumda işaretle
        $_SESSION['new_email_for_verification'] = $new_email_raw; // Doğrulama için geçici e-postayı sakla

    } catch (Exception $e) {
        error_log("Doğrulama e-postası gönderilirken hata oluştu: " . $mail->ErrorInfo);
        $_SESSION['error'] = "Doğrulama e-postası gönderilirken bir sorun oluştu. Lütfen bilgilerinizi kontrol edin veya daha sonra tekrar deneyin.";
        // Eğer e-posta gönderilemezse, veritabanındaki kodu ve geçici e-postayı temizle
        $stmt_clear_code_on_fail = mysqli_prepare($baglanti, "UPDATE users SET verification_code = NULL, verification_code_expires_at = NULL, new_email_temp = NULL WHERE id = ?");
        mysqli_stmt_bind_param($stmt_clear_code_on_fail, "i", $user_id);
        mysqli_stmt_execute($stmt_clear_code_on_fail);
        mysqli_stmt_close($stmt_clear_code_on_fail);
        unset($_SESSION['email_verification_pending']);
        unset($_SESSION['new_email_for_verification']);
    }

}

mysqli_close($baglanti);
header("Location: security.php");
exit();
?>