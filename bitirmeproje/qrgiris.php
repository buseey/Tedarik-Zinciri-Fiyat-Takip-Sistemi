<?php
session_start();
include("baglanti.php"); 


if (!isset($_SESSION['login'])) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location: login.php?redirect=$redirect_url");
    exit();
}


$zincir_id = $_GET['zincir_id'] ?? null;
if (!$zincir_id) {
   
    die("Zincir ID bulunamadı, işlem durduruldu.");
}

// --- 1. Güncel ürün bilgilerini çekme (Prepared Statement ile) ---

$stmt_urun = mysqli_prepare($baglanti, "
    SELECT * FROM urunler
    WHERE zincir_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");


if (!$stmt_urun) {
    die("Ürün sorgusu hazırlanamadı: " . mysqli_error($baglanti));
}



mysqli_stmt_bind_param($stmt_urun, "s", $zincir_id);

// Sorguyu çalıştır
mysqli_stmt_execute($stmt_urun);

// Sonuçları al
$result_urun = mysqli_stmt_get_result($stmt_urun);
$veri = mysqli_fetch_assoc($result_urun);

// Prepared Statement'ı kapat
mysqli_stmt_close($stmt_urun);

// Ürün bulunamadı kontrolü
if (!$veri) {
    echo "Ürün bulunamadı.";
    exit; 
}


$urun_adi = $veri['urun_adi'];
$urun_olusturan_id = $veri['user_id']; // Bu ürünü oluşturan kullanıcının ID'si
$user_id = $_SESSION['user_id']; // Mevcut oturumdaki kullanıcının ID'si
$guncel_fiyat = floatval($veri['kg_fiyati']);
$uretim_tarihi = $veri['uretim_tarihi'];


// --- 2. Önceki fiyatı çekme (Prepared Statement ile) ---
$onceki_fiyat = 0;

// Sorguyu hazırla: zincir_id ve user_id için '?' yer tutucuları kullan.
$stmt_onceki_fiyat = mysqli_prepare($baglanti, "
    SELECT fiyat FROM tedarik_zinciri
    WHERE zincir_id = ? AND user_id != ?
    ORDER BY tarih DESC
    LIMIT 1 OFFSET 1
");

// Sorgu hazırlama hatası kontrolü
if (!$stmt_onceki_fiyat) {
    die("Önceki fiyat sorgusu hazırlanamadı: " . mysqli_error($baglanti));
}

// Parametreleri bağla: 's' -> zincir_id için string, 'i' -> user_id için integer.

mysqli_stmt_bind_param($stmt_onceki_fiyat, "si", $zincir_id, $user_id);
mysqli_stmt_execute($stmt_onceki_fiyat);

// Sonuçları al
$result_onceki_fiyat = mysqli_stmt_get_result($stmt_onceki_fiyat);
if ($row = mysqli_fetch_assoc($result_onceki_fiyat)) {
    $onceki_fiyat = floatval($row['fiyat']);
}

// Prepared Statement'ı kapat
mysqli_stmt_close($stmt_onceki_fiyat);


// --- 3. Fiyat değişim oranı hesaplama ---
$fiyat_degisimi = null;
if ($onceki_fiyat > 0) {
    $fiyat_degisimi = round((($guncel_fiyat - $onceki_fiyat) / $onceki_fiyat) * 100, 2);
}

// --- 4. TÜFE verisini alma (Prepared Statement ile) ---
$tufe_orani = null;
$tufe_ay = null; 
$tufe_yil = null; 

if (!empty($veri['created_at'])) {
    $tarih = date_create($veri['created_at']);
    $tufe_yil = (int)date_format($tarih, 'Y');
    $tufe_ay = (int)date_format($tarih, 'm');

   
    $stmt_tufe = mysqli_prepare($baglanti, "
        SELECT oran FROM tufe_tablo
        WHERE year = ? AND month = ?
        LIMIT 1 
    ");

    
    if (!$stmt_tufe) {
        die("TÜFE sorgusu hazırlanamadı: " . mysqli_error($baglanti));
    }

    // Parametreleri bağla: 'ii' -> year ve month integer (tam sayı)
    mysqli_stmt_bind_param($stmt_tufe, "ii", $tufe_yil, $tufe_ay);
    mysqli_stmt_execute($stmt_tufe);

    // Sonuçları al
    $result_tufe = mysqli_stmt_get_result($stmt_tufe);
    if ($satir = mysqli_fetch_assoc($result_tufe)) {
        $tufe_orani = floatval($satir['oran']);
    }

    // Prepared Statement'ı kapat
    mysqli_stmt_close($stmt_tufe);
}

// --- 5. İhbar durumu kontrolü ---
$ihbar = ($fiyat_degisimi !== null && $tufe_orani !== null && $fiyat_degisimi > $tufe_orani) ? 1 : 0;



// --- 7. Veritabanı bağlantısını kapatma (Scriptin sonunda olmalı) ---
mysqli_close($baglanti);

?>


<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ürün Şikayet Formu</title>
    <style>
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #45a049;
            --background: #f9f9f9;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: var(--background);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            
            padding: 30px;
            border-radius: 15px;
            
            box-sizing: border-box;
        }

        h1, h2 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 25px;
        }

        ul {
    list-style: none;
    padding: 0;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

ul li {
    margin: 0;
    padding: 16px 24px;
    font-size: 15px;
    color: #444;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    border-bottom: 1px solid #f0f0f0;
}

ul li:last-child {
    border-bottom: none;
}



ul li strong {
    color: #2c3e50;
    font-weight: 600;
    min-width: 160px;
    display: inline-block;
    position: relative;
    padding-left: 28px;
}



ul li span {
    color: #666;
    font-weight: 500;
    flex-grow: 1;
    padding-left: 10px;
}

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 200;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            margin-top: 5px;
            transition: border-color 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .file-input {
            background: #f8f8f8;
            padding: 15px;
            border-radius: 8px;
            border: 2px dashed #ddd;
            text-align: center;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .radio-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: normal;
        }

        button {
            background: var(--primary-color);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
            width: 100%;
            margin-top: 20px;
        }

        button:hover {
            background: var(--secondary-color);
        }

        .info-item {
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .info-item strong {
            color: var(--primary-color);
            min-width: 150px;
            display: inline-block;
        }
        .price-change-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin: 25px 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border-left: 4px solid var(--warning);
        }

        .price-change-title {
            color: var(--warning);
            margin: 0 0 15px 0;
            font-size: 1.4em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .price-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .price-item {
            padding: 10px;
            background: #fff8e1;
            border-radius: 8px;
        }

        .price-item strong {
            color: var(--warning);
            display: block;
            margin-bottom: 5px;
        }

        @media (max-width: 480px) {
            .price-details {
                grid-template-columns: 1fr;
            }
            
            .price-change-title {
                font-size: 1.2em;
            }
        }
    </style>
</head>
<body>
<div class="container">
        <h1>Ürün Bilgileri</h1>
        <ul>
            <li><strong>Ürün Adı:</strong> <?php echo htmlspecialchars($urun_adi); ?></li>
            <li><strong>Önceki Fiyat:</strong> <?php echo number_format($onceki_fiyat, 2); ?> TL</li>
            <li><strong>Güncel Fiyat:</strong> <?php echo number_format($guncel_fiyat, 2); ?> TL</li>
            <li><strong>Fiyat Değişim Oranı:</strong> <?php echo ($fiyat_degisimi > 0 ? "+" : "") . $fiyat_degisimi; ?>%</li>
            <li><strong>TÜFE Oranı (<?php echo "$tufe_ay $tufe_yil"; ?>):</strong> %<?php echo $tufe_orani; ?></li>
        </ul>
        <div class="price-change-card">
    <div class="price-change-title">💰 Fiyat Karşılaştırması</div>
    <div class="price-details">
        <div class="price-item">
            <strong>Önceki Fiyat</strong>
            <?php echo number_format($onceki_fiyat, 2); ?> TL
        </div>
        <div class="price-item">
            <strong>Güncel Fiyat</strong>
            <?php echo number_format($guncel_fiyat, 2); ?> TL
        </div>
        <div class="price-item">
            <strong>Fiyat Artışı</strong>
            <?php echo ($fiyat_degisimi > 0 ? "+" : "") . $fiyat_degisimi; ?>%
        </div>
         <div class="price-item">
            <strong>TÜFE Oranı</strong>
            <?php echo isset($tufe_orani) ? "%" . $tufe_orani : "Veri bulunamadı"; ?>
         </div>
    </div>
</div>

        <?php if(isset($_GET['success'])): ?>
            <div class="success-message">✅ İhbarınız başarıyla gönderildi!</div>
        <?php endif; ?>

<?php if (isset($ihbar) && $ihbar == 1): ?>
  <div class="alert" style="background:#ffe0e0; color:#b00020; padding:15px; border-radius:8px; margin-bottom:20px;">
      ⚠️ <strong>Fiyat artışı TÜFE oranının üzerinde!</strong> Bu durum sisteme ihbar olarak kaydedildi.
  </div>
<?php elseif (isset($ihbar) && $ihbar == 0): ?>
  <div class="alert success" style="background:#e0ffe0; color:#2e7d32; padding:15px; border-radius:8px; margin-bottom:20px;">
      ✅ <strong>Fiyat artışı normal sınırlar içinde.</strong>
  </div>
<?php endif; ?>


<?php if (isset($_GET['zincir_id'])): ?>
    <div style="text-align:center; margin: 40px 0;">
        <a href="urunformu.php?zincir_id=<?php echo htmlspecialchars($_GET['zincir_id']); ?>" style="
            background:#007B55;
            color:white;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 16px;
            display:inline-block;">
            <span style="color:white;">➕</span> Bu Zincire Yeni Ürün Ekle
        </a>
    </div>
<?php endif; ?>


        <h2>İhbar Formu</h2>
        <form action="sikayet_kaydet.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="zincir_id" value="<?php echo htmlspecialchars($zincir_id); ?>">
            <label for="issueType">Sorun Türü:</label>
            <select name="issueType" required>
                <option value="">Seçiniz</option>
                <option>Fahiş Fiyat</option>
                <option>Deformasyon</option>
                <option>Diğer</option>
            </select>

            <label for="description">Açıklama:</label>
            <textarea name="description" rows="4" required></textarea>

            <label for="fileUpload">Görsel Yükleme (Opsiyonel):</label>
            <input type="file" name="fileUpload" accept=".jpg,.png,.jpeg">

            <button type="submit">📨 Şikayeti Gönder</button>
        </form>
    </div>

</body>
</html>