# Snuzdan — Foundational Architecture (v2)

> Personal Finance & Investment Tracking Web App  
> School Project · Zero Budget · Append-Only Ledger  
> **Stack: Laravel + Inertia.js + React · Docker**

---

## 1. Technology Stack

### Core

| Katman | Teknoloji | Neden |
|---|---|---|
| **Backend Framework** | Laravel 11 (PHP 8.3) | Full MVC, built-in queue/scheduler/mail, Docker desteği (Sail) |
| **Frontend Bridge** | Inertia.js | Laravel ↔ React bağlantısı; ayrı API yazmaya gerek yok |
| **Frontend UI** | React 18 + TypeScript | Component-based, zengin ekosistem |
| **Veritabanı** | PostgreSQL 16 | CHECK constraint, VIEW, trigger desteği — append-only ledger için ideal |
| **ORM** | Eloquent (Laravel built-in) | Migration, seeder, model relationship desteği |
| **CSS** | Tailwind CSS | Laravel default, utility-first, dark mode desteği |
| **UI Components** | shadcn/ui | Copy-paste React componentler, Radix-based, accessible |
| **Auth** | Laravel Breeze + Sanctum | Email/password + Google OAuth, built-in, ücretsiz |
| **Validation (Server)** | Laravel Form Requests | Built-in, güçlü, Türkçe hata mesajları |
| **Validation (Client)** | Zod | React form'larında runtime type validation |
| **Container** | Docker & Docker Compose (Laravel Sail) | Hoca zorunlu tutuyor; Sail ile tek komutla ayağa kalkar |
| **VCS** | GitHub | Versiyon kontrol, CI/CD |

### Grafik & Görselleştirme

| Teknoloji | Kullanım |
|---|---|
| **Recharts** | Line, Bar, Pie, Area chart (standart grafikler) |
| **nivo** (@nivo/sankey, @nivo/calendar, @nivo/treemap) | Sankey diagram, Heatmap takvim, Treemap — fark yaratacak gelişmiş grafikler |

### Yapay Zeka

| Teknoloji | Kullanım |
|---|---|
| **Google Gemini API** (free tier) | Doğal dil ile işlem ekleme, akıllı kategori tahmini, aylık finansal analiz raporu |

### Geliştirme Ortamı Servisleri (Docker Compose)

| Container | Servis |
|---|---|
| `app` | Laravel + PHP 8.3 (Laravel Sail) |
| `db` | PostgreSQL 16 |
| `redis` | Cache & Queue driver |
| `mailpit` | Local email testing |
| `node` | Vite dev server (frontend asset build) |

### Phase 2'ye Ertelenenler

- Telegram Bot + n8n
- Fiş tarama (OCR)
- Gamification (skor, başarımlar)
- PWA (Progressive Web App)
- CSV/PDF export
- Mobil uygulama

---

## 2. Database Schema (DDL)

