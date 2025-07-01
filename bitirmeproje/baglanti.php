<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "KENDI_KULLANICI_ADINIZI_BURAYA_YAZIN";
$password = "SIFRENIZI_BURAYA_YAZIN";
$database =  "foodtrac_foodtrackingsystem";


$baglanti = mysqli_connect($servername,$username,$password,$database);
if (!$baglanti) {
echo "MySQL sunucu ile baglanti kurulamadi! </br>";
echo "HATA: " . mysqli_connect_error();
exit;
    
}

mysqli_set_charset($baglanti, "utf8mb4");

?>
