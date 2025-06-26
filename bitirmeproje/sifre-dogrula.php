<?php
session_start();
include("baglanti.php");

$hata = "";
$basari = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"] ?? '');
    $kod = trim($_POST["kod"] ?? '');
    $yeni_sifre = $_POST["yeni_sifre"] ?? '';
    $sifre_onay = $_POST["sifre_onay"] ?? '';

    if (empty($email) || empty($kod) || empty($yeni_sifre) || empty($sifre_onay)) {
        $hata = "Tüm alanları doldurmanız gerekiyor.";
    } elseif ($yeni_sifre !== $sifre_onay) {
        $hata = "Yeni şifreler uyuşmuyor.";
    } else {
        // Kodun geçerliliğini ve zamanını kontrol et (örnek: 2 dakika)
        $stmt = mysqli_prepare($baglanti, "
            SELECT id FROM users 
            WHERE email = ? 
              AND unuttum = ? 
              AND unuttum_zaman >= NOW() - INTERVAL 2 MINUTE
        ");
        mysqli_stmt_bind_param($stmt, "ss", $email, $kod);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) === 0) {
            $hata = "Kod hatalı veya süresi dolmuş olabilir.";
        } else {
            $hashli = password_hash($yeni_sifre, PASSWORD_DEFAULT);
            $guncelle = mysqli_prepare($baglanti, "
                UPDATE users 
                SET password = ?, unuttum = NULL, unuttum_zaman = NULL 
                WHERE email = ?
            ");
            mysqli_stmt_bind_param($guncelle, "ss", $hashli, $email);
            if (mysqli_stmt_execute($guncelle)) {
                $basari = "Şifreniz başarıyla güncellendi. <a href='login.php'>Giriş yap</a>";
            } else {
                $hata = "Şifre güncellenemedi.";
            }

            if (isset($guncelle)) {
                mysqli_stmt_close($guncelle);
            }
        }

        mysqli_stmt_close($stmt);
    }
} else {
    $email = $_GET['email'] ?? '';
}
?>


<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Şifre Sıfırla</title>
    <style>
        body { background: #f0f0f5; font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .box { background: white; padding: 2rem; border-radius: 12px; width: 100%; max-width: 500px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        h1 { color: #2c843e; text-align: center; }
        label { display: block; margin-top: 1rem; font-weight: bold; }
        input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; }
        button { background: #2c843e; color: white; padding: 10px; width: 100%; margin-top: 1.5rem; border: none; border-radius: 6px; cursor: pointer; }
        button:hover { background: #236532; }
        .msg { margin-top: 1rem; text-align: center; }
        .msg.error { color: red; }
        .msg.success { color: green; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Şifre Sıfırla</h1>
        <!-- Geri Sayım Kutusu -->
        <div id="sureKutusu" style="text-align: center; font-weight: bold; color: #2c843e; margin-bottom: 15px;">
            Kodun süresi: <span id="sure">02:00</span>
        </div> 
        
        <?php if ($hata): ?>
            <div class="msg error"><?= $hata ?></div>
        <?php elseif ($basari): ?>
            <div class="msg success"><?= $basari ?></div>
        <?php endif; ?>

        <?php if (!$basari): ?>
        <form method="POST">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

            <label for="kod">E-posta ile gelen kod:</label>
            <input type="text" name="kod" id="kod" required>

            <label for="yeni_sifre">Yeni Şifre:</label>
            <input type="password" name="yeni_sifre" id="yeni_sifre" required>

            <label for="sifre_onay">Şifreyi Onayla:</label>
            <input type="password" name="sifre_onay" id="sifre_onay" required>

            <button type="submit">Şifreyi Güncelle</button>
        </form>
        <?php endif; ?>
    </div>
    
     <!-- Geri Sayım Scripti -->
    <script>
        let kalanSaniye = 2 * 60; // 5 dakika

        function formatla(saniye) {
            const dk = String(Math.floor(saniye / 60)).padStart(2, '0');
            const sn = String(saniye % 60).padStart(2, '0');
            return `${dk}:${sn}`;
        }

        function baslatGeriSayim() {
            const sure = document.getElementById("sure");

            const interval = setInterval(() => {
                kalanSaniye--;

                if (kalanSaniye >= 0) {
                    sure.textContent = formatla(kalanSaniye);
                } else {
                    clearInterval(interval);
                    sure.textContent = "SÜRE DOLDU";
                    alert("Kodun süresi doldu. Lütfen yeniden talep edin.");
                    document.querySelector("button[type='submit']").disabled = true;
                }
            }, 1000);
        }

        baslatGeriSayim();
    </script>
    
    
    
</body>
</html>
