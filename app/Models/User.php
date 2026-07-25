<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->hasOne(Company::class);
    }

    public function supportedSolutions()
    {
        return $this->belongsToMany(SoftwareSolution::class, 'software_solution_user');
    }

    public function assignedIncidents()
    {
        return $this->hasMany(Incident::class, 'technician_id');
    }

    public function interventions()
    {
        return $this->hasMany(Intervention::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function satisfactions()
    {
        return $this->hasMany(Satisfaction::class);
    }

    public function hasRole($role)
    {
        return $this->role === $role;
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isTechnician()
    {
        return $this->role === 'technician';
    }

    public function isCompany()
    {
        return $this->role === 'company';
    }
}
