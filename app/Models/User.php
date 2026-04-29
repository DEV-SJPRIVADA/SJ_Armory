<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'must_change_password',
        'role',
        'position_id',
        'responsibility_level_id',
        'is_active',
        'cost_center',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'must_change_password' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function responsibilityLevel()
    {
        return $this->belongsTo(ResponsibilityLevel::class);
    }

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'user_clients')->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    public function isResponsible(): bool
    {
        return $this->role === 'RESPONSABLE';
    }

    public function isAuditor(): bool
    {
        return $this->role === 'AUDITOR';
    }

    public function responsibilityLevelNumber(): ?int
    {
        if ($this->responsibilityLevel) {
            return (int) $this->responsibilityLevel->level;
        }

        if (!$this->responsibility_level_id) {
            return null;
        }

        return (int) optional($this->responsibilityLevel()->first())->level;
    }

    public function isResponsibleLevelOne(): bool
    {
        return $this->isResponsible() && $this->responsibilityLevelNumber() === 1;
    }

    public function isResponsibleReadOnly(): bool
    {
        return $this->isResponsible() && $this->responsibilityLevelNumber() === 2;
    }

    public function isReadOnlyOperator(): bool
    {
        return $this->isAuditor() || $this->isResponsibleReadOnly();
    }
}

