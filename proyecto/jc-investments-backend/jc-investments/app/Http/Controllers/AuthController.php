<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // ── LOGIN ──────────────────────────────────────────
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|min:6',
        ], [
            'email.required'    => 'El correo es obligatorio.',
            'email.email'       => 'Ingresa un correo válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min'      => 'La contraseña debe tener mínimo 6 caracteres.',
        ]);

        $credenciales = [
            'email'    => $request->email,
            'password' => $request->password,
        ];

        if (Auth::attempt($credenciales, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $usuario = Auth::user();

            if (!$usuario->activo) {
                Auth::logout();
                return back()->withErrors(['email' => 'Tu cuenta está desactivada. Contacta al administrador.']);
            }

            return redirect()->route($usuario->getDashboardRoute())
                             ->with('success', '¡Bienvenido de vuelta, ' . $usuario->nombres . '!');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Las credenciales no coinciden con nuestros registros.']);
    }

    // ── REGISTRO ───────────────────────────────────────
    public function showRegistro()
    {
        return view('auth.registro');
    }

    public function registro(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombres'          => 'required|string|max:100',
            'apellidos'        => 'required|string|max:100',
            'tipo_documento'   => 'required|in:CC,Pasaporte,CE',
            'numero_documento' => 'required|string|unique:usuarios,numero_documento',
            'email'            => 'required|email|unique:usuarios,email',
            'telefono'         => 'required|string|max:15',
            'ciudad'           => 'required|string|max:80',
            'password'         => 'required|min:8|confirmed',
            'terms'            => 'accepted',
        ], [
            'nombres.required'                 => 'El nombre es obligatorio.',
            'apellidos.required'               => 'El apellido es obligatorio.',
            'tipo_documento.required'          => 'Selecciona el tipo de documento.',
            'numero_documento.required'        => 'El número de documento es obligatorio.',
            'numero_documento.unique'          => 'Este número de documento ya está registrado.',
            'email.required'                   => 'El correo es obligatorio.',
            'email.unique'                     => 'Este correo ya está registrado.',
            'telefono.required'                => 'El teléfono es obligatorio.',
            'ciudad.required'                  => 'La ciudad es obligatoria.',
            'password.min'                     => 'La contraseña debe tener mínimo 8 caracteres.',
            'password.confirmed'               => 'Las contraseñas no coinciden.',
            'terms.accepted'                   => 'Debes aceptar los términos y condiciones.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $usuario = Usuario::create([
            'nombres'          => ucwords(strtolower($request->nombres)),
            'apellidos'        => ucwords(strtolower($request->apellidos)),
            'tipo_documento'   => $request->tipo_documento,
            'numero_documento' => $request->numero_documento,
            'email'            => strtolower($request->email),
            'telefono'         => $request->telefono,
            'ciudad'           => ucwords(strtolower($request->ciudad)),
            'password'         => Hash::make($request->password),
            'rol'              => 'cliente',
        ]);

        Auth::login($usuario);

        return redirect()->route('cliente.dashboard')
                         ->with('success', '¡Cuenta creada con éxito! Bienvenido a JC Investments.');
    }

    // ── RECUPERAR CONTRASEÑA ───────────────────────────
    public function showRecuperar()
    {
        return view('auth.recuperar');
    }

    public function recuperar(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:usuarios,email'], [
            'email.exists' => 'No encontramos una cuenta con ese correo.',
        ]);

        // En producción: enviar email con enlace de reset
        // Password::sendResetLink($request->only('email'));

        return back()->with('success', 'Te enviamos un correo con instrucciones para restablecer tu contraseña.');
    }

    // ── LOGOUT ─────────────────────────────────────────
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home')->with('success', 'Sesión cerrada correctamente.');
    }
}
