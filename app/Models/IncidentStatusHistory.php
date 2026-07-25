<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'incident_id',
        'old_status',
        'new_status',
        'changed_by',
    ];

    public function incident()
    {
        return $this->belongsTo(Incident::class);
    }

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}