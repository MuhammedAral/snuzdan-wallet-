# Snuz — Projede Kullanılacak Teknolojiler

> **Proje:** Snuz — Kişisel Finans ve Yatırım Takip Uygulaması  
> **Tür:** Web Uygulaması + Mobil Uygulama (iOS & Android)  
> **Ekip:** Muhammed Ali Aral, Akif  
> **Tarih:** Nisan 2026

---

## Genel Mimari Yaklaşım

Snuz, tek bir backend API üzerinden hem web hem mobil platformlara hizmet veren **monorepo** mimarisiyle geliştirilecektir. Backend tamamen PHP (Laravel) ile yazılacak, web ve mobil frontendler ise React/TypeScript tabanlı olacaktır. Tüm geliştirme ortamı Docker üzerinde çalışacaktır.

```
                    ┌──────────────────┐
                    │   Laravel API    │
                    │   (PHP 8.3)      │
                    └────────┬─────────┘
                             │
                    ┌────────┴─────────┐
                    │    JSON API      │
                    └────────┬─────────┘
                             │
              ┌──────────────┼──────────────┐
              ▼              │              ▼
     ┌────────────┐          │     ┌────────────────┐
     │  Web App   │          │     │  Mobil App     │
     │  (React)   │          │     │ (React Native) │
     └────────────┘          │     └────────────────┘
                             │
                    ┌────────┴─────────┐
                    │   PostgreSQL     │
                    │   + Redis        │
                    └──────────────────┘
```

Bu mimari tercih edilme sebebi, web ve mobil uygulamaların **aynı API'yi kullanması** sayesinde kod tekrarını azaltması, bakım kolaylığı sağlaması ve ölçeklenebilir bir yapı sunmasıdır.

---

## 1. Backend Teknolojileri

### 1.1 PHP 8.3
**Rol:** Backend programlama dili

PHP, web geliştirme alanında 30 yılı aşkın geçmişe sahip, dünya genelinde web sitelerinin yaklaşık %77'sinde kullanılan bir dildir. 8.3 sürümü ile gelen JIT derleyici, readonly sınıflar ve enum desteği gibi modern özelliklerle performans ve tip güvenliği önemli ölçüde artmıştır. Laravel ekosistemiyle birlikte kurumsal düzeyde uygulama geliştirmeye uygundur.

### 1.2 Laravel 11
**Rol:** Backend framework (MVC mimarisi)

Laravel, PHP dünyasının en popüler ve kapsamlı framework'üdür. Projemizde tercih edilmesinin başlıca sebepleri:

- **MVC mimarisi:** Model-View-Controller yapısı sayesinde kod organizasyonu temiz ve sürdürülebilir kalır.
- **Eloquent ORM:** Veritabanı işlemlerini nesne yönelimli olarak yönetmeyi sağlar; SQL yazmadan karmaşık sorgular kurulabilir.
- **Migration sistemi:** Veritabanı şemasını versiyon kontrollü olarak yönetir. Ekip içi tutarlılık sağlar.
- **Form Request:** Gelen isteklerin doğrulanmasını (validation) controller'dan ayırarak temiz bir yapı sunar.
- **Queue & Scheduler:** Zaman alıcı işleri (fiyat verisi çekme, e-posta gönderme, AI çağrısı) arka plana atarak kullanıcı deneyimini iyileştirir.
- **Sanctum:** API kimlik doğrulama sistemi; hem web hem mobil uygulama için token tabanlı güvenli erişim sağlar.
- **Laravel Sail:** Docker tabanlı geliştirme ortamını tek komutla ayağa kaldırır.

### 1.3 PostgreSQL 16
**Rol:** İlişkisel veritabanı

PostgreSQL, açık kaynak ilişkisel veritabanları arasında en güçlü ve güvenilir olanıdır. Snuz'un finansal defter (ledger) mimarisi için tercih edilme sebepleri:

- **CHECK constraint desteği:** Append-only (salt-ekleme) kurallarını veritabanı seviyesinde zorlar; yanlışlıkla veri silinmesini veya değiştirilmesini engeller.
- **VIEW desteği:** Portföy pozisyonları ve kâr/zarar hesaplamalarını hesaplanan görünümler (computed views) olarak tanımlar; veri saklamadan gerçek zamanlı sonuç üretir.
- **JSONB tipi:** AI yanıtları gibi yarı yapılandırılmış verileri verimli şekilde saklar.
- **UUID desteği:** Birincil anahtar olarak UUID kullanımını doğal olarak destekler.
- **Güvenilirlik:** ACID uyumluluğu ile finansal verilerin tutarlılığını garanti eder.

