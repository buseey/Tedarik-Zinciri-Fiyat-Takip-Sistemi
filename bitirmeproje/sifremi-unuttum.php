<?php
session_start();
include("baglanti.php");

// PHPMailer dosyaları
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    if (!empty($email)) {
        $stmt = mysqli_prepare($baglanti, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            mysqli_stmt_bind_result($stmt, $user_id);
            mysqli_stmt_fetch($stmt);

            // Kod oluştur
            $kod = rand(1000, 9999) . "-" . rand(1000, 9999);
            $zaman = date("Y-m-d H:i:s");

            // Kod veritabanına kaydedilsin
            $stmt_update = mysqli_prepare($baglanti, "UPDATE users SET unuttum = ?, unuttum_zaman = ?  WHERE id = ?");
            mysqli_stmt_bind_param($stmt_update, "ssi", $kod, $zaman, $user_id);
            mysqli_stmt_execute($stmt_update);

            // PHPMailer yapılandırması
            $mail = new PHPMailer(true);
            try {
               $mail->CharSet = 'UTF-8';
               $mail->isSMTP();
    $mail->Host = 'mail.foodtrackingsystem.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'foodtrac@foodtrackingsystem.com';  // e-posta adresin
    $mail->Password = 'fiyattakip+00';                     
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;        
    $mail->Port = 465;

    $mail->setFrom('mainaccount@foodtrackingsystem.com', 'QFiyat Destek');
    $mail->addAddress($email); // kullanıcıdan gelen mail
    $mail->isHTML(true);
    $mail->Subject = 'Şifre Sıfırlama Kodu';
    $mail->Body = "
        <p>Merhaba,</p>
        <p>Şifre sıfırlama kodunuz:</p>
        <h2 style='color:blue;'>$kod</h2>
        <p><a href='https://foodtrackingsystem.com/sifre-dogrula.php?email=$email'>Şifre sıfırlama sayfasına git</a></p>
    ";

                $mail->send();
                echo "<script>
    alert('Kod başarıyla gönderildi.');
    window.location.href='sifre-dogrula.php?email=" . urlencode($email) . "';
</script>";
            } catch (Exception $e) {
    echo "<h3 style='color:red;'>E-posta gönderilemedi:</h3>";
    echo "<pre>" . $mail->ErrorInfo . "</pre>";
}
        } else {
            echo "<script>alert('Bu e-posta sistemde kayıtlı değil.');</script>";
        }

        mysqli_stmt_close($stmt);
    } else {
        echo "<script>alert('E-posta boş olamaz.');</script>";
    }
}
?>


<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QFiyat - Şifremi Unuttum</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background: #f0f0f5;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .password-reset-container {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
            margin: 1rem;
        }

        .password-reset-title {
            color: #2c843e;
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            font-weight: bold;
        }

        .instruction-text {
            color: #666;
            text-align: center;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input[type="email"]:focus {
            outline: none;
            border-color: #2c843e;
            box-shadow: 0 0 5px rgba(44, 132, 62, 0.5);
        }

        button[type="submit"] {
            width: 100%;
            padding: 12px;
            background: #2c843e;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 1rem;
        }

        button[type="submit"]:hover {
            background: #236532;
        }

        .back-to-login {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-to-login a {
            color: #2c843e;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .back-to-login a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: none;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 480px) {
            .password-reset-container {
                padding: 1.5rem;
            }
            
            .password-reset-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="password-reset-container">
        <h1 class="password-reset-title">Şifremi Unuttum</h1>
        <p class="instruction-text">Şifrenizi sıfırlamak için kayıtlı e-posta adresinizi girin. Size şifre sıfırlama talimatlarını içeren bir e-posta göndereceğiz.</p>
        
        <div class="alert" id="messageBox"></div>
        
        <form action="sifremi-unuttum.php" method="POST" id="passwordResetForm">
            <div class="form-group">
                <input type="email" id="email" placeholder="E-posta adresiniz" name="email" required>
            </div>
            <button type="submit">Gönder</button>
        </form>
        
        <div class="back-to-login">
            <a href="login.php">&larr; Giriş Yap Sayfasına Dön</a>
        </div>
    </div>

    <script>
        const form = document.getElementById('passwordResetForm');
        const emailInput = document.getElementById('email');
        const messageBox = document.getElementById('messageBox');

        form.addEventListener('submit', function(e) {
           // e.preventDefault();
            const email = emailInput.value.trim();
            
            // Reset previous messages
            messageBox.style.display = 'none';
            messageBox.classList.remove('alert-success', 'alert-error');

            // Basic validation
            if(!validateEmail(email)) {
                showMessage('Lütfen geçerli bir e-posta adresi girin.', 'error');
                return;
            }

            // Simulate API call
            setTimeout(() => {
                showMessage('Şifre sıfırlama talimatları e-posta adresinize gönderildi!', 'success');
                form.reset();
            }, 1000);
        });

        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(String(email).toLowerCase());
        }

        function showMessage(message, type) {
            messageBox.textContent = message;
            messageBox.classList.add(`alert-${type}`);
            messageBox.style.display = 'block';
            
            // Auto-hide message after 5 seconds
            setTimeout(() => {
                messageBox.style.display = 'none';
            }, 5000);
        }
    </script>
</body>
</html>