<?php

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;

class IncidentPolicy
{
    public function view(User $user, Incident $incident)
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isCompany()) {
            return $incident->company_id === $user->company->id;
        }

        if ($user->isTechnician()) {
            return $user->supportedSolutions()
                ->where('software_solution_id', $incident->software_solution_id)
                ->exists();
        }

        return false;
    }

    public function viewDashboard(User $user)
    {
        return $user->isAdmin() || $user->isTechnician() || $user->isCompany();
    }   

    public function takeOver(User $user, Incident $incident)
    {
        if (!$user->isTechnician()) {
            return false;
        }

        if ($incident->technician_id) {
            return false;
        }

        return $user->supportedSolutions()
            ->where('software_solution_id', $incident->software_solution_id)
            ->exists();
    }

    public function updateStatus(User $user, Incident $incident)
        {
            if ($user->isAdmin()) {
                return true;
            }

            if ($user->isTechnician() && $incident->technician_id === $user->id) {
                return true;
            }

            // Le client peut valider (closed) ou refuser (in_progress) une résolution
            if ($user->isCompany()
                && $incident->company_id === $user->company->id
                && $incident->status === 'resolved') {
                return true;
            }

            return false;
        }

    public function addIntervention(User $user, Incident $incident)
    {
        if (!$user->isTechnician()) {
            return false;
        }

        return $incident->technician_id === $user->id;
    }

    public function evaluate(User $user, Incident $incident)
    {
        if (!$user->isCompany()) {
            return false;
        }

        return $incident->company_id === $user->company->id
                && $incident->status === 'closed';
    }
}