# Gerçek Ýigit web sitesi: kurulum ve önizleme

Bu dizin çok dilli PHP web sitesidir. İçerik `gercekyigit_site` veritabanından ve `admin/uploads/` klasöründen gelir. Üretilen temsili görseller `img/generated/` altındadır; gerçek sertifika veya partner kaydı yerine kullanılmaz.

## Yerel önizleme

Proje kökünde MariaDB konteynerini başlatın:

```bash
docker compose up -d db
```

İlk kurulumda, web kökü dışındaki `../private-backups/gercekyigit.sql` dosyasını `gercekyigit_site` adlı veritabanına aktarın. Ardından `database/migrations/20261013_slider_targets.sql` dosyasını aynı veritabanında çalıştırın. Bu dosya eski, mevcut olmayan proje bağlantılarını ilgili hizmetlere yönlendirir; mevcut geçerli bağlantıları korur. Yerel sunucu için:

```bash
GERCEK_PRODUCTION_MODE=0 GERCEK_DB_HOST=127.0.0.1 GERCEK_DB_PORT=3307 \
GERCEK_DB_NAME=gercekyigit_site GERCEK_DB_USER=root GERCEK_DB_PASS=root \
php -S 127.0.0.1:8765 -t gercek
```

Tarayıcıda `http://127.0.0.1:8765/index.php` adresini açın. `?page=about`, `?page=services`, `?page=projects`, `?page=news`, `?page=gallery`, `?page=certificates`, `?page=catalogues` ve `?page=contact` ile sayfaları ayrı ayrı kontrol edin.

## Yayınlama

1. `gercek/` içeriğini HTTPS ile sunulan web köküne yükleyin. `admin/uploads/` içerik dosyalarını da aktarın. PHP dosyaları 644, klasörler 755; yalnızca yükleme klasörleri web sunucusu tarafından yazılabilir olmalıdır.
2. Veritabanı dışa aktarımını ve `custom-site-db.json` dosyasını web kökü dışında tutun. Bu proje düzeninde özel dosya yolu `../private-backups/` klasörüdür. `config.php` bu yolu öncelikle kontrol eder; yerel dosya yoksa eski sunucu yolu `dirname(__DIR__, 2)/gercekyigit-private-backups/custom-site-db.json` kullanılmaya devam eder. Sunucudaki klasör yapısı farklıysa `$privateConfigPath` değerini uygun özel dizine ayarlayın.
3. `GERCEK_DB_HOST`, `GERCEK_DB_NAME`, `GERCEK_DB_USER`, `GERCEK_DB_PASS`, `GERCEK_DB_PORT` ortam değişkenlerini tanımlayın veya özel JSON yapılandırmasını sağlayın. Ortam değişkenleri önceliklidir.
4. Üretimde geçerli bir TLS sertifikası kurup doğrulayın. HTTPS yönlendirmesini sertifika çalıştıktan sonra etkinleştirin. Üretim modunda PHP hataları ziyaretçiye gösterilmez.
5. E-posta gönderimini kullanacaksanız yönetim panelinden SMTP ayarlarını tamamlayın ve iletişim formunu gerçek bir test mesajıyla doğrulayın. Harita bağlantısını `contact_settings` tablosundan ayarlayın.
6. Yedek `.sql`, `.gz`, `.orig`, `.txt` dosyalarını web köküne geri koymayın. `.htaccess` bu uzantıları ek önlem olarak engeller.

## Kontrol listesi

- [ ] Ana sayfa ve mobil menü
- [ ] Dil geçişleri (EN/RU/TKM)
- [ ] Hizmet, proje, haber ve galeri detayları
- [ ] Gerçek içerik fotoğrafları ve temsili görseller
- [ ] Katalog ve partner verisi varsa bunların görünümü
- [ ] İletişim formu, SMTP ve harita
- [ ] Yönetim paneli, yükleme izinleri ve HTTPS sertifikası

POS uygulaması ayrı bir Laravel kod ağacıdır. Bu çalışma kopyasında `pos/artisan` ve `pos/composer.json` bulunmadığı için web sitesiyle birlikte çalıştırılamaz; tam kaynak paketine ihtiyaç vardır.

