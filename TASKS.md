# Snuzdan — Görev Listesi (Task Board)

> **Proje:** Snuzdan — Kişisel Finans ve Yatırım Takip Uygulaması  
> **Stack:** Laravel 11 · Inertia.js · React 18 · TypeScript · PostgreSQL 16 · Docker  
> **Dökümanlar:** `ARCHITECTURE.md` (mimari + DB şema), `TECHNOLOGIES.md` (tüm teknolojiler)

---

## ⚠️ Genel Kurallar (İkisi İçin)

- Tüm finansal tablolarda **DELETE ve UPDATE yasak** — append-only ledger. İptal = `is_void = true`.
- Backend'de her zaman **Form Request** ile validation yap, controller'da değil.
- Frontend'de tüm formlar **Zod** ile valide edilecek.
- İlerideki Mobil Uygulama için Controller'ları ince (sadece HTTP Response), Service'leri kalın (Tüm Business Logic) tutun (Fat Service, Thin Controller).
- Test Yazma: Pest PHP ile önemli servisler (özellikle finansal hesaplamalar) için test yazılacak.
- Tema sistemi: Tailwind `dark:` prefix + `ThemeProvider` context. Light/dark her component'te desteklenmeli.
- Tüm primary key'ler **UUID** — `gen_random_uuid()`.
- Her PR'dan önce diğer kişiyi haberdar et, özellikle `migrations/` değişikliklerinde.

---

## 🔴 KRİTİK: Başlamadan Önce

> Akif projeyi kurup Docker'ı ayağa kaldırıncaya kadar Muhammed Ali migrations yazabilir ama çalıştıramaz. **İlk koordinasyon noktası burası.**

**Sıra şu:**
1. Akif projeyi kurar → GitHub'a push eder
2. Muhammed Ali repo'yu clone eder
3. İkisi paralel çalışmaya başlar

---

## 👤 AKİF'İN GÖREVLERİ

### 🏗️ AŞAMA 1 — Proje Kurulumu (Önce Bu)

#### GÖREV A-1: Laravel Projesi Oluştur
```
Snuzdan adında yeni bir Laravel 11 projesi kur. 
Laravel Breeze + Inertia.js + React + TypeScript stack'ini seç.
Laravel Sail ile Docker Compose ayarla: PostgreSQL 16, Redis, Mailpit servisleri olsun.
Tailwind CSS ve shadcn/ui kur.
.env dosyasını ayarla, .env.example'ı güncelle.
GitHub'a push et.
```

#### GÖREV A-2: Docker Compose Yapılandır
```
docker-compose.yml dosyasını aşağıdaki containerları içerecek şekilde düzenle:
- app: Laravel + PHP 8.3 (port 80)
- db: PostgreSQL 16 (port 5432)
- redis: Redis (port 6379)
- mailpit: E-posta test sunucusu (port 8025)
- node: Vite dev server (port 5173)
- reverb: Laravel Reverb WebSocket sunucusu (port 8080) ← Muhammed Ali ekleyecek

"docker compose up" komutuyla tüm servisler ayağa kalksın.
```

#### GÖREV A-3: Temel Dizin Yapısı ve Config
```
app/Http/Controllers/ altında şu boş controller dosyalarını oluştur:
- Auth/LoginController.php
- Auth/RegisterController.php  
- Auth/GoogleOAuthController.php
- DashboardController.php
- ExpenseController.php
- IncomeController.php
- CategoryController.php
- AiController.php
- SettingsController.php

routes/api.php dosyasını oluşturup, Sanctum eşliğinde örnek bir auth grubu aç. İleride mobil uygulama buradan bağlanacak.

config/snuzdan.php dosyasını oluştur: desteklenen para birimleri (USD, EUR, TRY), 
desteklenen asset sınıfları (CRYPTO, STOCK, FX) gibi app-specific config'leri içersin.
```

---

### 🔐 AŞAMA 2 — Auth Modülü

#### GÖREV A-4: Users Tablosu Migration
```
ARCHITECTURE.md dosyasındaki "USERS & AUTH" bölümündeki DDL'i kullanarak migration yaz.

users tablosu alanları:
- id: UUID primary key
- email: varchar(255) unique
- password_hash: varchar(255) nullable (OAuth kullanıcılar için)
- display_name: varchar(100)
- base_currency: char(3) default 'USD'
- theme: varchar(10) default 'dark'
- status: enum('pending', 'active', 'suspended') default 'pending'
- email_verified: boolean default false
- avatar_url: text nullable
- created_at, updated_at: timestamptz

oauth_accounts ve verification_tokens tablolarını da aynı migration veya ayrı migration'larda oluştur.
```