```sql
-- ============================================================
-- SNUZDAN DATABASE SCHEMA v2
-- Append-Only Ledger · Laravel + Eloquent
-- PostgreSQL 16+
-- ============================================================

-- --------------------------------------------------------
-- ENUMS
-- --------------------------------------------------------
CREATE TYPE user_status       AS ENUM ('pending', 'active', 'suspended');
CREATE TYPE transaction_side  AS ENUM ('BUY', 'SELL');
CREATE TYPE asset_class       AS ENUM ('CRYPTO', 'STOCK', 'FX');
CREATE TYPE category_type     AS ENUM ('SYSTEM', 'CUSTOM');
CREATE TYPE flow_direction    AS ENUM ('INCOME', 'EXPENSE');

-- --------------------------------------------------------
-- USERS & AUTH
-- --------------------------------------------------------
CREATE TABLE users (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email           VARCHAR(255) NOT NULL UNIQUE,
    password_hash   VARCHAR(255),                     -- NULL for OAuth-only users
    display_name    VARCHAR(100) NOT NULL,
    base_currency   CHAR(3) NOT NULL DEFAULT 'USD',   -- reporting currency (USD/EUR/TRY)
    theme           VARCHAR(10) NOT NULL DEFAULT 'dark', -- 'light' | 'dark'
    status          user_status NOT NULL DEFAULT 'pending',
    email_verified  BOOLEAN NOT NULL DEFAULT FALSE,
    avatar_url      TEXT,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_users_email ON users (email);

-- OAuth accounts (Google, etc.)
CREATE TABLE oauth_accounts (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id         UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    provider        VARCHAR(50) NOT NULL,             -- 'google'
    provider_id     VARCHAR(255) NOT NULL,
    access_token    TEXT,
    refresh_token   TEXT,
    expires_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (provider, provider_id)
);

-- Email verification tokens
CREATE TABLE verification_tokens (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id     UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token       VARCHAR(255) NOT NULL UNIQUE,
    expires_at  TIMESTAMPTZ NOT NULL,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- --------------------------------------------------------
-- INCOME & EXPENSE MODULE (unified categories, separate ledgers)
-- --------------------------------------------------------

-- Categories shared by both income and expense
CREATE TABLE categories (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id     UUID REFERENCES users(id),            -- NULL = system category
    name        VARCHAR(100) NOT NULL,
    icon        VARCHAR(50),                           -- emoji or icon name
    color       VARCHAR(7),                            -- hex color
    direction   flow_direction NOT NULL,               -- INCOME or EXPENSE
    cat_type    category_type NOT NULL DEFAULT 'CUSTOM',
    is_active   BOOLEAN NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    -- System categories: user_id must be NULL
    -- Custom categories: user_id must be set
    CONSTRAINT chk_category_owner CHECK (
        (cat_type = 'SYSTEM' AND user_id IS NULL)
        OR
        (cat_type = 'CUSTOM' AND user_id IS NOT NULL)
    )
);

CREATE INDEX idx_cat_user ON categories (user_id);
CREATE INDEX idx_cat_direction ON categories (direction);

-- ============================================================
-- INCOME LEDGER — append-only
-- ============================================================
CREATE TABLE income_transactions (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id         UUID NOT NULL REFERENCES users(id),
    category_id     UUID NOT NULL REFERENCES categories(id),
    amount          NUMERIC(20, 2) NOT NULL,
    currency        CHAR(3) NOT NULL DEFAULT 'USD',
    fx_rate_to_base NUMERIC(20, 8) NOT NULL DEFAULT 1.0,
    note            TEXT,                              -- optional free-text
    income_date     TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    is_void         BOOLEAN NOT NULL DEFAULT FALSE,
    void_reason     TEXT,
    voided_at       TIMESTAMPTZ,

    CONSTRAINT chk_income_positive CHECK (amount > 0)
);

CREATE INDEX idx_inc_tx_user ON income_transactions (user_id);
CREATE INDEX idx_inc_tx_date ON income_transactions (user_id, income_date);

-- ============================================================
-- EXPENSE LEDGER — append-only
-- ============================================================
CREATE TABLE expense_transactions (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id         UUID NOT NULL REFERENCES users(id),
    category_id     UUID NOT NULL REFERENCES categories(id),
    amount          NUMERIC(20, 2) NOT NULL,
    currency        CHAR(3) NOT NULL DEFAULT 'USD',
    fx_rate_to_base NUMERIC(20, 8) NOT NULL DEFAULT 1.0,
    note            TEXT,
    expense_date    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    is_void         BOOLEAN NOT NULL DEFAULT FALSE,
    void_reason     TEXT,
    voided_at       TIMESTAMPTZ,

    CONSTRAINT chk_expense_positive CHECK (amount > 0)
);

CREATE INDEX idx_exp_tx_user ON expense_transactions (user_id);
CREATE INDEX idx_exp_tx_date ON expense_transactions (user_id, expense_date);
CREATE INDEX idx_exp_tx_cat  ON expense_transactions (category_id);

-- --------------------------------------------------------
-- INVESTMENT MODULE — APPEND-ONLY LEDGER
-- --------------------------------------------------------

CREATE TABLE assets (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    asset_class     asset_class NOT NULL,
    symbol          VARCHAR(20) NOT NULL,              -- 'BTC', 'AAPL', 'EUR/USD'
    name            VARCHAR(255) NOT NULL,
    base_currency   CHAR(3) NOT NULL DEFAULT 'USD',
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (asset_class, symbol)
);

CREATE INDEX idx_assets_class ON assets (asset_class);

-- ============================================================
-- INVESTMENT LEDGER — single source of truth
-- RULE: NEVER DELETE or UPDATE amount columns.
-- Every event = one INSERT.
-- ============================================================
CREATE TABLE investment_transactions (
    id               UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id          UUID NOT NULL REFERENCES users(id),
    asset_id         UUID NOT NULL REFERENCES assets(id),
    side             transaction_side NOT NULL,         -- BUY or SELL
    quantity         NUMERIC(20, 8) NOT NULL,
    unit_price       NUMERIC(20, 8) NOT NULL,
    total_amount     NUMERIC(20, 8) NOT NULL,           -- = quantity × unit_price
    commission       NUMERIC(20, 8) NOT NULL DEFAULT 0,
    fx_rate_to_base  NUMERIC(20, 8) NOT NULL DEFAULT 1.0,
    note             TEXT,
    transaction_date TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    is_void          BOOLEAN NOT NULL DEFAULT FALSE,
    void_reason      TEXT,
    voided_at        TIMESTAMPTZ,

    CONSTRAINT chk_total CHECK (
        ABS(total_amount - quantity * unit_price) < 0.01
    ),
    CONSTRAINT chk_positive_qty CHECK (quantity > 0),
    CONSTRAINT chk_positive_price CHECK (unit_price > 0)
);

CREATE INDEX idx_inv_tx_user ON investment_transactions (user_id);
CREATE INDEX idx_inv_tx_asset ON investment_transactions (asset_id);
CREATE INDEX idx_inv_tx_date ON investment_transactions (user_id, transaction_date);

-- ============================================================
-- POSITION SUMMARY VIEW (computed, never stored)
-- ============================================================
CREATE VIEW v_positions AS
SELECT
    t.user_id,
    t.asset_id,
    a.asset_class,
    a.symbol,
    a.name,
    SUM(CASE WHEN side = 'BUY'  AND NOT is_void THEN quantity ELSE 0 END)
  - SUM(CASE WHEN side = 'SELL' AND NOT is_void THEN quantity ELSE 0 END)
        AS net_quantity,
    SUM(CASE WHEN side = 'BUY' AND NOT is_void
             THEN (total_amount + commission) * fx_rate_to_base ELSE 0 END)
        AS total_cost_base,
    SUM(CASE WHEN side = 'SELL' AND NOT is_void
             THEN (total_amount - commission) * fx_rate_to_base ELSE 0 END)
        AS total_sell_proceeds_base,
    SUM(CASE WHEN NOT is_void THEN commission * fx_rate_to_base ELSE 0 END)
        AS total_commission_base,
    MIN(t.transaction_date) AS first_trade,
    MAX(t.transaction_date) AS last_trade,
    COUNT(*) FILTER (WHERE NOT is_void) AS trade_count
FROM investment_transactions t
JOIN assets a ON a.id = t.asset_id
GROUP BY t.user_id, t.asset_id, a.asset_class, a.symbol, a.name;

-- --------------------------------------------------------
-- PRICE CACHE
-- --------------------------------------------------------
CREATE TABLE price_snapshots (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    asset_id    UUID NOT NULL REFERENCES assets(id),
    price       NUMERIC(20, 8) NOT NULL,
    currency    CHAR(3) NOT NULL DEFAULT 'USD',
    source      VARCHAR(50) NOT NULL,                 -- 'binance', 'yahoo'
    fetched_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_price_asset_time ON price_snapshots (asset_id, fetched_at DESC);

CREATE TABLE fx_rate_snapshots (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    base_currency   CHAR(3) NOT NULL,
    quote_currency  CHAR(3) NOT NULL,
    rate            NUMERIC(20, 8) NOT NULL,
    source          VARCHAR(50) NOT NULL,
    fetched_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_fx_pair_time ON fx_rate_snapshots (base_currency, quote_currency, fetched_at DESC);

-- --------------------------------------------------------
-- AI MODULE — interaction logs
-- --------------------------------------------------------
CREATE TABLE ai_interactions (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id         UUID NOT NULL REFERENCES users(id),
    prompt          TEXT NOT NULL,                     -- user's natural language input
    response        JSONB NOT NULL,                    -- AI's structured response
    action_type     VARCHAR(50) NOT NULL,              -- 'parse_transaction', 'categorize', 'report'
    was_accepted    BOOLEAN,                           -- did user confirm AI's suggestion?
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_ai_user ON ai_interactions (user_id);

-- --------------------------------------------------------
-- SEED: System Categories
-- --------------------------------------------------------

-- Expense categories
INSERT INTO categories (name, icon, color, direction, cat_type) VALUES
    ('Kira',          '🏠', '#FF6B6B', 'EXPENSE', 'SYSTEM'),
    ('Faturalar',     '📄', '#4ECDC4', 'EXPENSE', 'SYSTEM'),
    ('Yemek',         '🍕', '#FFE66D', 'EXPENSE', 'SYSTEM'),
    ('Ulaşım',       '🚗', '#A8E6CF', 'EXPENSE', 'SYSTEM'),
    ('Eğlence',      '🎬', '#DDA0DD', 'EXPENSE', 'SYSTEM'),
    ('Sağlık',       '💊', '#98D8C8', 'EXPENSE', 'SYSTEM'),
    ('Alışveriş',    '🛍️', '#F7DC6F', 'EXPENSE', 'SYSTEM'),
    ('Eğitim',       '📚', '#85C1E9', 'EXPENSE', 'SYSTEM'),
    ('Abonelikler',  '📱', '#C39BD3', 'EXPENSE', 'SYSTEM'),
    ('Diğer',        '📌', '#AEB6BF', 'EXPENSE', 'SYSTEM');

-- Income categories
INSERT INTO categories (name, icon, color, direction, cat_type) VALUES
    ('Maaş',         '💰', '#2ECC71', 'INCOME', 'SYSTEM'),
    ('Freelance',    '💻', '#3498DB', 'INCOME', 'SYSTEM'),
    ('Yatırım Geliri','📈', '#E67E22', 'INCOME', 'SYSTEM'),
    ('Hediye',       '🎁', '#E91E63', 'INCOME', 'SYSTEM'),
    ('Burs',         '🎓', '#9B59B6', 'INCOME', 'SYSTEM'),
    ('Diğer',        '📌', '#95A5A6', 'INCOME', 'SYSTEM');
```

