<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;


class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'pengurus']);
    }

    public function kegiatan(): HasMany
    {
        return $this->hasMany(Kegiatan::class);
    }

    public function keuangan(): HasMany
    {
        return $this->hasMany(Keuangan::class);
    }

    public function surat(): HasMany
    {
        return $this->hasMany(Surat::class);
    }

    public function dokumentasi(): HasMany
    {
        return $this->hasMany(Dokumentasi::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(Log::class);
    }
}
