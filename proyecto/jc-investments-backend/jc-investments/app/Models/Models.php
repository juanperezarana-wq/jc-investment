<?php
// ══════════════════════════════════════════
// app/Models/TipoPrestamo.php
// ══════════════════════════════════════════
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TipoPrestamo extends Model
{
    protected $table = 'tipos_prestamo';
    protected $fillable = [
        'nombre','descripcion','tasa_mensual',
        'plazo_min_meses','plazo_max_meses','monto_min','monto_max','activo',
    ];
    protected $casts = ['activo' => 'boolean'];

    public function solicitudes() { return $this->hasMany(Solicitud::class); }
    public function scopeActivos($q) { return $q->where('activo', true); }
}

// ══════════════════════════════════════════
// app/Models/Solicitud.php
// ══════════════════════════════════════════
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    protected $fillable = [
        'codigo','usuario_id','tipo_prestamo_id','monto_solicitado',
        'plazo_meses','tasa_mensual','cuota_mensual','total_a_pagar',
        'proposito','ingresos_mensuales','ocupacion','estado',
        'comentario_analista','motivo_rechazo','revisado_por','fecha_revision',
        'documento_cedula','documento_ingresos',
    ];

    protected $casts = ['fecha_revision' => 'datetime'];

    // Relaciones
    public function usuario()      { return $this->belongsTo(Usuario::class, 'usuario_id'); }
    public function tipoPrestamo() { return $this->belongsTo(TipoPrestamo::class); }
    public function revisor()      { return $this->belongsTo(Usuario::class, 'revisado_por'); }
    public function pagos()        { return $this->hasMany(Pago::class); }

    // Helpers de estado
    public function isPendiente()   { return $this->estado === 'pendiente'; }
    public function isEnRevision()  { return $this->estado === 'en_revision'; }
    public function isAprobado()    { return $this->estado === 'aprobado'; }
    public function isRechazado()   { return $this->estado === 'rechazado'; }
    public function isCancelado()   { return $this->estado === 'cancelado'; }
    public function puedeCanelarse(){ return in_array($this->estado, ['pendiente','en_revision']); }

    public function getEstadoBadgeAttribute(): string
    {
        return match($this->estado) {
            'pendiente'   => '<span class="pill pill-pending">Pendiente</span>',
            'en_revision' => '<span class="pill pill-review">En revisión</span>',
            'aprobado'    => '<span class="pill pill-approved">Aprobado</span>',
            'rechazado'   => '<span class="pill pill-rejected">Rechazado</span>',
            'cancelado'   => '<span class="pill" style="background:#eee;color:#555">Cancelado</span>',
            default       => $this->estado,
        };
    }

    // Generar código único
    public static function generarCodigo(): string
    {
        $año = date('Y');
        $ultimo = self::whereYear('created_at', $año)->count() + 1;
        return 'SOL-' . $año . '-' . str_pad($ultimo, 4, '0', STR_PAD_LEFT);
    }

    // Calcular cuota mensual (sistema francés)
    public static function calcularCuota(float $monto, float $tasaMensual, int $plazo): float
    {
        $i = $tasaMensual / 100;
        if ($i == 0) return $monto / $plazo;
        return $monto * ($i * pow(1 + $i, $plazo)) / (pow(1 + $i, $plazo) - 1);
    }

    // Scopes
    public function scopePendientes($q)  { return $q->where('estado', 'pendiente'); }
    public function scopeAprobadas($q)   { return $q->where('estado', 'aprobado'); }
    public function scopeRechazadas($q)  { return $q->where('estado', 'rechazado'); }
}

// ══════════════════════════════════════════
// app/Models/Pago.php
// ══════════════════════════════════════════
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $fillable = [
        'solicitud_id','usuario_id','numero_cuota','monto','capital','interes',
        'fecha_vencimiento','fecha_pago','estado','metodo_pago','comprobante',
    ];
    protected $casts = [
        'fecha_vencimiento' => 'date',
        'fecha_pago'        => 'date',
    ];

    public function solicitud() { return $this->belongsTo(Solicitud::class); }
    public function usuario()   { return $this->belongsTo(Usuario::class); }

    public function isPagado()   { return $this->estado === 'pagado'; }
    public function isVencido()  { return $this->estado === 'vencido' || ($this->estado === 'pendiente' && $this->fecha_vencimiento->isPast()); }

    public function scopePendientes($q) { return $q->where('estado', 'pendiente'); }
    public function scopePagados($q)    { return $q->where('estado', 'pagado'); }
}

// ══════════════════════════════════════════
// app/Models/Notificacion.php
// ══════════════════════════════════════════
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'notificaciones';
    protected $fillable = ['usuario_id','titulo','mensaje','tipo','leida'];
    protected $casts    = ['leida' => 'boolean'];

    public function usuario() { return $this->belongsTo(Usuario::class); }

    public static function enviar(int $userId, string $titulo, string $mensaje, string $tipo = 'info'): void
    {
        self::create(['usuario_id'=>$userId,'titulo'=>$titulo,'mensaje'=>$mensaje,'tipo'=>$tipo]);
    }
}
