#  Tedarik Zincirinde Gıda Fiyat Takip Sistemi

Bu proje, gıda ürünlerinin fiyat artışlarını izlemek ve tedarik zinciri boyunca şeffaflık sağlamak amacıyla geliştirilmiştir. QR kod teknolojisi ile ürünlerin fiyat geçmişine erişim sağlanır, TÜFE oranlarına göre fiyat artışları kontrol edilerek kullanıcılar gerekli durumları ihbar edebilir.

##  Özellikler

-  **QR Kod Entegrasyonu**: Her ürün benzersiz bir zincir ID ile tanımlanır ve QR kod ile izlenebilir.
-  **Fiyat Artış Kontrolü (TÜFE Karşılaştırmalı)**: Son fiyatlar geçmiş fiyatlarla karşılaştırılır. Eğer artış, Türkiye İstatistik Kurumu (TÜİK) tarafından yayımlanan TÜFE oranını aşarsa kullanıcıya uyarı gösterilir.
-  **Otomatik TÜFE Güncellemesi (Web Scraping ile)**: Python kullanılarak TÜİK web sitesinden aylık TÜFE verileri otomatik olarak çekilir ve sisteme aktarılır.
-  **İhbar Mekanizması**: Kullanıcılar şüpheli fiyat artışlarını açıklama ve görsel ekleyerek ihbar edebilir.
-  **Giriş Sistemi**: Kullanıcılar kullanıcı adı ve şifreyle giriş yaparak sisteme katkı sağlar.
-  **Zincir Takibi**: Ürünün tedarik zincirine katkıda bulunan her kullanıcı sistemde izlenebilir şekilde kayıt altına alınır.
  

##  Kullanılan Teknolojiler

- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP
- **Veritabanı**: MySQL
- **QR Kod Kütüphanesi**: `qr-code-styling.js`
- **TÜFE Verisi Web Scraping**: Python + Selenium


##  TÜFE Web Scraping Özelliği

- `tufe_scraping.py` dosyası Python ile TÜİK’in yayımladığı güncel TÜFE oranını çeker.
- Elde edilen veri doğrudan MySQL'e yazılarak PHP tarafında fiyat karşılaştırması için kullanılır.


## Gereksinimler

Aşağıdaki bileşenlerin sisteminizde kurulu olması gerekmektedir:

- **PHP** 
- **MySQL** 
- **Python** 3.8 veya üzeri
- **Python Kütüphaneleri**:
  - `selenium`
  - `webdriver-manager`
  - `python-dateutil`
- **WebDriver**:
  - Microsoft Edge WebDriver *(veya alternatif olarak Chrome/Firefox WebDriver)*
- **Web Sunucusu**:
  - XAMPP
 
## Kurulum Adımları 
1. Depoyu klonlayın
2. Veritabanını kurun <br>
`baglanti.php` dosyasındaki veritabanı bilgilerini kendi sisteminize göre güncelleyin.
3. Python Scraping’i çalıştırın:
  ```bash
pip install selenium webdriver-manager python-dateutil
python3 tufe_scraper.py
