<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Gider (EXPENSE) kategorileri
        $expenses = [
            ['name' => 'Kira',        'icon' => '🏠', 'color' => '#FF6B6B'],
            ['name' => 'Faturalar',   'icon' => '📄', 'color' => '#4ECDC4'],
            ['name' => 'Yemek',       'icon' => '🍕', 'color' => '#FFE66D'],
            ['name' => 'Ulaşım',     'icon' => '🚗', 'color' => '#A8E6CF'],
            ['name' => 'Eğlence',    'icon' => '🎬', 'color' => '#DDA0DD'],
            ['name' => 'Sağlık',     'icon' => '💊', 'color' => '#98D8C8'],
            ['name' => 'Alışveriş',  'icon' => '🛍️', 'color' => '#F7DC6F'],
            ['name' => 'Eğitim',     'icon' => '📚', 'color' => '#85C1E9'],
            ['name' => 'Abonelikler','icon' => '📱', 'color' => '#C39BD3'],
            ['name' => 'Diğer',      'icon' => '📌', 'color' => '#AEB6BF'],
        ];

        foreach ($expenses as $cat) {
            DB::table('categories')->insert([
                'name'      => $cat['name'],
                'icon'      => $cat['icon'],
                'color'     => $cat['color'],
                'direction' => 'EXPENSE',
                'cat_type'  => 'SYSTEM',
                'workspace_id' => null,
                'is_active' => true,
            ]);
        }

        // Gelir (INCOME) kategorileri
        $incomes = [
            ['name' => 'Maaş',           'icon' => '💰', 'color' => '#2ECC71'],
            ['name' => 'Freelance',       'icon' => '💻', 'color' => '#3498DB'],
            ['name' => 'Yatırım Geliri', 'icon' => '📈', 'color' => '#E67E22'],
            ['name' => 'Hediye',          'icon' => '🎁', 'color' => '#E91E63'],
            ['name' => 'Burs',            'icon' => '🎓', 'color' => '#9B59B6'],
            ['name' => 'Diğer',           'icon' => '📌', 'color' => '#95A5A6'],
        ];

        foreach ($incomes as $cat) {
            DB::table('categories')->insert([
                'name'      => $cat['name'],
                'icon'      => $cat['icon'],
                'color'     => $cat['color'],
                'direction' => 'INCOME',
                'cat_type'  => 'SYSTEM',
                'workspace_id' => null,
                'is_active' => true,
            ]);
        }
    }
}
