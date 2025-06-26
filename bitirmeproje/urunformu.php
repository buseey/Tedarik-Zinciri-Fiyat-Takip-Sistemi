<!-- urun_formu.php -->
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Oluşturucu</title>
    <script src="https://cdn.jsdelivr.net/npm/qr-code-styling/lib/qr-code-styling.min.js"></script>

    <style>
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #45a049;
            --background: #f9f9f9;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: var(--background);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #f9f9f9;
            padding: 30px;
            border-radius: 15px;
            
        }

        h1 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 30px;
        }
        h6{
        text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            margin-top: 5px;
            transition: border-color 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .file-input {
            background: #f8f8f8;
            padding: 15px;
            border-radius: 8px;
            border: 2px dashed #ddd;
            text-align: center;
        }

        #qrcode {
            margin-top: 30px;
            text-align: center;
        }

        button {
            background: var(--primary-color);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
            width: 100%;
        }

        button:hover {
            background: var(--secondary-color);
        }

        .example {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }

        .date-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Ürün Bilgisi Giriş</h1>
        <h6>Ürün bilgilerini aşağıdaki formdan doldurun ve QR kodunuzu oluşturun.</h6>
       
        <form id="productForm" method="post" enctype="multipart/form-data" action="javascript:void(0);">

    <div class="form-group">
        <label for="productName">Ürün Adı:</label>
        <input type="text" id="productName" required placeholder="Örn: Organik Domates" name="urun_adi">
    </div>

    <div class="form-group">
        <label for="category">Kategori:</label>
        <select id="category" required name="kategori">
            <option value="">Seçiniz</option>
            <option>Sebzeler</option>
            <option>Meyveler</option>
            <option>Bakliyat</option>
            <option>Diğer</option>
        </select>
    </div>

    <div class="form-group">
        <label for="price">Kilogram Başına Fiyat (TL):</label>
        <input type="number" step="0.01" id="price" required placeholder="Örn: 12.50" name="kg_fiyati">
    </div>

    <div class="form-group">
        <label for="quantity">Ürün Miktarı (kg):</label>
        <input type="number" step="0.1" id="quantity" required placeholder="Örn: 100" name="miktar">
    </div>

    <div class="form-group">
        <label for="productionDate">Üretim Tarihi:</label>
        <input type="date" id="productionDate" required name="uretim_tarihi">
    </div>
    
    <div class="form-group">
        <label for="expiryDate">Son Kullanma Tarihi:</label>
        <input type="date" id="expiryDate" required name="son_kullanma_tarihi">
    </div>

    <div class="form-group">
        <label for="ettn">ETTN Numarası (20 haneli):</label><br>
        <input type="text" id="ettn" name="ettn" required pattern="\d{20}"><br><br>
        <input type="hidden" name="zincir_id" value="<?php echo $_GET['zincir_id'] ?? bin2hex(random_bytes(8)); ?>">

    </div>

    <div class="form-group">
        <label for="description">Ürün Açıklaması:</label>
        <textarea id="description" rows="4" placeholder="Ürün hakkında kısa bilgi..." name="aciklama"></textarea>
    </div>

    <div class="form-group">
        <label>Fatura Yükleme:</label>
        <div class="file-input">
            <input type="file" id="invoice" accept=".pdf,.jpg,.png" required name="fatura">
            <div class="example">(PDF veya Resim formatında)</div>
        </div>
    </div>

    <button type="submit">🌱 QR Kodu Oluştur</button>
</form>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("productForm");
  let isSubmitting = false;

  form.addEventListener("submit", async function (e) {
    e.preventDefault();
    if (isSubmitting) return;
    isSubmitting = true;

    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.textContent = "Oluşturuluyor...";

    const formData = new FormData(form);
    
    // Eğer URL'den zincir_id gelmediyse ve yeni oluşturulduysa, bunu FormData'ya ekleyelim.
                    // PHP tarafında da bu değeri kontrol edip kullanabiliriz.
                    const existingZincirId = "<?php echo htmlspecialchars($_GET['zincir_id'] ?? ''); ?>";
                    if (!existingZincirId) {
                         // Eğer mevcut bir zincir_id yoksa, yeni bir tane JavaScript tarafında üretmiyoruz,
                         // PHP tarafında yönetilmesini sağlıyoruz. Bu input'u kaldırabiliriz
                         // veya sadece placeholder olarak tutabiliriz.
                         // PHP tarafı zaten $_POST["zincir_id"] ?? null ile bunu yönetecek.
                    }

    try {
      if (document.getElementById("invoice").files.length === 0) {
        alert("Lütfen fatura dosyası yükleyin.");
        return;
      }

      const res = await fetch("urunbilgisigiris.php", {
        method: "POST",
        body: formData,
        credentials: "include",
      });

      const text = await res.text();
      console.log("💬 HAM CEVAP:", text);

      const json = JSON.parse(text);

      if (json.success) {
        if (json.fahis) {
          alert("⚠️ Bu ürünün fiyatı TÜFE oranını aştı. Sistem otomatik ihbar oluşturdu.");
        }
        window.location.href = json.redirect;
      } else {
        alert("Hata: " + json.message);
      }
    } catch (error) {
      console.error("İstek veya ayrıştırma hatası:", error);
      alert("Bir hata oluştu. Lütfen tekrar deneyin.");
    } finally {
      isSubmitting = false; // her durumda sıfırla
      submitButton.disabled = false;
      submitButton.textContent = "🌱 QR Kodu Oluştur";
    }
  });
});
</script>
