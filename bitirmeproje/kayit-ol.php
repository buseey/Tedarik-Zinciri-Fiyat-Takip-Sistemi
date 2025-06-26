<?php
session_start();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol | Modern Design</title>
    <style>
        :root {
            --primary-color: #2d7a2d;
            --primary-hover: #1f5a1f;
            --background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            --input-focus: #94d3a2;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: var(--background);
            padding: 1rem;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 480px;
            transition: transform 0.3s ease;
        }

        .form-container:hover {
            transform: translateY(-5px);
        }

        h2 {
            text-align: center;
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 2rem;
            letter-spacing: -0.5px;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #495057;
            font-weight: 500;
            font-size: 0.9rem;
        }

        input {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid #e9ecef;
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        input:focus {
            outline: none;
            border-color: var(--input-focus);
            box-shadow: 0 0 0 3px rgba(45, 122, 45, 0.1);
        }

        input:hover {
            border-color: #ced4da;
        }

        .btn {
            width: 100%;
            padding: 0.8rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(45, 122, 45, 0.2);
        }

        .message-box {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .error-box {
            background: #fee2e2;
            border: 2px solid #fca5a5;
            color: #dc2626;
        }

        .success-box {
            background: #dcfce7;
            border: 2px solid #86efac;
            color: #16a34a;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #6c757d;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            position: relative;
        }

        .login-link a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: width 0.3s ease;
        }

        .login-link a:hover::after {
            width: 100%;
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 1.5rem;
                border-radius: 1rem;
            }

            h2 {
                font-size: 1.75rem;
            }

            input {
                padding: 0.75rem;
            }
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-container {
            animation: slideIn 0.4s ease-out;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Hesap Oluştur</h2>

    <?php
    if (isset($_SESSION['errors'])) {
        echo "<div class='message-box error-box'>";
        echo "<svg xmlns='http://www.w3.org/2000/svg' class='icon icon-tabler icon-tabler-alert-circle' width='24' height='24' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' fill='none' stroke-linecap='round' stroke-linejoin='round'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0'/><path d='M12 8v4'/><path d='M12 16h.01'/></svg>";
        echo "<div>";
        foreach ($_SESSION['errors'] as $error) {
            echo "<p>$error</p>";
        }
        echo "</div></div>";
        unset($_SESSION['errors']);
    }

    ?>

    <form action="kayit-islem.php" method="POST">
        <div class="form-group">
           <label for="fullname">Ad Soyad</label>
           <input type="text" id="fullname" name="fullname" placeholder="" required>
        </div>
        <div class="form-group">
            <label for="username">Kullanıcı Adı</label>
            <input type="text" id="username" name="username" placeholder="" required>
        </div>

        <div class="form-group">
            <label for="email">E-posta Adresi</label>
            <input type="email" id="email" name="email" placeholder="" required>
        </div>
        <div class="form-group">
            <label for="phone">Telefon Numarası</label>
            <input type="tel" id="phone" name="phone" placeholder="05XX XXX XX XX" pattern="[0-9]{10,11}" required>
        </div>


        <div class="form-group">
            <label for="password">Şifre</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>

        <div class="form-group">
            <label for="confirm_password">Şifre Tekrar</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn">Kayıt Ol</button>
    </form>

    <div class="login-link">
        Zaten hesabınız var mı? <a href="login.php">Giriş Yap</a>
    </div>
</div>

</body>
</html>