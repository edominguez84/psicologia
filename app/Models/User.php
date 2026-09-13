<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'banned_at',
        'banned_reason',
        'two_factor_method',
        'phone_number',
        'birth_date',
        // two_factor_secret NUNCA por mass-assignment: se setea explícitamente
        // en el controlador de configuración de TOTP, tras confirmar el código.
        // avatar_path NUNCA por mass-assignment: se setea explícitamente en
        // PatientProfileController tras validar la imagen subida.
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
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
            'role' => UserRole::class,
            'banned_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'birth_date' => 'date',
        ];
    }

    public function isPatient(): bool
    {
        return $this->role === UserRole::Patient;
    }

    /**
     * A dónde debe ir este usuario justo después de autenticarse (login,
     * verificar 2FA, confirmar password, verificar email, login social):
     * el staff (admin/super_admin/editor) al panel, cualquier otra cuenta
     * (paciente, o el rol 'user' de compatibilidad) a su propio perfil.
     * Centralizado aquí para no repetir el criterio en cada controlador de
     * Auth/ — evita que una cuenta no-admin caiga en un 403 tras loguearse.
     */
    public function defaultRedirectRouteName(): string
    {
        return $this->isAdmin() || $this->role === UserRole::Editor
            ? 'admin.dashboard'
            : 'patient.profile.edit';
    }

    public function loginCodes(): HasMany
    {
        return $this->hasMany(LoginCode::class);
    }

    public function trustedDevices(): HasMany
    {
        return $this->hasMany(TrustedDevice::class);
    }

    public function oauthConnections(): HasMany
    {
        return $this->hasMany(OauthConnection::class);
    }

    public function testimonial(): HasOne
    {
        return $this->hasOne(Testimonial::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [UserRole::SuperAdmin, UserRole::Admin], true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    /**
     * Método de 2FA efectivo: la preferencia del usuario si ya confirmó TOTP,
     * o si eligió sms/whatsapp; si no hay nada confirmado, cae siempre a email.
     */
    public function effectiveTwoFactorMethod(): string
    {
        if ($this->two_factor_method === 'totp' && $this->two_factor_confirmed_at !== null) {
            return 'totp';
        }

        if (in_array($this->two_factor_method, ['sms', 'whatsapp'], true)) {
            return $this->two_factor_method;
        }

        return 'email';
    }
}
