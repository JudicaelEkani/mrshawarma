<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ShawarmaSeeder extends Seeder
{
    public function run(): void
    {
        $this->upsertStaff(
            'Admin Mr. Shawarma',
            env('SEED_ADMIN_EMAIL', 'admin@mrshawarma.cm'),
            env('SEED_ADMIN_PASSWORD', 'Admin@2026'),
            'admin'
        );

        $this->upsertStaff(
            'Livreur Principal',
            env('SEED_LIVREUR_EMAIL', 'livreur@mrshawarma.cm'),
            env('SEED_LIVREUR_PASSWORD', 'Livreur@2026'),
            'livreur'
        );

        $products = [
            ['id' => 'sw2', 'category' => 'shawarma', 'name' => 'Shawarma Boeuf', 'description' => 'Boeuf grille, epices maison', 'base_price' => 1000, 'has_flavors' => false],
            ['id' => 'dr1', 'category' => 'boissons', 'name' => 'Jus naturel', 'description' => 'Ananas, cocktail, gingembre...', 'base_price' => 750, 'has_flavors' => true],
            ['id' => 'dr2', 'category' => 'boissons', 'name' => 'Eau minerale', 'description' => '50 cl fraiche', 'base_price' => 300, 'has_flavors' => false],
        ];

        foreach ($products as $p) {
            Product::updateOrCreate(['id' => $p['id']], $p + ['active' => true]);
        }

        $this->command->info(count($products) . ' produits initialises.');
        $this->command->info('Admin   -> ' . env('SEED_ADMIN_EMAIL', 'admin@mrshawarma.cm') . ' / ' . env('SEED_ADMIN_PASSWORD', 'Admin@2026'));
        $this->command->info('Livreur -> ' . env('SEED_LIVREUR_EMAIL', 'livreur@mrshawarma.cm') . ' / ' . env('SEED_LIVREUR_PASSWORD', 'Livreur@2026'));
    }

    private function upsertStaff(string $name, string $email, string $password, string $role): void
    {
        $user = User::where('email', $email)->first();
        if ($user) {
            $user->update(['name' => $name, 'password' => $password, 'role' => $role]);
        } else {
            User::create(['name' => $name, 'email' => $email, 'password' => $password, 'role' => $role]);
        }
        $this->command->info("Compte {$role} : {$email}");
    }
}
