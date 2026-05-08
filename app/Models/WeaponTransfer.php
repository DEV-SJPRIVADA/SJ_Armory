<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeaponTransfer extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'weapon_id',
        'from_user_id',
        'to_user_id',
        'requested_by',
        'accepted_by',
        'from_client_id',
        'new_client_id',
        'status',
        'requested_at',
        'answered_at',
        'note',
        'ammo_count',
        'provider_count',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'answered_at' => 'datetime',
    ];

    public function weapon()
    {
        return $this->belongsTo(Weapon::class);
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function acceptedBy()
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function newClient()
    {
        return $this->belongsTo(Client::class, 'new_client_id');
    }

    public function fromClient()
    {
        return $this->belongsTo(Client::class, 'from_client_id');
    }
}

