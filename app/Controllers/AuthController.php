<?php
namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\User;
use Throwable;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('dashboard');
        }
        $this->view('auth/login');
    }

    public function login(): void
    {
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            flash('error', 'La sesión del formulario expiró. Intenta nuevamente.');
            $this->redirect('login');
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $_SESSION['_old'] = ['username' => $username];

        if ($username === '' || $password === '') {
            flash('error', 'Completa usuario y contraseña.');
            $this->redirect('login');
        }

        try {
            $user = User::findByUsername($username);
        } catch (Throwable $e) {
            flash('error', config('app.debug')
                ? 'Error de conexión: ' . $e->getMessage()
                : 'No se pudo validar el acceso.');
            $this->redirect('login');
        }

        if (!$user || !(bool) $user['activo'] || !password_verify($password, $user['password_hash'])) {
            Audit::log('LOGIN_FALLIDO', 'usuarios', $user['id_usuario'] ?? null, null, ['username' => $username]);
            flash('error', 'Usuario o contraseña incorrectos.');
            $this->redirect('login');
        }

        Auth::login($user);
        User::touchLastLogin((int) $user['id_usuario']);
        Audit::log('LOGIN_EXITOSO', 'usuarios', (int) $user['id_usuario'], null, ['username' => $username], null, (int) $user['id_usuario']);
        unset($_SESSION['_old']);

        // Un colaborador vinculado entra directamente a su pantalla diaria de asistencia.
        if (Auth::hasRole('COLABORADOR') && Auth::collaboratorId() !== null) {
            $this->redirect('mi-asistencia');
        }
        $this->redirect('dashboard');
    }

    public function logout(): void
    {
        Auth::requireLogin();
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            flash('error', 'Solicitud inválida.');
            $this->redirect('dashboard');
        }

        $id = Auth::id();
        Audit::log('LOGOUT', 'usuarios', $id, null, null, null, $id);
        Auth::logout();
        flash('success', 'Sesión cerrada correctamente.');
        $this->redirect('login');
    }
}
