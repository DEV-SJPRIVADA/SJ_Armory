<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'color',
        'sort_order',
        'requires_modality',
        'requires_attachment',
        'requires_resolution_note',
        'default_status',
        'sla_hours',
        'blocks_operation',
        'persists_operational_block',
        'is_active',
        'is_reportable',
    ];

    protected $casts = [
        'requires_modality' => 'boolean',
        'requires_attachment' => 'boolean',
        'requires_resolution_note' => 'boolean',
        'sla_hours' => 'integer',
        'blocks_operation' => 'boolean',
        'persists_operational_block' => 'boolean',
        'is_active' => 'boolean',
        'is_reportable' => 'boolean',
    ];

    public function modalities()
    {
        return $this->hasMany(IncidentModality::class)->orderBy('sort_order')->orderBy('name');
    }

    public function incidents()
    {
        return $this->hasMany(WeaponIncident::class);
    }

    public function scopeOperationalBlocking(Builder $query): Builder
    {
        return $query->where('blocks_operation', true);
    }

    public function scopeReportable(Builder $query): Builder
    {
        return $query->where('is_reportable', true);
    }

    public function scopeNonReportable(Builder $query): Builder
    {
        return $query->where('is_reportable', false);
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }
}