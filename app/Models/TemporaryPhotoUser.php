<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemporaryPhotoUser extends Model
{
    protected $fillable = [
        'owner_responsible_user_id',
        'created_by_user_id',
        'name',
        'email',
        'is_shared',
        'is_active',
        'deactivated_at',
    ];

    protected $casts = [
        'is_shared' => 'boolean',
        'is_active' => 'boolean',
        'deactivated_at' => 'datetime',
    ];

    public function ownerResponsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_responsible_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function grants(): HasMany
    {
        return $this->hasMany(TemporaryPhotoAccessGrant::class);
    }

    public function authorizedResponsibles(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'temporary_photo_user_responsibles',
            'temporary_photo_user_id',
            'responsible_user_id',
        )->withPivot('assigned_by_user_id')->withTimestamps();
    }

    public function canBeManagedBy(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isResponsibleLevelOne()) {
            return false;
        }

        if ((int) $this->owner_responsible_user_id === (int) $user->id) {
            return true;
        }

        if (! $this->is_shared) {
            return false;
        }

        if ($this->relationLoaded('authorizedResponsibles')) {
            return $this->authorizedResponsibles->contains('id', $user->id);
        }

        return $this->authorizedResponsibles()->where('users.id', $user->id)->exists();
    }

    public function canBeEditedBy(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isResponsibleLevelOne()
            && (int) $this->owner_responsible_user_id === (int) $user->id;
    }

    public function stagingPhotos(): HasMany
    {
        return $this->hasMany(WeaponPhotoStaging::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('deactivated_at');
    }
}
