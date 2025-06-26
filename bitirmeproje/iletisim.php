<?php
session_start();
include("baglanti.php"); // Veritabanı bağlantı dosyanız
mysqli_set_charset($baglanti, "utf8mb4");

// Kullanıcı oturumu kontrolü
if (!isset($_SESSION["user_id"])) {
    // Kullanıcı giriş yapmadıysa, login sayfasına yönlendir veya hata mesajı göster
    header("Location: login.php"); // Kullanıcıyı giriş sayfasına yönlendir
    exit();
    // Alternatif: die("Lütfen önce giriş yapınız."); // Eğer direkt sayfayı durdurmak istiyorsanız
}

$success_message = false; // Başarı mesajını kontrol etmek için bayrak

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION["user_id"];

    // Gelen verileri al ve trim (başındaki/sonundaki boşlukları temizle) et
    $ad_soyad_raw = trim($_POST["ad_soyad"] ?? '');
    $email_raw = trim($_POST["email"] ?? '');
    $konu_raw = trim($_POST["konu"] ?? '');
    $mesaj_raw = trim($_POST["mesaj"] ?? '');

    // Verilerin boş olup olmadığını kontrol et
    if (empty($ad_soyad_raw) || empty($email_raw) || empty($konu_raw) || empty($mesaj_raw)) {
        echo "<script>alert('Lütfen tüm alanları doldurun.');</script>";
    } else {
        // Prepared Statement ile güvenli veri ekleme
        // iletisim tablonuzda 'ad_soyad' ve 'email' sütunlarının olduğuna dikkat edin.
        // Yoksa, tablonuzu güncellemeniz gerekir (örnek: ALTER TABLE iletisim ADD COLUMN ad_soyad VARCHAR(255), ADD COLUMN email VARCHAR(255);)
        $stmt = $baglanti->prepare("INSERT INTO iletisim (user_id, ad_soyad, email, konu, mesaj) VALUES (?, ?, ?, ?, ?)");

        if ($stmt === FALSE) {
            // SQL sorgusu hazırlanırken oluşan hatayı logla (kullanıcıya gösterme)
            error_log("SQL sorgusu hazırlanırken hata oluştu: " . $baglanti->error . " Tarih: " . date('Y-m-d H:i:s'));
            echo "<script>alert('Mesajınız gönderilemedi. Bir teknik sorun oluştu, lütfen daha sonra tekrar deneyin.');</script>";
        } else {
            // Parametreleri bağla: 'i' integer (user_id için), 'ssss' string (diğer alanlar için)
            $stmt->bind_param("issss", $user_id, $ad_soyad_raw, $email_raw, $konu_raw, $mesaj_raw);

            if ($stmt->execute()) {
                $success_message = true; // Başarı bayrağını true yap
            } else {
                // Veritabanına eklerken oluşan hatayı logla (kullanıcıya gösterme)
                error_log("Mesaj gönderilirken veritabanı hatası oluştu: " . $stmt->error . " Tarih: " . date('Y-m-d H:i:s'));
                echo "<script>alert('Mesajınız gönderilemedi. Lütfen tekrar deneyin.');</script>";
            }
            $stmt->close();
        }
    }
}

// Veritabanı bağlantısını kapat (tüm PHP işlemleri bittikten sonra)
if ($baglanti) {
    $baglanti->close();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İletişim - Tarladan Sofraya</title>
    <style>
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #45a049;
            --background: #f9f9f9;
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
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }

        /* Başarı Mesajı Stili */
        .success-message {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #f0fff4;
            border: 2px solid #68d391;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none; /* Varsayılan olarak gizli */
        }

        .success-message.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .contact-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .contact-header h1 {
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        .contact-info {
            background: #f8f8f8;
            padding: 30px;
            border-radius: 12px;
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .info-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            color: white;
        }

        .contact-form input,
        .contact-form textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }

        .contact-form input:focus,
        .contact-form textarea:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .contact-form button {
            background: var(--primary-color);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
            width: 100%;
        }

        .contact-form button:hover {
            background: var(--secondary-color);
        }
        .success-message .info-icon {
            margin: 0 auto 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 50px;
            height: 50px;
        }
        .map-container {
            margin-top: 40px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        iframe {
            width: 100%;
            height: 300px;
            border: 0;
        }

        @media (max-width: 768px) {
            .contact-grid {
                grid-template-columns: 1fr;
            }
            .container {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="contact-header">
            <h1>🌱 Bizimle İletişime Geçin</h1>
            <p>Sorularınız veya önerileriniz için aşağıdaki formu doldurabilirsiniz</p>
        </div>

        <div class="contact-grid">
            <div class="contact-info">
                <div class="info-item">
                    <div class="info-icon">📍</div>
                    <div>
                        <h3>Adresimiz</h3>
                        <p>Atatürk Mah. Tarım Cad. No:123<br>Ankara, Türkiye</p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">📞</div>
                    <div>
                        <h3>Telefon</h3>
                        <p>+90 312 123 45 67<br>Hafta içi 09:00 - 18:00</p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">✉️</div>
                    <div>
                        <h3>E-posta</h3>
                        <p>destek@gmail.com<br>info@gmail.com</p>
                    </div>
                </div>
            </div>

            <form action="iletisim.php" class="contact-form" method="post" id="contactForm">
                <input type="text" placeholder="Adınız Soyadınız" required name="ad_soyad">
                <input type="email" placeholder="E-posta Adresiniz" required name="email">
                <input type="text" placeholder="Konu" required name="konu">
                <textarea rows="6" placeholder="Mesajınız..." required name="mesaj"></textarea>
                <button type="submit">📨 Mesajı Gönder</button>
            </form>
        </div>

        <div class="map-container">
          <iframe
  src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3009.6890985223326!2d29.006947715694783!3d41.03714577929949!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14cab7a44f80879f%3A0x675a8a6d90a6e6a!2zR2FsdXMgQ2FmZSAmIFJlc3RhdXJhbnQ!5e0!3m2!1str!2str!4v1678891234567!5m2!1str!2str"
  width="600"
  height="450"
  style="border:0;"
  allowfullscreen=""
  loading="lazy"
  referrerpolicy="no-referrer-when-downgrade">
</iframe>
        </div>
    </div>

    <div class="success-message" id="successMessage">
        <div class="info-icon">✓</div>
        <h2>Mesajınız Başarıyla Gönderildi!</h2>
        <p>En kısa sürede sizinle iletişime geçeceğiz.</p>
    </div>

    <script>
        // PHP'den gelen başarı bayrağını kontrol et ve mesajı göster
        // Bu kısım, sayfa yüklendiğinde çalışır ve PHP'nin mesajı başarıyla kaydettiğini varsayar.
        <?php if ($success_message): ?>
            const successMessage = document.getElementById('successMessage');
            successMessage.classList.add('active');

            // 3 saniye sonra mesajı gizle
            setTimeout(() => {
                successMessage.classList.remove('active');
            }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>