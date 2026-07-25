<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Satisfaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SatisfactionController extends Controller
{
    public function store(Request $request, Incident $incident)
    {
        $this->authorize('evaluate', $incident);

        // Vérifier que l'incident est résolu ou clôturé
        if ($incident->status !== 'closed') {
            return response()->json([
                'message' => 'Vous ne pouvez évaluer qu\'un incident résolu ou clôturé.',
            ], 400);
        }

        // Vérifier qu'il n'y a pas déjà une évaluation
        if ($incident->satisfaction) {
            return response()->json([
                'message' => 'Cet incident a déjà été évalué.',
            ], 400);
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $satisfaction = Satisfaction::create([
            'incident_id' => $incident->id,
            'user_id' => Auth::id(),
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json($satisfaction, 201);
    }

    public function show(Incident $incident)
    {
        $this->authorize('view', $incident);

        return response()->json($incident->satisfaction);
    }
}