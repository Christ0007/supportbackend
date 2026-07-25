<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\SoftwareSolution;
use App\Models\User;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            [
                'user' => [
                    'name' => 'Entreprise Alpha',
                    'email' => 'contact@alpha.com',
                    'password' => 'password123',
                    'role' => 'company',
                ],
                'company' => [
                    'company_name' => 'Alpha Corp',
                    'contact_name' => 'Robert Johnson',
                    'phone' => '01 23 45 67 89',
                    'address' => '123 Rue de Paris, 75001 Paris',
                ],
                'solutions' => ['Gestion Commerciale', 'RH Manager'],
            ],
            [
                'user' => [
                    'name' => 'Entreprise Beta',
                    'email' => 'contact@beta.com',
                    'password' => 'password123',
                    'role' => 'company',
                ],
                'company' => [
                    'company_name' => 'Beta Industries',
                    'contact_name' => 'Sophie Lefebvre',
                    'phone' => '01 98 76 54 32',
                    'address' => '456 Avenue des Champs-Élysées, 75008 Paris',
                ],
                'solutions' => ['ERP Production', 'Comptabilité Plus'],
            ],
        ];

        foreach ($companies as $data) {
            $user = User::create($data['user']);
            $company = Company::create(array_merge(['user_id' => $user->id], $data['company']));

            $solutionIds = SoftwareSolution::whereIn('name', $data['solutions'])->pluck('id');
            $company->softwareSolutions()->sync($solutionIds);
        }
    }
}