#### GÖREV A-5: Auth Sayfaları (Kayıt / Giriş)
```
React (Inertia) ile şu sayfaları oluştur:
- resources/js/Pages/Auth/Login.tsx: E-posta + şifre formu, "Google ile Giriş" butonu
- resources/js/Pages/Auth/Register.tsx: Ad, e-posta, şifre, şifre tekrar formu
- resources/js/Pages/Auth/VerifyEmail.tsx: E-posta doğrulama bekleme sayfası

UI: shadcn/ui Card, Input, Button componentleri kullan.
Tailwind dark mode destekli olsun.
Form validasyonu Zod ile yapılsın.
Hata mesajları Türkçe olsun.
```

#### GÖREV A-6: Google OAuth
```
Laravel Socialite paketi ile Google OAuth entegre et.
GoogleOAuthController: redirect() ve callback() metodları.
Callback'te:
  - oauth_accounts tablosuna kayıt ekle
  - Kullanıcı yoksa users tablosuna oluştur
  - Varsa mevcut hesaba bağla
routes/web.php'ye Google OAuth route'larını ekle.
.env.example'a GOOGLE_CLIENT_ID ve GOOGLE_CLIENT_SECRET ekle.
```

---

### 🏷️ AŞAMA 3 — Kategoriler

#### GÖREV A-7: Categories Tablosu ve Seeder
```
ARCHITECTURE.md'deki categories tablosu DDL'ini kullanarak migration yaz.

Alanlar: id, workspace_id (nullable), name, icon (emoji), color (hex), 
direction enum('INCOME','EXPENSE'), cat_type enum('SYSTEM','CUSTOM'), is_active, created_at

CHECK constraint: SYSTEM kategorilerde workspace_id NULL olmalı, CUSTOM'da dolu olmalı.

CategorySeeder.php yaz — ARCHITECTURE.md'deki Türkçe system kategori INSERT'lerini ekle:
Gider: Kira 🏠, Faturalar 📄, Yemek 🍕, Ulaşım 🚗, Eğlence 🎬, Sağlık 💊, 
       Alışveriş 🛍️, Eğitim 📚, Abonelikler 📱, Diğer 📌
Gelir: Maaş 💰, Freelance 💻, Yatırım Geliri 📈, Hediye 🎁, Burs 🎓, Diğer 📌
```

#### GÖREV A-8: Category Model ve Controller
```
app/Models/Category.php:
- fillable alanları tanımla
- user() ve transactions() ilişkilerini ekle
- scopeForUser($userId): kullanıcının hem system hem custom kategorilerini döndürür
- scopeByDirection($direction): INCOME veya EXPENSE filtresi

CategoryController.php:
- index(): kullanıcının kategorilerini listele (direction filtresi destekli)
- store(): yeni custom kategori oluştur
- update(): sadece name, icon, color güncellenebilir
- destroy(): is_active = false yap (gerçekten silme)
```

#### GÖREV A-9: CategoryPicker React Componenti
```
resources/js/Components/expenses/CategoryPicker.tsx yaz:

Props: direction ('INCOME' | 'EXPENSE'), selectedId, onChange callback
- API'den kategorileri çek (TanStack Query)
- Icon grid görünümü: her kategori emoji + isim olarak gösterilsin
- "Yeni Kategori" butonu: modal açar, isim + emoji + renk seçimi
- Seçili kategori highlight edilsin
- shadcn/ui bileşenleri kullan, dark mode destekli
```

---

### 💸 AŞAMA 4 — Gider Modülü

#### GÖREV A-10: Gider Tablosu Migration
```
ARCHITECTURE.md'deki expense_transactions DDL'ini kullanarak migration yaz.

Alanlar:
- id: UUID
- workspace_id: UUID FK → workspaces
- created_by_user_id: UUID FK → users (işlemi ekleyen)
- account_id: UUID FK → accounts
- category_id: UUID FK → categories  
- amount: numeric(20,2) NOT NULL CHECK > 0
- currency: char(3) default 'USD'
- fx_rate_to_base: numeric(20,8) default 1.0
- note: text nullable
- expense_date: timestamptz default now()
- created_at: timestamptz
- is_void: boolean default false
- void_reason: text nullable
- voided_at: timestamptz nullable

Index'ler: (workspace_id), (workspace_id, expense_date), (category_id)
```

#### GÖREV A-11: AppendOnlyGuard Middleware
```
app/Http/Middleware/AppendOnlyGuard.php yaz:

Bu middleware finansal tablolara (expense_transactions, income_transactions) 
gelen DELETE ve PUT/PATCH isteklerini engellesin.
Eğer böyle bir istek gelirse 403 döndürsün ve hata logla.

NOT: Bu middleware Muhammed Ali'nin investment_transactions tablosunu da koruyacak — 
ona haber ver ve birlikte Kernel.php'ye ekleyin.
```

#### GÖREV A-12: ExpenseService ve Controller
```
app/Services/ExpenseService.php:
- store(array $data, User $user): ExpenseTransaction — sadece INSERT
- void(string $id, string $reason, User $user): is_void=true, voided_at=now()
- listForUser(User $user, array $filters): paginated — tarih aralığı, kategori, arama filtresi
- monthlyTotals(User $user, int $year, int $month): toplam tutar
- categoryBreakdown(User $user, int $year, int $month): kategori bazlı dağılım

ExpenseController.php:
- index(): listeleme (filter destekli)
- store(): StoreExpenseRequest validate → ExpenseService::store()
- void(): gider iptal et
```

