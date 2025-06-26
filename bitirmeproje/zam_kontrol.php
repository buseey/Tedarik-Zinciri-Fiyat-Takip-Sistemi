<?php
// Hata ayarları (geliştirme sırasında açık tut)
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("baglanti.php"); 

// Sorgu
$sql = "
SELECT 
    a.user_id,
    a.urun_adi,
    DATE_FORMAT(a.uretim_tarihi, '%Y-%m') AS ay,
    a.kg_fiyati AS fiyat,
    b.kg_fiyati AS onceki_fiyat,
    (a.kg_fiyati - b.kg_fiyati) AS fark,
    ROUND(((a.kg_fiyati - b.kg_fiyati) / b.kg_fiyati) * 100, 2) AS zam_orani_yuzde,
    t.oran AS tufe_orani,
    CASE 
        WHEN ROUND(((a.kg_fiyati - b.kg_fiyati) / b.kg_fiyati) * 100, 2) > t.oran
        THEN 'FAHIŞ ZAM'
        ELSE 'NORMAL'
    END AS durum
FROM urunler a
JOIN urunler b ON 
    a.user_id = b.user_id AND 
    a.urun_adi = b.urun_adi AND 
    DATE_FORMAT(a.uretim_tarihi, '%Y-%m') = DATE_FORMAT(DATE_ADD(b.uretim_tarihi, INTERVAL 1 MONTH), '%Y-%m')
JOIN tufe_tablo t ON 
    YEAR(a.uretim_tarihi) = t.year AND MONTH(a.uretim_tarihi) = t.month
ORDER BY a.user_id, a.urun_adi, a.uretim_tarihi
";

// Sorguyu çalıştır
$sonuc = mysqli_query($baglanti, $sql);

// Hata kontrolü
if (!$sonuc) {
    echo "Sorgu çalıştırılamadı: " . mysqli_error($baglanti);
    exit;
}

// Sonuçları yazdır
if (mysqli_num_rows($sonuc) == 0) {
    echo "Fahiş zam tespit edilmedi.";
} else {
    echo "<h3>Fiyat Değişim Raporu</h3>";
    echo "<table border='1' cellpadding='5'>
            <tr>
                <th>Satıcı</th>
                <th>Ürün</th>
                <th>Ay</th>
                <th>Fiyat</th>
                <th>Önceki Fiyat</th>
                <th>Zam (%)</th>
                <th>TÜFE (%)</th>
                <th>Durum</th>
            </tr>";

    while ($satir = mysqli_fetch_assoc($sonuc)) {
        echo "<tr>
                <td>{$satir['user_id']}</td>
                <td>{$satir['urun_adi']}</td>
                <td>{$satir['ay']}</td>
                <td>{$satir['fiyat']}</td>
                <td>{$satir['onceki_fiyat']}</td>
                <td>{$satir['zam_orani_yuzde']}%</td>
                <td>{$satir['tufe_orani']}%</td>
                <td><strong>{$satir['durum']}</strong></td>
              </tr>";
    }

    echo "</table>";
}

// Bağlantıyı kapat
mysqli_close($baglanti);
?>
