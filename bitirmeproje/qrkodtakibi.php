<?php
session_start();
include("baglanti.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$zincirler = [];

$stmt = mysqli_prepare($baglanti, "
    SELECT zincir_id, MIN(created_at) AS ilk_tarih, COUNT(*) AS urun_sayisi
    FROM urunler
    WHERE user_id = ?
    GROUP BY zincir_id
    ORDER BY ilk_tarih DESC
");

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$zincirler = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Ürün Fiyat Takibi</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f7f7f7; padding: 30px; }
        h2 { text-align: center; color: #2B8C44; }
        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: center; }
        th { background: #e8f5e9; color: #2B8C44; }
        tr:hover { background-color: #f1f1f1; }
    </style>
</head>
<body>

<h2>📦 Oluşturduğunuz QR Kodlar</h2>

<?php if (empty($zincirler)): ?>
    <p>Henüz zincir oluşturulmamış.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Zincir ID</th>
                <th>Başlangıç Tarihi</th>
                <th>Ürün Sayısı</th>
                <th>Detay</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($zincirler as $i => $z): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($z['zincir_id']) ?></td>
                    <td><?= date("d.m.Y H:i", strtotime($z['ilk_tarih'])) ?></td>
                    <td><?= $z['urun_sayisi'] ?></td>
                    <td><a href="zincir_takip.php?zincir_id=<?= $z['zincir_id'] ?>">🡺 Görüntüle</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

</body>
</html>