#### GÖREV A-13: StoreExpenseRequest
```
app/Http/Requests/StoreExpenseRequest.php yaz:

Validation kuralları:
- amount: required, numeric, min:0.01
- currency: required, string, size:3, in: USD/EUR/TRY
- account_id: required, uuid, exists:accounts,id (ve mevcut çalışma alanına ait olmalı)
- category_id: required, uuid, exists:categories,id (ve mevcut çalışma alanına ait olmalı)
- note: nullable, string, max:500
- expense_date: required, date, before_or_equal:today
- fx_rate_to_base: nullable, numeric, min:0

Hata mesajları Türkçe olsun.
```

#### GÖREV A-14: Gider Sayfası (React)
```
resources/js/Pages/Expenses/Index.tsx yaz:

Sol panel — Gider Ekleme Formu (ExpenseEntryForm component):
- Tutar input (büyük, belirgin)
- Para birimi seçici (USD/EUR/TRY)
- AccountPicker component (Hesap Seçimi: Nakit/Banka)
- CategoryPicker component (EXPENSE direction)
- Tarih seçici (DatePicker, default bugün)
- Not alanı (textarea, opsiyonel)
- "Yapay Zeka ile Ekle" butonu (AiTransactionInput'u açar)
- Kaydet butonu

Sağ panel — Gider Listesi (ExpenseList component):
- Tarih aralığı filtresi
- Kategori filtresi  
- Arama kutusu
- Tablo: tarih, kategori emoji+isim, tutar, not, iptal butonu
- Pagination
- İptal edilen kayıtlar üstü çizgili ve soluk görünsün
- Void butonu: onay modal'ı açsın, sebep gir, onayla

UI: dark mode destekli, shadcn/ui Table, Dialog, Input kullan.
Veriler TanStack Query ile çekilsin, form gönderimi sonrası invalidate edilsin.
```

---

### 💰 AŞAMA 5 — Gelir Modülü

#### GÖREV A-15: Gelir Tablosu Migration
```
ARCHITECTURE.md'deki income_transactions DDL'ini kullanarak migration yaz.

Gider tablosuyla neredeyse aynı, farklar:
- expense_date yerine income_date
- category_id: direction='INCOME' olan kategorilere FK

Index'ler: (workspace_id), (workspace_id, income_date)
```

#### GÖREV A-16: IncomeService ve Controller
```
app/Services/IncomeService.php — ExpenseService ile aynı yapı, income_transactions tablosu için:
- store(), void(), listForUser(), monthlyTotals(), categoryBreakdown()

IncomeController.php:
- index(), store(), void()

StoreIncomeRequest.php — StoreExpenseRequest ile aynı kurallar, income_date alanı.
```

#### GÖREV A-17: Gelir Sayfası (React)
```
resources/js/Pages/Income/Index.tsx yaz — Gider sayfasıyla aynı yapı:

- IncomeEntryForm: tutar, para birimi, CategoryPicker (INCOME direction), tarih, not
- IncomeList: filtrelenebilir tablo, void özelliği

Renk teması: Gelir için yeşil tonlar (gider kırmızı olacak), 
kategori ikonları INCOME kategorilerini göstersin.
```

---

### 🤖 AŞAMA 6 — Yapay Zeka Entegrasyonu

#### GÖREV A-18: AI Interaction Tablosu ve Model
```
ARCHITECTURE.md'deki ai_interactions DDL'ini kullanarak migration yaz:
- id, user_id, prompt, response (JSONB), action_type, was_accepted, created_at

app/Models/AiInteraction.php:
- fillable: user_id, prompt, response, action_type, was_accepted
- Cast: response → array
```

#### GÖREV A-19: AiService — Gemini API Entegrasyonu
```
app/Services/AiService.php yaz. Google Gemini API free tier kullan.

Üç metod:

1. parseTransaction(string $userText, User $user): array
   Kullanıcının "Dün akşam 250 lira yemek yedim" gibi metnini analiz et.
   Gemini'ye structured JSON döndürmesini söyle:
   { type: "EXPENSE"|"INCOME", amount: float, currency: "TRY", 
     category_name: string, date: "YYYY-MM-DD", note: string }
   AI sonucunu ai_interactions tablosuna kaydet.

2. suggestCategory(string $note, string $direction): string
   Not yazılırken en uygun kategori ismini öner.
   Kısa, hızlı çağrı — debounce ile kullan.

3. generateMonthlyReport(User $user, int $year, int $month): string
   Kullanıcının o ayki gelir/gider verilerini al.
   Gemini'ye Türkçe doğal dil özet + tasarruf önerileri yazmasını söyle.
   Markdown formatında döndür.

Config: GEMINI_API_KEY .env'den okunacak.
Rate limit: Dakikada 60 istek (free tier) — çağrıları debounce ve cache ile yönet.
```

