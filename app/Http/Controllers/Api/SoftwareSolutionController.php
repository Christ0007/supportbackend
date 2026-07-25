<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SoftwareSolution;
use Illuminate\Http\Request;

class SoftwareSolutionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = SoftwareSolution::with('technicians');

        if ($user->isCompany()) {
            $query->whereHas('companies', fn($q) => $q->where('companies.id', $user->company->id));
        } elseif ($user->isTechnician()) {
            $query->whereHas('technicians', fn($q) => $q->where('users.id', $user->id));
        }

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->sort_by === 'id') {
                $query->orderBy('id', $request->sort_dir === 'asc' ? 'asc' : 'desc');
            } else {
                $query->latest();
        }

            $solutions = $query->paginate(20);

        return response()->json($solutions);
    }

    public function store(Request $request)
    {
        $this->authorize('create', SoftwareSolution::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'version' => 'nullable|string|max:50',
            'technician_ids' => 'nullable|array',
            'technician_ids.*' => 'exists:users,id',
        ]);

        $solution = SoftwareSolution::create($request->only(['name', 'description', 'version']));

        if ($request->technician_ids) {
            $solution->technicians()->sync($request->technician_ids);
        }

        return response()->json($solution->load('technicians'), 201);
    }

public function show(SoftwareSolution $software_solution)
{
    $this->authorize('view', $software_solution);

    $software_solution->load(['technicians', 'companies.user', 'incidents']);
    return response()->json($software_solution);
}

public function update(Request $request, SoftwareSolution $software_solution)
{
    $this->authorize('update', $software_solution);

    $request->validate([
        'name' => 'sometimes|string|max:255',
        'description' => 'nullable|string',
        'version' => 'nullable|string|max:50',
        'is_active' => 'sometimes|boolean',
        'technician_ids' => 'nullable|array',
        'technician_ids.*' => 'exists:users,id',
    ]);

    $software_solution->update($request->only(['name', 'description', 'version', 'is_active']));

    if ($request->has('technician_ids')) {
        $software_solution->technicians()->sync($request->technician_ids);
    }

    return response()->json($software_solution->load('technicians'));
}
}