<?php
session_start();
include 'baglanti.php'; // Veritabanı bağlantısını içe aktar

// Hata ve başarı mesajları için diziler
$error = [];
$success_message = ''; // Tek bir başarı mesajı için string

$user_id = $_SESSION['user_id'] ?? null;
$fullname = $phone = $current_email = ""; // Varsayılan değerler

// Kullanıcının oturumda olup olmadığını kontrol et
if (!$user_id) {
    header("Location: login.php"); // Giriş yapmamışsa login sayfasına yönlendir
    exit();
}

// Bağlantıyı kapatmadan önce tüm veritabanı işlemlerini yapmak için,
// PHP kodunun en başında tekrar bir bağlantı kontrolü ve alma.
// Eğer security.php içinde hem POST hem GET işlemleri varsa,
// $baglanti->close() en sonda olmalı.
// Burada şifre değiştirme formu için zaten $baglanti kullanılıyor.

// Kullanıcının bilgilerini (fullname, phone, email) çek
$stmt_user = mysqli_prepare($baglanti, "SELECT fullname, phone, email FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt_user, "i", $user_id);
mysqli_stmt_execute($stmt_user);
$result_user = mysqli_stmt_get_result($stmt_user);
$user_info = mysqli_fetch_assoc($result_user);
mysqli_stmt_close($stmt_user);

if ($user_info) {
    $fullname = htmlspecialchars($user_info['fullname']);
    $phone = htmlspecialchars($user_info['phone']);
    $current_email = htmlspecialchars($user_info['email']);
} else {
    // Kullanıcı bilgileri bulunamazsa (nadiren, oturumdan sonra)
    session_destroy(); // Oturumu sonlandır
    header("Location: login.php");
    exit();
}

// --- Şifre Değiştirme Kısmı (Mevcut haliyle bırakıldı) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sadece şifre değiştirme formundan gelip gelmediğini kontrol edelim
    if (isset($_POST['change_password_form'])) { // Gizli bir input ile formları ayırabiliriz
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validasyon
        if (empty($current_password)) $error[] = "Mevcut şifre boş olamaz.";
        if (strlen($new_password) < 8) $error[] = "Yeni şifre en az 8 karakter olmalı.";
        if ($new_password !== $confirm_password) $error[] = "Yeni şifreler eşleşmiyor.";

        if (empty($error)) {
            // Kullanıcının mevcut şifresini veritabanından çek
            $stmt_check_password = mysqli_prepare($baglanti, "SELECT password FROM users WHERE id = ?");
            mysqli_stmt_bind_param($stmt_check_password, "i", $user_id);
            mysqli_stmt_execute($stmt_check_password);
            mysqli_stmt_bind_result($stmt_check_password, $db_password_hash);
            mysqli_stmt_fetch($stmt_check_password);
            mysqli_stmt_close($stmt_check_password);

            if ($db_password_hash && password_verify($current_password, $db_password_hash)) {
                // Yeni şifreyi hashle
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update = mysqli_prepare($baglanti, "UPDATE users SET password = ? WHERE id = ?");
                mysqli_stmt_bind_param($update, "si", $new_hash, $user_id);

                if (mysqli_stmt_execute($update)) {
                    $success_message = "Şifreniz başarıyla güncellendi.";
                    session_regenerate_id(true); // Güvenlik için oturum kimliğini yenile
                } else {
                    $error[] = "Şifre güncellenirken bir hata oluştu: " . mysqli_error($baglanti);
                    error_log("Şifre güncelleme hatası: " . mysqli_error($baglanti));
                }
                mysqli_stmt_close($update);
            } else {
                $error[] = "Mevcut şifreniz hatalı.";
            }
        }
    }
}
// --- Şifre Değiştirme Kısmı Sonu ---

// Oturum mesajlarını yerel değişkenlere ata ve oturumdan temizle
$session_success = $_SESSION['success'] ?? '';
$session_error = $_SESSION['error'] ?? '';
$session_info = $_SESSION['info'] ?? '';