### 1.4 Redis
**Rol:** Önbellekleme (cache) ve kuyruk (queue) yönetimi

Redis, bellek içi (in-memory) çalışan bir veri yapıları sunucusudur. Projemizde iki temel amaçla kullanılacaktır:

- **Cache:** Sık erişilen verileri (döviz kurları, fiyat verileri) bellekte tutarak veritabanı yükünü azaltır ve yanıt sürelerini kısaltır.
- **Queue Driver:** Laravel Queue sisteminin arka plan işlerini yönetmesi için mesaj kuyruğu sağlar. Fiyat güncellemeleri, e-posta gönderimi ve AI çağrıları bu kuyruk üzerinden işlenir.

### 1.5 Laravel Sanctum
**Rol:** API kimlik doğrulama

Sanctum, Laravel'in hafif API authentication paketidir. Hem web uygulaması (cookie tabanlı) hem mobil uygulama (token tabanlı) için güvenli kimlik doğrulama sağlar. E-posta/şifre girişi ve Google OAuth desteği Sanctum üzerinden yönetilecektir.

### 1.6 Google Gemini API (Ücretsiz Katman)
**Rol:** Yapay zeka entegrasyonu

Gemini, Google'ın en gelişmiş büyük dil modelidir. Ücretsiz katmanı dakikada 60 istek kapasitesi sunar. Projemizde üç temel alanda kullanılacaktır:

- **Doğal dil ile işlem ekleme:** Kullanıcı "Dün akşam 250 lira yemek yedim" gibi serbest metin girer; AI bu metni ayrıştırarak tutar, kategori, tarih ve not alanlarını otomatik doldurur.
- **Akıllı kategori tahmini:** Kullanıcı bir not yazdığında, AI'ın o gider veya gelir için en uygun kategoriyi önermesi sağlanır.
- **Aylık finansal analiz raporu:** Kullanıcının aylık harcama ve gelir verilerini analiz ederek Türkçe doğal dilde özet ve öneriler üreten bir rapor sistemi.

Bu özellikler, uygulamayı standart bir finans takip aracından ayırarak kullanıcı deneyiminde önemli bir fark yaratmayı hedeflemektedir.

---

## 2. Web Frontend Teknolojileri

### 2.1 React 18
**Rol:** Kullanıcı arayüzü framework'ü

React, Meta (Facebook) tarafından geliştirilen ve dünya genelinde en yaygın kullanılan frontend kütüphanesidir. Bileşen tabanlı (component-based) mimarisi sayesinde tekrar kullanılabilir, test edilebilir ve bakımı kolay arayüzler oluşturmayı sağlar. 18. sürümüyle gelen Concurrent Rendering ve Suspense özellikleri, kullanıcı deneyimini iyileştirir.

### 2.2 TypeScript
**Rol:** Tip güvenliği sağlayan JavaScript üst kümesi

TypeScript, JavaScript'e statik tip sistemi ekleyen bir dildir. Büyük projelerde hataları derleme zamanında yakalamayı, kod okunabilirliğini artırmayı ve ekip içi geliştirme tutarlılığını sağlamayı mümkün kılar. Hem web hem mobil frontendde kullanılarak tip tanımlarının paylaşılması sağlanacaktır.

### 2.3 Vite
**Rol:** Build aracı ve geliştirme sunucusu

Vite, modern JavaScript projeleri için son derece hızlı bir build aracıdır. Geleneksel araçlara (Webpack gibi) kıyasla geliştirme sunucusu anında başlar, Hot Module Replacement (HMR) ile değişiklikler milisaniyeler içinde yansır. React projelerinde standart olarak tercih edilmektedir.

### 2.4 Tailwind CSS
**Rol:** CSS framework'ü

Tailwind CSS, utility-first yaklaşımıyla çalışan bir CSS framework'üdür. Önceden tanımlanmış yardımcı sınıflar (utility classes) kullanarak hızlı ve tutarlı tasarım yapılmasını sağlar. Projemizde açık/koyu tema desteği Tailwind'in dark mode özelliğiyle yönetilecektir.