#### GÖREV A-20: AI React Componentleri
```
resources/js/Components/ai/ altında:

AiTransactionInput.tsx:
- Büyük textarea: "Türkçe yaz, AI anlasın" placeholder
- Gönder butonu → POST /ai/parse
- Loading spinner Gemini işlerken
- AiSuggestionCard component'ini göster

AiSuggestionCard.tsx:
- Gemini'nin çıkardığı bilgileri göster: tutar, kategori, tarih, not
- "Onayla" butonu → ExpenseController veya IncomeController'a gönder
- "Düzenle" butonu → alanları düzenlenebilir yap
- "Reddet" butonu → kartı kapat

AiMonthlyReport.tsx:
- "Aylık Raporunu Gör" butonu
- Tıklayınca POST /ai/monthly-report
- Dönen markdown'ı render et (react-markdown kullan)
- Modal içinde göster, kopyalama butonu ekle
```

---

### 📊 AŞAMA 7 — Dashboard

#### GÖREV A-21: Dashboard Sayfası
```
resources/js/Pages/Dashboard.tsx yaz:

Üst satır — Özet Kartları:
- NetWorthCard: Toplam varlık (gelir - gider + portföy değeri) — Muhammed Ali'nin PortfolioService'inden veri alır
- MonthlyBalanceCard: Bu ay gelir - gider
- TopMoversCard: En çok değişen yatırımlar ← Muhammed Ali'nin endpoint'inden veri alır

Orta bölüm — Grafikler:
- SankeyDiagram (nivo/sankey): Gelir kaynaklarından gider kategorilerine para akışı
- HeatmapCalendar (nivo/calendar): Son 365 günün günlük harcama yoğunluğu haritası

Alt bölüm:
- RecentActivityFeed: Son 10 işlem (gelir + gider + yatırım karma liste)

NOT: NetWorthCard ve TopMoversCard için Muhammed Ali'nin /api/portfolio/summary 
ve /api/portfolio/movers endpoint'lerini bekleyebilirsin — önce mock veri ile yap.
```

---

### ⚙️ AŞAMA 8 — Ayarlar

#### GÖREV A-22: Ayarlar Sayfası
```
resources/js/Pages/Settings.tsx:

ProfileForm component:
- display_name güncelle
- avatar_url güncelle (URL input)
- Kaydet butonu → PATCH /settings/profile

BaseCurrencySelector component:
- USD / EUR / TRY radio group
- Değiştirince tüm uygulama bu para birimini baz alır
- PATCH /settings/currency

ThemeToggle component:
- Light / Dark toggle
- Tailwind dark class'ını toggle eder
- localStorage'a yaz + DB'ye yaz (users.theme)

LinkedAccountsList component:
- Bağlı Google hesabını göster
- "Bağlantıyı Kaldır" butonu
```

---

### 💳 AŞAMA 9 — Hesaplar (Wallets) & Düzenli İşlemler

#### GÖREV A-23: Accounts Tablosu ve Modeli
```text
ARCHITECTURE.md'deki accounts tablosu DDL'ini kullanarak migration yaz.
account_type enum('CASH', 'BANK', 'CREDIT_CARD', 'E_WALLET') eklenecek.
app/Models/Account.php: fillable (name, type, currency, balance, color) ve ilişkileri ekle.
AccountController.php: Yeni hesap oluşturma, listeleme, silme (pasife alma) işlemleri.
resources/js/Components/accounts/AccountPicker.tsx: Gelir/Gider formları için hesap seçici.
```

#### GÖREV A-24: Düzenli İşlemler (Recurring Transactions)
```text
ARCHITECTURE.md'deki recurring_transactions tablosu DDL'i ile migration yaz.
recurring_period enum('DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY') eklenecek.
app/Models/RecurringTransaction.php modeli ve ilişkileri.
RecurringController: Düzenli gider/gelir tanımı (Örn: Her ay 1'inde 5000TL Kira) ekleme formları.
app/Jobs/ProcessRecurringTransactions.php yaz: 
- Laravel Scheduler (Kernel.php) ile her gün gece 00:01'de çalışacak. 
- next_run_date'i gelmiş aktif kayıtları bulup expense veya income tablolarına otomatik olarak INSERT edecek.
- Sonrasında o kaydın next_run_date'ini period'a göre ileri saracak (örneğin 1 ay sonraya).
```

---


### 🛡️ AŞAMA 10 — Enterprise Seviye (2FA & Workspaces & E2E)

#### GÖREV A-25: 2FA (Two-Factor Authentication)
```text
Kullanıcının hesabını güvene almak için 2FA entegrasyonu yap (`pragmarx/google2fa-laravel` veya Jetstream).
- Ayarlar sayfasında "2FA Aktive Et" butonu ile QR kod gösterimi.
- Login sonrası eğer 2FA aktifse doğrulama ekranına (TOTP) yönlendirme.
```