---

## 3. Backend Architecture (Laravel)

### Folder Structure

```
snuzdan/
├── docker-compose.yml              # Laravel Sail (PostgreSQL, Redis, Mailpit)
├── Dockerfile                      # PHP 8.3 + extensions
│
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   ├── LoginController.php
│   │   │   │   ├── RegisterController.php
│   │   │   │   └── GoogleOAuthController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── ExpenseController.php
│   │   │   ├── IncomeController.php
│   │   │   ├── InvestmentController.php
│   │   │   ├── PortfolioController.php
│   │   │   ├── CategoryController.php
│   │   │   ├── PriceController.php
│   │   │   ├── AiController.php          # AI endpoints
│   │   │   └── SettingsController.php
│   │   ├── Requests/                     # Form Request validation
│   │   │   ├── StoreExpenseRequest.php
│   │   │   ├── StoreIncomeRequest.php
│   │   │   ├── StoreTradeRequest.php
│   │   │   └── AiParseRequest.php
│   │   └── Middleware/
│   │       └── AppendOnlyGuard.php       # Blocks DELETE on financial tables
│   │
│   ├── Models/
│   │   ├── User.php
│   │   ├── Category.php
│   │   ├── ExpenseTransaction.php
│   │   ├── IncomeTransaction.php
│   │   ├── InvestmentTransaction.php
│   │   ├── Asset.php
│   │   ├── PriceSnapshot.php
│   │   ├── FxRateSnapshot.php
│   │   └── AiInteraction.php
│   │
│   ├── Services/                         # Business logic layer
│   │   ├── ExpenseService.php            # Expense CRUD (append-only)
│   │   ├── IncomeService.php             # Income CRUD (append-only)
│   │   ├── InvestmentService.php         # Trade entry, void, auto-calc
│   │   ├── PortfolioService.php          # Positions, FIFO PnL, unrealized PnL
│   │   ├── PriceService.php              # Price abstraction layer
│   │   ├── FxService.php                 # FX rate fetch & normalization
│   │   └── AiService.php                 # Gemini API integration
│   │
│   ├── Providers/                        # Price data source implementations
│   │   ├── PriceProviderInterface.php    # Contract
│   │   ├── BinanceProvider.php           # Crypto prices (free, no key)
│   │   └── YahooProvider.php             # Stocks + FX (free)
│   │
│   ├── Jobs/                             # Queue jobs
│   │   ├── FetchPriceSnapshots.php       # Periodic price caching
│   │   └── FetchFxRates.php              # FX rate updates
│   │
│   └── Console/
│       └── Kernel.php                    # Laravel Scheduler (cron)
│           # - Hourly: fetch price snapshots
│           # - Daily:  fetch FX rates
│
├── database/
│   ├── migrations/                       # Eloquent migrations (DDL above)
│   │   ├── 0001_create_users_table.php
│   │   ├── 0002_create_categories_table.php
│   │   ├── 0003_create_expense_transactions_table.php
│   │   ├── 0004_create_income_transactions_table.php
│   │   ├── 0005_create_assets_table.php
│   │   ├── 0006_create_investment_transactions_table.php
│   │   ├── 0007_create_price_snapshots_table.php
│   │   ├── 0008_create_fx_rate_snapshots_table.php
│   │   ├── 0009_create_ai_interactions_table.php
│   │   └── 0010_create_v_positions_view.php
│   └── seeders/
│       ├── CategorySeeder.php            # System categories (Türkçe)
│       └── DemoDataSeeder.php            # Test data for development
│
├── resources/
│   └── js/                               # React (Inertia) frontend → see §4
│
├── routes/
│   └── web.php                           # All routes (Inertia handles SPA)
│
└── config/
    ├── services.php                      # Gemini API key, provider config
    └── snuzdan.php                          # App-specific config (supported currencies, etc.)
```