### 2.5 shadcn/ui
**Rol:** UI bileşen kütüphanesi

shadcn/ui, Radix UI primitifleri üzerine kurulmuş, kopyala-yapıştır mantığıyla çalışan bir bileşen koleksiyonudur. Geleneksel paket bağımlılığı oluşturmaz; bileşenler doğrudan projeye kopyalanır ve özelleştirilir. Buton, form, dialog, tablo, sekme gibi temel arayüz elemanlarını erişilebilir (accessible) ve tutarlı şekilde sunar.

### 2.6 TanStack Query (React Query)
**Rol:** Sunucu durumu yönetimi (server state management)

TanStack Query, API'den gelen verilerin çekilmesini, önbelleklenmesini, senkronize edilmesini ve güncellenmesini yöneten bir kütüphanedir. Canlı fiyat verilerinin belirli aralıklarla otomatik güncellenmesi (polling), hata durumunda yeniden deneme ve arka plan senkronizasyonu gibi özellikleri ile kullanıcıya her zaman güncel veri sunulmasını sağlar.

### 2.7 Axios
**Rol:** HTTP istemcisi

Axios, tarayıcı ve Node.js ortamlarında çalışan, Promise tabanlı bir HTTP istemcisidir. API çağrılarında interceptor desteği, otomatik JSON dönüşümü ve hata yönetimi kolaylığı sunar. Web ve mobil uygulamada ortak kullanılarak kod paylaşımı sağlanacaktır.

### 2.8 Zod
**Rol:** İstemci tarafı veri doğrulama (client-side validation)

Zod, TypeScript-first bir şema doğrulama kütüphanesidir. Form verilerini kullanıcı göndermeden önce istemci tarafında doğrular ve anlamlı hata mesajları üretir. Aynı şemalar web ve mobil uygulamada paylaşılarak doğrulama kurallarının tutarlılığı garanti edilir.

### 2.9 Recharts
**Rol:** Standart grafikler (çizgi, çubuk, pasta, alan grafikleri)

Recharts, React bileşenleri olarak kullanılan, hafif ve esnek bir grafik kütüphanesidir. Gelir/gider çubuk grafikleri, portföy değer değişimi çizgi grafikleri ve varlık dağılımı pasta grafikleri gibi standart finansal görselleştirmeler için kullanılacaktır.

### 2.10 nivo
**Rol:** Gelişmiş ve fark yaratan görselleştirmeler

nivo, D3.js tabanlı, React uyumlu bir gelişmiş veri görselleştirme kütüphanesidir. Projede standart grafiklerin ötesinde, dikkat çekici ve profesyonel görselleştirmeler sunmak amacıyla kullanılacaktır:

- **Sankey Diagram:** Gelir kaynaklarından gider kategorilerine para akışını görselleştirir. Kullanıcının parasının nereden gelip nereye gittiğini tek bir grafikte anlamasını sağlar.
- **Heatmap Takvim:** GitHub katkı haritası tarzında, her günün harcama yoğunluğunu renk skalasıyla gösteren bir takvim. Kullanıcının harcama alışkanlıklarını bir bakışta görmesini sağlar.
- **Treemap:** Portföy dağılımını hiyerarşik alan oranlarıyla gösteren bir harita. Hangi varlığın portföyde ne kadar yer kapladığını görsel olarak aktarır.

### 2.11 React Router
**Rol:** İstemci tarafı sayfa yönlendirme

React Router, tek sayfa uygulamalarında (SPA) sayfa geçişlerini yöneten standart kütüphanedir. Giriş, panel, giderler, gelirler, portföy ve ayarlar gibi sayfalar arası geçiş bu kütüphane ile sağlanacaktır.

---

## 3. Mobil Uygulama Teknolojileri

### 3.1 React Native
**Rol:** Çapraz platform mobil framework

React Native, React bilgisiyle hem iOS hem Android için tek bir kod tabanından yerel (native) uygulama geliştirmeyi sağlayan bir framework'tür. Web uygulamasıyla aynı dili (TypeScript) ve benzer bileşen yapısını paylaşması sayesinde geliştirme hızını artırır ve ekip içi bilgi transferini kolaylaştırır.

### 3.2 Expo SDK 52
**Rol:** React Native geliştirme platformu

