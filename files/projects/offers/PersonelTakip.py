import xlwings as xw

# Yeni bir Excel uygulaması başlat
#excel başlayınca login form otomatik başlamalı
app = xw.App(add_book=False, visible=False)  

# PersonelTakip dosyasını aç
wb_path = 'PersonelTakip.xlsm'
wb = app.books.open(wb_path)

#exe dosyasına dönüştürmek için
#pyinstaller --onefile --icon=Rennova.ico PersonelTakip.py 