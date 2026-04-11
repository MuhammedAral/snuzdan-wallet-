import React, { useState } from 'react';
import { AssetClass } from '@/types/models';

/**
 * AssetClassTabs — Varlık Sınıfı Sekmeleri
 *
 * CRYPTO | STOCKS | FX arasında geçiş yapan tab switcher.
 * Portföy sayfasında pozisyonları filtrelemek için kullanılır.
 */

interface AssetClassTabsProps {
    activeTab: AssetClass | 'ALL';
    onChange: (tab: AssetClass | 'ALL') => void;
}

const tabs = [
    { key: 'ALL' as const, label: 'Tümü', icon: '📊' },
    { key: 'CRYPTO' as const, label: 'Kripto', icon: '₿' },
    { key: 'STOCK' as const, label: 'Hisse', icon: '📈' },
    { key: 'FX' as const, label: 'Döviz', icon: '💱' },
];

const AssetClassTabs: React.FC<AssetClassTabsProps> = ({ activeTab, onChange }) => {
    return (
        <div className="flex gap-1 p-1 bg-gray-100 dark:bg-gray-800 rounded-xl">
            {tabs.map((tab) => (
                <button
                    key={tab.key}
                    onClick={() => onChange(tab.key)}
                    className={`
                        flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium
                        transition-all duration-200 ease-in-out
                        ${activeTab === tab.key
                            ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                        }
                    `}
                >
                    <span>{tab.icon}</span>
                    <span>{tab.label}</span>
                </button>
            ))}
        </div>
    );
};

export default AssetClassTabs;
