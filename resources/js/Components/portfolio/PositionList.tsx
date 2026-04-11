import React from 'react';
import { Position, AssetClass } from '@/types/models';
import PositionCard from './PositionCard';

/**
 * PositionList — Açık Pozisyonlar Listesi
 *
 * AssetClassTabs ile filtrelenen pozisyonları grid olarak gösterir.
 * Boş durum mesajı içerir.
 */

interface PositionListProps {
    positions: Position[];
    activeTab: AssetClass | 'ALL';
}

const PositionList: React.FC<PositionListProps> = ({ positions, activeTab }) => {
    const filtered = activeTab === 'ALL'
        ? positions
        : positions.filter((p) => p.asset_class === activeTab);

    if (filtered.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center py-16 text-center">
                <div className="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-4">
                    <span className="text-2xl">📭</span>
                </div>
                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-1">
                    Henüz pozisyon yok
                </h3>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                    "İşlem Ekle" butonuna tıklayarak ilk yatırımını ekle.
                </p>
            </div>
        );
    }

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            {filtered.map((position) => (
                <PositionCard key={position.asset_id} position={position} />
            ))}
        </div>
    );
};

export default PositionList;
