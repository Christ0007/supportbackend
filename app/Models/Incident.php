<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Incident extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'priority',
        'category',
        'status',
        'software_solution_id',
        'company_id',
        'technician_id',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    const VALID_TRANSITIONS = [
    'declared' => ['analyzed', 'taken_over'],
    'analyzed' => ['taken_over'],
    'taken_over' => ['in_progress'],
    'in_progress' => ['resolved'],
    'resolved' => ['closed', 'in_progress'],  // closed = validé, in_progress = réouvert
    'closed' => [],
    ];

    const STATUS_LABELS = [
        'declared' => 'Déclaré',
        'analyzed' => 'Analysé',
        'taken_over' => 'Pris en charge',
        'in_progress' => 'En traitement',
        'resolved' => 'Résolu',
        'closed' => 'Clôturé',
    ];

    public function softwareSolution()
    {
        return $this->belongsTo(SoftwareSolution::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function interventions()
    {
        return $this->hasMany(Intervention::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function satisfaction()
    {
        return $this->hasOne(Satisfaction::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(IncidentStatusHistory::class);
    }

    public function canTransitionTo($newStatus)
    {
        return in_array($newStatus, self::VALID_TRANSITIONS[$this->status] ?? []);
    }

    public function transitionTo($newStatus, $userId)
    {
        if (!$this->canTransitionTo($newStatus)) {
            throw new \Exception("Transition invalide de {$this->status} vers {$newStatus}");
        }

        $oldStatus = $this->status;
        $this->status = $newStatus;

        if ($newStatus === 'resolved' || $newStatus === 'closed') {
            $this->resolved_at = now();
        }

        $this->save();

        // Enregistrer l'historique
        IncidentStatusHistory::create([
            'incident_id' => $this->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $userId,
        ]);

        // Créer une notification pour le client
        // Notifier la bonne personne selon qui a agi
        $notifyUserId = ($userId === $this->company->user_id)
            ? $this->technician_id          // le client a agi → prévenir le technicien
            : $this->company->user_id;      // le technicien/admin a agi → prévenir le client

        if ($notifyUserId) {
            $author = \App\Models\User::find($userId);
            $oldLabel = self::STATUS_LABELS[$oldStatus] ?? $oldStatus;
            $newLabel = self::STATUS_LABELS[$newStatus] ?? $newStatus;

            Notification::create([
                'user_id' => $notifyUserId,
                'type' => 'status_change',
                'message' => "L'incident '{$this->title}' est passé de \"{$oldLabel}\" à \"{$newLabel}\" par {$author?->name}",
                'data' => [
                    'incident_id' => $this->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'changed_by' => $userId,
                ],
            ]);
        }

        return $this;
    }
}