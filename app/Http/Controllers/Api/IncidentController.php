<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IncidentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Incident::with(['softwareSolution', 'company', 'technician', 'satisfaction']);

        if ($user->isCompany()) {
            $query->where('company_id', $user->company->id);
        } elseif ($user->isTechnician()) {
            $solutionIds = $user->supportedSolutions()->pluck('software_solutions.id');
            $query->whereIn('software_solution_id', $solutionIds);
        }

        // Filtres
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->priority) {
            $query->where('priority', $request->priority);
        }
        if ($request->software_solution_id) {
            $query->where('software_solution_id', $request->software_solution_id);
        }
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->sort_by === 'id') {
            $query->orderBy('id', $request->sort_dir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $incidents = $query->paginate(15);
        return response()->json($incidents);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,critical',
            'category' => 'required|string|max:100',
            'software_solution_id' => 'required|exists:software_solutions,id',
        ]);

        $user = Auth::user();

        // Vérifier que la solution est associée à l'entreprise
        $isAssociated = $user->company->softwareSolutions()
            ->where('software_solution_id', $request->software_solution_id)
            ->exists();

        if (!$isAssociated) {
            return response()->json([
                'message' => 'Cette solution n\'est pas associée à votre entreprise.',
            ], 403);
        }

        $incident = Incident::create([
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'category' => $request->category,
            'software_solution_id' => $request->software_solution_id,
            'company_id' => $user->company->id,
            'status' => 'declared',
        ]);

        // Notifier les techniciens responsables
        $technicians = $incident->softwareSolution->technicians;
        foreach ($technicians as $technician) {
            Notification::create([
                'user_id' => $technician->id,
                'type' => 'new_incident',
                'message' => "Nouvel incident déclaré : {$incident->title}",
                'data' => ['incident_id' => $incident->id],
            ]);
        }

        return response()->json($incident->load(['softwareSolution', 'company']), 201);
    }

    public function show(Incident $incident)
    {
        $this->authorize('view', $incident);

        $incident->load(['softwareSolution', 'company', 'technician', 'interventions', 'messages.user', 'statusHistories.changer', 'satisfaction']);

        return response()->json($incident);
    }

    public function takeOver(Incident $incident)
    {
        $this->authorize('takeOver', $incident);

        if ($incident->technician_id) {
            return response()->json([
                'message' => 'Cet incident est déjà pris en charge.',
            ], 400);
        }

        if (!$incident->canTransitionTo('taken_over')) {
            return response()->json([
                'message' => 'Transition de statut non autorisée.',
            ], 400);
        }

        $incident->technician_id = Auth::id();
        $incident->transitionTo('taken_over', Auth::id());

        // Notification au client
        Notification::create([
            'user_id' => $incident->company->user_id,
            'type' => 'taken_over',
            'message' => "Votre incident '{$incident->title}' a été pris en charge par " . Auth::user()->name,
            'data' => ['incident_id' => $incident->id],
        ]);

        return response()->json($incident->load('technician'));
    }

    public function updateStatus(Request $request, Incident $incident)
    {
        $request->validate([
            'status' => 'required|string',
        ]);

        $this->authorize('updateStatus', $incident);

        $newStatus = $request->status;

        if (!$incident->canTransitionTo($newStatus)) {
            return response()->json([
                'message' => "Transition de statut non autorisée : {$incident->status} vers {$newStatus}",
            ], 400);
        }

        $incident->transitionTo($newStatus, Auth::id());

        return response()->json($incident);
    }

    public function getAvailableTransitions(Incident $incident)
    {
        $this->authorize('view', $incident);

        $allowedTransitions = [];
        $currentStatus = $incident->status;

        foreach (Incident::VALID_TRANSITIONS[$currentStatus] as $nextStatus) {
            $allowedTransitions[] = [
                'status' => $nextStatus,
                'label' => $this->getStatusLabel($nextStatus),
            ];
        }

        return response()->json($allowedTransitions);
    }

    private function getStatusLabel($status)
    {
        $labels = [
            'declared' => 'Déclaré',
            'analyzed' => 'Analysé',
            'taken_over' => 'Pris en charge',
            'in_progress' => 'En traitement',
            'resolved' => 'Résolu',
            'closed' => 'Clôturé',
        ];

        return $labels[$status] ?? $status;
    }
}