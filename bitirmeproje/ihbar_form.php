<?php
session_start();
include("baglanti.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$urun_id = $_GET['id'] ?? null;

if (!$urun_id || !is_numeric($urun_id)) {
    die("Geçersiz ürün ID");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $aciklama = mysqli_real_escape_string($baglanti, $_POST['aciklama']);
    $dosya_yolu = null;

    if (!empty($_FILES['dosya']['name'])) {
        $hedef_klasor = "uploads/";
        $dosya_adi = time() . "_" . basename($_FILES["dosya"]["name"]);
        $dosya_yolu = $hedef_klasor . $dosya_adi;
        move_uploaded_file($_FILES["dosya"]["tmp_name"], $dosya_yolu);
    }

    $sql = "INSERT INTO ihbarlar (urun_id, user_id, aciklama, dosya_yolu, durum, created_at)
            VALUES (?, ?, ?, ?, 'Bekliyor', NOW())";

    $stmt = mysqli_prepare($baglanti, $sql);
    mysqli_stmt_bind_param($stmt, "iiss", $urun_id, $user_id, $aciklama, $dosya_yolu);
    mysqli_stmt_execute($stmt);

    echo "<script>alert('İhbar başarıyla gönderildi.'); window.location='qrgiris.php?id={$urun_id}';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>İhbar Formu</title>
</head>
<body>
    <h2>Ürün Hakkında İhbar</h2>
    <form method="post" enctype="multipart/form-data">
        <label>Açıklama:</label><br>
        <textarea name="aciklama" required></textarea><br><br>

        <label>Dosya Ekle (opsiyonel):</label>
        <input type="file" name="dosya"><br><br>

        <button type="submit">Gönder</button>
    </form>
</body>
</html>
