<?php
session_start();
include("baglanti.php");

echo "Gelen URL: " . $_SERVER['REQUEST_URI'];
// 1. ID kontrolü
if (!isset($_GET['id']) || empty($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Geçersiz veya eksik ürün ID!");
}

$urun_id = intval($_GET['id']);

// 2. Ürün bilgilerini çek
$sql = "SELECT * FROM urunler WHERE id = ?";
$stmt = mysqli_prepare($baglanti, $sql);
mysqli_stmt_bind_param($stmt, "i", $urun_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$urun = mysqli_fetch_assoc($result);

// 3. Ürün bulunamazsa hata ver
if (!$urun) {
    die("Bu ID ile ürün bulunamadı!");
}

// 4. QR kod içeriğini hazırla
$qr_data = "https://foodtrackingsystem.com/qrgiris.php?id={$urun['id']}&zincir_id={$urun['zincir_id']}";



?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adil Fiyatın Dijital Takipçisi</title>
   
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-green: #2B8C44;
            --dark-green: #1F6632;
            --light-bg: #F8FAF9;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--light-bg);
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .header {
            padding: 2rem 0;
            background: white;
            width: 100%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        h1 {
            color: var(--dark-green);
            font-weight: 700;
            font-size: 2.5rem;
            margin: 0;
            letter-spacing: -1px;
        }

        h2 {
            color: var(--primary-green);
            font-weight: 500;
            font-size: 1.2rem;
            margin-top: 0.5rem;
        }

        .success-container {
            background: white;
            padding: 3rem 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(43,140,68,0.1);
            margin-top: 3rem;
            width: 90%;
            max-width: 400px;
        }

        .checkmark {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--primary-green);
            margin: 0 auto 2rem;
            position: relative;
            animation: scaleUp 0.5s ease;
        }

        .checkmark::after {
            content: '';
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -60%) rotate(45deg);
            height: 35px;
            width: 20px;
            border-bottom: 4px solid white;
            border-right: 4px solid white;
        }

        #qrContainer {
            margin: 2rem auto;
            width: 200px;
            height: 200px;
            background: white;
            border-radius: 15px;
            padding: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .download-btn {
            background: var(--primary-green);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 30px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }

        .download-btn:hover {
            background: var(--dark-green);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(43,140,68,0.3);
        }

        @keyframes scaleUp {
            from { transform: scale(0); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>

    <div class="success-container">
        <div class="checkmark"></div>
        <p style="color: var(--dark-green); font-size: 1.4rem; margin-bottom: 2rem;">
            QR Kodunuz Başarıyla Oluşturuldu!
        </p>
        <div id="qrContainer"></div>
        <button class="download-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
            QR Kodunu İndir
        </button>
    </div>
   <script src="https://cdn.jsdelivr.net/npm/qr-code-styling@1.9.1/lib/qr-code-styling.min.js"></script>
<script>
    // Tanım dışarı alındı, böylece her yerden erişilebilir
    let qrCode;

    document.addEventListener("DOMContentLoaded", function () {
        const qrContent = <?= json_encode($qr_data) ?>;

        qrCode = new QRCodeStyling({
            width: 200,
            height: 200,
            data: qrContent,
            dotsOptions: {
                color: "#2B8C44",
                type: "rounded"
            },
            backgroundOptions: {
                color: "#ffffff"
            }
        });

        qrCode.append(document.getElementById("qrContainer"));
    });

    // Artık global tanım sayesinde bu fonksiyon çalışır
    document.querySelector(".download-btn").addEventListener("click", function () {
        qrCode.download({ name: "qr-kod", extension: "png" });
    });
</script>

</body>
</html>