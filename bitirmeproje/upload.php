<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Lütfen giriş yapın!"]);
    exit;
}

if (isset($_FILES['fatura']) && is_uploaded_file($_FILES['fatura']['tmp_name'])) {
    $hedef_dizin = "uploads/";

    // Geçerli dosya türleri
    $izin_verilen_uzantilar = ['pdf', 'jpg', 'jpeg', 'png'];
    $dosya_uzantisi = strtolower(pathinfo($_FILES["fatura"]["name"], PATHINFO_EXTENSION));

    if (!in_array($dosya_uzantisi, $izin_verilen_uzantilar)) {
        echo json_encode(["success" => false, "message" => "Geçersiz dosya türü! Sadece PDF, JPG, JPEG veya PNG yükleyin."]);
        exit;
    }

    // Maksimum dosya boyutu (5MB)
    if ($_FILES['fatura']['size'] > 5 * 1024 * 1024) {
        echo json_encode(["success" => false, "message" => "Dosya boyutu 5MB'ı geçemez!"]);
        exit;
    }

    $fatura_dosya_adı = time() . "_" . basename($_FILES["fatura"]["name"]);
    $hedef_dosya = $hedef_dizin . $fatura_dosya_adı;

    if (move_uploaded_file($_FILES["fatura"]["tmp_name"], $hedef_dosya)) {
        echo json_encode(["success" => true, "file" => $fatura_dosya_adı]);


        


    } else {
        echo json_encode(["success" => false, "message" => "Fatura yüklenirken hata oluştu!"]);exit;
    }
} else {
    echo json_encode(["success" => false, "message" => "Dosya yüklenmedi!"]);exit;
}
?>