#### GÖREV A-26: Paylaşımlı Cüzdanlar (Workspaces)
```text
ARCHITECTURE.md'deki `workspaces` ve `workspace_user` tabloları için migration ve Model yaz.
- Ayarlar sayfasına "Çalışma Alanları" menüsü ekle.
- "Ortak Davet Et" özelliği: Sisteme kayıtlı bir e-postayı workspace'e `editor` veya `viewer` rolüyle ekle.
- TopBar'a "Geçerli Cüzdanı Değiştir" dropdown'ı ekle. UI buna göre veri çeksinecek (artık her şey user_id değil workspace_id üzerinden çalışacak).
```

#### GÖREV A-27: E2E Testleri (Cypress) & Observability
```text
- Sentry entegrasyonu yap (Merkezi loglama).
- Sistemin tüm kritik süreçlerini (Kayıt, Login, Gider Ekleme, Kategori Seçme) Cypress ile end-to-end teste sok.
- `cypress/e2e` klasöründe otomatize test senaryolarını yaz.
```

## 👤 MUHAMMED ALİ'NİN GÖREVLERİ

### 🏗️ AŞAMA 1 — Altyapıya Ekleme

#### GÖREV M-1: Reverb Container'ı Docker'a Ekle
```
Akif'in oluşturduğu docker-compose.yml'e reverb servisini ekle:
- image: Laravel Reverb (php artisan reverb:start)
- port: 8080
- env değişkenleri: REVERB_APP_ID, REVERB_APP_KEY, REVERB_APP_SECRET

.env.example'a Reverb değişkenlerini ekle.
```

#### GÖREV M-2: Kendi Migration'larını Yaz
```
Aşağıdaki tabloları ARCHITECTURE.md DDL'ini kullanarak migration dosyaları olarak yaz:

0005_create_assets_table.php
0006_create_investment_transactions_table.php  
0007_create_price_snapshots_table.php
0008_create_fx_rate_snapshots_table.php

assets tablosu: id, asset_class enum(CRYPTO,STOCK,FX), symbol, name, base_currency, created_at
  UNIQUE(asset_class, symbol)

investment_transactions tablosu:
- id, user_id FK→users, asset_id FK→assets
- side enum(BUY,SELL), quantity numeric(20,8), unit_price numeric(20,8)
- total_amount numeric(20,8) CHECK: |total_amount - quantity*unit_price| < 0.01
- commission numeric(20,8) default 0
- fx_rate_to_base numeric(20,8) default 1.0
- note text nullable
- transaction_date timestamptz default now()
- created_at timestamptz
- is_void boolean default false, void_reason text, voided_at timestamptz

price_snapshots: id, asset_id FK→assets, price numeric(20,8), currency char(3), source varchar(50), fetched_at
fx_rate_snapshots: id, base_currency char(3), quote_currency char(3), rate numeric(20,8), source varchar(50), fetched_at
```

#### GÖREV M-3: v_positions VIEW
```
0010_create_v_positions_view.php migration yaz.
ARCHITECTURE.md §2'deki CREATE VIEW v_positions AS ... SQL'ini kullan.

Bu view hiç veri saklamaz, her çalıştığında investment_transactions'dan hesaplar:
- net_quantity: BUY toplamı - SELL toplamı (is_void=false olanlar)
- total_cost_base: Toplam alım maliyeti (fx_rate ile base currency'ye çevrilmiş)
- total_sell_proceeds_base: Toplam satış geliri
- total_commission_base: Toplam komisyon
- first_trade, last_trade, trade_count
```

---

### 💹 AŞAMA 2 — Fiyat Sağlayıcılar

#### GÖREV M-4: PriceProviderInterface
```
app/Providers/PriceProviderInterface.php yaz:

interface PriceProviderInterface {
    public function fetchPrice(string $symbol, string $currency): float;
    public function supports(string $assetClass): bool;
}

Bu interface'i implement eden iki class yaz:
BinanceProvider.php — CRYPTO asset'leri için
YahooProvider.php — STOCK ve FX asset'leri için
```

#### GÖREV M-5: BinanceProvider
```
app/Providers/BinanceProvider.php:

supports(): sadece 'CRYPTO' döndür.

fetchPrice(symbol, currency):
- Binance REST API'ye istek at (API key gerektirmez)
- URL: https://api.binance.com/api/v3/ticker/price?symbol={SYMBOL}USDT
- Fiyatı float olarak döndür
- Hata durumunda exception fırlat

fetchBatch(array $symbols): birden fazla fiyatı aynı anda çek
- https://api.binance.com/api/v3/ticker/price (tümünü çeker, filtrele)
```

