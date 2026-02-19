<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Authorization;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuditoriaService;

final class ConfiguracaoController
{
    public function empresa(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente']);

        \App\Core\Breadcrumb::reset();
        \App\Core\Breadcrumb::add('Dashboard', View::url('/'));
        \App\Core\Breadcrumb::add('Configurações');

        $stmt = DB::pdo()->query('SELECT * FROM empresa LIMIT 1');
        $empresa = $stmt->fetch();

        $termosRecibo = '';
        $stmt = DB::pdo()->prepare('SELECT valor FROM configuracoes WHERE chave = :chave LIMIT 1');
        $stmt->execute(['chave' => 'termos_recibo']);
        $val = $stmt->fetchColumn();
        if (is_string($val)) {
            $termosRecibo = $val;
        }

        if (is_array($empresa)) {
            $empresa['termos_recibo'] = $termosRecibo;
        }

        View::render('configuracoes/empresa', [
            'pageTitle' => 'Dados da Empresa',
            'empresa' => $empresa,
        ]);
    }

    public function updateEmpresa(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente']);

        if (!Csrf::validate((string) Request::input('_csrf'))) {
            http_response_code(419);
            echo 'CSRF';
            return;
        }

        $nome = trim((string) Request::input('nome', ''));
        $cnpj = trim((string) Request::input('cnpj', ''));
        $endereco = trim((string) Request::input('endereco', ''));
        $telefone = trim((string) Request::input('telefone', ''));
        $email = trim((string) Request::input('email', ''));
        $logo_url = trim((string) Request::input('logo_url', ''));
        $primary_color = trim((string) Request::input('primary_color', '#008bcd'));
        $termos_recibo = trim((string) Request::input('termos_recibo', ''));

        if ($nome === '') {
            Flash::add('error', 'Nome da empresa é obrigatório.');
            Response::redirect('/configuracoes/empresa');
        }

        $stmt = DB::pdo()->prepare(
            'INSERT INTO empresa (id, nome, cnpj, endereco, telefone, email, logo_url, primary_color, termos_recibo)
             VALUES (1, :nome, :cnpj, :endereco, :telefone, :email, :logo_url, :primary_color, :termos_recibo)
             ON DUPLICATE KEY UPDATE
               nome = VALUES(nome),
               cnpj = VALUES(cnpj),
               endereco = VALUES(endereco),
               telefone = VALUES(telefone),
               email = VALUES(email),
               logo_url = VALUES(logo_url),
               primary_color = VALUES(primary_color),
               termos_recibo = VALUES(termos_recibo)'
        );
        $stmt->execute([
            'nome' => $nome,
            'cnpj' => $cnpj,
            'endereco' => $endereco,
            'telefone' => $telefone,
            'email' => $email,
            'logo_url' => $logo_url,
            'primary_color' => $primary_color,
            'termos_recibo' => $termos_recibo,
        ]);

        AuditoriaService::log(\App\Core\Auth::user(), 'empresa_atualizada', 'empresa', 1, null, ['nome' => $nome]);

        Flash::add('success', 'Dados da empresa atualizados.');
        Response::redirect('/configuracoes/empresa');
    }
}