Expo, React Native üzerine kurulmuş bir geliştirme platformudur. Kamera, bildirimler, güvenli depolama gibi cihaz özelliklerine tek bir API üzerinden erişim sağlar. EAS Build servisi ile bulut üzerinde iOS ve Android derlemeleri yapılabilir. Geliştirme sürecinde `npx expo start` komutuyla emülatör veya fiziksel cihazda anında test imkânı sunar.

### 3.3 Expo Router
**Rol:** Dosya tabanlı sayfa yönlendirme

Expo Router, dosya sistemi tabanlı bir yönlendirme çözümüdür. Next.js ve web geliştirmedeki dosya tabanlı routing mantığıyla aynı şekilde çalışır; klasör ve dosya yapısı otomatik olarak ekran yönlendirmelerini oluşturur.

### 3.4 NativeWind
**Rol:** React Native için Tailwind CSS

NativeWind, Tailwind CSS söz dizimini React Native ortamında kullanmayı sağlayan bir kütüphanedir. Web uygulamasında kullanılan aynı Tailwind sınıf isimleriyle mobilde de tutarlı tasarım yapılmasını mümkün kılar.

### 3.5 Tamagui
**Rol:** Mobil UI bileşen kütüphanesi

Tamagui, React Native için performans odaklı bir UI bileşen kütüphanesidir. shadcn/ui'ın mobil karşılığı olarak düşünülebilir; özelleştirilebilir, erişilebilir ve animasyon destekli bileşenler sunar. Açık/koyu tema geçişini doğal olarak destekler.

### 3.6 Victory Native
**Rol:** Mobil grafik kütüphanesi

Victory Native, React Native ortamında çalışan bir grafik kütüphanesidir. Recharts web ortamına özel olduğundan, mobilde finansal grafiklerin (portföy değeri, gelir/gider dağılımı) gösterimi için Victory Native kullanılacaktır.

### 3.7 Expo SecureStore
**Rol:** Güvenli token saklama

SecureStore, kullanıcının kimlik doğrulama token'ını cihazda şifreli olarak saklayan bir Expo modülüdür. iOS'ta Keychain, Android'de Keystore kullanarak hassas verilerin güvenliğini sağlar.

---

## 4. Paylaşılan Teknolojiler (Web + Mobil Ortak)

Monorepo yapısı sayesinde aşağıdaki teknolojiler ve kod dosyaları web ve mobil uygulama arasında **doğrudan paylaşılacaktır:**

| Teknoloji | Paylaşılan İçerik |
|---|---|
| **TypeScript** | Tüm veri tipleri ve arayüz tanımları (interface/type) |
| **Zod** | Form doğrulama şemaları (aynı kurallar her iki platformda geçerli) |
| **TanStack Query** | API çağrı hook'ları (useExpenses, usePortfolio, usePrices vb.) |
| **Axios** | HTTP istemci yapılandırması ve API endpoint tanımları |

Bu paylaşım sayesinde bir doğrulama kuralı veya API çağrısı değiştiğinde tek bir yerden güncelleme yapılması yeterli olacaktır.

---

## 5. Altyapı ve DevOps Teknolojileri

### 5.1 Docker & Docker Compose
**Rol:** Konteynerleştirme ve çoklu servis orkestrasyonu

Docker, uygulamayı ve bağımlılıklarını izole konteynerler içinde çalıştırarak "bende çalışıyor ama sende çalışmıyor" sorununu ortadan kaldırır. Docker Compose ile birden fazla servis (uygulama, veritabanı, cache) tek bir `docker-compose.yml` dosyasıyla tanımlanır ve `docker compose up` komutuyla tamamı ayağa kaldırılır.

Projede çalışacak konteynerler:

| Konteyner | Servis | Port |
|---|---|---|
| `api` | Laravel API (PHP 8.3) | 8000 |
| `web` | React Web App (Vite) | 3000 |
| `db` | PostgreSQL 16 | 5432 |
| `redis` | Redis (cache + queue) | 6379 |
| `mailpit` | Geliştirme e-posta sunucusu | 8025 |

### 5.2 Laravel Sail
**Rol:** Docker geliştirme ortamı

Sail, Laravel'in resmi Docker geliştirme ortamıdır. Kendi içinde PHP, PostgreSQL, Redis ve diğer servisleri barındıran hazır bir `docker-compose.yml` sunar. `./vendor/bin/sail up` komutuyla tüm altyapı saniyeler içinde başlatılır.

