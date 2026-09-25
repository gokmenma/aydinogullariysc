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

## Özet (KPI) Kartları ve Arama Kutusu Standartları

- Özet (KPI / istatistik) kartları bulunan tüm modül ve liste sayfalarında, özet kartlarını gizleyip gösterebilen (`slideToggle`) bir daraltma/genişletme butonu (`#toggleKpiSummary` veya `.btn-kpi-toggle`) bulunmalıdır.
- **Buton Konumu ve Hizalama Standardı**: Tablo kart başlığında (`.form-card-header`), bu buton **HER ZAMAN ARAMA KUTUSUNUN SAĞINDA** yer almalıdır (`[ Arama Kutusu ] [ ^ Gizle/Göster Butonu ]`). Arama kutusu ile buton arasında `gap: 8px` boşluk bırakılmalıdır.
- **İkon Standardı**: Kartlar açıkken yukarı ok (`fa fa-chevron-up`), kapalıyken aşağı ok (`fa fa-chevron-down`) ikonu gösterilmeli; butonun `title` niteliğinde "Özet Kartlarını Gizle / Göster" açıklaması bulunmalıdır.
- **Kalıcılık (localStorage)**: Kullanıcının özet kartlarını gizleme/gösterme tercihi `localStorage` üzerinde modüle özgü anahtarla (örn. `aydinogullari_kpi_[modul]_collapsed`) saklanmalı ve sayfa yenilendiğinde layout kayması (CLS) yaşanmaması için sayfa yüklenirken bu tercih derhal uygulanmalıdır.
- **Genişlik ve Hizalama**: Özet KPI kartları (`.mission-kpi-grid`, `.crm-kpi-grid` vb.) `grid-template-columns: repeat(4, 1fr); width: 100%;` gibi CSS Grid yapısıyla üstteki başlık kartı ve alttaki tablo kartıyla birebir aynı hizada ve kenarlardan taşma yapmayacak şekilde `%100` genişlikte yerleştirilmelidir.

## Sürüm Notları Kaydı (Changelog Standardı)

- Sürüm notu, her mesaj veya ara adım için değil; kullanıcıya teslim edilebilir durumdaki **tamamlanmış geliştirme paketi** için bir kez oluşturulmalıdır.
- Aynı konuşmada veya farklı konuşmalarda aynı amaca hizmet eden geliştirme, düzeltme ve doğrulamalar tek sürüm notunda birleştirilmelidir. Görev devam ederken ara sürüm kayıtları açılmamalıdır.
- Tüm sürüm notu SQL kayıtları yalnızca `database/migrations/version_notes.sql` dosyasında tutulmalıdır. Sürüm notu için ayrı tarihli SQL dosyası oluşturulmamalı ve başka migration dosyalarının içine sürüm notu eklenmemelidir.
- Birbirinden bağımsız ve ayrı teslim edilebilen geliştirmeler aynı konuşmada yapılsa bile ayrı sürüm notları olabilir; yalnızca mesaj sayısı veya çalışma süresinin uzaması yeni sürüm gerekçesi değildir.
- Yalnızca analiz, inceleme, soru-cevap, test, dokümantasyon veya proje çalışma kurallarındaki değişiklikler; uygulamanın kodunu, veritabanını ya da kullanıcıya sunulan davranışı değiştirmiyorsa sürüm notu gerektirmez.
- Sürüm notu ve buna ait SQL migration, işin kapsamı netleşip uygulama ve doğrulama tamamlandıktan sonra, nihai teslimattan hemen önce hazırlanmalıdır.
- Sürüm notu kaydında şu standartlara uyulmalıdır:
  - `title`: Yapılan geliştirmeyi veya çözümü net özetleyen başlık (Örn: "Sürüm Notları Sayfası Modernizasyonu").
  - `version_tag`: Güncel sürüm etiketi veya alt sürüm numarası (Örn: `v2.4.0` veya `v2026.09.20`).
  - `category`: İşin niteliğine göre uygun kategori (`feature`, `improvement`, `bugfix`, `security`, `other`).
  - `description`: Kullanıcının anlayacağı dilde, teknik ayrıntıya girmeyen ve tercihen tek maddeden oluşan kısa açıklama (`- ` ile başlamalıdır).
  - `author`: "Antigravity AI" veya işlem yapan kullanıcı/ajan bilgisi.
  - `created_at`: Anlık tarih-saat bilgisi (`Y-m-d H:i:s`).
- **Merkezi SQL Dosyası Zorunluluğu**:
  - Teslimatta eklenen sürüm notu mevcut `database/migrations/version_notes.sql` dosyasına eklenmelidir; ikinci bir sürüm notu SQL dosyası oluşturulmamalıdır.
  - SQL kaydı `INSERT INTO version_notes (...) SELECT ... WHERE NOT EXISTS (...)` biçiminde olmalı ve tekrar çalıştırıldığında mükerrer kayıt oluşturmamalıdır.
- Kayıt, nihai teslimat aşamasında hem merkezi SQL dosyasında bulunmalı hem de doğrudan veritabanına (`App\Model\VersionNoteModel` veya güvenli PDO sorgusu ile) eklenmelidir. Teslimat açıklamasında sürüm notunun ve merkezi SQL dosyasının güncellendiği belirtilmelidir.

## Doğrulama

- Değiştirilen PHP dosyalarında `php -l` çalıştırılmalıdır.
- SQL migration mümkünse boş veya geçici bir test şemasında uygulanmalı; en azından hedef MariaDB/MySQL sürümüyle sözdizimi doğrulanmalıdır.
- Veritabanı davranışı değiştiğinde ilgili kayıt ilişkisi test edilmelidir.
- Test için eklenen veriler yalnızca açıkça belirlenmiş kimliklerle temizlenmeli; geniş veya belirsiz silme sorguları kullanılmamalıdır.
