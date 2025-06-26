<?php
include("baglanti.php");
header("Content-Type: text/html; charset=utf-8"); // Türkçe karakter desteği

if (!isset($_GET['zincir_id'])) {
    die("Zincir ID eksik!");
}

$zincir_id = $_GET['zincir_id'];

$sql = <<<SQL
SELECT 
    tz.*, 
    u.fullname, 
    (
        SELECT ur2.urun_adi 
        FROM urunler ur2 
        WHERE ur2.zincir_id = tz.zincir_id AND ur2.user_id = tz.user_id 
        ORDER BY ur2.id DESC 
        LIMIT 1
    ) AS urun_adi
FROM 
    tedarik_zinciri tz
JOIN 
    users u ON tz.user_id = u.id
WHERE 
    tz.zincir_id = ?
ORDER BY 
    tz.tarih ASC
SQL;



$stmt = mysqli_prepare($baglanti, $sql);
mysqli_stmt_bind_param($stmt, "s", $zincir_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$zincir = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Zincir Takibi</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f7f7f7; padding: 30px; }
        h2 { text-align: center; color: #2B8C44; }
        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: center; }
        th { background: #e8f5e9; color: #2B8C44; }
        tr:hover { background-color: #f1f1f1; }
        .danger { color: red; font-weight: bold; }
        .ok { color: green; font-weight: bold; }
    </style>
</head>
<body>

<h2>📦 Zincir Takibi: <?= htmlspecialchars($zincir_id) ?></h2>

<?php if (empty($zincir)): ?>
    <p>Bu zincire ait herhangi bir ürün kaydı bulunamadı.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Tedarikçi</th>
                <th>Ürün</th>
                <th>Fiyat (₺/kg)</th>
                <th>Miktar (kg)</th>
                <th>Tarih</th>
                <th>Fatura</th>
                <th>İhbar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($zincir as $i => $satir): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($satir['fullname']) ?></td>
                    <td><?= htmlspecialchars($satir['urun_adi'] ?? '-') ?></td>
                    <td><?= number_format($satir['fiyat'], 2) ?></td>
                    <td><?= $satir['miktar'] ?></td>
                    <td><?= $satir['tarih'] ?? '-' ?></td>
                    <td>
                        <?php if (!empty($satir['fatura_yolu'])): ?>
                            <a href="uploads/<?= htmlspecialchars($satir['fatura_yolu']) ?>" target="_blank">Faturayı Gör</a>
                        <?php else: ?>
                            Yok
                        <?php endif; ?>
                    </td>
                    <td class="<?= $satir['ihbar_var_mi'] ? 'danger' : 'ok' ?>">
                        <?= $satir['ihbar_var_mi'] ? '🚨 İhbar Var' : '✅ Temiz' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

</body>
</html>

