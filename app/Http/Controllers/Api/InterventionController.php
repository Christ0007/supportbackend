<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Intervention;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InterventionController extends Controller
{
    public function store(Request $request, Incident $incident)
    {
        $this->authorize('addIntervention', $incident);

        $request->validate([
            'intervention_date' => 'required|date',
            'duration' => 'required|integer|min:1',
            'description' => 'required|string',
        ]);

        $intervention = Intervention::create([
            'incident_id' => $incident->id,
            'user_id' => Auth::id(),
            'intervention_date' => $request->intervention_date,
            'duration' => $request->duration,
            'description' => $request->description,
        ]);

        return response()->json($intervention, 201);
    }

    public function index(Incident $incident)
    {
        $this->authorize('view', $incident);

        $interventions = $incident->interventions()->with('user')->latest()->paginate(10);

        return response()->json($interventions);
    }
}