# Aydınoğulları YSC Proje Kuralları

Bu dosya, proje üzerinde çalışan geliştiriciler ve kodlama ajanları için ana kural kaynağıdır. Kod veya veritabanı değişikliği yapmadan önce ilgili mevcut akış incelenmeli ve bu kurallar uygulanmalıdır.

## Dil ve Çalışma Biçimi

- Kullanıcıya yapılan açıklamalar Türkçe olmalıdır.
- Değişiklikten önce ilgili sayfa, API, model, yetki ve veritabanı ilişkileri incelenmelidir.
- Mevcut kullanıcı değişiklikleri korunmalı; görev dışındaki dosyalar veya kodlar geri alınmamalıdır.
- Geçici test kayıtları ve dosyaları test sonunda güvenli biçimde temizlenmelidir.
- Değişiklikler mümkün olan en dar kapsamda yapılmalı, mevcut çalışan akışlarda gereksiz yeniden yazım yapılmamalıdır.

## PHP ve Veritabanı

- Veritabanı erişiminde PDO ve hazırlanmış sorgular (`prepare` / `execute`) kullanılmalıdır.
- Kullanıcı girdileri SQL sorgularına doğrudan eklenmemelidir.
- Tablo veya kolon adları varsayılmadan önce mevcut şema `SHOW COLUMNS`, `DESCRIBE` veya eşdeğer sorguyla kontrol edilmelidir.
- Her şema veya sabit veri değişikliği için `database/migrations/` altında tarihli ve tekrar çalıştırılabilir bir `.sql` dosyası oluşturulmalıdır.
- Yalnızca canlı veritabanına komut çalıştırmak veya yalnızca PHP migration bırakmak yeterli değildir; SQL dosyası teslimatın zorunlu parçasıdır.
- PHP migration gerekiyorsa aynı isimli SQL migration ile aynı sonucu üretmeli ve iki dosya da idempotent olmalıdır.
- Migration dosyaları mevcut veriyi silmemeli veya birleştirmemelidir; böyle bir işlem gerekiyorsa kullanıcıdan açık onay alınmalıdır.
- API hata cevapları mümkün olduğunca JSON ve uygun HTTP durum koduyla dönmelidir.

## Yetkilendirme ve Güvenlik

- Yeni bir işlem hem arayüzde hem sunucu tarafındaki API/endpoint içinde yetki kontrolüne sahip olmalıdır.
- Sadece butonu gizlemek yetkilendirme sayılmaz.
- İstemciden gelen tablo, kolon veya dosya yolu değerleri doğrulanmadan kullanılmamalıdır.
- HTML çıktısına yazılan kullanıcı/veritabanı verileri `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` ile kaçırılmalıdır.
- Silme, dışa aktarma ve toplu işlemler mevcut yetki sistemiyle ayrı ayrı kontrol edilmelidir.

## Firma Kayıtları

- Firma adları kaydedilirken baştaki ve sondaki boşluklar temizlenmelidir.
- Aynı isimde aktif firma kaydı büyük/küçük harften bağımsız olarak engellenmelidir.
- Firma silme fiziksel `DELETE` ile yapılmamalıdır; `deleted_at` ve `deleted_by` alanlarıyla pasife alınmalıdır.
- Firma pasife alındığında bağlı teklifler, servisler, raporlar ve diğer geçmiş kayıtlar korunmalıdır.
- Yeni kayıt seçimlerinde yalnızca aktif firmalar gösterilmelidir.
- Geçmiş teklif ve servis listelerinde pasif firmanın adı korunmalı, bağlantısız biçimde “Silinmiş” etiketiyle gösterilmelidir.
- Pasif firma düzenleme sayfası ve güncelleme API’si üzerinden değiştirilememelidir.

## Arayüz

- Sistem genelinde mevcut Bootstrap sınıfları korunmalı; ortak görsel değişiklikler global tema dosyasından yapılmalıdır.
- Buton, kart ve tablo tasarımları sade, kompakt ve tutarlı olmalıdır; yoğun animasyon ve aşırı gölge kullanılmamalıdır.
- Tablo içindeki işlem butonları küçük ve tek satırda kalmalıdır.
- Mobil görünüm ve dark mode mevcutsa yapılan stil değişikliği bu durumları bozmamalıdır.

## Sürüm Notları Kaydı (Changelog Standardı)

- Sistemde tamamlanan **her konuşma / geliştirme görevi sonunda** `version_notes` tablosuna yapılan işin bilgisi kaydedilmelidir.
- Sürüm notu kaydında şu standartlara uyulmalıdır:
  - `title`: Yapılan geliştirmeyi veya çözümü net özetleyen başlık (Örn: "Sürüm Notları Sayfası Modernizasyonu").
  - `version_tag`: Güncel sürüm etiketi veya alt sürüm numarası (Örn: `v2.4.0` veya `v2026.09.20`).
  - `category`: İşin niteliğine göre uygun kategori (`feature`, `improvement`, `bugfix`, `security`, `other`).
  - `description`: Yapılan değişiklikleri, eklenen/güncellenen özellikleri ve düzeltmeleri listeleyen maddeli açıklama (`- ` maddeleri şeklinde).
  - `author`: "Antigravity AI" veya işlem yapan kullanıcı/ajan bilgisi.
  - `created_at`: Anlık tarih-saat bilgisi (`Y-m-d H:i:s`).
- **SQL Migration Dosyası Zorunluluğu**:
  - Eklenen her sürüm notu için mutlaka `database/migrations/` altında tarihli ve idempotent bir `.sql` dosyası oluşturulmalıdır (Örn: `database/migrations/YYYYMMDD_HHMMSS_version_note_vX_X_X.sql` veya `YYYYMMDD_add_version_note_vX_X_X.sql`).
  - SQL dosyası `INSERT INTO version_notes (...) SELECT ... WHERE NOT EXISTS (...)` formatında olmalı ve tekrar çalıştırıldığında mükerrer kayıt oluşturmamalıdır.
- Kayıt hem `database/migrations/` altındaki SQL dosyasında bulunmalı hem de doğrudan veritabanına (`App\Model\VersionNoteModel` veya güvenli PDO sorgusu ile) eklenmeli ve teslimat açıklamasında sürüm notunun ve SQL dosyasının oluşturulduğu belirtilmelidir.

## Doğrulama

- Değiştirilen PHP dosyalarında `php -l` çalıştırılmalıdır.
- SQL migration mümkünse boş veya geçici bir test şemasında uygulanmalı; en azından hedef MariaDB/MySQL sürümüyle sözdizimi doğrulanmalıdır.
- Veritabanı davranışı değiştiğinde ilgili kayıt ilişkisi test edilmelidir.
- Test için eklenen veriler yalnızca açıkça belirlenmiş kimliklerle temizlenmeli; geniş veya belirsiz silme sorguları kullanılmamalıdır.

