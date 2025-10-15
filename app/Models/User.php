<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'created_by');
    }

    // metodos de verificacion de roles
    public function isAdmin(): bool
    {
        return $this->role->name === Role::ADMIN;
    }

    public function isCoordinator(): bool
    {
        return $this->role->name === Role::COORDINATOR;
    }

    public function isTechnician(): bool
    {
        return $this->role->name === Role::TECHNICIAN;
    }

    public function isSeller(): bool
    {
        return $this->role->name === Role::SELLER;
    }

    // metodos de autorizacion basados en roles
    public function canManageAllReservations(): bool
    {
        return $this->isAdmin() || $this->isCoordinator();
    }

    public function canChangeToScheduled(): bool
    {
        return $this->isAdmin() || $this->isCoordinator();
    }

    public function canChangeToInstalled(): bool
    {
        return $this->isAdmin() || $this->isCoordinator() || $this->isTechnician();
    }

    public function canChangeToUninstalled(): bool
    {
        return $this->isAdmin() || $this->isTechnician();
    }

    public function canChangeToCanceled(): bool
    {
        return $this->isAdmin() || $this->isCoordinator() || $this->isSeller();
    }
}
