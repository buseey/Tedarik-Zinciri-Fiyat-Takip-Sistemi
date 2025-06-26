<?php
session_start();
include 'baglanti.php';

$new_email = $_POST['new_email'] ?? '';
$password = $_POST['email_password'] ?? '';
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id || !$new_email || !$password) {
    $_SESSION['error'] = "Tüm alanlar doldurulmalıdır.";
    header("Location: security.php");
    exit;
}

// Kullanıcının mevcut şifresini kontrol et
$stmt = mysqli_prepare($baglanti, "SELECT password FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $db_password);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if (!password_verify($password, $db_password)) {
    $_SESSION['error'] = "Şifre hatalı.";
    header("Location: security.php");
    exit;
}

// E-postayı güncelle
$stmt = mysqli_prepare($baglanti, "UPDATE users SET email = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, "si", $new_email, $user_id);
if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success'] = "E-posta adresiniz başarıyla güncellendi.";
} else {
    $_SESSION['error'] = "E-posta güncellenirken bir hata oluştu.";
}
mysqli_stmt_close($stmt);

header("Location: security.php");
exit;
?>
