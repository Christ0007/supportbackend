<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoftwareSolution extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'version',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function technicians()
    {
        return $this->belongsToMany(User::class, 'software_solution_user');
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_software_solution');
    }

    public function incidents()
    {
        return $this->hasMany(Incident::class);
    }
}