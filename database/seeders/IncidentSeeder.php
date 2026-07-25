<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Incident;
use App\Models\SoftwareSolution;
use Illuminate\Database\Seeder;

class IncidentSeeder extends Seeder
{
    public function run(): void
    {
        $company1 = Company::where('company_name', 'Alpha Corp')->first();
        $company2 = Company::where('company_name', 'Beta Industries')->first();

        $solutions = SoftwareSolution::all();

        $incidents = [
            [
                'title' => 'Erreur lors de la génération de facture',
                'description' => 'Une erreur 500 apparaît lors de la génération des factures mensuelles.',
                'priority' => 'high',
                'category' => 'bug',
                'status' => 'in_progress',
                'software_solution_id' => $solutions[0]->id,
                'company_id' => $company1->id,
                'technician_id' => 2, // Jean Dupont
            ],
            [
                'title' => 'Lenteur du module de reporting',
                'description' => 'Le temps de chargement des rapports est très long (+30 secondes).',
                'priority' => 'medium',
                'category' => 'performance',
                'status' => 'declared',
                'software_solution_id' => $solutions[1]->id,
                'company_id' => $company2->id,
            ],
            [
                'title' => 'Impossible de modifier un utilisateur',
                'description' => 'Le bouton de modification dans la gestion des utilisateurs ne fonctionne plus.',
                'priority' => 'critical',
                'category' => 'bug',
                'status' => 'taken_over',
                'software_solution_id' => $solutions[2]->id,
                'company_id' => $company1->id,
                'technician_id' => 2, // Jean Dupont
            ],
            [
                'title' => 'Demande d\'ajout de fonctionnalité',
                'description' => 'Ajouter la possibilité d\'exporter les données au format CSV.',
                'priority' => 'low',
                'category' => 'feature',
                'status' => 'resolved',
                'software_solution_id' => $solutions[3]->id,
                'company_id' => $company2->id,
                'technician_id' => 4, // Pierre Durand
            ],
        ];

        foreach ($incidents as $incident) {
            Incident::create($incident);
        }
    }
}