unset($_SESSION['success']);
unset($_SESSION['error']);
unset($_SESSION['info']);

// Pop-up'ın açılıp açılmayacağını ve hangi pop-up'ın açılacağını belirleyen PHP bayrakları
$show_email_update_modal = false; // E-posta güncelleme modalı
$show_verification_modal = false; // Doğrulama kodu modalı

// Eğer send_verification_email.php'den yönlendirme yapıldıysa
if (isset($_SESSION['email_verification_pending']) && $_SESSION['email_verification_pending'] === true) {
    $show_verification_modal = true;
    // new_email_for_verification pop-up içinde gösterileceği için oturumda tutuluyor
}

// Veritabanı bağlantısını kapat
mysqli_close($baglanti);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Güvenlik Merkezi - Tarladan Sofraya</title>
    <style>
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #45a049;
            --background: #f9f9f9;
            --text-dark: #2c3e50;
            --text-light: #666;
            --warning-light: #fff3e0;
            --warning-dark: #e65100;
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
            background: white; /* Arka plan ekledim */
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); /* Gölge ekledim */
            box-sizing: border-box;
        }

        h1 { text-align: center; color: #333; margin-bottom: 30px; }
        .security-section { margin-bottom: 40px; }
        .security-title {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-size: 1.5em;
        }

        .security-card {
            background: #f8f8f8;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .security-item {
            padding: 15px;
            margin: 10px 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .email-info { /* Eski email-info div'inin stilini koru */
            display: flex;
            align-items: center;
            flex-grow: 1; /* Mevcut e-posta adresi alanının genişlemesini sağlar */
        }
        .email-info div {
             margin-left: 15px; /* İkon ile metin arasına boşluk */
        }
        .email-address {
            font-weight: bold;
            color: var(--text-dark);
            word-break: break-all; /* Uzun e-posta adresleri için */
        }

        .security-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0; /* Küçülmesini engelle */
        }

        .change-email-btn {
            background: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
            flex-shrink: 0; /* Küçülmesini engelle */
        }
        .change-email-btn:hover {
            background:  var(--primary-color);
}

        .security-form {
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            background: #fff; /* Şifre formu için arka plan */
        }

        .form-group {
            margin-bottom: 20px;
        }

        input[type="password"], input[type="email"], input[type="text"] { /* Tüm input tiplerini kapsayacak şekilde güncellendi */
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin-top: 5px;
            box-sizing: border-box; /* Padding'in genişliği etkilemesini engelle */
        }

        button[type="submit"] {
            background: var(--primary-color);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
        }

        button[type="submit"]:hover {
            background: var(--secondary-color);
        }

        /* Mesaj Stilleri */
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
        }
        .success { background: #e0ffe0; color: #2e7d32; border: 1px solid #c3e6cb; }
        .error { background: #ffe0e0; color: #b00020; border: 1px solid #f5c6cb; }
        .info { background: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }


        /* Modal Ortak Stilleri */
        .modal {
            display: none; /* Varsayılan olarak gizli */
            position: fixed;
            z-index: 1000; /* Diğer her şeyin üstünde olmalı */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6); /* Yarı şeffaf koyu arka plan */
            justify-content: center;
            align-items: center;
            padding: 20px; /* Kenarlardan boşluk */
            box-sizing: border-box;
        }

        .modal-content {
            background-color: #fefefe;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative; /* Kapatma butonu için */
            animation-name: animatetop;
            animation-duration: 0.4s;
        }

        @keyframes animatetop {
            from {top: -100px; opacity: 0}
            to {top: 0; opacity: 1}
        }

        .close-button {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            top: 10px;
            right: 20px;
            cursor: pointer;
        }
        .close-button:hover, .close-button:focus {
            color: black;
            text-decoration: none;
        }

        .modal-header {
            margin-bottom: 20px;
            text-align: center;
        }
        .modal-header h3 {
            color: var(--primary-color);
            margin: 0;
            font-size: 1.5em;
        }
        .modal-description {
            color: var(--text-light);
            font-size: 0.9em;
            margin-top: 10px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }
        .cancel-btn, .confirm-btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s;
        }
        .cancel-btn { background: #e0e0e0; color: #666; }
        .cancel-btn:hover { background: #bdbdbd; }
        .confirm-btn { background: var(--primary-color); color: white; }
        .confirm-btn:hover { background: var(--secondary-color); }

        .security-warning {
            background: var(--warning-light);
            color: var(--warning-dark);
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 0.9em;
            line-height: 1.4;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Responsive Tasarım */
        @media (max-width: 768px) {
            .container { padding: 15px; }
            .security-item { flex-direction: column; align-items: flex-start; }
            .email-info { margin-bottom: 15px; }
            .change-email-btn { width: 100%; text-align: center; }
            .security-title { font-size: 1.3em; }
            .modal-content { width: 95%; }
            .form-actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔒 Güvenlik </h1>

        <?php if ($session_success): ?>
            <div class="message success"><?= htmlspecialchars($session_success); ?></div>
        <?php endif; ?>
        <?php if ($session_error): ?>
            <div class="message error"><?= htmlspecialchars($session_error); ?></div>
        <?php endif; ?>
        <?php if ($session_info): ?>
            <div class="message info"><?= htmlspecialchars($session_info); ?></div>
        <?php endif; ?>

        <div class="security-section">
            <h2 class="security-title">E-Posta</h2>
            <div class="security-card">
                <div class="security-item">
                    <div class="email-info">
                        <div class="security-icon">📧</div>
                        <div>
                            <h3>E-posta Adresiniz</h3>
                            <div class="email-address">
                                <span><?php echo $current_email; ?></span>
                            </div>
                        </div>
                    </div>
                    <button class="change-email-btn" id="openEmailModalBtn">E-postayı Değiştir</button>
                </div>
            </div>
        </div>

        <div class="security-section">
            <h2 class="security-title">Şifre Güncelleme</h2>
            <?php if (!empty($error)): ?>
                <div class="message error">
                    <?php foreach ($error as $msg): ?>
                        <p><?= htmlspecialchars($msg) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="message success">
                    <p><?= htmlspecialchars($success_message) ?></p>
                </div>
            <?php endif; ?>

            <div class="security-form">
                <form action="security.php" method="POST">
                    <input type="hidden" name="change_password_form" value="1"> <div class="form-group">
                        <label for="current_password">Mevcut Şifre:</label>
                        <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                    </div>
                    <div class="form-group">
                        <label for="new_password">Yeni Şifre:</label>
                        <input type="password" id="new_password" name="new_password" required autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Şifreyi Onayla:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                    </div>
                    <button type="submit">Şifreyi Güncelle</button>
                </form>
            </div>
        </div>

    </div>

    <div class="modal" id="emailUpdateModal">
        <div class="modal-content">
            <span class="close-button" id="closeEmailModalBtn">&times;</span>
            <div class="modal-header">
                <h3>✉️ E-posta Adresinizi Değiştirin</h3>
                <p class="modal-description">Lütfen kullanmak istediğiniz yeni e-posta adresini ve mevcut şifrenizi girin.</p>
            </div>
            <form class="email-form" action="send_verification_email.php" method="post">
                <div class="form-group">
                    <label class="input-label">Mevcut E-posta Adresiniz:</label>
                    <input type="email" value="<?php echo $current_email; ?>" class="current-email" disabled>
                </div>
                <div class="form-group">
                    <label class="input-label" for="new_email_input">Yeni E-posta Adresi <span class="required">*</span></label>
                    <input type="email" placeholder="yeni@ornek.com" required class="new-email" name="new_email" id="new_email_input">
                </div>
                <div class="form-group">
                    <label class="input-label" for="email_password_input">Mevcut Şifre <span class="required">*</span></label>
                    <input type="password" placeholder="••••••••" required class="password-input" name="current_password" id="email_password_input">
                </div>
                <div class="security-warning">
                    ⚠️ Güvenliğiniz için, yeni e-posta adresinize bir doğrulama kodu gönderilecektir.
                </div>
                <div class="form-actions">
                    <button type="button" class="cancel-btn" id="cancelEmailModalBtn">İptal</button>
                    <button type="submit" class="confirm-btn">Doğrulama Kodu Gönder</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="verificationCodeModal">
        <div class="modal-content">
            <span class="close-button" id="closeVerificationModalBtn">&times;</span>
            <div class="modal-header">
                <h3>✅ E-posta Doğrulama</h3>
                <p class="modal-description">Yeni e-posta adresinize (<strong id="newEmailDisplayInModal"></strong>) bir doğrulama kodu gönderildi. Lütfen gelen kutunuzu kontrol edin ve kodu aşağıdaki alana girin.</p>
            </div>
            <form action="verify_email.php" method="POST">
                <div class="form-group">
                    <label class="input-label" for="verification_code_input">Doğrulama Kodu:</label>
                    <input type="text" id="verification_code_input" name="verification_code" required maxlength="6" pattern="\d{6}" title="6 haneli sayısal kod">
                </div>
                <div class="form-actions">
                    <button type="button" class="cancel-btn" id="cancelVerificationModalBtn">İptal</button>
                    <button type="submit" class="confirm-btn">E-postayı Doğrula ve Güncelle</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // --- E-posta Güncelleme Modalı Kontrolleri ---
        const emailUpdateModal = document.getElementById('emailUpdateModal');
        const openEmailModalBtn = document.getElementById('openEmailModalBtn');
        const closeEmailModalBtn = document.getElementById('closeEmailModalBtn');
        const cancelEmailModalBtn = document.getElementById('cancelEmailModalBtn');

        openEmailModalBtn.onclick = function() {
            emailUpdateModal.style.display = 'flex';
        }

        closeEmailModalBtn.onclick = function() {
            emailUpdateModal.style.display = 'none';
        }

        cancelEmailModalBtn.onclick = function() {
            emailUpdateModal.style.display = 'none';
        }

        // --- Doğrulama Kodu Modalı Kontrolleri ---
        const verificationCodeModal = document.getElementById('verificationCodeModal');
        const closeVerificationModalBtn = document.getElementById('closeVerificationModalBtn');
        const cancelVerificationModalBtn = document.getElementById('cancelVerificationModalBtn');
        const newEmailDisplayInModal = document.getElementById('newEmailDisplayInModal');

        closeVerificationModalBtn.onclick = function() {
            verificationCodeModal.style.display = 'none';
        }

        cancelVerificationModalBtn.onclick = function() {
            verificationCodeModal.style.display = 'none';
            // Kullanıcı bu modalı iptal ettiğinde doğrulama sürecini sıfırlamak isteyebiliriz
            // Ancak bu durumda session'ı PHP tarafında temizlemek daha güvenli olacaktır.
            // Örn: window.location.href = 'security.php?reset_verification=1'; gibi bir yönlendirme yapabiliriz.
        }

        // Modalların dışına tıklayınca kapatma
        window.onclick = function(event) {
            if (event.target == emailUpdateModal) {
                emailUpdateModal.style.display = 'none';
            }
            if (event.target == verificationCodeModal) {
                verificationCodeModal.style.display = 'none';
                // İptal butonunun yaptığı gibi burada da session'ı temizleme düşünülmeli.
            }
        }

        // PHP'den gelen bayrağa göre doğrulama kodu modalını aç
        <?php if ($show_verification_modal): ?>
            newEmailDisplayInModal.textContent = '<?php echo htmlspecialchars($_SESSION['new_email_for_verification'] ?? ''); ?>';
            verificationCodeModal.style.display = "flex";
            // Modal açıldıktan sonra doğrulama bekleme durumunu PHP tarafından sıfırlamamak
            // kullanıcının sayfayı yenilese bile modalın tekrar açılmasını sağlar.
            // Bu, kullanıcı deneyimi açısından iyi olabilir ancak süresi dolmuş kodlar için
            // ek PHP kontrolü (verify_email.php'de var) önemlidir.
        <?php endif; ?>
    </script>
</body>
</html>