<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\SoftwareSolution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewDashboard', Incident::class);

        $user = $request->user();
        $startDate = $request->start_date ? now()->parse($request->start_date) : now()->subMonth();
        $endDate = $request->end_date ? now()->parse($request->end_date) : now();

        if ($user->role === 'admin') {
            $stats = [
                'total_incidents' => $this->getTotalIncidents($startDate, $endDate),
                'incidents_by_status' => $this->getIncidentsByStatus($startDate, $endDate),
                'incidents_by_priority' => $this->getIncidentsByPriority($startDate, $endDate),
                'incidents_by_category' => $this->getIncidentsByCategory($startDate, $endDate),
                'top_solutions' => $this->getTopSolutions($startDate, $endDate),
                'incidents_by_company' => $this->getIncidentsByCompany($startDate, $endDate),
                'technician_stats' => $this->getTechnicianStats($startDate, $endDate),
                'average_resolution_time' => $this->getAverageResolutionTime($startDate, $endDate),
                'satisfaction_stats' => $this->getSatisfactionStats($startDate, $endDate),
                'recurring_incidents' => $this->getRecurringIncidents($startDate, $endDate),
            ];
        } elseif ($user->role === 'technician') {
            $stats = [
                'total_incidents' => Incident::where('technician_id', $user->id)
                    ->whereBetween('created_at', [$startDate, $endDate])->count(),
                'incidents_by_status' => Incident::where('technician_id', $user->id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->select('status', DB::raw('count(*) as count'))->groupBy('status')->get(),
            ];
        } elseif ($user->company) {
            $companyId = $user->company->id;
            $stats = [
                'total_incidents' => Incident::where('company_id', $companyId)
                    ->whereBetween('created_at', [$startDate, $endDate])->count(),
                'incidents_by_status' => Incident::where('company_id', $companyId)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->select('status', DB::raw('count(*) as count'))->groupBy('status')->get(),
                'incidents_by_priority' => Incident::where('company_id', $companyId)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->select('priority', DB::raw('count(*) as count'))->groupBy('priority')->get(),
                'top_solutions' => SoftwareSolution::whereHas('incidents', function ($q) use ($companyId, $startDate, $endDate) {
                        $q->where('company_id', $companyId)->whereBetween('created_at', [$startDate, $endDate]);
                    })
                    ->withCount(['incidents' => function ($q) use ($companyId, $startDate, $endDate) {
                        $q->where('company_id', $companyId)->whereBetween('created_at', [$startDate, $endDate]);
                    }])
                    ->orderByDesc('incidents_count')
                    ->get()
                    ->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'incidents_count' => $s->incidents_count]),
                'satisfaction_stats' => DB::table('satisfactions')
                    ->join('incidents', 'satisfactions.incident_id', '=', 'incidents.id')
                    ->where('incidents.company_id', $companyId)
                    ->whereBetween('incidents.created_at', [$startDate, $endDate])
                    ->select(DB::raw('AVG(rating) as average_rating'), DB::raw('COUNT(*) as total_evaluations'))
                    ->first(),
            ];
        } else {
            $stats = [
                'total_incidents' => 0,
                'incidents_by_status' => [],
                'incidents_by_priority' => [],
            ];
        }

        return response()->json($stats);
    }

    private function getTotalIncidents($startDate, $endDate)
    {
        return Incident::whereBetween('created_at', [$startDate, $endDate])->count();
    }

    private function getIncidentsByStatus($startDate, $endDate)
    {
        return Incident::whereBetween('created_at', [$startDate, $endDate])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();
    }

    private function getIncidentsByPriority($startDate, $endDate)
    {
        return Incident::whereBetween('created_at', [$startDate, $endDate])
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->get();
    }

    private function getIncidentsByCategory($startDate, $endDate)
    {
        return Incident::whereBetween('created_at', [$startDate, $endDate])
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->get();
    }

    private function getTopSolutions($startDate, $endDate)
    {
        return SoftwareSolution::withCount(['incidents' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])
        ->orderByDesc('incidents_count')
        ->take(10)
        ->get();
    }

    private function getIncidentsByCompany($startDate, $endDate)
    {
        return Incident::with('company')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('company_id', DB::raw('count(*) as count'))
            ->groupBy('company_id')
            ->orderByDesc('count')
            ->take(10)
            ->get();
    }

    private function getTechnicianStats($startDate, $endDate)
    {
        return Incident::with('technician')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('technician_id')
            ->select('technician_id', DB::raw('count(*) as count'))
            ->groupBy('technician_id')
            ->orderByDesc('count')
            ->get();
    }

    private function getAverageResolutionTime($startDate, $endDate)
    {
        $avg = Incident::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('resolved_at')
            ->select(DB::raw("AVG(TIMESTAMPDIFF(SECOND, created_at, resolved_at)) as avg_seconds"))
            ->first();
        
        if ($avg && $avg->avg_seconds) {
            $avg->avg_hours = round($avg->avg_seconds / 3600, 2);
        }
        
        return $avg;
    }

    private function getSatisfactionStats($startDate, $endDate)
    {
        return DB::table('satisfactions')
            ->join('incidents', 'satisfactions.incident_id', '=', 'incidents.id')
            ->whereBetween('incidents.created_at', [$startDate, $endDate])
            ->select(
                DB::raw('AVG(rating) as average_rating'),
                DB::raw('COUNT(*) as total_evaluations')
            )
            ->first();
    }

    private function getRecurringIncidents($startDate, $endDate)
    {
        return Incident::whereBetween('created_at', [$startDate, $endDate])
            ->select('category', 'software_solution_id', DB::raw('count(*) as count'))
            ->groupBy('category', 'software_solution_id')
            ->having('count', '>', 1)
            ->orderByDesc('count')
            ->get();
    }
}