#### GÖREV M-6: YahooProvider
```
app/Providers/YahooProvider.php:

supports(): 'STOCK' ve 'FX' için true döndür.

fetchPrice(symbol, currency):
- Yahoo Finance API'ye HTTP istek at
- Ticker formatı: hisse için 'AAPL', döviz için 'EURUSD=X'
- Fiyatı float olarak döndür

NOT: Yahoo Finance resmi API'si yok, HTTP scraping veya 
yahoo-finance2 benzeri kütüphane kullan.
```

#### GÖREV M-6.5: AlphaVantageProvider (Fallback)
```text
app/Providers/AlphaVantageProvider.php yaz.
Yahoo Finance patlarsa diye yedek (fallback) API olarak kullanılacak.
```

#### GÖREV M-7: PriceService
```text
app/Services/PriceService.php:

fetchAndCache(Asset $asset):
- asset_class'a göre doğru Provider'ı seç (Binance, Yahoo)
- Eğer Yahoo Exception fırlatırsa `try-catch` ile AlphaVantageProvider'a fallback (yedek) yap.
- Fiyatı çek
- price_snapshots tablosuna INSERT et
- Redis'e cache'le (TTL: 1 dakika, key: "price:{asset_id}")

getLatestPrice(Asset $asset): float
- Önce Redis cache'e bak
- Cache yoksa price_snapshots'tan son kaydı al
- O da yoksa fetchAndCache() çağır (İki provider da çökmüşse son çare olarak DB'deki eski fiyatı dön)
```

#### GÖREV M-8: FxService
```
app/Services/FxService.php:

fetchAndCache(string $baseCurrency, string $quoteCurrency):
- Yahoo Finance'ten döviz kurunu çek (örn: USD/TRY için "TRY=X")
- fx_rate_snapshots'a INSERT et
- Redis cache'le (TTL: 1 saat)

getRate(string $from, string $to): float
- Cache'ten veya DB'den kur döndür

convertToBase(float $amount, string $currency, string $baseCurrency): float
- Kullanıcının base currency'sine çevir
```

#### GÖREV M-9: Queue Jobs
```
app/Jobs/FetchPriceSnapshots.php:
- Tüm aktif Asset'leri çek
- Her biri için PriceService::fetchAndCache() çağır
- Paralel çalışması için dispatch() kullan

app/Jobs/FetchFxRates.php:
- Desteklenen para çiftleri için FxService::fetchAndCache() çağır
- USD/TRY, EUR/TRY, EUR/USD

app/Console/Kernel.php (Scheduler):
- FetchPriceSnapshots: her 5 dakikada bir
- FetchFxRates: her saat başı
```

---

### 📈 AŞAMA 3 — Yatırım Modülü

#### GÖREV M-10: Asset ve InvestmentTransaction Modelleri
```
app/Models/Asset.php:
- fillable: asset_class, symbol, name, base_currency
- Cast: asset_class → enum
- transactions() hasMany ilişkisi
- latestPrice() helper metodu: PriceService::getLatestPrice() çağırır

app/Models/InvestmentTransaction.php:
- fillable tüm alanlar
- Cast: is_void → bool, side → enum, quantity/unit_price/total_amount → float
- user() ve asset() ilişkileri
```

#### GÖREV M-11: InvestmentService
```
app/Services/InvestmentService.php:

store(array $data, User $user): InvestmentTransaction
- Validasyon: total_amount = quantity * unit_price (±0.01 tolerans)
- Eğer 3. alan eksikse otomatik hesapla (2-of-3 logic):
  * quantity ve unit_price verildi → total_amount = quantity * unit_price
  * quantity ve total_amount verildi → unit_price = total_amount / quantity
  * unit_price ve total_amount verildi → quantity = total_amount / unit_price
- Sadece INSERT

void(string $id, string $reason, User $user): 
- is_void = true, void_reason, voided_at = now()

listForUser(User $user, array $filters): paginate
- asset_class, asset_id, side, tarih aralığı filtreleri
```

#### GÖREV M-12: PortfolioService
```
app/Services/PortfolioService.php:

getPositions(User $user): Collection
- v_positions VIEW'ını Eloquent ile sorgula
- Her pozisyon için PriceService::getLatestPrice() ile anlık fiyatı al
- Unrealized PnL hesapla: (anlık_fiyat - ortalama_maliyet) * net_quantity

getFifoPnL(User $user, string $assetId): float
- FIFO metoduna göre realize edilmiş kar/zarar hesapla
- BUY işlemlerini sıraya al, SELL işlemlerinde FIFO tüket

getSummary(User $user): array
- Toplam portföy değeri (base currency)
- Toplam unrealized PnL
- Toplam realized PnL
- Asset class'a göre dağılım yüzdeleri

getTopMovers(User $user, int $limit = 5): array
- Son 24 saatte en çok % değişen pozisyonlar
```

