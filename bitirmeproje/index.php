<?php
session_start(); 
include("baglanti.php"); 
mysqli_set_charset($baglanti, "utf8mb4");


if (!isset($_SESSION['user_id'])) {
    // Eğer kullanıcı giriş yapmamışsa, login sayfasına yönlendir
    header("Location: login.php");
    exit();
}

$sorgu = $baglanti->prepare("SELECT fullname, username, email, phone FROM users");
$sorgu->execute();

// Sonuçları al
$sonuc = $sorgu->get_result();

$email = "example@example.com"; // Varsayılan değer

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT username, email FROM users WHERE id = ?";
    $stmt = $baglanti->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $username = $row['username'];
        $email = $row['email'];  // Burada değişkeni kesinlikle tanımlıyoruz
    } else {
        $username = "Misafir";
    }
} else {
    $username = "Misafir";
}


// Belleği temizle ve bağlantıyı kapat
$sorgu->close();
$baglanti->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Üreticiden Tüketiciye Fiyat Takip</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        :root {
            --primary-green: #3A7D44;
            --secondary-beige: #F5F5DC;
            --accent-grey: #6B7280;
        }

        /* Güncellenmiş Navigasyon */
        nav {
            background: rgba(255, 255, 255, 0.95);
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .logo {
            color: var(--primary-green);
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo img {
            height: 40px;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .contact-link {
            color: var(--accent-grey);
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
            position: relative;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }

        .contact-link:hover {
            background: rgba(58,125,68,0.1);
        }

        .contact-link::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-green);
            transition: 0.3s;
        }

        .contact-link:hover::after {
            width: 100%;
        }

        /* Profil Menüsü Stilleri */
        .profile-menu {
            position: relative;
            margin-right: 1.5rem;
        }

        .profile-icon {
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .profile-icon:hover {
            transform: scale(1.05);
        }

        .profile-icon img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: 2px solid var(--primary-green);
            object-fit: cover;
        }

        .profile-dropdown {
            display: none;
            position: absolute;
            top: 65px;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            width: 260px;
            padding: 1.5rem;
            z-index: 1000;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .profile-dropdown.active {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .user-info {
            text-align: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            border: 3px solid var(--primary-green);
            object-fit: cover;
        }

        .user-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-green);
            margin-bottom: 0.25rem;
        }

        .user-email {
            font-size: 0.9rem;
            color: var(--accent-grey);
        }

        .menu-items {
            list-style: none;
        }

        .menu-item {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--accent-grey);
        }

        .menu-item:hover {
            background: #f8f8f8;
            color: var(--primary-green);
        }

        .menu-item svg {
            width: 20px;
            height: 20px;
            stroke: currentColor;
        }

        .logout-button {
            width: 100%;
            background: none;
            border: none;
            color: #e74c3c;
            font-weight: 500;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        /* Header Section */
        .header-visual {
            height: 70vh;
            background: linear-gradient(45deg, rgba(58,125,68,0.9), rgba(245,245,220,0.8)),
                        url('https://images.unsplash.com/photo-1625246333195-78d9c38ad449?ixlib=rb-1.2.1');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            padding: 0 5%;
            margin-top: 80px;
            border-radius: 0 0 30px 30px;
        }

        .header-content {
            max-width: 600px;
        }

        .header-content h1 {
            font-size: 3rem;
            color: #ffffff;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .cta-button {
            padding: 1rem 2rem;
            border-radius: 50px;
            cursor: pointer;
            transition: 0.3s;
            font-weight: 500;
            background: white;
            border: 2px solid var(--primary-green);
            color: var(--primary-green);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-right: 1rem;
        }

        .cta-button:hover {
            background: var(--primary-green);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(58,125,68,0.3);
        }
        /* Hamburger buton */
.hamburger-icon {
    display: none;
    font-size: 2rem;
    cursor: pointer;
}

/* Sidebar */
.mobile-sidebar {
    position: fixed;
    top: 0;
    right: -300px; /* Başlangıçta sağ dışında */
    left: auto;
    width: 260px;
    height: 100vh;
    background-color: #1f1f1f;
    color: white;
    z-index: 2000;
    padding: 1rem;
    transition: left 0.3s ease-in-out;
    box-shadow: 2px 0 12px rgba(0,0,0,0.2);
}

.mobile-sidebar.active {
    right: 0; 
}

.sidebar-header {
    text-align: center;
    border-bottom: 1px solid #444;
    padding-bottom: 1rem;
}

.sidebar-header img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 3px solid #3A7D44;
    margin-bottom: 0.5rem;
}

.sidebar-header h3 {
    color: #3A7D44;
    margin-bottom: 0.2rem;
}

.sidebar-header p {
    font-size: 0.85rem;
    color: #ccc;
}

.sidebar-menu {
    list-style: none;
    padding: 0;
    margin-top: 1.5rem;
}

