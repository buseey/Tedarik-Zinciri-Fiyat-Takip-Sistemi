<?php
session_start();
include("baglanti.php");

// Karakter seti ayarla
$baglanti->set_charset("utf8");

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = trim($_POST["fullname"]);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $phone = trim($_POST['phone']);
     

    if (!preg_match('/^[0-9]{10,11}$/', $phone)) {
        $errors[] = "Geçerli bir telefon numarası girin.";
    }

  
    if (empty($username)) $errors[] = "Kullanıcı adı gereklidir.";
    if (empty($email)) $errors[] = "E-posta gereklidir.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Geçersiz e-posta formatı.";
    if (empty($password)) $errors[] = "Şifre gereklidir.";
    if ($password !== $confirm_password) $errors[] = "Şifreler eşleşmiyor.";
    if (strlen($password) < 8) $errors[] = "Şifre en az 8 karakter olmalıdır.";

    // Kullanıcı kontrolü
    if (empty($errors)) {
        $check_query = "SELECT COUNT(*) FROM users WHERE username = ? OR email = ?";
        $stmt = $baglanti->prepare($check_query);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $errors[] = "Kullanıcı adı veya e-posta zaten kayıtlı.";
        }
    }

    if (empty($errors)) {
        // Şifreyi hashle
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Kullanıcıyı ekle
        $insert_query = "INSERT INTO users (fullname, username, email, phone, password) VALUES (?, ?, ?, ?, ?)";
        $stmt = $baglanti->prepare($insert_query);
        $stmt->bind_param("sssss",$fullname, $username, $email, $phone, $hashed_password);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $_SESSION['success'] = "Kayıt başarılı! Giriş yapabilirsiniz.";
            header("Location: login.php");
            exit();
        } else {
            $errors[] = "Kayıt sırasında bir hata oluştu, lütfen tekrar deneyin.";
        }
        $stmt->close();
    }

    // Eğer hata varsa session içinde tut
    $_SESSION['errors'] = $errors;
    header("Location: kayit-ol.php");
    exit();
}

$baglanti->close();


?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QFiyat - Kayıt Ol</title>
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

        .register-container {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
            margin: 1rem;
        }

        .register-title {
            color: #2c843e;
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
            margin-bottom: 0.5rem;
        }

        input:focus {
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

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .login-link a {
            color: #2c843e;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .error {
            color: #e74c3c;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>

    <div class="register-container">
        <h1 class="register-title">Kayıt Ol</h1>
        <form action="kayit-ol.php" method="POST" id="registerForm">
            <div class="form-group">
                <input type="text" name="username" placeholder="Kullanıcı Adı" required>
            </div>
            <div class="form-group">
                <input type="email" name="email" placeholder="E-posta Adresiniz" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Şifreniz" required>
            </div>
            <div class="form-group">
                <input type="password" name="confirm_password" placeholder="Şifreyi Tekrar Girin" required>
            </div>
            <button type="submit">Hesap Oluştur</button>
            <div class="login-link">
                Zaten hesabınız var mı? <a href="login.php">Giriş Yap</a>
            </div>
        </form>
    </div>
</body>
</html>