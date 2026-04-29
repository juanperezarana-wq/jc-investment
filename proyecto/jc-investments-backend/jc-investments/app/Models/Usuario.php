<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'nombres', 'apellidos', 'tipo_documento', 'numero_documento',
        'email', 'telefono', 'ciudad', 'password', 'rol', 'activo',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'activo'            => 'boolean',
        'password'          => 'hashed',
    ];

    // ── ACCESSORS ─────────────────────────────────────
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }

    public function getInicialesAttribute(): string
    {
        return strtoupper(substr($this->nombres, 0, 1) . substr($this->apellidos, 0, 1));
    }

    // ── RELACIONES ────────────────────────────────────
    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'usuario_id');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'usuario_id');
    }

    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class, 'usuario_id');
    }

    public function notificacionesSinLeer()
    {
        return $this->notificaciones()->where('leida', false);
    }

    // ── HELPERS DE ROL ────────────────────────────────
    public function esAdmin(): bool    { return $this->rol === 'admin'; }
    public function esAnalista(): bool { return $this->rol === 'analista'; }
    public function esCliente(): bool  { return $this->rol === 'cliente'; }

    public function getDashboardRoute(): string
    {
        return match($this->rol) {
            'admin'    => 'admin.dashboard',
            'analista' => 'analista.dashboard',
            default    => 'cliente.dashboard',
        };
    }

    // ── SCOPES ────────────────────────────────────────
    public function scopeActivos($query)  { return $query->where('activo', true); }
    public function scopeClientes($query) { return $query->where('rol', 'cliente'); }
}
