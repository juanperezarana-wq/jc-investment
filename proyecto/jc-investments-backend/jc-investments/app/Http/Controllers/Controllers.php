<?php
// ══════════════════════════════════════════
// app/Http/Controllers/SolicitudController.php
// ══════════════════════════════════════════
namespace App\Http\Controllers;

use App\Models\Solicitud;
use App\Models\TipoPrestamo;
use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SolicitudController extends Controller
{
    // Listado de solicitudes del cliente
    public function index()
    {
        $solicitudes = Solicitud::where('usuario_id', Auth::id())
                                ->with('tipoPrestamo')
                                ->latest()
                                ->paginate(10);
        return view('cliente.solicitudes', compact('solicitudes'));
    }

    // Formulario nueva solicitud
    public function create()
    {
        $tipos = TipoPrestamo::activos()->get();
        return view('cliente.nueva-solicitud', compact('tipos'));
    }

    // Guardar nueva solicitud
    public function store(Request $request)
    {
        $request->validate([
            'tipo_prestamo_id'  => 'required|exists:tipos_prestamo,id',
            'monto_solicitado'  => 'required|numeric|min:100000|max:100000000',
            'plazo_meses'       => 'required|integer|min:1|max:60',
            'proposito'         => 'required|string|max:500',
            'ingresos_mensuales'=> 'required|numeric|min:0',
            'ocupacion'         => 'required|string|max:100',
            'documento_cedula'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'documento_ingresos'=> 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'monto_solicitado.min' => 'El monto mínimo es $100.000.',
            'tipo_prestamo_id.required' => 'Selecciona el tipo de préstamo.',
        ]);

        $tipo   = TipoPrestamo::findOrFail($request->tipo_prestamo_id);
        $cuota  = Solicitud::calcularCuota($request->monto_solicitado, $tipo->tasa_mensual, $request->plazo_meses);
        $total  = $cuota * $request->plazo_meses;

        // Subir documentos si existen
        $rutaCedula   = null;
        $rutaIngresos = null;
        if ($request->hasFile('documento_cedula')) {
            $rutaCedula = $request->file('documento_cedula')->store('documentos/cedulas', 'public');
        }
        if ($request->hasFile('documento_ingresos')) {
            $rutaIngresos = $request->file('documento_ingresos')->store('documentos/ingresos', 'public');
        }

        $solicitud = Solicitud::create([
            'codigo'             => Solicitud::generarCodigo(),
            'usuario_id'         => Auth::id(),
            'tipo_prestamo_id'   => $request->tipo_prestamo_id,
            'monto_solicitado'   => $request->monto_solicitado,
            'plazo_meses'        => $request->plazo_meses,
            'tasa_mensual'       => $tipo->tasa_mensual,
            'cuota_mensual'      => $cuota,
            'total_a_pagar'      => $total,
            'proposito'          => $request->proposito,
            'ingresos_mensuales' => $request->ingresos_mensuales,
            'ocupacion'          => $request->ocupacion,
            'documento_cedula'   => $rutaCedula,
            'documento_ingresos' => $rutaIngresos,
            'estado'             => 'pendiente',
        ]);

        // Notificar al cliente
        Notificacion::enviar(
            Auth::id(),
            'Solicitud enviada',
            "Tu solicitud #{$solicitud->codigo} fue recibida. La revisaremos pronto.",
            'info'
        );

        return redirect()->route('cliente.solicitudes')
                         ->with('success', "¡Solicitud #{$solicitud->codigo} enviada con éxito! Te notificaremos cuando sea revisada.");
    }

    // Ver detalle
    public function show($id)
    {
        $solicitud = Solicitud::where('usuario_id', Auth::id())
                              ->with(['tipoPrestamo','pagos'])
                              ->findOrFail($id);
        return view('cliente.solicitud-detalle', compact('solicitud'));
    }

    // Cancelar solicitud (solo si está pendiente o en revisión)
    public function cancelar($id)
    {
        $solicitud = Solicitud::where('usuario_id', Auth::id())->findOrFail($id);

        if (!$solicitud->puedeCanelarse()) {
            return back()->withErrors(['error' => 'Esta solicitud ya no puede cancelarse.']);
        }

        $solicitud->update(['estado' => 'cancelado']);

        return redirect()->route('cliente.solicitudes')
                         ->with('success', 'Solicitud cancelada correctamente.');
    }
}

