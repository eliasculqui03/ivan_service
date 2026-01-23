<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'username',
        'phone',
        'avatar_url',
        'language',
        'timezone',
        'status',
        'notifications_enabled',
        'marketing_consent',
        'last_login_at',
        'last_activity_at',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'password' => 'hashed',
            'notifications_enabled' => 'boolean',
            'marketing_consent' => 'boolean',
        ];
    }

    // JWT Methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
    public function medico()
    {
        return $this->hasOne(Medicos::class);
    }

    /**
     * Archivos subidos por el usuario
     */
    public function archivosSubidos()
    {
        return $this->hasMany(ArchivosAdjuntos::class, 'uploaded_by');
    }

    /**
     * Exámenes validados por el usuario
     */
    public function examenesValidados()
    {
        return $this->hasMany(ExamenesLaboratorio::class, 'validado_por');
    }

    /**
     * Estudios informados por el usuario
     */
    public function estudiosInformados()
    {
        return $this->hasMany(EstudiosMedicos::class, 'informado_por');
    }

    // ==================== MÉTODOS DE ROLES ====================

    /**
     * Verificar si el usuario tiene un rol específico
     */
    public function hasRole($roleName)
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Verificar si el usuario tiene alguno de los roles especificados
     */
    public function hasAnyRole(array $roles)
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Asignar un rol al usuario
     */
    public function assignRole($roleId)
    {
        if (!$this->roles()->where('role_id', $roleId)->exists()) {
            $this->roles()->attach($roleId);
        }
        return $this;
    }

    /**
     * Remover un rol del usuario
     */
    public function removeRole($roleId)
    {
        $this->roles()->detach($roleId);
        return $this;
    }

    /**
     * Sincronizar roles (reemplaza todos los roles existentes)
     */
    public function syncRoles(array $roleIds)
    {
        $this->roles()->sync($roleIds);
        return $this;
    }

    // ==================== SCOPES ====================

    /**
     * Scope para usuarios activos
     */
    public function scopeActivos($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope para usuarios por rol
     */
    public function scopePorRol($query, $roleName)
    {
        return $query->whereHas('roles', function ($q) use ($roleName) {
            $q->where('name', $roleName);
        });
    }

    /**
     * Scope para médicos
     */
    public function scopeMedicos($query)
    {
        return $query->whereHas('medico');
    }

    // ==================== ACCESSORS ====================

    /**
     * Verificar si el usuario es médico
     */
    public function getEsMedicoAttribute()
    {
        return $this->medico()->exists();
    }

    /**
     * Verificar si el usuario es administrador
     */
    public function getEsAdministradorAttribute()
    {
        return $this->hasRole('Administrador');
    }

    /**
     * Obtener nombres de roles
     */
    public function getNombresRolesAttribute()
    {
        return $this->roles->pluck('name')->toArray();
    }

    /**
     * Obtener primer rol
     */
    public function getRolPrincipalAttribute()
    {
        return $this->roles->first()?->name;
    }

    // ==================== MÉTODOS AUXILIARES ====================

    /**
     * Registrar última actividad
     */
    public function registrarActividad()
    {
        $this->last_activity_at = now();
        $this->save();
        return $this;
    }

    /**
     * Registrar último login
     */
    public function registrarLogin()
    {
        $this->last_login_at = now();
        $this->last_activity_at = now();
        $this->save();
        return $this;
    }

    /**
     * Verificar si está activo
     */
    public function estaActivo()
    {
        return $this->status === 1;
    }

    /**
     * Activar usuario
     */
    public function activar()
    {
        $this->status = 1;
        $this->save();
        return $this;
    }

    /**
     * Desactivar usuario
     */
    public function desactivar()
    {
        $this->status = 0;
        $this->save();
        return $this;
    }
}