### Key Service Responsibilities

| Service | Sorumluluk |
|---|---|
| `ExpenseService` | Gider ekleme (INSERT only), void (soft-delete), tarih/kategori filtresi, aylık toplamlar |
| `IncomeService` | Gelir ekleme (INSERT only), void, tarih/kategori filtresi, aylık toplamlar |
| `InvestmentService` | Trade girişi (3 alandan 2'si girilir, 3. auto-calc), BUY/SELL kısıtları, void |
| `PortfolioService` | Net pozisyonlar, FIFO realized PnL, live fiyatlarla unrealized PnL, tarihsel chart data |
| `PriceService` | Provider abstraction — asset_class'a göre Binance/Yahoo çağırır, cache'e yazar |
| `FxService` | Döviz kuru çekme, base currency'ye normalizasyon |
| `AiService` | Gemini API çağrısı — doğal dil parse, kategori tahmini, aylık rapor üretimi |

### Price Provider Abstraction

```
┌──────────────────┐
│  PriceService    │  ← Controller bu servisi çağırır
└───────┬──────────┘
        │ asset_class'a göre yönlendirir
   ┌────┴─────┐
   ▼          ▼
┌────────┐ ┌────────┐
│Binance │ │Yahoo   │   ← Değiştirilebilir provider'lar
│Provider│ │Provider│
└────────┘ └────────┘
     │          │
     └────┬─────┘
          ▼
  price_snapshots (cache)
```

---

## 4. Frontend Component Tree (React + Inertia)

```
resources/js/
├── app.tsx                              # Inertia app bootstrap
├── ssr.tsx                              # SSR entry (optional)
│
├── Layouts/
│   ├── AuthLayout.tsx                   # Login/register page shell
│   ├── AppLayout.tsx                    # Main app shell (sidebar + topbar)
│   ├── Sidebar.tsx                      # Navigation links
│   ├── TopBar.tsx                       # User avatar, currency selector, theme toggle
│   └── MobileNav.tsx                    # Hamburger menu
│
├── Components/
│   ├── ui/                              # shadcn/ui primitives
│   │   ├── button.tsx
│   │   ├── card.tsx
│   │   ├── dialog.tsx
│   │   ├── input.tsx
│   │   ├── select.tsx
│   │   ├── tabs.tsx
│   │   ├── toast.tsx
│   │   └── ...
│   │
│   ├── theme/
│   │   ├── ThemeProvider.tsx             # Light/dark tema context
│   │   └── ThemeToggle.tsx              # Açık/koyu geçiş butonu
│   │
│   ├── ai/
│   │   ├── AiTransactionInput.tsx       # "50 lira yemek yedim" → parse
│   │   ├── AiSuggestionCard.tsx         # AI'ın önerdiği işlemi gösterir
│   │   ├── AiMonthlyReport.tsx          # Aylık AI finansal analiz
│   │   └── AiCategoryPredictor.tsx      # Not yazınca kategori öner
│   │
│   ├── charts/                          # ⭐ Gelişmiş Grafikler
│   │   ├── SankeyDiagram.tsx            # Gelir → Kategorilere akış (nivo)
│   │   ├── HeatmapCalendar.tsx          # Harcama takvimi (nivo)
│   │   ├── TreemapChart.tsx             # Portföy dağılımı ağaç haritası (nivo)
│   │   ├── PnLLineChart.tsx             # Realized + Unrealized PnL (recharts)
│   │   ├── ExpenseBarChart.tsx          # Aylık gider bar chart (recharts)
│   │   ├── IncomeBarChart.tsx           # Aylık gelir bar chart (recharts)
│   │   ├── AllocationPieChart.tsx       # Portföy pie/donut chart (recharts)
│   │   ├── IncomeVsExpenseChart.tsx     # Gelir vs gider karşılaştırma (recharts)
│   │   └── SparkLine.tsx               # Mini inline chart for cards
│   │
│   ├── dashboard/
│   │   ├── NetWorthCard.tsx             # Toplam varlık (base currency)
│   │   ├── MonthlyBalanceCard.tsx       # Bu ay: gelir - gider
│   │   ├── TopMoversCard.tsx            # En çok değişen yatırımlar
│   │   └── RecentActivityFeed.tsx       # Son işlemler (tüm modüller)
│   │
│   ├── expenses/
│   │   ├── ExpenseEntryForm.tsx         # Tutar, kategori, tarih, not
│   │   ├── ExpenseList.tsx              # Filtrelenebilir/sıralanabilir tablo
│   │   ├── ExpenseListItem.tsx          # Tek gider satırı (void butonu)
│   │   ├── CategoryPicker.tsx           # System + custom kategoriler (icon grid)
│   │   └── CategoryManager.tsx          # Custom kategori ekleme/düzenleme
│   │
│   ├── income/
│   │   ├── IncomeEntryForm.tsx          # Tutar, kategori, tarih, not
│   │   ├── IncomeList.tsx               # Filtrelenebilir gelir listesi
│   │   └── IncomeListItem.tsx           # Tek gelir satırı
│   │
│   ├── portfolio/
│   │   ├── AssetClassTabs.tsx           # Crypto | Stocks | FX tab switcher
│   │   ├── PositionList.tsx             # Açık pozisyonlar
│   │   ├── PositionCard.tsx             # Varlık, miktar, maliyet, PnL
│   │   ├── TradeEntryForm.tsx           # BUY/SELL, 2-of-3 auto-calc
│   │   ├── TradeHistory.tsx             # İşlem geçmişi
│   │   ├── TradeHistoryRow.tsx          # Tek trade satırı
│   │   └── LivePriceTicker.tsx          # Canlı fiyat göstergesi
│   │
│   ├── settings/
│   │   ├── BaseCurrencySelector.tsx     # USD / EUR / TRY
│   │   ├── ProfileForm.tsx             # İsim, avatar
│   │   └── LinkedAccountsList.tsx      # Google hesap durumu
│   │
│   └── common/
│       ├── DateRangePicker.tsx          # Tarih aralığı seçici
│       ├── CurrencyDisplay.tsx         # Formatted para gösterimi
│       ├── VoidButton.tsx              # Soft-delete tetikleyici
│       └── EmptyState.tsx              # "Henüz kayıt yok" placeholder
│
├── Pages/                               # Inertia pages (route-mapped)
│   ├── Auth/
│   │   ├── Login.tsx
│   │   ├── Register.tsx
│   │   └── VerifyEmail.tsx
│   ├── Dashboard.tsx                    # Ana sayfa
│   ├── Expenses/
│   │   └── Index.tsx                    # Gider sekmesi
│   ├── Income/
│   │   └── Index.tsx                    # Gelir sekmesi
│   ├── Portfolio/
│   │   ├── Index.tsx                    # Portföy özeti
│   │   ├── Crypto.tsx
│   │   ├── Stocks.tsx
│   │   └── Fx.tsx
│   └── Settings.tsx
│
├── hooks/
│   ├── useTheme.ts                      # Tema state hook
│   ├── useCurrency.ts                   # Currency formatting
│   └── useAi.ts                         # AI interaction hook
│
├── lib/
│   ├── utils.ts                         # cn() helper, formatters
│   └── validators.ts                    # Zod schemas (client-side)
│
└── types/
    ├── models.ts                        # DB model types
    └── inertia.d.ts                     # Inertia shared data types
```

### Key Component Responsibilities

| Component | Sorumluluk |
|---|---|
| `AiTransactionInput` | Kullanıcı Türkçe yazar → Gemini parse eder → öneri kartı gösterir → onaylarsa kayıt oluşur |
| `SankeyDiagram` | Gelir kaynakları → gider kategorilerine para akışını görselleştirir |
| `HeatmapCalendar` | GitHub contribution map tarzı; hangi günler ne kadar harcama yapılmış |
| `TreemapChart` | Portföy dağılımını alan oranlarıyla gösterir (BTC %40, AAPL %25...) |
| `TradeEntryForm` | 3 alandan 2'si girilir (unit_price, quantity, total_amount); 3. real-time hesaplanır |
| `ThemeProvider` | Tailwind dark mode class toggle; localStorage'da persist |
| `CategoryPicker` | INCOME/EXPENSE'e göre filtreli system + custom kategori grid'i |

---

## 5. Key Design Decisions & Trade-offs

### ✅ Kararlar

| Karar | Gerekçe |
|---|---|
| **Laravel + Inertia + React** | Docker ile en uyumlu stack; Sail hazır; backend gücü (queue, scheduler, mail) built-in |
| **Gelir ve Gider ayrı tablolar** | Farklı iş kuralları olabilir; raporlarda bağımsız sorgulanır; ama ortak `categories` tablosu paylaşılır |
| **`categories.direction` ile INCOME/EXPENSE ayrımı** | Tek tablo, iki yönlü; system seed'de Türkçe kategoriler |
| **Append-only + `is_void` soft-delete** | Tam denetim izi; silme yok, sadece iptal + ters kayıt |
| **FIFO PnL → service layer** | SQL window function fragile; PHP'de test etmesi kolay |
| **nivo kütüphanesi gelişmiş grafikler için** | Recharts Sankey/Heatmap/Treemap desteklemiyor; nivo bu üçünde en iyi |
| **AI interaction log tablosu** | Kullanıcının AI ile etkileşimini kaydeder; kabul/ret oranı ölçülebilir |
| **Tema tercihi DB'de** | `users.theme` alanı; cihazlar arası senkronize; localStorage + DB hybrid |

### ⚠️ Trade-offs

| Trade-off | Çözüm |
|---|---|
| **Gemini API rate limit** (free: 60 req/min) | AI çağrılarını debounce et; response cache'le |
| **Yahoo Finance kararsızlığı** | Provider abstraction ile Alpha Vantage'a geçilebilir |
| **Inertia SSR opsiyonel** | İlk aşamada SSR kapalı; performans gerekirse açılır |
| **Redis zorunlu mu?** | Queue ve cache için gerekli; Docker Compose'da zaten var |

### 🔄 Data Flow: AI ile İşlem Ekleme

```
User: "Dün akşam 250 lira yemek yedim"
         │
         ▼
  AiTransactionInput (React)
         │ POST /ai/parse
         ▼
  AiController → AiService
         │ Gemini API çağrısı
         ▼
  Gemini Response:
  { type: "EXPENSE", amount: 250, currency: "TRY",
    category: "Yemek", date: "2026-04-04", note: "akşam yemeği" }
         │
         ▼
  AiSuggestionCard (React) ← kullanıcı onaylar/düzenler
         │ POST /expenses
         ▼
  ExpenseController → ExpenseService → INSERT expense_transactions
```

---

## 6. Docker Compose Architecture

```
┌─────────────────────────────────────────────────┐
│                  Docker Network                  │
│                                                  │
│  ┌───────────┐  ┌──────────┐  ┌──────────────┐ │
│  │  Laravel   │  │PostgreSQL│  │    Redis      │ │
│  │  App       │  │    16    │  │   (cache +    │ │
│  │  (PHP 8.3) │  │          │  │    queue)     │ │
│  │  Port:80   │  │ Port:5432│  │  Port:6379   │ │
│  └───────────┘  └──────────┘  └──────────────┘ │
│  ┌───────────┐  ┌──────────┐                    │
│  │   Vite    │  │ Mailpit  │                    │
│  │ Dev Server│  │  (email  │                    │
│  │ Port:5173 │  │  testing)│                    │
│  └───────────┘  └──────────┘                    │
└─────────────────────────────────────────────────┘
```