## Yeni masaüstü ana sayfa

Ana sayfa `pages/home.php`, `css/home.css`, `js/home.js` ve `inc/home-*.php` dosyalarından oluşur. Metinler EN/TM/RU için `inc/home-content.php` içindedir; haberler ve hizmetlerin görünürlüğü mevcut veritabanından gelir. Tüm iç sayfalar da aynı menü, alt bilgi ve tipografi sistemini kullanır.

`media/textile-film.mp4`, kullanıcının Gemini hesabında 13 Ekim 2026 tarihinde oluşturulmuş 10 saniyelik temsili tekstil filmidir; gerçek tesis çekimi değildir. İndirilen videonun görüntüsü yeniden kodlanmadan korunmuş, ses izi kaldırılmış ve hızlı web başlangıcı etkinleştirilmiştir. `img/generated/textile-film-poster.webp` aynı filmin duraklatılmış karesidir. Video, azaltılmış hareket/veri tasarrufu tercihinde otomatik başlamaz; ekran dışında durur ve ziyaretçi oynatmayı duraklatabilir.

Önceki ana sayfanın kopyası web kökü dışındaki `../private-backups/before-editorial-home-20261013/` dizinindedir.

## Üç dil ve ortak tipografi

`database/migrations/20261013_localize_content.sql` dosyasını içerik veritabanına uygulayın. Haberler için dil sütunlarını ekler; mevcut hizmet, ürün, galeri, haber, adres ve hakkımızda içeriklerinin EN/RU/TKM metinlerini tamamlar. Dolu ve farklı çevirileri korur. Yerel veritabanına uygulanmıştır; üretime geçmeden önce üretim veritabanını yedekleyip aynı geçişi çalıştırın.

Dil seçimi `inc/language-switcher.php` üzerinden tüm ziyaretçi sayfalarında gösterilir. Dış bağlantılarda Türkmençe `lang=tkm`, HTML dil kodunda `tk`, mevcut veritabanı alanlarında ise uyumluluk için `tm` kullanılır. Eski `lang=tm` bağlantıları desteklenir. Seçim çerezde saklanır; sayfa ve içerik kimliği dil değiştirilirken korunur.

Site genelindeki Gilroy fontları `css/fonts/gilroy/`, font ağırlıkları ve 14 px buton köşeleri `css/typography.css` içindedir. Fontlar kullanıcının mevcut `abat-melhem/abat-app-client/assets/fonts/` dosyalarından alınmış ve yerel olarak sunulmuştur; Türkmençe ve Rusça karakter kapsamı doğrulanmıştır. Dairesel oynatma ve simge düğmeleri dairesel kalır. Font için dış ağ isteği gerekmez.


## Tüm ziyaretçi sayfalarının yenilenmesi — 14 Ekim 2026

- Ortak üst menü/alt bilgi: `header.php`, `inc/home-nav.php`, `inc/home-footer.php`.
- İç sayfa tasarımları: `pages/*.php`, `css/pages.css`; üç dilde yeni metinler: `inc/page-copy.php`.
- Ortak içerik yardımcıları: `inc/editorial.php`. Mevcut içerik, ürün görselleri, sertifikalar ve haberler veritabanından gelir.
- Görsel inceleme: `inc/media-dialog.php` ve `js/pages.js`; yerleşik HTML dialog, Escape, ok tuşları, odağı geri verme ve galeri filtreleri.
- Eski Bootstrap/jQuery/Owl/WOW dosyaları ziyaretçi sayfalarında artık yüklenmez. Yönetim panelinin bağımlılıkları korunur.
- `?page=services&id=...` eski bağlantıları ilgili sektör detayını açmaya devam eder.
- Katalog ve proje arşivi boşken gerçek içerik varmış gibi kayıt üretilmez; ilgili hizmet ve iletişim bağlantıları gösterilir.

### Yeni görseller

Yerleşik `image_gen` aracıyla üretildi ve WebP olarak projeye kaydedildi:

- `img/generated/sector-textile.webp`: Türkmenistan bağlamında pamuk dokuma salonu.
- `img/generated/sector-socks.webp`: çorap örme makinesi ve pamuk iplikleri.
- `img/generated/sector-construction.webp`: Aşgabat mimarisinden esinlenen temsili yapı sahası.
- `img/generated/sector-machinery.webp`: Köpetdağ eteklerini çağrıştıran temsili iş makinesi sahası.

Tam üretim istemleri `img/generated/sector-prompts.json` dosyasındadır. Bu görseller şirketin gerçek tesis/proje fotoğrafı olarak sunulmaz; ilgili sayfalarda üç dilde temsili görsel açıklaması bulunur. Orijinal ürün ve sertifika görselleri korunmuştur.

### 30 saniyelik ana sayfa filmi

`media/textile-story-30s.mp4`: 1280×720, H.264, 24 fps, ses izi yok, hızlı web başlangıcı. Üç 10 saniyelik Gemini filmi 0,6 saniyelik geçişlerle birleştirildi; geçişlerde önceki planın son karesi uzatılarak toplam süre tam 30 saniye tutuldu.

1. `media/textile-film.mp4`: dokuma tezgâhı ve iplikler.
2. `media/textile-finishing.mp4`: desenli kumaşın bitim merdanesinden geçişi.
3. `media/textile-products.mp4`: bitmiş havlu, kumaş ve çoraplar; çalışanın havluyu eline alıp incelemesi.

Kaynak filmler kullanıcının Gemini hesabındaki aynı sohbette oluşturuldu. Temsili yapay zekâ çekimleridir. Ana sayfada oynat/duraklat kontrolü, ekran dışındayken durdurma ve azaltılmış hareket desteği vardır. İnşaat filmlerinin eklenmesiyle bölüm göstergesinin yerini aşağıda açıklanan sektör seçicisi almıştır.

### Doğrulama ve geri dönüş

51 sayfa/dil yanıtı; yerel sayfa bağlantıları ve 64 görsel, stil, betik ve video kaynağı kontrol edildi. İletişim formunun zorunlu alanları ve geçersiz CSRF reddi kontrol edildi; gerçek e-posta gönderilmedi. Üretim e-posta teslimi SMTP kurulumu ile ayrıca doğrulanmalıdır.

1440×1000 masaüstü görünümünde dört sektör, hakkımızda, haber/haber detayı, galeri, sertifikalar, katalog, projeler, arama, iletişim ve abonelik sayfaları görsel olarak incelendi. Galeri filtresi, görsel penceresinde ileri/Escape/odak dönüşü ve sertifika görünümü doğrulandı. Ana sayfada tarayıcı 30 saniye süreyi okudu; 03/03 bölüm göstergesi, duraklatma ve yeniden oynatma çalıştı. Son tarayıcı kontrolünde JavaScript hatası veya uyarısı yoktu.

Bu tur öncesi kaynaklar web kökü dışındaki `../private-backups/before-all-pages-20261013/` dizinindedir. Katalog kaydındaki eski PDF yolu, içerik ve kapak kontrolüyle projede bulunan gerçek makine kataloğuna bağlandı. Katalog tablosunun önceki kopyası aynı yedek dizininde `catalogues.sql` olarak saklandı. `database/migrations/20261014_repair_catalogue_path.sql` yalnızca eski yolu taşıyan kaydı günceller; yerelde uygulanmıştır. Bir önceki çeviri geçişi ve bu yol düzeltmesi yeni sunucuda hâlâ gereklidir.

## Açık / koyu tema — 14 Ekim 2026

Tüm ziyaretçi sayfalarının ortak üst menüsünde güneş/ay düğmesi bulunur. İlk ziyaret cihazın renk tercihini izler. Kullanıcının seçimi `gercek-theme` localStorage anahtarına kaydedilir; sayfa, dil ve sekme değişiminde korunur. Açık sekmeler `storage` olayıyla eşitlenir. Depolama engellense bile mevcut sayfadaki düğme çalışır. JavaScript kapalıysa açık tema ve içerik kullanılabilir; işlevsiz düğme gösterilmez.

