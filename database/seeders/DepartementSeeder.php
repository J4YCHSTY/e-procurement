<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Departement;

class DepartementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departements = ['IT', 'Finance', 'Procurement', 'HR', 'Operations'];

        foreach ($departements as $dept) {
            Departement::create(['name' => $dept]);
        }
    }
}
