<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Authorization;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Flash;
use App\Core\Pagination;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuditoriaService;

final class UsuarioController
{
    public function index(): void
    {
        Authorization::requireRoles(['Administrador']);

        $page = Pagination::getPageFromRequest();
        $perPage = 20;

        // Count total for pagination
        $countStmt = DB::pdo()->query('SELECT COUNT(*) FROM usuarios');
        $total = (int) $countStmt->fetchColumn();

        // Setup pagination
        $pagination = new Pagination($page, $perPage);
        $pagination->setTotal($total);

        // Fetch paginated results
        $stmt = DB::pdo()->prepare('SELECT id, nome, login, perfil, ativo, created_at FROM usuarios ORDER BY nome LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $pagination->perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $pagination->offset, \PDO::PARAM_INT);
        $stmt->execute();
        $usuarios = $stmt->fetchAll();

        View::render('usuarios/index', [
            'pageTitle' => 'Usuários',
            'usuarios' => $usuarios,
            'pagination' => $pagination,
        ]);
    }

    public function create(): void
    {
        Authorization::requireRoles(['Administrador']);

        View::render('usuarios/form', [
            'pageTitle' => 'Novo Usuário',
            'usuario' => [],
        ]);
    }

    public function store(): void
    {
        Authorization::requireRoles(['Administrador']);

        if (!Csrf::validate((string) Request::input('_csrf'))) {
            http_response_code(419);
            echo 'CSRF';
            return;
        }

        $nome = trim((string) Request::input('nome', ''));
        $login = trim((string) Request::input('login', ''));
        $senha = (string) Request::input('senha', '');
        $perfil = (string) Request::input('perfil', 'Atendente');

        if ($nome === '' || $login === '' || $senha === '' || strlen($senha) < 6) {
            Flash::add('error', 'Preencha todos os campos. Senha mínimo 6 caracteres.');
            Response::redirect('/usuarios/create');
        }

        // Verificar login duplicado
        $stmt = DB::pdo()->prepare('SELECT id FROM usuarios WHERE login = :login');
        $stmt->execute(['login' => $login]);
        if ($stmt->fetch()) {
            Flash::add('error', 'Login já existe.');
            Response::redirect('/usuarios/create');
        }

        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = DB::pdo()->prepare(
            'INSERT INTO usuarios (nome, login, senha, perfil, ativo) VALUES (:nome, :login, :senha, :perfil, 1)'
        );
        $stmt->execute([
            'nome' => $nome,
            'login' => $login,
            'senha' => $senhaHash,
            'perfil' => $perfil,
        ]);

        $id = (int) DB::pdo()->lastInsertId();

        AuditoriaService::log(Auth::user(), 'usuario_criado', 'usuarios', $id, null, [
            'nome' => $nome,
            'login' => $login,
            'perfil' => $perfil,
        ]);

        Flash::add('success', 'Usuário criado com sucesso.');
        Response::redirect('/usuarios');
    }

    public function edit(): void
    {
        Authorization::requireRoles(['Administrador']);

        $id = (int) Request::input('id', 0);

        $stmt = DB::pdo()->prepare('SELECT * FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            Flash::add('error', 'Usuário não encontrado.');
            Response::redirect('/usuarios');
        }

        View::render('usuarios/form', [
            'pageTitle' => 'Editar Usuário',
            'usuario' => $usuario,
        ]);
    }

    public function update(): void
    {
        Authorization::requireRoles(['Administrador']);

        if (!Csrf::validate((string) Request::input('_csrf'))) {
            http_response_code(419);
            echo 'CSRF';
            return;
        }

        $id = (int) Request::input('id', 0);
        $nome = trim((string) Request::input('nome', ''));
        $login = trim((string) Request::input('login', ''));
        $senha = (string) Request::input('senha', '');
        $perfil = (string) Request::input('perfil', 'Atendente');
        $ativo = (int) Request::input('ativo', 1);

        if ($nome === '' || $login === '') {
            Flash::add('error', 'Nome e login são obrigatórios.');
            Response::redirect('/usuarios/edit?id=' . $id);
        }

        // Verificar login duplicado
        $stmt = DB::pdo()->prepare('SELECT id FROM usuarios WHERE login = :login AND id != :id');
        $stmt->execute(['login' => $login, 'id' => $id]);
        if ($stmt->fetch()) {
            Flash::add('error', 'Login já existe.');
            Response::redirect('/usuarios/edit?id=' . $id);
        }

        $stmt = DB::pdo()->prepare('SELECT * FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $before = $stmt->fetch();

        if (!$before) {
            Flash::add('error', 'Usuário não encontrado.');
            Response::redirect('/usuarios');
        }

        // Não permitir desativar a si mesmo
        $user = Auth::user();
        if ($user && (int) $user['id'] === $id && $ativo === 0) {
            Flash::add('error', 'Você não pode desativar sua própria conta.');
            Response::redirect('/usuarios/edit?id=' . $id);
        }

        if ($senha !== '') {
            if (strlen($senha) < 6) {
                Flash::add('error', 'Senha deve ter pelo menos 6 caracteres.');
                Response::redirect('/usuarios/edit?id=' . $id);
            }
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = DB::pdo()->prepare(
                'UPDATE usuarios SET nome = :nome, login = :login, senha = :senha, perfil = :perfil, ativo = :ativo WHERE id = :id'
            );
            $stmt->execute([
                'nome' => $nome,
                'login' => $login,
                'senha' => $senhaHash,
                'perfil' => $perfil,
                'ativo' => $ativo,
                'id' => $id,
            ]);
        } else {
            $stmt = DB::pdo()->prepare(
                'UPDATE usuarios SET nome = :nome, login = :login, perfil = :perfil, ativo = :ativo WHERE id = :id'
            );
            $stmt->execute([
                'nome' => $nome,
                'login' => $login,
                'perfil' => $perfil,
                'ativo' => $ativo,
                'id' => $id,
            ]);
        }

        AuditoriaService::log(Auth::user(), 'usuario_atualizado', 'usuarios', $id, $before, [
            'nome' => $nome,
            'login' => $login,
            'perfil' => $perfil,
            'ativo' => $ativo,
        ]);

        Flash::add('success', 'Usuário atualizado.');
        Response::redirect('/usuarios');
    }
}
