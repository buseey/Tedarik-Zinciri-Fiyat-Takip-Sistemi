from selenium import webdriver
from selenium.webdriver.edge.service import Service
from selenium.webdriver.common.by import By
from webdriver_manager.microsoft import EdgeChromiumDriverManager
import time
import re

from datetime import datetime
from dateutil.relativedelta import relativedelta
import locale
locale.setlocale(locale.LC_TIME, 'tr_TR.UTF-8')
previous_date = datetime.now() - relativedelta(months=1)
year = previous_date.year
month_name = previous_date.strftime("%B")
def fix_turkish_characters(text):
    corrections = {
        "Å": "Ş", "Ç": "Ç", "Ğ": "Ğ", "İ": "İ", "Ö": "Ö", "Ş": "Ş",
        "Ü": "Ü", "ç": "ç", "ğ": "ğ", "ı": "ı", "ö": "ö", "ş": "ş", "ü": "ü"
    }
    for wrong, right in corrections.items():
        text = re.sub(wrong, right, text)
    return text
month_name = fix_turkish_characters(month_name)

options = webdriver.EdgeOptions()
options.add_argument("--headless")
options.add_argument("--disable-gpu")
options.add_argument("--no-sandbox")
options.add_argument("start-maximized")

driver = webdriver.Edge(service=Service(EdgeChromiumDriverManager().install()), options=options)
url = "https://data.tuik.gov.tr/Search/Search?text=Tüfe"
driver.get(url)
time.sleep(2)
try:
    a_element = driver.find_element(By.XPATH, f"//a[contains(text(), 'Tüketici Fiyat Endeksi, {month_name} {year}')]")
    parent_div = a_element.find_element(By.XPATH, "./parent::div")
    tufe_element = parent_div.find_element(By.CLASS_NAME, "text-secondary.pt-2")
    print("TÜFE Verisi:", tufe_element.text.strip())
    oran = re.search(r"aylık %([\d,]+)", tufe_element.text.strip()).group(1)
    print(oran)

except Exception as e:
    print("Veri çekilemedi:", str(e))

driver.quit()


###PHPMYADMIN
import mysql.connector # type: ignore

conn = mysql.connector.connect(
    host="localhost", 
    user="root",  
    password="",  
    database="food_tracking_system"  
)
if conn.is_connected():
    print("Veritabanına başarıyla bağlandı.")
else:
    print("Bağlantı başarısız!")
    
cursor = conn.cursor()
sql = "INSERT INTO tufe_tablo (month,year,oran) VALUES (%s, %s, %s)"
values = (month_name, year, float(oran.replace(",", ".")))
cursor.execute(sql, values)
conn.commit()

print(f"{cursor.rowcount} kayıt eklendi! ID: {cursor.lastrowid}")

cursor.close()
conn.close() 