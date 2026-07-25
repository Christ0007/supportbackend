<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Company::class);

        $companies = Company::with(['user', 'softwareSolutions'])
            ->latest()
            ->paginate(20);

        return response()->json($companies);
    }

    public function store(StoreCompanyRequest $request)
    {
        $this->authorize('create', Company::class);

        $company = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'company',
                'is_active' => true,
            ]);

            $company = Company::create([
                'user_id' => $user->id,
                'company_name' => $request->company_name,
                'contact_name' => $request->contact_name,
                'phone' => $request->phone,
                'address' => $request->address,
                'is_active' => true,
            ]);

            if ($request->software_solution_ids) {
                $company->softwareSolutions()->sync($request->software_solution_ids);
            }

            return $company;
        });

        return response()->json($company->load(['user', 'softwareSolutions']), 201);
    }

    public function show(Company $company)
    {
        $this->authorize('view', $company);

        $company->load(['user', 'softwareSolutions', 'incidents']);

        return response()->json($company);
    }

    public function update(UpdateCompanyRequest $request, Company $company)
    {
        $this->authorize('update', $company);

        $company->update($request->only(['company_name', 'contact_name', 'phone', 'address']));

        if ($request->has('software_solution_ids')) {
            $company->softwareSolutions()->sync($request->software_solution_ids);
        }

        return response()->json($company->load(['user', 'softwareSolutions']));
    }

    public function destroy(Company $company)
    {
        $this->authorize('delete', $company);

        $company->user->update(['is_active' => false]);
        $company->delete();

        return response()->json(['message' => 'Entreprise cliente désactivée.']);
    }
}