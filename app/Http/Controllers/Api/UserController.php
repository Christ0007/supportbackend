<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $query = User::with('company');

        if ($request->role) {
            $query->where('role', $request->role);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(20);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'role' => 'required|in:admin,technician,company',
            'company_name' => 'nullable|required_if:role,company|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'software_solution_ids' => 'nullable|array',
            'software_solution_ids.*' => 'exists:software_solutions,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => $request->role,
        ]);

        if ($request->role === 'company') {
            $company = Company::create([
                'user_id' => $user->id,
                'company_name' => $request->company_name,
                'contact_name' => $request->contact_name ?? $request->name,
                'phone' => $request->phone,
                'address' => $request->address,
            ]);

            if ($request->has('software_solution_ids')) {
                $company->softwareSolutions()->sync($request->software_solution_ids);
            }
        }

        if ($request->role === 'technician' && $request->software_solution_ids) {
            $user->supportedSolutions()->sync($request->software_solution_ids);
        }

        return response()->json($user->load('company'), 201);
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);

        $user->load('company.softwareSolutions', 'supportedSolutions');

        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|min:8',
            'role' => 'sometimes|in:admin,technician,company',
            'is_active' => 'sometimes|boolean',
            'company_name' => 'nullable|required_if:role,company|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'software_solution_ids' => 'nullable|array',
            'software_solution_ids.*' => 'exists:software_solutions,id',
        ]);

        $userData = $request->only(['name', 'email', 'role', 'is_active']);

        if ($request->has('password')) {
            $userData['password'] = $request->password;
        }

        $user->update($userData);

        if ($request->has('is_active') && !$request->is_active) {
            $user->tokens()->delete();
        }

        if ($user->isCompany()) {
            $company = $user->company;
            if ($company) {
                $company->update($request->only(['company_name', 'contact_name', 'phone', 'address']));

                if ($request->has('software_solution_ids')) {
                    $company->softwareSolutions()->sync($request->software_solution_ids);
                }
            }
        }

        if ($user->isTechnician() && $request->has('software_solution_ids')) {
            $user->supportedSolutions()->sync($request->software_solution_ids);
        }

        return response()->json($user->load('company.softwareSolutions', 'supportedSolutions'));
    }

    public function deactivate(User $user)
    {
        $this->authorize('update', $user);

        $user->update(['is_active' => false]);

        $user->tokens()->delete();

        return response()->json(['message' => 'Compte désactivé avec succès']);
    }

    public function activate(User $user)
    {
        $this->authorize('update', $user);

        $user->update(['is_active' => true]);

        return response()->json(['message' => 'Compte réactivé avec succès']);
    }
}