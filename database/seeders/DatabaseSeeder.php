<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\FiscalYear;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\User;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([ChartOfAccountSeeder::class]);

    }
}
