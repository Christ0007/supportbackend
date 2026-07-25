<?php

namespace Database\Seeders;

use App\Models\SoftwareSolution;
use App\Models\User;
use Illuminate\Database\Seeder;

class SoftwareSolutionSeeder extends Seeder
{
    public function run(): void
    {
        $solutions = [
            ['name' => 'Gestion Commerciale', 'description' => 'Solution de gestion commerciale et CRM', 'version' => '2.1'],
            ['name' => 'ERP Production', 'description' => 'ERP pour la gestion de production industrielle', 'version' => '1.5'],
            ['name' => 'Comptabilité Plus', 'description' => 'Logiciel de comptabilité avancée', 'version' => '3.0'],
            ['name' => 'RH Manager', 'description' => 'Gestion des ressources humaines', 'version' => '1.2'],
        ];

        $technician1 = User::where('email', 'jean.dupont@support.com')->first();
        $technician2 = User::where('email', 'marie.martin@support.com')->first();
        $technician3 = User::where('email', 'pierre.durand@support.com')->first();

        foreach ($solutions as $index => $solutionData) {
            $solution = SoftwareSolution::create($solutionData);

            if ($index === 0) {
                $solution->technicians()->sync([$technician1->id, $technician2->id]);
            } elseif ($index === 1) {
                $solution->technicians()->sync([$technician2->id, $technician3->id]);
            } elseif ($index === 2) {
                $solution->technicians()->sync([$technician1->id]);
            } else {
                $solution->technicians()->sync([$technician3->id]);
            }
        }
    }
}