<?php

namespace App\Policies;

use App\Models\SoftwareSolution;
use App\Models\User;

class SoftwareSolutionPolicy
{
    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, SoftwareSolution $solution)
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isTechnician()) {
            return $solution->technicians()->where('users.id', $user->id)->exists();
        }
        if ($user->isCompany()) {
            return $solution->companies()->where('companies.id', $user->company->id)->exists();
        }
        return false;
    }

    public function create(User $user)
    {
        return $user->isAdmin();
    }

    public function update(User $user, SoftwareSolution $solution)
    {
        return $user->isAdmin();
    }

    public function delete(User $user, SoftwareSolution $solution)
    {
        return $user->isAdmin();
    }
}