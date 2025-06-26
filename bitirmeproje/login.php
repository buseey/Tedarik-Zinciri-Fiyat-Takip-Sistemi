<?php
session_start();
include("baglanti.php");

// AJAX isteği mi kontrol et
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Zaten giriş yapmışsa yönlendir
if (isset($_SESSION['login'])) {
    if (isset($_GET['redirect'])) {
        $redirect_url = urldecode($_GET['redirect']);
        $allowed_pages = ['qrgiris.php', 'index.php'];
        $path = parse_url($redirect_url, PHP_URL_PATH);
        $page = basename($path);

        if (in_array($page, $allowed_pages)) {
            if ($isAjax) {
                echo json_encode(["success" => true, "redirect" => $redirect_url]);
            } else {
                header("Location: $redirect_url");
            }
            exit();
        }
    }
    $defaultRedirect = 'index.php';
    if ($isAjax) {
        echo json_encode(["success" => true, "redirect" => $defaultRedirect]);
    } else {
        header("Location: $defaultRedirect");
    }
    exit();
}

// Giriş işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $sql = "SELECT id, username, password FROM users WHERE username = ? LIMIT 1";
    $stmt = mysqli_prepare($baglanti, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $storedPassword = $row['password'];

            if (password_verify($password, $storedPassword)) {
                // Giriş başarılı
                $_SESSION['login'] = true;
                $_SESSION['username'] = $username;
                $_SESSION['user_id'] = $row['id'];

                $redirectPage = $_GET['redirect'] ?? 'index.php';
                $redirectPage = basename($redirectPage); // Güvenlik

                if ($isAjax) {
                    echo json_encode(["success" => true, "redirect" => $redirectPage]);
                } else {
                    header("Location: $redirectPage");
                }
                exit();
            } else {
                $error = "Hatalı şifre!";
            }
        } else {
            $error = "Kullanıcı bulunamadı!";
        }

        mysqli_stmt_close($stmt);
    } else {
        $error = "SQL sorgusu hazırlanırken hata oluştu!";
    }

    if ($isAjax) {
        echo json_encode(["success" => false, "message" => $error]);
    } else {
        $_SESSION['error'] = $error;
        header("Location: login.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QFiyat - Giriş Yap</title>
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
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .container {
            width: 100%;
            max-width: 500px;
            padding: 1rem;
        }

        .login-box {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .form-title {
            color: #2c843e;
            text-align: center;
            margin-bottom: 2rem;
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
        }

        input:focus {
            outline: none;
            border-color: #2c843e;
            box-shadow: 0 0 5px rgba(44, 132, 62, 0.5);
        }

        button {
            width: 100%;
            padding: 12px;
            background: #2c843e;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background: #236532;
        }

        
        .forgot-password {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }

        .register-link {
            color: #2c843e;
            text-decoration: none;
            font-size: 0.9rem;
            padding: 8px 12px;
            
            border-radius: 5px;
            transition: all 0.3s;
        }

      
        .forgot-password a {
            color: #2c843e;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .forgot-password a:hover {
            text-decoration: underline;
            color: #236532;
        }

        .error {
            color: #e74c3c;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            display: none;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="login-box">
        <h2 class="form-title">Giriş Yap</h2>
        <form action="login.php" method="post" id="loginForm">
            <div class="form-group">
                <input type="text" id="username" placeholder="Kullanıcı Adı" required name="username">
            </div>
            <div class="form-group">
                <input type="password" id="password" placeholder="Şifreniz" required name="password">
            </div>
            <div class="error" id="errorMessage">Lütfen tüm alanları doldurun!</div>
            <button type="submit">Giriş</button>
            <div class="forgot-password">
                <a href="sifremi-unuttum.php">Şifremi Unuttum?</a>
                <a href="kayit-ol.php" class="register-link">Hesabın yok mu?</a>
            </div>
        </form>
    </div>
</div>

<script>
   document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const errorMessage = document.getElementById('errorMessage');

    if(username && password) {
        fetch('login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                errorMessage.style.display = 'none';
                
                // Kullanıcının geldiği sayfaya yönlendirme
                const urlParams = new URLSearchParams(window.location.search);
                const redirect = urlParams.get('redirect') || 'index.php';
                window.location.href = redirect;

            } else {
                errorMessage.textContent = data.message;
                errorMessage.style.display = 'block';
            }
        })
        .catch(error => {
            errorMessage.textContent = 'Sunucu hatası!';
            errorMessage.style.display = 'block';
        });
    } else {
        errorMessage.textContent = 'Lütfen tüm alanları doldurun!';
        errorMessage.style.display = 'block';
    }
});
</script>

    
</body>
</html>