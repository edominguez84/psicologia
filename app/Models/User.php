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
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, LogsActivity;

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
        'sex',
        'department',
        'municipality',
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

    /**
     * Solo campos no sensibles y relevantes para auditoría (nunca password,
     * two_factor_secret, remember_token). logOnly() en vez de logAll() para
     * que un cambio futuro de columna no empiece a filtrarse al log sin que
     * alguien lo decida explícitamente aquí.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'role', 'banned_at', 'banned_reason'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
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
        return $this->isAdmin() ? 'admin.dashboard' : 'patient.profile.edit';
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

    /**
     * Incluye 'editor' a propósito: el enum UserRole ya documenta que editor
     * tiene el mismo acceso que admin ("hoy tiene el mismo acceso que admin
     * porque ninguna ruta distingue permisos más finos todavía"), y
     * defaultRedirectRouteName() ya compensaba la ausencia de editor aquí
     * con una condición aparte — este método es el único punto real que
     * decide si el middleware 'admin' deja pasar a alguien al panel, así
     * que dejar editor fuera de aquí bloqueaba con 403 cualquier pantalla
     * del panel para ese rol, contradiciendo lo ya documentado.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::Editor], true);
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