.sidebar-menu li {
    padding: 0.75rem 1rem;
    border-radius: 8px;
    margin-bottom: 0.5rem;
    background-color: #2c2c2c;
    cursor: pointer;
    transition: background 0.2s;
}

.sidebar-menu li:hover {
    background-color: #3A7D44;
    color: white;
}

.sidebar-logout {
    margin-top: 1.5rem;
    width: 100%;
    background: none;
    border: 1px solid #e74c3c;
    color: #e74c3c;
    padding: 0.6rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}

/* Sayfa üzerine karartma */
.overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.4);
    z-index: 1500;
    display: none;
}

.overlay.active {
    display: block;
}


       @media (max-width: 768px) {
    .logo {
        font-size: 1.2rem;
    }

    .profile-icon img {
        width: 35px;
        height: 35px;
    }

    .contact-link {
        font-size: 0.85rem;
        padding: 0.4rem 0.6rem;
        margin-right: 0.5rem;
    }

    .nav-right {
        gap: 1rem;
    }
     .hamburger-icon {
        display: block;
        color: var(--primary-green);
        font-weight: bold;
        font-size: 2rem;
    }

    .contact-link,
    .profile-menu {
        display: none;
    }
}
    </style>
</head>
<body>
    <nav>
        <div class="mobile-sidebar" id="mobileSidebar">
    <div class="sidebar-header">
        <img src="https://cdn-icons-png.flaticon.com/512/149/149071.png" alt="Avatar">
        <h3><?php echo htmlspecialchars($username); ?></h3>
        <p><?php echo htmlspecialchars($email); ?></p>
    </div>
    <ul class="sidebar-menu">
        <li onclick="window.location.href='profile.php'"> Profilim</li>
        <li onclick="window.location.href='iletisim.php'"> İletişim</li>
        <li onclick="window.location.href='security.php'"> Güvenlik</li>
    </ul>
    <button class="sidebar-logout" onclick="window.location.href='cikis.php'"> Çıkış Yap</button>
</div>

<div class="overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
        <div class="logo">
            <img src="https://cdn-icons-png.flaticon.com/512/3143/3143461.png" alt="Logo">
            Gıda Fiyat Takip Sistemi
        </div>
        
        <div class="nav-right">
            <a href="#contact" class="contact-link" onclick="window.location.href='iletisim.php'">İletişim</a>
            <div class="profile-menu">
                <div class="profile-icon" onclick="toggleProfileMenu()">
                    <img src="https://cdn-icons-png.flaticon.com/512/149/149071.png" alt="Profile Icon">
                </div>
                <div class="profile-dropdown" id="profileDropdown">
                    <div class="user-info">
                        <img src="https://cdn-icons-png.flaticon.com/512/149/149071.png" class="user-avatar" alt="User Avatar">
                        <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                        <div class="user-email"><?php echo htmlspecialchars($email); ?></div>
                    </div>
                    <ul class="menu-items">
                        <li class="menu-item" onclick="window.location.href='profile.php';">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Profilim
                        </li>
                        <li class="menu-item" onclick="window.location.href='security.php';">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Güvenlik
                        </li>
                    </ul>
                    <button class="logout-button" onclick="window.location.href='cikis.php'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Çıkış Yap
                    </button>
                </div>
            </div>
        </div>
         <div class="hamburger-icon" onclick="openSidebar()">☰</div>
    </nav>

    <section class="header-visual">
        <div class="header-content">
            <h1>Adil Fiyatın<br>Dijital Takipçisi</h1>
            <div class="cta-buttons">
            <button class="cta-button" onclick="window.location.href='urunformu.php'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <path d="M16 3v18"></path>
                        <path d="M8 3v18"></path>
                        <path d="M3 8h18"></path>
                        <path d="M3 16h18"></path>
                    </svg>
                    QR Oluştur
                </button>
                 <button class="cta-button" onclick="window.location.href='qrkodtakibi.php'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    Ürün Fiyat Takibi
                </button>
            </div>
        </div>
    </section>

    <script>
        function toggleProfileMenu() {
            const dropdown = document.getElementById("profileDropdown");
            dropdown.classList.toggle("active");
        }

        document.addEventListener("click", function(event) {
            const dropdown = document.getElementById("profileDropdown");
            const profileIcon = document.querySelector(".profile-icon");
            
            if (!event.target.closest(".profile-menu")) {
                dropdown.classList.remove("active");
            }
        });


         function openSidebar() {
        document.getElementById("mobileSidebar").classList.add("active");
        document.getElementById("sidebarOverlay").classList.add("active");
    }

    function closeSidebar() {
        document.getElementById("mobileSidebar").classList.remove("active");
        document.getElementById("sidebarOverlay").classList.remove("active");
    }   
        function logout() {
    window.location.href = 'dashboard.php?logout=1';
}
    </script>
</body>
</html>