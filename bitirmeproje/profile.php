<?php
session_start();
ob_start();
include 'baglanti.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); 
    exit;
}

$user_id = $_SESSION['user_id'];

// Kullanıcı bilgilerini çekme
$sql_user = "SELECT fullname, phone FROM users WHERE id = ?";
$stmt_user = mysqli_prepare($baglanti, $sql_user);
$user = null;

if ($stmt_user) {
    mysqli_stmt_bind_param($stmt_user, "i", $user_id);
    mysqli_stmt_execute($stmt_user);
    $result_user = mysqli_stmt_get_result($stmt_user);
    $user = mysqli_fetch_assoc($result_user);
    mysqli_stmt_close($stmt_user);
}

$fullname = $phone = ""; // Varsayılan değerleri belirle

if ($user) {
    $fullname = htmlspecialchars($user['fullname']);
    $phone = htmlspecialchars($user['phone']);
} else {
    // Kullanıcı bulunamazsa hata mesajı veya yönlendirme eklenebilir
}

$sql_urunler = "SELECT urun_adi, created_at FROM urunler
                 WHERE user_id = ?
                 ORDER BY created_at DESC
                 LIMIT 5";
$stmt_urunler = mysqli_prepare($baglanti, $sql_urunler);
$urunler = [];

if ($stmt_urunler) {
    mysqli_stmt_bind_param($stmt_urunler, "i", $user_id);
    mysqli_stmt_execute($stmt_urunler);
    $result_urunler = mysqli_stmt_get_result($stmt_urunler);

    while ($row = mysqli_fetch_assoc($result_urunler)) {
        $urunler[] = $row;
    }
    mysqli_stmt_close($stmt_urunler);
}

// --- Son Şikayetleri çekme ---
$sql_sikayetler = "SELECT zincir_id, created_at FROM ihbarlar
                   WHERE user_id = ?
                   ORDER BY created_at DESC
                   LIMIT 5";

$stmt_sikayetler = mysqli_prepare($baglanti, $sql_sikayetler);
$sikayetler = []; // Şikayetleri tutacak yeni dizi

if ($stmt_sikayetler) {
    mysqli_stmt_bind_param($stmt_sikayetler, "i", $user_id);
    mysqli_stmt_execute($stmt_sikayetler);
    $result_sikayetler = mysqli_stmt_get_result($stmt_sikayetler);

    while ($row_sikayet = mysqli_fetch_assoc($result_sikayetler)) {
        $sikayetler[] = $row_sikayet;
    }
    mysqli_stmt_close($stmt_sikayetler);
}
mysqli_close($baglanti);

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim</title>
    <style>
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #45a049;
            --background:#f9f9f9;
            --text-dark: #2c3e50;
            --text-light: #666;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: var(--background);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            background:rgb(255, 255, 255);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 20px;
            padding-bottom: 30px;
            border-bottom: 2px solid #eee;
        }

        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
        }

        .profile-info h2 {
            color: var(--text-dark);
            margin: 0 0 10px 0;
        }

        .profile-info p {
            margin: 8px 0;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .profile-content {
            display: grid;
            grid-template-columns: 1fr 1fr; /* İki eşit sütun */
            gap: 30px;
            margin-top: 20px;
            justify-content: center; /* Ortalamak için */
        }

        .profile-card {
            background:rgb(255, 255, 255);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            min-height: 300px; /* Minimum yükseklik */
            min-width: 500px;
        }
        /* YENİ EKLENEN KOD BAŞLANGICI */
        .activity-list {
            max-height: 350px; /* Listeler için maksimum yükseklik belirleyin */
            overflow-y: auto; /* Dikeyde taşma olduğunda kaydırma çubuğu göster */
            padding-right: 15px; /* Kaydırma çubuğu için biraz boşluk bırak (isteğe bağlı) */
            list-style: none; /* Varsayılan madde işaretlerini kaldır */
            padding-left: 0; /* Sol padding'i sıfırla */
        }

        .activity-list li {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .activity-list li:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        /* YENİ EKLENEN KOD BİTİŞİ */

        /* Mobil uyumluluk için medya sorgusu */
@media (max-width: 768px) {
            .container {
                padding: 15px;
                box-sizing: border-box;
                overflow-x: hidden;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }

            .profile-picture {
                width: 120px;
                height: 120px;
            }

            .profile-info h2 {
                font-size: 20px;
            }

            .profile-info p {
                font-size: 14px;
            }

            .profile-content {
                display: flex;
                flex-direction: column;
                gap: 20px;
                padding: 0;
                margin-top: 10px;
                width: 100%;
                box-sizing: border-box;
            }

            .profile-card {
                width: 100%;
                padding: 20px;
                box-sizing: border-box;
            }

            .activity-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .activity-item .activity-icon {
                margin-bottom: 10px;
            }
            /* Mobil uyumluluk için activity-list scroll yüksekliği ayarı (isteğe bağlı, farklı olabilir) */
            .activity-list {
                max-height: 250px; /* Mobilde daha küçük bir yükseklik uygun olabilir */
                overflow-y: auto;
                padding-right: 10px;
            }
        }

    </style>
</head>
<body>
    <div class="container">
        <div class="profile-header">
            <img src="https://via.placeholder.com/150/e0e0e0/4CAF50?text=?"  class="profile-picture">
            <div class="profile-info">
            <h2><?php echo $fullname; ?></h2>
            <p>📞 <?php echo $phone; ?></p>
            </div>
        </div>

        <div class="profile-content">
            <!-- Son Ürünlerim Bölümü -->
            <div class="profile-card">
                <h2 class="card-title">Ürünlerim</h2>
                <ul class="activity-list">
                <?php if (!empty($urunler)): ?>
                <?php foreach ($urunler as $urun): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($urun['urun_adi']); ?></strong> <br>
                        Eklenme Tarihi: <?php echo date("d.m.Y", strtotime($urun['created_at'])); ?>
                    </li>
                    <hr>
                <?php endforeach; ?>
            <?php else: ?>
                <li>Henüz ürün eklenmemiş.</li>
            <?php endif; ?>
            </div>
            <div class="profile-card">
    <h2 class="card-title">Şikayetlerim</h2> <ul class="activity-list">
    <?php if (!empty($sikayetler)): ?>
    <?php foreach ($sikayetler as $sikayet): ?>
        <li>
            <strong><?php echo htmlspecialchars($sikayet['zincir_id']); ?></strong> <br>
            Tarih: <?php echo date("d.m.Y", strtotime($sikayet['created_at'])); ?>
        </li>
        <hr>
    <?php endforeach; ?>
    <?php else: ?>
        <li>Henüz ihbar veya şikayet bildirilmemiş.</li>
    <?php endif; ?>
    </ul>
</div>
        </div>


</body>
</html>