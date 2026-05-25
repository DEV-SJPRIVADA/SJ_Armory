<?php

namespace App\Models;

use App\Support\PostCustodyRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'custody_role',
        'owner_responsible_user_id',
        'name',
        'address',
        'city',
        'department',
        'latitude',
        'longitude',
        'notes',
        'archived_at',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function ownerResponsible()
    {
        return $this->belongsTo(User::class, 'owner_responsible_user_id');
    }

    public function isCustodyPost(): bool
    {
        return filled($this->custody_role);
    }

    public function isNonOperationalCustody(): bool
    {
        return in_array($this->custody_role, PostCustodyRole::nonOperational(), true);
    }

    public function scopeSelectableForInternalAssignment(Builder $query): Builder
    {
        return $query->where(function (Builder $inner) {
            $inner->whereNull('custody_role')
                ->orWhere('custody_role', PostCustodyRole::ARMERO);
        });
    }

    public function scopeOperationalCustody(Builder $query): Builder
    {
        return $query->where(function (Builder $inner) {
            $inner->whereNull('custody_role')
                ->orWhere('custody_role', PostCustodyRole::ARMERILLO);
        });
    }

    public function scopeNonOperationalCustody(Builder $query): Builder
    {
        return $query->whereIn('custody_role', PostCustodyRole::nonOperational());
    }

    public function scopeForResponsibleCustody(Builder $query, int $responsibleUserId): Builder
    {
        return $query->where('owner_responsible_user_id', $responsibleUserId);
    }

    public function assignments()
    {
        return $this->hasMany(WeaponPostAssignment::class);
    }

    public function histories()
    {
        return $this->hasMany(PostHistory::class);
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }
}