### 5.3 Mailpit
**Rol:** Geliştirme ortamı e-posta sunucusu

Mailpit, geliştirme aşamasında gönderilen e-postaları yakalar ve web arayüzünde görüntüler. Gerçek e-posta gönderilmesini önleyerek e-posta doğrulama akışlarının güvenle test edilmesini sağlar.

### 5.4 GitHub
**Rol:** Versiyon kontrol ve iş birliği

Tüm kaynak kodu GitHub üzerinde barındırılacaktır. Pull request, code review ve issue takibi gibi özellikler ekip içi koordinasyon için kullanılacaktır.

### 5.5 Nginx
**Rol:** Web sunucusu ve ters proxy (production)

Nginx, production ortamında React web uygulamasının statik dosyalarını sunmak ve API isteklerini Laravel konteynerine yönlendirmek için kullanılacaktır.

---

## 6. Üçüncü Parti API'ler (Ücretsiz)

### 6.1 Binance Public REST API
**Rol:** Kripto para canlı fiyat verisi

Binance'in herkese açık REST API'si, API anahtarı gerektirmeden anlık kripto para fiyatlarını sunar. Bitcoin, Ethereum ve diğer kripto varlıkların güncel fiyatları bu API üzerinden çekilecektir.

### 6.2 Yahoo Finance API
**Rol:** Hisse senedi ve döviz canlı fiyat verisi

Yahoo Finance, hisse senedi (AAPL, TSLA vb.) ve döviz çifti (EUR/USD, USD/TRY vb.) fiyat verilerini sunan ücretsiz bir kaynaktır. Bir Price Provider soyutlama katmanı ile sarmalanacağından, ileride farklı bir veri kaynağına geçiş iş mantığını etkilemeden yapılabilir.

---

## 7. Güvenlik Teknolojileri ve Önlemleri

Finansal verilerin korunması uygulamanın en kritik gereksinimlerinden biridir. Snuz, çok katmanlı bir güvenlik mimarisi ile kullanıcı verilerini koruma altına alacaktır.

### 7.1 Laravel Sanctum (Kimlik Doğrulama)
**Rol:** Token tabanlı API kimlik doğrulama

Sanctum, her API isteğinde kullanıcının kimliğini doğrular. Web uygulaması için cookie tabanlı oturum, mobil uygulama için Bearer token yöntemi kullanılır. Yetkisiz erişim tamamen engellenir; her endpoint kimlik doğrulaması gerektirir.

### 7.2 bcrypt (Şifre Hashleme)
**Rol:** Şifrelerin geri döndürülemez biçimde saklanması

Kullanıcı şifreleri veritabanında düz metin olarak asla saklanmaz. Laravel'in varsayılan şifre hashleme algoritması olan bcrypt, şifreyi tek yönlü (one-way) olarak hashler. Veritabanı ele geçirilse dahi şifreler okunamaz. bcrypt'in cost factor'ü sayesinde brute-force saldırılarına karşı dayanıklıdır.

### 7.3 HTTPS / SSL/TLS (İletişim Şifreleme)
**Rol:** İstemci ile sunucu arasındaki tüm trafiğin şifrelenmesi

Tüm API iletişimi HTTPS üzerinden gerçekleştirilecektir. SSL/TLS sertifikası ile istemci (web tarayıcı veya mobil uygulama) ile sunucu arasındaki veri transferi uçtan uca şifrelenir. Ortadaki adam (man-in-the-middle) saldırıları önlenir. Production ortamında Let's Encrypt ile ücretsiz SSL sertifikası kullanılacaktır.

### 7.4 CSRF Koruması (Cross-Site Request Forgery)
**Rol:** Sahte istek saldırılarının engellenmesi

Laravel, her form gönderiminde otomatik olarak CSRF token üretir ve doğrular. Bu sayede kötü niyetli bir web sitesinin, oturum açmış kullanıcı adına izinsiz işlem yapması (örneğin para transferi tetiklemesi) engellenir. Laravel'de bu koruma varsayılan olarak aktiftir.

### 7.5 XSS Koruması (Cross-Site Scripting)
**Rol:** Zararlı kod enjeksiyonunun engellenmesi

React, varsayılan olarak tüm kullanıcı girdilerini render öncesinde otomatik olarak escape eder (sanitize). Bu sayede saldırganın form alanlarına veya not alanlarına zararlı JavaScript kodu enjekte etmesi önlenir. Sunucu tarafında da Laravel'in Blade template engine'i aynı korumayı sağlar.

