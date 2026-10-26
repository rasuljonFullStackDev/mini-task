<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isExecutor(): bool
    {
        return $this->role === 'executor';
    }

    public function isChief(): bool
    {
        return $this->role === 'chief';
    }

    public function isAccountant(): bool
    {
        return $this->role === 'accountant';
    }

    /** Superadmin yoki boshliq user boshqaruviga kirishi mumkin */
    public function canManageUsers(): bool
    {
        return $this->isSuperAdmin() || $this->isChief();
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'executor_id');
    }
}
