<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('items')->insert([

            [
                'name' => 'T4 Broadsword',
                'category' => 'Weapon',
                'description' => 'Tier 4 Sword Weapon',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'T5 Claymore',
                'category' => 'Weapon',
                'description' => 'Tier 5 Sword Weapon',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'T4 Bloodletter',
                'category' => 'Weapon',
                'description' => 'Popular ganking dagger',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'T6 Battleaxe',
                'category' => 'Weapon',
                'description' => 'Strong solo PvE weapon',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'T4 Soldier Helmet',
                'category' => 'Armor',
                'description' => 'Plate Helmet',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'T5 Mercenary Jacket',
                'category' => 'Armor',
                'description' => 'Leather Armor',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'T6 Scholar Sandals',
                'category' => 'Armor',
                'description' => 'Cloth Shoes',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'T4 Riding Horse',
                'category' => 'Mount',
                'description' => 'Basic mount',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'T5 Armored Horse',
                'category' => 'Mount',
                'description' => 'Tanky mount',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'T7 Ox',
                'category' => 'Mount',
                'description' => 'Heavy transport mount',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'T4 Bag',
                'category' => 'Accessory',
                'description' => 'Inventory expansion bag',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'T5 Cape',
                'category' => 'Accessory',
                'description' => 'Energy cape',
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}