// ══════════════════════════════════════════
// app/Http/Controllers/AdminController.php
// ══════════════════════════════════════════
namespace App\Http\Controllers;

use App\Models\{Solicitud, Usuario, Pago, TipoPrestamo, Notificacion};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AdminController extends Controller
{
    // Dashboard Admin
    public function dashboard()
    {
        $hoy = Carbon::today();
        $data = [
            'solicitudes_hoy'    => Solicitud::whereDate('created_at', $hoy)->count(),
            'solicitudes_pend'   => Solicitud::pendientes()->count(),
            'aprobadas_mes'      => Solicitud::aprobadas()->whereMonth('created_at', $hoy->month)->count(),
            'ingresos_mes'       => Pago::pagados()->whereMonth('fecha_pago', $hoy->month)->sum('monto'),
            'total_clientes'     => Usuario::clientes()->count(),
            'solicitudes_recien' => Solicitud::with(['usuario','tipoPrestamo'])->latest()->take(10)->get(),
        ];
        return view('admin.dashboard', $data);
    }

    // Listado de solicitudes (admin)
    public function solicitudes(Request $request)
    {
        $query = Solicitud::with(['usuario','tipoPrestamo'])->latest();

        if ($request->estado) $query->where('estado', $request->estado);
        if ($request->buscar) {
            $query->whereHas('usuario', fn($q) =>
                $q->where('nombres', 'like', "%{$request->buscar}%")
                  ->orWhere('apellidos', 'like', "%{$request->buscar}%")
                  ->orWhere('email', 'like', "%{$request->buscar}%")
            )->orWhere('codigo', 'like', "%{$request->buscar}%");
        }

        $solicitudes = $query->paginate(15);
        return view('admin.solicitudes', compact('solicitudes'));
    }

    // Ver detalle de solicitud
    public function verSolicitud($id)
    {
        $solicitud = Solicitud::with(['usuario','tipoPrestamo','pagos'])->findOrFail($id);
        return view('admin.solicitud-detalle', compact('solicitud'));
    }

    // Aprobar solicitud
    public function aprobar(Request $request, $id)
    {
        $request->validate(['comentario' => 'nullable|string|max:500']);

        $solicitud = Solicitud::findOrFail($id);
        $solicitud->update([
            'estado'               => 'aprobado',
            'comentario_analista'  => $request->comentario,
            'revisado_por'         => Auth::id(),
            'fecha_revision'       => now(),
        ]);

        // Generar tabla de pagos
        $this->generarCuotas($solicitud);

        // Notificar al cliente
        Notificacion::enviar(
            $solicitud->usuario_id,
            '¡Préstamo aprobado!',
            "Tu solicitud #{$solicitud->codigo} por \$" . number_format($solicitud->monto_solicitado, 0, ',', '.') . " fue aprobada.",
            'success'
        );

        return redirect()->route('admin.solicitudes')
                         ->with('success', "Solicitud #{$solicitud->codigo} aprobada.");
    }

    // Rechazar solicitud
    public function rechazar(Request $request, $id)
    {
        $request->validate(['motivo_rechazo' => 'required|string|max:500']);

        $solicitud = Solicitud::findOrFail($id);
        $solicitud->update([
            'estado'          => 'rechazado',
            'motivo_rechazo'  => $request->motivo_rechazo,
            'revisado_por'    => Auth::id(),
            'fecha_revision'  => now(),
        ]);

        Notificacion::enviar(
            $solicitud->usuario_id,
            'Solicitud rechazada',
            "Tu solicitud #{$solicitud->codigo} fue rechazada. Motivo: {$request->motivo_rechazo}",
            'error'
        );

        return redirect()->route('admin.solicitudes')
                         ->with('success', "Solicitud #{$solicitud->codigo} rechazada.");
    }

    // Generar cuotas al aprobar
    private function generarCuotas(Solicitud $solicitud): void
    {
        $i     = $solicitud->tasa_mensual / 100;
        $n     = $solicitud->plazo_meses;
        $P     = $solicitud->monto_solicitado;
        $cuota = $solicitud->cuota_mensual;
        $saldo = $P;
        $fecha = Carbon::now()->addMonth();

        for ($mes = 1; $mes <= $n; $mes++) {
            $interes  = $saldo * $i;
            $capital  = $cuota - $interes;
            $saldo   -= $capital;

            Pago::create([
                'solicitud_id'      => $solicitud->id,
                'usuario_id'        => $solicitud->usuario_id,
                'numero_cuota'      => $mes,
                'monto'             => round($cuota, 2),
                'capital'           => round($capital, 2),
                'interes'           => round($interes, 2),
                'fecha_vencimiento' => $fecha->copy(),
                'estado'            => 'pendiente',
            ]);
            $fecha->addMonth();
        }
    }

    // Gestión de usuarios
    public function usuarios(Request $request)
    {
        $query = Usuario::latest();
        if ($request->buscar) {
            $query->where('nombres','like',"%{$request->buscar}%")
                  ->orWhere('email','like',"%{$request->buscar}%");
        }
        $usuarios = $query->paginate(20);
        return view('admin.usuarios', compact('usuarios'));
    }

    public function verUsuario($id)
    {
        $usuario = Usuario::with(['solicitudes.tipoPrestamo','pagos'])->findOrFail($id);
        return view('admin.usuario-detalle', compact('usuario'));
    }

    public function eliminarUsuario($id)
    {
        $usuario = Usuario::findOrFail($id);
        $usuario->update(['activo' => false]);
        return redirect()->route('admin.usuarios')->with('success', 'Usuario desactivado.');
    }

    // Pagos (admin)
    public function pagos()
    {
        $pagos = Pago::with(['solicitud.usuario','solicitud.tipoPrestamo'])->latest()->paginate(20);
        return view('admin.pagos', compact('pagos'));
    }

    // Reportes
    public function reportes()
    {
        $data = [
            'aprobadas'     => Solicitud::aprobadas()->count(),
            'rechazadas'    => Solicitud::rechazadas()->count(),
            'pendientes'    => Solicitud::pendientes()->count(),
            'total_cartera' => Solicitud::aprobadas()->sum('monto_solicitado'),
            'total_cobrado' => Pago::pagados()->sum('monto'),
            'por_tipo'      => TipoPrestamo::withCount('solicitudes')->get(),
        ];
        return view('admin.reportes', $data);
    }

    // Configuración
    public function configuracion()
    {
        $tipos = TipoPrestamo::all();
        return view('admin.configuracion', compact('tipos'));
    }

    public function updateConfig(Request $request)
    {
        $request->validate([
            'tipos.*.nombre'       => 'required|string',
            'tipos.*.tasa_mensual' => 'required|numeric|min:0|max:100',
            'tipos.*.monto_min'    => 'required|numeric|min:0',
            'tipos.*.monto_max'    => 'required|numeric|min:0',
        ]);

        foreach ($request->tipos as $id => $datos) {
            TipoPrestamo::findOrFail($id)->update($datos);
        }

        return back()->with('success', 'Configuración guardada correctamente.');
    }

    // Dashboard Analista
    public function dashboardAnalista()
    {
        $solicitudes = Solicitud::with(['usuario','tipoPrestamo'])
                                ->whereIn('estado',['pendiente','en_revision'])
                                ->latest()->paginate(20);
        return view('analista.dashboard', compact('solicitudes'));
    }

    public function evaluar(Request $request, $id)
    {
        $request->validate([
            'accion'    => 'required|in:aprobar,rechazar,en_revision',
            'comentario'=> 'nullable|string|max:500',
        ]);

        $solicitud = Solicitud::findOrFail($id);
        $estado = match($request->accion) {
            'aprobar'     => 'aprobado',
            'rechazar'    => 'rechazado',
            'en_revision' => 'en_revision',
        };

        $solicitud->update([
            'estado'              => $estado,
            'comentario_analista' => $request->comentario,
            'revisado_por'        => Auth::id(),
            'fecha_revision'      => now(),
        ]);

        if ($estado === 'aprobado') $this->generarCuotas($solicitud);

        return back()->with('success', 'Solicitud actualizada.');
    }
}
