# Sistem Aktivite Logları Saklama Politikası

Log bakımı yalnızca CLI üzerinden çalışır. Varsayılan çağrı hiçbir kayıt
değiştirmeden aday sayılarını gösterir:

```bash
/opt/lampp/bin/php bin/log-retention.php
```

Arşivleme ve süresi dolan arşiv kayıtlarının temizlenmesi için:

```bash
/opt/lampp/bin/php bin/log-retention.php --execute --batch-size=1000
```

Önerilen günlük zamanlanmış görev (02:20):

```cron
20 2 * * * cd /opt/lampp/htdocs/aydinogullariysc_trae && /opt/lampp/bin/php bin/log-retention.php --execute --batch-size=1000 >/dev/null 2>&1
```

Sunucuda `crontab` bulunmuyorsa aynı komut cPanel/Plesk içindeki Zamanlanmış
Görevler bölümüne eklenmelidir. Her çalışmanın sonucu `log_retention_runs`
tablosuna kaydedilir.

Saklama süreleri:

| Kayıt grubu | Aktif tablo | Arşiv dahil toplam süre |
|---|---:|---:|
| Sayfa görüntüleme | 60 gün | 180 gün |
| Giriş / çıkış | 180 gün | 2 yıl |
| Standart işlemler | 1 yıl | 3 yıl |
| Kritik, hata, silme ve dışa aktarma | 2 yıl | 5 yıl |

Aktif tablodan çıkarılan bütün kayıtlar önce `logs_archive` tablosunda
doğrulanır. Nihai temizleme öncesinde günlük adetler `log_daily_stats`
tablosuna yazılır.