#### GÖREV M-13: InvestmentController
```
app/Http/Controllers/InvestmentController.php:

index(): Kullanıcının işlem geçmişi (paginate, filtreli)
store(): StoreTradeRequest → InvestmentService::store()
void(): İşlemi iptal et

app/Http/Controllers/PortfolioController.php:
index(): Pozisyon listesi
summary(): /api/portfolio/summary → Akif'in Dashboard'u bu endpoint'i kullanır
movers(): /api/portfolio/movers → Akif'in TopMoversCard'ı bu endpoint'i kullanır
```

#### GÖREV M-14: StoreTradeRequest
```
app/Http/Requests/StoreTradeRequest.php:

Validation kuralları:
- asset_id: required, uuid, exists:assets,id
- side: required, in:BUY,SELL
- quantity: nullable, numeric, min:0.000001 (3 alandan en az 2'si dolu olmalı)
- unit_price: nullable, numeric, min:0.000001
- total_amount: nullable, numeric, min:0.01
- commission: nullable, numeric, min:0
- note: nullable, string, max:500
- transaction_date: required, date, before_or_equal:today

Custom validation: quantity, unit_price, total_amount'tan en az 2'si dolu olmalı.
```

---

### 🔴 AŞAMA 4 — Gerçek Zamanlı Fiyatlar (WebSocket)

#### GÖREV M-15: Laravel Reverb Kurulumu
```
php artisan install:broadcasting komutunu çalıştır.
Reverb için config/broadcasting.php ayarla.
.env'e REVERB_APP_ID, REVERB_APP_KEY, REVERB_APP_SECRET ekle.

Bir PriceUpdated event sınıfı oluştur:
app/Events/PriceUpdated.php
- Implements ShouldBroadcast
- public $symbol, $price, $change_percent alanları
- broadcastOn(): public channel 'prices.{symbol}'
```

#### GÖREV M-16: Binance WebSocket Akışı
```
app/Services/BinanceStreamService.php yaz:

Binance WebSocket stream'ine bağlan:
wss://stream.binance.com:9443/ws/{symbol}@ticker

Yeni fiyat geldiğinde:
1. price_snapshots tablosuna INSERT et
2. PriceUpdated event dispatch et → Reverb üzerinden frontend'e ilet

Bu servisi bir Laravel Artisan komutu olarak çalıştır:
app/Console/Commands/StartBinanceStream.php
docker-compose'da reverb container'ı bu komutu çalıştırsın.
```

#### GÖREV M-17: Laravel Echo + Frontend Entegrasyonu
```
Frontend'e Laravel Echo kur: npm install laravel-echo pusher-js

resources/js/echo.ts dosyasını oluştur:
- Reverb bağlantı config'i
- export edilmiş echo instance'ı

LivePriceTicker.tsx component:
Props: symbol (örn: "BTCUSDT")
- Mount'ta Echo ile 'prices.{symbol}' channel'ına subscribe ol
- Gelen PriceUpdated event'ini dinle
- Fiyatı state'e yaz, component'i güncelle
- Unmount'ta unsubscribe ol
- Fiyat yukarı gidince yeşil, aşağı gidince kırmızı animasyon
```

---

### 📊 AŞAMA 5 — Yatırım Frontend Sayfaları

#### GÖREV M-18: Yatırım Sayfaları (React)
```
resources/js/Pages/Portfolio/ altında:

Index.tsx — Portföy ana sayfası:
- AssetClassTabs: CRYPTO | STOCKS | FX sekmeler
- PositionList: Açık pozisyonlar tablosu
  * Her satır: PositionCard (varlık adı, miktar, ortalama maliyet, anlık fiyat, unrealized PnL)
  * PnL pozitifse yeşil, negatifse kırmızı
- "İşlem Ekle" butonu → TradeEntryForm modal
- AllocationPieChart: Portföy dağılımı pasta grafik (recharts)

TradeEntryForm.tsx — BUY/SELL modal:
- Asset arama/seçimi (autocomplete)
- BUY / SELL toggle
- 3 alan: Adet, Birim Fiyat, Toplam — herhangi 2'si girilince 3.'sü otomatik hesaplansın
- Komisyon (opsiyonel)
- Tarih seçici
- Not

TradeHistory.tsx — İşlem geçmişi tablosu:
- Her satır: tarih, varlık, BUY/SELL badge, adet, fiyat, toplam, iptal butonu
```

#### GÖREV M-19: Gelişmiş Portföy Grafikleri
```
resources/js/Components/charts/ altında:

TreemapChart.tsx (nivo/treemap):
- Portföy dağılımını alan oranlarıyla göster
- Her hücre: varlık adı, değer, % oran
- Hover'da detay tooltip
- Animasyon destekli

PnLLineChart.tsx (recharts):
- X ekseni: tarih
- İki çizgi: Realized PnL ve Unrealized PnL
- Sıfır çizgisi referans olarak (kar/zarar ayrımı)
- Tooltip'te detay

SparkLine.tsx (recharts):
- Mini satır içi grafik (PositionCard içinde kullanılacak)
- Son 24 saatin fiyat değişimi
- Pozitif/negatife göre renk
```