### 7.6 SQL Injection Koruması
**Rol:** Veritabanı sorgularına zararlı kod enjeksiyonunun engellenmesi

Eloquent ORM, tüm veritabanı sorgularında parametreli sorgular (prepared statements) kullanır. Kullanıcı girdileri asla doğrudan SQL sorgusuna eklenmez; parametrize edilerek veritabanı motoruna gönderilir. Bu sayede SQL injection saldırıları yapısal olarak imkânsız hale getirilir.

### 7.7 Rate Limiting (İstek Hız Sınırlama)
**Rol:** Brute-force ve DDoS saldırılarına karşı koruma

Laravel'in built-in rate limiter'ı ile API endpoint'lerine dakika başına istek limiti konulacaktır. Özellikle giriş (login) endpoint'inde dakikada maksimum 5 deneme izni verilecek; aşıldığında geçici süre engelleme uygulanacaktır. Bu önlem, şifre tahmin saldırılarını (brute-force) etkisiz kılar.

### 7.8 CORS (Cross-Origin Resource Sharing)
**Rol:** İzinsiz alan adlarından API erişiminin engellenmesi

CORS yapılandırması ile yalnızca yetkili origin'lerden (web uygulamasının alan adı) gelen API isteklerine izin verilecektir. Bilinmeyen veya izinsiz bir web sitesinden API'ye yapılan çağrılar tarayıcı düzeyinde reddedilir.

### 7.9 Expo SecureStore (Mobil Token Güvenliği)
**Rol:** Kimlik doğrulama token'ının cihazda şifreli saklanması

Mobil uygulamada kullanıcının oturum token'ı düz metin olarak saklanmaz. iOS'ta Keychain, Android'de Keystore donanım destekli güvenli depolama alanları kullanılarak token şifreli olarak muhafaza edilir. Cihaz çalınsa dahi token'a erişilemez.

### 7.10 Append-Only Ledger (Veri Bütünlüğü)
**Rol:** Finansal verilerin değiştirilmesinin veya silinmesinin yapısal olarak engellenmesi

Veritabanı seviyesinde CHECK constraint'ler ve uygulama katmanında middleware ile finansal tablolarda DELETE ve UPDATE (tutar alanlarında) işlemleri tamamen engellenir. Her işlem yalnızca INSERT olarak kaydedilir. İptal gereken işlemler silinmez; `is_void` işaretiyle geçersiz kılınır ve ters kayıt eklenir. Bu yaklaşım bankacılık ve muhasebe standartlarındaki denetim izi (audit trail) gereksinimiyle uyumludur.

### 7.11 Helmet / Güvenlik Başlıkları (HTTP Security Headers)
**Rol:** Tarayıcı düzeyinde ek güvenlik katmanları

Nginx ve Laravel middleware aracılığıyla aşağıdaki HTTP güvenlik başlıkları ayarlanacaktır:

- **X-Content-Type-Options: nosniff** — MIME type sniffing'i engeller
- **X-Frame-Options: DENY** — Clickjacking saldırılarını önler
- **Strict-Transport-Security (HSTS)** — Tarayıcıyı her zaman HTTPS kullanmaya zorlar
- **Content-Security-Policy (CSP)** — İzin verilen kaynak türlerini sınırlayarak XSS riskini azaltır

---

## 8. Teknoloji Sayıları Özeti

| Kategori | Adet |
|---|---|
| Backend | 6 (PHP, Laravel, PostgreSQL, Redis, Sanctum, Gemini) |
| Web Frontend | 11 (React, TypeScript, Vite, Tailwind, shadcn/ui, TanStack Query, Axios, Zod, Recharts, nivo, React Router) |
| Mobil | 7 (React Native, Expo, Expo Router, NativeWind, Tamagui, Victory Native, SecureStore) |
| Güvenlik | 11 (Sanctum, bcrypt, HTTPS/SSL, CSRF, XSS, SQL Injection Protection, Rate Limiting, CORS, SecureStore, Append-Only Ledger, Security Headers) |
| Paylaşılan | 4 (TypeScript, Zod, TanStack Query, Axios) |
| Altyapı | 6 (Docker, Docker Compose, Sail, Mailpit, GitHub, Nginx) |
| Harici API | 2 (Binance, Yahoo Finance) |
