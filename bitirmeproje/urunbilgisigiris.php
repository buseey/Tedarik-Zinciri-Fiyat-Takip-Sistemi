<?php
session_start();
include("baglanti.php");
mysqli_set_charset($baglanti, "utf8mb4");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Sadece POST istekleri kabul edilir."]);
    exit;
}

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Oturum geçersiz."]);
    exit;
}

if (!isset($_FILES["fatura"]) || !is_uploaded_file($_FILES["fatura"]["tmp_name"])) {
    echo json_encode(["success" => false, "message" => "Fatura dosyası eksik."]);
    exit;
}

$izin_verilen = ['pdf', 'jpg', 'jpeg', 'png'];
$uzanti = strtolower(pathinfo($_FILES["fatura"]["name"], PATHINFO_EXTENSION));

if (!in_array($uzanti, $izin_verilen)) {
    echo json_encode(["success" => false, "message" => "Geçersiz dosya uzantısı."]);
    exit;
}

if ($_FILES["fatura"]["size"] > 5 * 1024 * 1024) {
    echo json_encode(["success" => false, "message" => "Dosya boyutu 5MB'ı aşamaz."]);
    exit;
}

$fatura_dosya_adi = time() . "_" . basename($_FILES["fatura"]["name"]);
$hedef_dosya = "uploads/" . $fatura_dosya_adi;

if (!move_uploaded_file($_FILES["fatura"]["tmp_name"], $hedef_dosya)) {
    echo json_encode(["success" => false, "message" => "Dosya taşınamadı."]);
    exit;
}

$user_id = $_SESSION["user_id"];
$satici_id = $user_id;
$urun_adi = $_POST["urun_adi"] ?? '';
$kategori = $_POST["kategori"] ?? '';
$kg_fiyati = floatval($_POST["kg_fiyati"] ?? 0);
$miktar = floatval($_POST["miktar"] ?? 0);
$uretim = date('Y-m-d', strtotime($_POST["uretim_tarihi"] ?? ''));
$skt = date('Y-m-d', strtotime($_POST["son_kullanma_tarihi"] ?? ''));
$ettn = $_POST["ettn"] ?? '';
$aciklama = $_POST["aciklama"] ?? '';
$zincir_id = $_POST["zincir_id"] ?? null;

// TÜFE oranını veritabanından al
$tufe_orani = 0.03;
$q = mysqli_query($baglanti, "SELECT oran FROM tufe_tablo ORDER BY id DESC LIMIT 1");
if ($q && $r = mysqli_fetch_assoc($q)) {
    $tufe_orani = floatval($r["oran"]) / 100;
}

$onceki_fiyat = null;
$onceki = mysqli_prepare($baglanti, "
    SELECT fiyat 
    FROM tedarik_zinciri 
    WHERE zincir_id = ? 
      AND user_id != ? 
    ORDER BY id DESC 
    LIMIT 1
");
mysqli_stmt_bind_param($onceki, "si", $zincir_id, $user_id);
mysqli_stmt_execute($onceki);
mysqli_stmt_bind_result($onceki, $onceki_fiyat);
mysqli_stmt_fetch($onceki);
mysqli_stmt_close($onceki);

$ihbar = false;
if (!empty($onceki_fiyat) && $onceki_fiyat > 0) {
    $artis = ($kg_fiyati - $onceki_fiyat) / $onceki_fiyat;
    if ($artis > $tufe_orani) $ihbar = true;
}

$stmt = mysqli_prepare($baglanti, "INSERT INTO urunler 
(user_id, urun_adi, satici_id, kategori, kg_fiyati, miktar, uretim_tarihi, son_kullanma_tarihi, aciklama, fatura, ettn, zincir_id) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    $error = mysqli_error($baglanti);
    echo json_encode(["success" => false, "message" => "Sorgu hazırlanamadı: " . $error]);
    exit;
}

mysqli_stmt_bind_param($stmt, "isissddsssss", 
    $user_id, $urun_adi, $satici_id, $kategori, $kg_fiyati, $miktar,
    $uretim, $skt, $aciklama, $fatura_dosya_adi, $ettn, $zincir_id
);

if (mysqli_stmt_execute($stmt)) {
    $last_id = mysqli_insert_id($baglanti);

   // *** DÜZELTİLDİ:
    $fiyat = $kg_fiyati;
    $fatura_yolu = $fatura_dosya_adi;
    $ihbar_var_mi = $ihbar ? 1 : 0; // Boolean ihbar durumunu integer'a çevir

    $ekle_tedarik_zinciri_stmt = mysqli_prepare($baglanti, "
        INSERT INTO tedarik_zinciri (zincir_id, user_id, fiyat, miktar, tarih, fatura_yolu, ihbar_var_mi)
        VALUES (?, ?, ?, ?, NOW(), ?, ?)
    ");
    if (!$ekle_tedarik_zinciri_stmt) {
        // Hata durumunda sadece ürün eklenmiş olabilir, tedarik zinciri eklenemez.
        // Bu durumu loglamak veya kullanıcıya bildirmek gerekebilir.
        error_log("Tedarik zinciri ekleme sorgusu hazırlanamadı: " . mysqli_error($baglanti));
    } else {
        mysqli_stmt_bind_param($ekle_tedarik_zinciri_stmt, "siddsi",
            $zincir_id,
            $user_id,
            $fiyat,
            $miktar,
            $fatura_yolu,
            $ihbar_var_mi
        );
        if (!mysqli_stmt_execute($ekle_tedarik_zinciri_stmt)) {
            error_log("Tedarik zinciri kaydı hatası: " . mysqli_stmt_error($ekle_tedarik_zinciri_stmt));
        }
        mysqli_stmt_close($ekle_tedarik_zinciri_stmt);
    }
    // *** DÜZELTME SONU ***


    if ($ihbar) {
        $ihbar_aciklama = "Fiyat artışı enflasyon oranını aşmaktadır.";
        $ihbar_dosya_yolu = $hedef_dosya;

       $ihbar_stmt = mysqli_prepare($baglanti, "INSERT INTO ihbarlar 
    (zincir_id, user_id, aciklama, dosya_yolu, durum, kaynak, created_at)
    VALUES (?, ?, ?, ?, 'Bekliyor', 'sistem', NOW())");
        mysqli_stmt_bind_param($ihbar_stmt, "siss", $zincir_id, $user_id, $ihbar_aciklama, $ihbar_dosya_yolu);
        mysqli_stmt_execute($ihbar_stmt);
        mysqli_stmt_close($ihbar_stmt);
    }

    echo json_encode([
        "success" => true,
        "message" => $ihbar ? "Fiyat artışı TÜFE oranının üzerinde!" : "Ürün başarıyla eklendi!",
       "redirect" => "qrcikis.php?id=" . $last_id . "&zincir_id=" . urlencode($zincir_id),
        "fahis" => $ihbar
    ]);
} else {
    $error = mysqli_stmt_error($stmt);
    echo json_encode(["success" => false, "message" => "Veritabanı hatası: " . $error]);
}

mysqli_stmt_close($stmt);
exit;
?>