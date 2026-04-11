/**
 * TypeScript Model Tanımları — Snuzdan
 *
 * Backend modellerinin frontend karşılıkları.
 * Inertia.js ile gelen verilerin tiplerini tanımlar.
 */

// ============================================================
// USERS & AUTH
// ============================================================
export interface User {
    id: string;
    email: string;
    display_name: string;
    base_currency: 'USD' | 'EUR' | 'TRY';
    theme: 'light' | 'dark';
    status: 'pending' | 'active' | 'suspended';
    email_verified: boolean;
    current_workspace_id: string | null;
    avatar_url: string | null;
    created_at: string;
    updated_at: string;
}

// ============================================================
// ASSETS & INVESTMENTS
// ============================================================
export type AssetClass = 'CRYPTO' | 'STOCK' | 'FX';
export type TransactionSide = 'BUY' | 'SELL';

export interface Asset {
    id: string;
    asset_class: AssetClass;
    symbol: string;
    name: string;
    base_currency: string;
    created_at: string;
}

export interface InvestmentTransaction {
    id: string;
    workspace_id: string;
    created_by_user_id: string;
    asset_id: string;
    asset: Asset;
    side: TransactionSide;
    quantity: number;
    unit_price: number;
    total_amount: number;
    commission: number;
    fx_rate_to_base: number;
    note: string | null;
    transaction_date: string;
    created_at: string;
    is_void: boolean;
    void_reason: string | null;
    voided_at: string | null;
}

// ============================================================
// PORTFOLIO
// ============================================================
export interface Position {
    workspace_id: string;
    asset_id: string;
    asset_class: AssetClass;
    symbol: string;
    name: string;
    net_quantity: number;
    avg_cost: number;
    current_price: number;
    total_cost_base: number;
    total_sell_proceeds_base: number;
    total_commission_base: number;
    unrealized_pnl: number;
    unrealized_pnl_percent: number;
    first_trade: string;
    last_trade: string;
    trade_count: number;
}

export interface PortfolioSummary {
    total_value: number;
    total_unrealized: number;
    total_realized: number;
    allocation: Record<AssetClass, number>;
    position_count: number;
}

export interface TopMover {
    symbol: string;
    name: string;
    asset_class: AssetClass;
    current_price: number;
    change_percent: number;
    pnl: number;
}

// ============================================================
// PAGINATION (Laravel)
// ============================================================
export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}