- Renkler: `css/theme.css`. Açık tema sıcak krem; koyu tema derin yeşil yüzeyler, açık metin ve adaçayı vurgularıdır. Formlar, görsel pencereleri, uyarılar, makale metinleri, menüler ve çağrı alanları ayrı renklerle düzenlenmiştir. Fotoğraflara renk filtresi uygulanmaz.
- Davranış: `js/theme.js`, ilk boyamadan önce başlık bölümünde yüklenir. Tema için yeni kütüphane yoktur. Destekleyen tarayıcıda [View Transitions](https://developer.mozilla.org/en-US/docs/Web/API/Document/startViewTransition) ile 200 ms geçiş; diğerlerinde yüzey renklerine 200 ms CSS geçişi uygulanır. Güneş/ay simgeleri 200 ms transform/opacity geçişi kullanır. Klavye ve azaltılmış hareket tercihinde geçiş anlıktır.
- Erişilebilirlik: 44×44 px düğme, EN/RU/TKM işlem açıklaması, görünür odak ve etkin menü işareti. Tema değişimi form değerlerini değiştirmez. Ana metin kontrastı her iki temada 13:1 üzerinde; ikincil metin/form yüzeyi açık temada 4,62:1, koyu temada 6,90:1; form çizgileri 3:1 üzerinde ölçülmüştür.
- Kontrol: 17 sayfa görünümü × 2 tema = 34 tarayıcı kontrolü, üç dilde düğmeler, sıfır yatay taşma. Sayfa/dil/yenileme kalıcılığı, klavye, sekmeler arası eşitleme, form değeri ve görsel penceresi doğrulandı. Başlangıç tercihi, hızlı tıklama, kısıtlı depolama, azaltılmış hareket ve yedek geçiş dahil 13 davranış kontrolü geçti.

Değişiklik öncesi kaynak kopyaları `../private-backups/before-theme-20261014/` içindedir. Bu tema çalışması veritabanı geçişi gerektirmez.

## Gurluşyk görsellerinin yenilenmesi — 14 Ekim 2026

`service_detail&id=4` sayfası için yerleşik `image_gen` aracıyla bir kapak ve altı galeri görseli üretildi. Türkmenistan mimarisi ve inşaat faaliyetlerinden esinlenen temsili görseller `img/generated/construction/` içindedir: `hero.webp`, `earthworks.webp`, `port-steel.webp`, `decorative-steel.webp`, `carport.webp`, `granite.webp`, `site-assembly.webp`. Tam istemler ve kaynak/çıktı eşleştirmeleri aynı dizindeki `prompts.json` dosyasındadır.

`inc/construction-media.php` yalnızca mevcut altı eski CMS görsel yolunu yeni dosyalarla eşleştirir. Veritabanı kayıtları ve özgün yüklemeler korunur; yönetim panelinden farklı bir fotoğraf kaydedilirse bu eşleştirme otomatik olarak devreden çıkar. İnşaat sektörü kartı ve detay kapağı ortak yeni kapak görselini kullanır. Galeri kartları ile büyütme penceresinde EN/RU/TKM temsili görsel açıklaması bulunur.

Üç dilde sayfa yanıtları, yedi WebP kaynağı, açık/koyu tema görünümü ve büyütme penceresi doğrulandı. Tarayıcı tüm yeni görselleri tam çözünürlükte yükledi. Değişiklik öncesi dosyalar `../private-backups/before-construction-images-20261014/` içindedir. Bu değişiklik veritabanı geçişi gerektirmez.

## İş makineleri görsellerinin yenilenmesi — 14 Ekim 2026

`service_detail&id=1` sayfasının kapağı ve beş galeri görseli yerleşik `image_gen` ile yeniden üretildi. Türkmenistan bağlamındaki temsili kazı, ekipman kontrolü, operatör, bakım ve filo sahneleri `img/generated/machinery/` dizinindedir: `hero.webp`, `earthmoving.webp`, `quality-inspection.webp`, `operator.webp`, `maintenance.webp`, `fleet.webp`. Tam istemler ve kaynak yolları `prompts.json` içindedir. Bunlar şirketin gerçek çalışanlarını veya makine filosunu gösteren fotoğraflar değildir; kartlarda ve büyütme penceresinde üç dilde temsili görsel notu vardır.

`inc/machinery-media.php` beş mevcut CMS görsel yolunu eşleştirir; özgün dosyalar ve veritabanı korunur. Yeni bir CMS yüklemesi eşleştirmeyi devre dışı bırakır. Ortak sektör kartı ve detay sayfası yeni kapağı kullanır. Kaynak yedeği `../private-backups/before-machinery-images-20261014/` içindedir.

PHP sözdizimi, EN/RU/TKM sayfa yanıtları, altı WebP kaynağı, önceki inşaat ve diğer sektör eşleştirmeleri kontrol edildi. Masaüstünde kapak, kartlar, tam çözünürlükte yüklenme, büyütme penceresi ve açık/koyu tema doğrulandı. Veritabanı geçişi gerekmez.

## Galeri: Gurluşyk tehnikasy — 14 Ekim 2026

Galerinin 1 numaralı grubundaki dört eski fotoğraf için yerleşik `image_gen` aracıyla yeni temsili görseller üretildi: `img/generated/gallery-construction/earthworks.webp`, `haulage.webp`, `site-preparation.webp`, `excavation-detail.webp`. Her biri 1536×1024 boyutundadır. Tam istemler ve kaynak PNG yolları aynı dizindeki `prompts.json` dosyasındadır.

`inc/gallery-construction-media.php` özgün CMS yollarını yeni görseller ve EN/RU/TKM başlıklarla eşleştirir. `pages/gallery.php` bu grubu iki sütunda gösterir; her yeni görselde üç dilde temsili görsel notu vardır. Gerçek şirket projelerinin fotoğrafları olarak sunulmaz. Grup başlığı, mevcut içerikler ve 12 çorap görseli korunmuştur. Başka bir fotoğraf yükleyen CMS yöneticisinin yeni yolu öncelik kazanır; veritabanı değişikliği yapılmaz.

Üç dilde yanıtlar, dört WebP dosyası ve PHP sözdizimi kontrol edildi. Masaüstünde iki tema, kategori filtresi, dört görselin tam çözünürlükte yüklenmesi ve büyütme penceresinde 04/04 → 01/04 geçişi doğrulandı. Önceki galeri şablonu `../private-backups/before-gallery-construction-20261014/` dizinindedir.

## Üst menü ikonlarının hizalanması — 14 Ekim 2026

`inc/home-nav.php` ve `css/theme.css` içinde tema, arama, iletişim ve açılır menü ikonları ortak 20×20 SVG ölçüsü ve 1,6 px çizgiyle düzenlendi. Dil seçici, tema, arama ve iletişim kontrolleri 44 px yüksekliğe, 14 px köşe yarıçapına ve ortak çerçeveye sahip. Tema/arama düğmeleri 44×44 px; klavye odağı 2 px çizgi ve 3 px aralıkla görünür kalır. Tema geçişi ve bağlantılar korunur. `header.php` stil önbelleği sürümü güncellendi.

1440 px ve 1280 px masaüstü görünümü, EN/RU/TKM menü yerleşimi, açık/koyu tema ve ana sayfanın şeffaf menüsü kontrol edildi. 1280 px genişlikte üç dilde de kontroller 44 px ölçüldü; çakışma veya yatay taşma yoktu. Klavyeyle arama, iletişim bağlantısı ve tema düğmesi doğrulandı. Önceki kaynaklar `../private-backups/before-nav-icons-20261014/` dizinindedir.

## Ana sayfa: inşaat ve makine filmleri — 14 Ekim 2026

Kullanıcının Gemini hesabında iki farklı 10 saniyelik film üretildi:

- `media/construction-work.mp4`: vinçle çelik montajı, çalışanın granit döşemeyi kontrol etmesi ve tamamlanan giriş yapısı; 2.353.718 bayt.
- `media/construction-machinery.mp4`: ekskavatör ve damperli kamyon, mobil vinç, kazıcı yükleyici ve operatör; 3.627.980 bayt.

Her iki dosya 1280×720, H.264, 24 fps, tam 10 saniye, sessiz ve `faststart` düzenindedir. Türkmenistan bağlamından esinlenen temsili AI videolarıdır; gerçek şirket projeleri veya sahip olunan araçların kayıtları olarak sunulmaz. İstemler, kaynak sohbet bağlantıları ve dosya bilgileri `media/construction-film-prompts.json` içindedir. Kapak kareleri `img/generated/construction-film-poster.webp` ve `img/generated/machinery-film-poster.webp` dosyalarıdır.

`pages/home.php`, `js/home.js`, `css/home.css` ve `inc/home-content.php` üç dilde Dokma / Gurluşyk / Tehnika seçimini sağlar. Tek video öğesi tekstil (30 s), inşaat (10 s), makine (10 s) sırasını döngüyle oynatır. Kullanıcı filmi doğrudan seçebilir veya duraklatabilir. Yalnızca seçilen kaynak yüklenir; ekran dışında ve gizli sekmede durur. Azaltılmış hareket veya veri tasarrufu tercihinde kendiliğinden başlamaz. Oynatım hatasında kapak görseli görünür; başka film seçilerek devam edilebilir. Klavye ile seçim anlıktır; normal geçiş 250 ms opaklık kullanır. Yeni dosyalar eksikse ilgili seçenek gösterilmez. Ana sayfanın sektör önizlemeleri de yeni inşaat ve makine kapaklarına bağlandı.

PHP/JavaScript sözdizimi, üç dilde yerleşim, gerçek videoların 10 saniye oynatımı, sıra döngüsü, duraklatmanın korunması, klavye seçimi ve ekran dışında durma kontrol edildi. 1280 px masaüstünde üç dilde yatay taşma veya seçici çakışması yoktur. Açık/koyu tema görünümü incelendi. Davranış kontrolü ayrıca kaynak değişimi, yükleme hatasından dönüş, veri tasarrufu ve azaltılmış hareket durumlarını kapsar. Önceki kaynaklar `../private-backups/before-construction-films-20261014/` içindedir; veritabanı geçişi gerekmez.


## Klasörlerin birleştirilmesi — 15 Ekim 2026

Proje klasörünün yanında bulunan üç yardımcı klasör ana `gercekyigit/` klasörüne taşındı:

- `gercek/`: düzenlenen web sitesi kaynakları; yerel önizleme için belge kökü.
- `release/`: eski `gercekyigit-release/` yayın kopyası.
- `release-migrations/`: eski `gercekyigit-release-migrations/` geçiş ve sunucu yardımcı dosyaları.
- `private-backups/`: eski `gercekyigit-private-backups/` özel yedekleri.

Taşıma sırasında mevcut dosyaların içerikleri SHA-256 ile karşılaştırıldı. Kaynak ve yayın kopyasındaki yapılandırma yeni yerel yedek yolunu kullanır; eski sunucu yolu yedek seçenek olarak korunur. Docker Compose içindeki başlangıç dökümü yolu `./private-backups/site.sql.gz` olarak güncellendi. Önceden oluşturulmuş yerel DB konteynerinin bağlama yolunu güncellemek için Docker çalışırken proje kökünde `docker compose up -d db` kullanılabilir; adlandırılmış veri birimi aynı kalır.

Yedek ve geçiş klasörlerine Apache erişimi kapatıldı; proje kökündeki geliştirme yönlendiricisi de bu yolları engeller. Yayınlanacak belge kökü `gercek/` veya hazırlanmış `release/` içeriğidir; tüm çalışma klasörü değildir. Bu düzenleme canlı sunucudaki dosyaları değiştirmez. Önceki ayarlar ve taşıma manifesti `../private-backups/before-folder-consolidation-20261015/` içindedir.
