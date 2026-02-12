<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuditoriaService;
use App\Services\LoginThrottleService;

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            Response::redirect('/');
        }

        View::render('auth/login', [
            'pageTitle' => 'Login',
        ]);
    }

    public function login(): void
    {
        if (!Csrf::validate((string) Request::input('_csrf'))) {
            http_response_code(419);
            echo 'CSRF';
            return;
        }

        $login = trim((string) Request::input('login', ''));
        $senha = (string) Request::input('senha', '');

        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        if ($login !== '' && LoginThrottleService::isLocked($login, $ip)) {
            Flash::add('error', 'Muitas tentativas. Aguarde alguns minutos e tente novamente.');
            Response::redirect('/login');
        }

        if ($login === '' || $senha === '') {
            Flash::add('error', 'Informe usuário e senha.');
            Response::redirect('/login');
        }

        $stmt = DB::pdo()->prepare('SELECT id, nome, login, senha, perfil, ativo FROM usuarios WHERE login = :login LIMIT 1');
        $stmt->execute(['login' => $login]);
        $user = $stmt->fetch();

        if (!$user || (int) $user['ativo'] !== 1 || !password_verify($senha, (string) $user['senha'])) {
            if ($login !== '') {
                LoginThrottleService::registerFailure($login, $ip);
            }
            AuditoriaService::log(null, 'login_falha', 'usuarios', null, null, [
                'login' => $login,
                'ip' => $ip,
            ]);
            Flash::add('error', 'Login inválido.');
            Response::redirect('/login');
        }

        Auth::login($user);
        LoginThrottleService::clear($login, $ip);
        AuditoriaService::log(Auth::user(), 'login_sucesso', 'usuarios', (int) $user['id'], null, [
            'ip' => $ip,
        ]);
        
        // Verificar alertas de prazo
        $this->verificarAlertasPrazo();
        
        Flash::add('success', 'Login realizado com sucesso.');
        Response::redirect('/');
    }

    private function verificarAlertasPrazo(): void
    {
        $db = DB::pdo();
        $hoje = date('Y-m-d');
        $amanha = date('Y-m-d', strtotime('+1 day'));

        // OS atrasadas
        $stmt = $db->prepare("SELECT COUNT(*) FROM ordens_servico 
                             WHERE prazo_entrega < :hoje 
                             AND status NOT IN ('Entregue', 'Cancelado')");
        $stmt->execute(['hoje' => $hoje]);
        $atrasadas = (int) $stmt->fetchColumn();

        // OS para hoje
        $stmt = $db->prepare("SELECT COUNT(*) FROM ordens_servico 
                             WHERE prazo_entrega = :hoje 
                             AND status NOT IN ('Entregue', 'Cancelado')");
        $stmt->execute(['hoje' => $hoje]);
        $paraHoje = (int) $stmt->fetchColumn();

        // OS para amanhã
        $stmt = $db->prepare("SELECT COUNT(*) FROM ordens_servico 
                             WHERE prazo_entrega = :amanha 
                             AND status NOT IN ('Entregue', 'Cancelado')");
        $stmt->execute(['amanha' => $amanha]);
        $paraAmanha = (int) $stmt->fetchColumn();

        // Adicionar alertas
        if ($atrasadas > 0) {
            Flash::add('error', "🔴 ATENÇÃO: Você tem {$atrasadas} OS(s) ATRASADA(S)! Verifique o dashboard.");
        }
        if ($paraHoje > 0) {
            Flash::add('warning', "🟡 Hoje há {$paraHoje} OS(s) para ENTREGAR.");
        }
        if ($paraAmanha > 0) {
            Flash::add('info', "🔵 Amanhã há {$paraAmanha} OS(s) com prazo de entrega.");
        }
    }

    public function logout(): void
    {
        if (!Csrf::validate((string) Request::input('_csrf'))) {
            http_response_code(419);
            echo 'CSRF';
            return;
        }

        $user = Auth::user();
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        if ($user) {
            AuditoriaService::log($user, 'logout', 'usuarios', (int) $user['id'], null, [
                'ip' => $ip,
            ]);
        }
        Auth::logout();
        Flash::add('info', 'Você saiu do sistema.');
        Response::redirect('/login');
    }

    public function showRecuperarSenha(): void
    {
        if (Auth::check()) {
            Response::redirect('/');
        }

        $this->ensureRecuperacaoSenhaColumns();

        View::render('auth/recuperar_senha', [
            'pageTitle' => 'Recuperar Senha',
        ]);
    }

    public function recuperarSenha(): void
    {
        if (!Csrf::validate((string) Request::input('_csrf'))) {
            http_response_code(419);
            echo 'CSRF';
            return;
        }

        $this->ensureRecuperacaoSenhaColumns();

        $login = trim((string) Request::input('login', ''));

        if ($login === '') {
            Flash::add('error', 'Informe o login.');
            Response::redirect('/recuperar-senha');
        }

        $stmt = DB::pdo()->prepare('SELECT id, nome, login FROM usuarios WHERE login = :login AND ativo = 1 LIMIT 1');
        $stmt->execute(['login' => $login]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', time() + 3600);

            $stmt = DB::pdo()->prepare('UPDATE usuarios SET token_recuperacao = :token, token_expira_em = :expira WHERE id = :id');
            $stmt->execute(['token' => $token, 'expira' => $expira, 'id' => $user['id']]);

            AuditoriaService::log(null, 'recuperacao_senha_solicitada', 'usuarios', (int) $user['id'], null, [
                'login' => $login,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            // Em ambiente real, enviaria email. Aqui mostramos na tela para facilitar testes
            $baseUrl = (string) (\App\Core\App::config('base_url', '') ?? '');
            if ($baseUrl !== '') {
                $baseUrl = rtrim($baseUrl, '/');
                $link = $baseUrl . \App\Core\View::url('/nova-senha') . '?token=' . $token;
            } else {
                $https = (string) ($_SERVER['HTTPS'] ?? '');
                $isHttps = $https !== '' && strtolower($https) !== 'off';
                $scheme = $isHttps ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $link = $scheme . '://' . $host . \App\Core\View::url('/nova-senha') . '?token=' . $token;
            }
            Flash::add('success', 'Link de recuperação: ' . $link);
        } else {
            Flash::add('info', 'Se o login existir, instruções foram enviadas.');
        }

        Response::redirect('/login');
    }

    public function showNovaSenha(): void
    {
        if (Auth::check()) {
            Response::redirect('/');
        }

        $this->ensureRecuperacaoSenhaColumns();

        $token = trim((string) Request::input('token', ''));

        if ($token === '') {
            Flash::add('error', 'Token inválido.');
            Response::redirect('/login');
        }

        $stmt = DB::pdo()->prepare('SELECT id FROM usuarios WHERE token_recuperacao = :token AND token_expira_em > NOW() LIMIT 1');
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();

        if (!$user) {
            Flash::add('error', 'Token inválido ou expirado.');
            Response::redirect('/login');
        }

        View::render('auth/nova_senha', [
            'pageTitle' => 'Nova Senha',
            'token' => $token,
        ]);
    }

    public function novaSenha(): void
    {
        if (!Csrf::validate((string) Request::input('_csrf'))) {
            http_response_code(419);
            echo 'CSRF';
            return;
        }

        $this->ensureRecuperacaoSenhaColumns();

        $token = trim((string) Request::input('token', ''));
        $senha = (string) Request::input('senha', '');
        $senhaConfirmacao = (string) Request::input('senha_confirmacao', '');

        if ($token === '' || $senha === '' || strlen($senha) < 6) {
            Flash::add('error', 'Senha deve ter pelo menos 6 caracteres.');
            Response::redirect('/nova-senha?token=' . $token);
        }

        if ($senha !== $senhaConfirmacao) {
            Flash::add('error', 'Senhas não conferem.');
            Response::redirect('/nova-senha?token=' . $token);
        }

        $stmt = DB::pdo()->prepare('SELECT id FROM usuarios WHERE token_recuperacao = :token AND token_expira_em > NOW() LIMIT 1');
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();

        if (!$user) {
            Flash::add('error', 'Token inválido ou expirado.');
            Response::redirect('/login');
        }

        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = DB::pdo()->prepare('UPDATE usuarios SET senha = :senha, token_recuperacao = NULL, token_expira_em = NULL WHERE id = :id');
        $stmt->execute(['senha' => $senhaHash, 'id' => $user['id']]);

        AuditoriaService::log(null, 'senha_redefinida', 'usuarios', (int) $user['id'], null, [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        Flash::add('success', 'Senha alterada com sucesso. Faça login.');
        Response::redirect('/login');
    }

    private function ensureRecuperacaoSenhaColumns(): void
    {
        try {
            $stmt = DB::pdo()->query('DESCRIBE usuarios');
            $cols = $stmt ? $stmt->fetchAll() : [];
            $names = [];
            foreach ($cols as $c) {
                if (isset($c['Field'])) {
                    $names[(string) $c['Field']] = true;
                }
            }

            $alterParts = [];
            if (!isset($names['token_recuperacao'])) {
                $alterParts[] = 'ADD COLUMN token_recuperacao VARCHAR(255) NULL';
            }
            if (!isset($names['token_expira_em'])) {
                $alterParts[] = 'ADD COLUMN token_expira_em DATETIME NULL';
            }
            if ($alterParts) {
                DB::pdo()->exec('ALTER TABLE usuarios ' . implode(', ', $alterParts));
                DB::pdo()->exec('CREATE INDEX idx_usuarios_token ON usuarios (token_recuperacao)');
            }
        } catch (\Throwable $e) {
            // Se não conseguir migrar, deixa o fluxo seguir para falhar de forma explícita no SQL
        }
    }
}