#### GÖREV M-20: Test Yazımı (Unit Tests)
```text
Pest PHP kullanarak tests/Unit/ klasörü altına logic testleri yaz.
Özellikle PortfolioService::getFifoPnL metodunun;
1. Sadece ALIŞ yapılmış durumlar,
2. Karışık ALIŞ-SATIŞ yapılmış durumlar,
3. Kısmi satım ve void edilmiş kayıtlar içeren durumlar,
farklı komplo senaryoları altında kesinlikle doğru sonuç verdiğini otomatize eden testler hazırla.
```

---

## 🔗 KOORDİNASYON NOKTALARI

> Bu adımları ikisi birlikte yapacak veya birbirini bilgilendirecek.

| # | Ne | Kim başlatır | Kim bekler |
|---|---|---|---|
| 1 | `users` tablosu migration merge edildi | Akif | Muhammed Ali |
| 2 | `AppendOnlyGuard` middleware hazır | Akif | İkisi birlikte Kernel'e ekler |
| 3 | `/api/portfolio/summary` ve `/api/portfolio/movers` endpoint'leri hazır | Muhammed Ali | Akif (Dashboard için) |
| 4 | `docker-compose.yml`'e reverb container eklendi | Muhammed Ali | İkisi birlikte test eder |
| 5 | `categories` seeder çalışıyor | Akif | Muhammed Ali (assets seeder'ı ekleyecek) |

---

## 📁 Dosya Sahipliği Özeti

### Akif'in Dosyaları
```
app/Http/Controllers/Auth/*
app/Http/Controllers/DashboardController.php
app/Http/Controllers/ExpenseController.php
app/Http/Controllers/IncomeController.php
app/Http/Controllers/CategoryController.php
app/Http/Controllers/AiController.php
app/Http/Controllers/SettingsController.php
app/Http/Requests/StoreExpenseRequest.php
app/Http/Requests/StoreIncomeRequest.php
app/Http/Requests/AiParseRequest.php
app/Http/Middleware/AppendOnlyGuard.php  ← ikisi koordineli
app/Models/User.php
app/Models/Category.php
app/Models/ExpenseTransaction.php
app/Models/IncomeTransaction.php
app/Models/AiInteraction.php
app/Services/ExpenseService.php
app/Services/IncomeService.php
app/Services/AiService.php
database/migrations/0001_create_users_table.php
database/migrations/0002_create_categories_table.php
database/migrations/0003_create_expense_transactions_table.php
database/migrations/0004_create_income_transactions_table.php
database/migrations/0009_create_ai_interactions_table.php
database/seeders/CategorySeeder.php
resources/js/Pages/Auth/*
resources/js/Pages/Dashboard.tsx
resources/js/Pages/Expenses/Index.tsx
resources/js/Pages/Income/Index.tsx
resources/js/Pages/Settings.tsx
resources/js/Components/expenses/*
resources/js/Components/income/*
resources/js/Components/ai/*
resources/js/Components/dashboard/*
resources/js/Components/charts/SankeyDiagram.tsx
resources/js/Components/charts/HeatmapCalendar.tsx
```

### Muhammed Ali'nin Dosyaları
```
app/Http/Controllers/InvestmentController.php
app/Http/Controllers/PortfolioController.php
app/Http/Controllers/PriceController.php
app/Http/Requests/StoreTradeRequest.php
app/Models/Asset.php
app/Models/InvestmentTransaction.php
app/Models/PriceSnapshot.php
app/Models/FxRateSnapshot.php
app/Services/InvestmentService.php
app/Services/PortfolioService.php
app/Services/PriceService.php
app/Services/FxService.php
app/Services/BinanceStreamService.php
app/Providers/PriceProviderInterface.php
app/Providers/BinanceProvider.php
app/Providers/YahooProvider.php
app/Jobs/FetchPriceSnapshots.php
app/Jobs/FetchFxRates.php
app/Events/PriceUpdated.php
app/Console/Commands/StartBinanceStream.php
database/migrations/0005_create_assets_table.php
database/migrations/0006_create_investment_transactions_table.php
database/migrations/0007_create_price_snapshots_table.php
database/migrations/0008_create_fx_rate_snapshots_table.php
database/migrations/0010_create_v_positions_view.php
resources/js/Pages/Portfolio/*
resources/js/Components/portfolio/*
resources/js/Components/charts/TreemapChart.tsx
resources/js/Components/charts/PnLLineChart.tsx
resources/js/Components/charts/SparkLine.tsx
resources/js/echo.ts
```

### Paylaşılan Dosyalar (PR açılırken diğerini bilgilendir)
```
docker-compose.yml
routes/web.php
resources/js/types/models.ts
resources/js/Components/common/*
resources/js/Components/theme/*
resources/js/Components/ui/*  (shadcn/ui)
resources/js/lib/utils.ts
database/seeders/DemoDataSeeder.php
```
