<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Authorization;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuditoriaService;
use App\Services\UploadService;

final class SapatoController
{
    public function store(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        if (!Csrf::validate((string) Request::input('_csrf'))) {
            http_response_code(419);
            echo 'CSRF';
            return;
        }

        $orcamentoId = (int) Request::input('orcamento_id', 0);
        $osId = (int) Request::input('os_id', 0);

        $categoria = trim((string) Request::input('categoria', ''));
        $tipoServico = trim((string) Request::input('tipo_servico', ''));
        $valor = (float) Request::input('valor', 0);

        if ($categoria === '' || $tipoServico === '' || $valor <= 0) {
            Flash::add('error', 'Preencha categoria, tipo de serviço e valor.');
            if ($orcamentoId > 0) {
                Response::redirect('/orcamentos/edit?id=' . $orcamentoId);
            } elseif ($osId > 0) {
                Response::redirect('/os/edit?id=' . $osId);
            } else {
                Response::redirect('/');
            }
        }

        $stmt = DB::pdo()->prepare(
            'INSERT INTO sapatos (orcamento_id, os_id, categoria, cor, modelo, tipo_servico, marca, valor, material, observacoes) 
             VALUES (:orcamento_id, :os_id, :categoria, :cor, :modelo, :tipo_servico, :marca, :valor, :material, :observacoes)'
        );
        $stmt->execute([
            'orcamento_id' => $orcamentoId > 0 ? $orcamentoId : null,
            'os_id' => $osId > 0 ? $osId : null,
            'categoria' => $categoria,
            'cor' => trim((string) Request::input('cor', '')),
            'modelo' => trim((string) Request::input('modelo', '')),
            'tipo_servico' => $tipoServico,
            'marca' => trim((string) Request::input('marca', '')),
            'valor' => $valor,
            'material' => trim((string) Request::input('material', '')),
            'observacoes' => trim((string) Request::input('observacoes', '')),
        ]);

        $id = (int) DB::pdo()->lastInsertId();

        // Processar fotos enviadas
        $fotos = [];
        if (isset($_FILES['fotos']) && is_array($_FILES['fotos']['tmp_name'])) {
            $fileCount = count($_FILES['fotos']['tmp_name']);
            for ($i = 0; $i < $fileCount && $i < 4; $i++) {
                if ($_FILES['fotos']['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'tmp_name' => $_FILES['fotos']['tmp_name'][$i],
                        'name' => $_FILES['fotos']['name'][$i],
                        'size' => $_FILES['fotos']['size'][$i],
                        'type' => $_FILES['fotos']['type'][$i],
                        'error' => $_FILES['fotos']['error'][$i]
                    ];
                    try {
                        $path = UploadService::uploadSapatoFoto($file, $id);
                        if ($path) {
                            $fotos[] = $path;
                        }
                    } catch (\Exception $e) {
                        // Log error but continue
                        error_log('Erro ao upload foto: ' . $e->getMessage());
                    }
                }
            }
        }

        // Salvar fotos no banco se houver
        if (!empty($fotos)) {
            $stmt = DB::pdo()->prepare('UPDATE sapatos SET fotos = :fotos WHERE id = :id');
            $stmt->execute(['fotos' => json_encode($fotos), 'id' => $id]);
        }

        AuditoriaService::log(Auth::user(), 'sapato_criado', 'sapatos', $id, null, [
            'orcamento_id' => $orcamentoId,
            'os_id' => $osId,
            'categoria' => $categoria,
            'tipo_servico' => $tipoServico,
            'valor' => $valor,
            'fotos_count' => count($fotos),
        ]);

        $this->recalcularOrcamento($orcamentoId);
        $this->recalcularOS($osId);

        Flash::add('success', 'Item adicionado.');

        if ($orcamentoId > 0) {
            Response::redirect('/orcamentos/edit?id=' . $orcamentoId);
        } elseif ($osId > 0) {
            Response::redirect('/os/edit?id=' . $osId);
        } else {
            Response::redirect('/');
        }
    }

    public function destroy(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        if (!Csrf::validate((string) Request::input('_csrf'))) {
            http_response_code(419);
            echo 'CSRF';
            return;
        }

        $id = (int) Request::input('id', 0);
        $orcamentoId = (int) Request::input('orcamento_id', 0);
        $osId = (int) Request::input('os_id', 0);

        $stmt = DB::pdo()->prepare('SELECT * FROM sapatos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $sapato = $stmt->fetch();

        if ($sapato) {
            $stmt = DB::pdo()->prepare('DELETE FROM sapatos WHERE id = :id');
            $stmt->execute(['id' => $id]);

            AuditoriaService::log(Auth::user(), 'sapato_removido', 'sapatos', $id, $sapato, null);

            $this->recalcularOrcamento((int) ($sapato['orcamento_id'] ?? 0));
            $this->recalcularOS((int) ($sapato['os_id'] ?? 0));
        }

        Flash::add('info', 'Item removido.');

        if ($orcamentoId > 0) {
            Response::redirect('/orcamentos/edit?id=' . $orcamentoId);
        } elseif ($osId > 0) {
            Response::redirect('/os/edit?id=' . $osId);
        } else {
            Response::redirect('/');
        }
    }

    public function uploadFoto(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        if (!Csrf::validate((string) Request::input('_csrf'))) {
            http_response_code(419);
            echo 'CSRF';
            return;
        }

        $sapatoId = (int) Request::input('sapato_id', 0);
        $orcamentoId = (int) Request::input('orcamento_id', 0);
        $osId = (int) Request::input('os_id', 0);

        $stmt = DB::pdo()->prepare('SELECT * FROM sapatos WHERE id = :id');
        $stmt->execute(['id' => $sapatoId]);
        $sapato = $stmt->fetch();

        if (!$sapato) {
            Flash::add('error', 'Sapato não encontrado.');
            Response::redirect('/');
        }

        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            Flash::add('error', 'Selecione uma foto para enviar.');
            if ($orcamentoId > 0) {
                Response::redirect('/orcamentos/edit?id=' . $orcamentoId);
            } elseif ($osId > 0) {
                Response::redirect('/os/edit?id=' . $osId);
            } else {
                Response::redirect('/');
            }
        }

        try {
            $path = UploadService::uploadSapatoFoto($_FILES['foto'], $sapatoId);

            $fotos = json_decode((string) ($sapato['fotos'] ?? '[]'), true) ?: [];
            $fotos[] = $path;

            $stmt = DB::pdo()->prepare('UPDATE sapatos SET fotos = :fotos WHERE id = :id');
            $stmt->execute(['fotos' => json_encode($fotos), 'id' => $sapatoId]);

            AuditoriaService::log(Auth::user(), 'sapato_foto_adicionada', 'sapatos', $sapatoId, null, ['path' => $path]);

            Flash::add('success', 'Foto adicionada.');
        } catch (\Exception $e) {
            Flash::add('error', 'Erro ao enviar foto: ' . $e->getMessage());
        }

        if ($orcamentoId > 0) {
            Response::redirect('/orcamentos/edit?id=' . $orcamentoId);
        } elseif ($osId > 0) {
            Response::redirect('/os/edit?id=' . $osId);
        } else {
            Response::redirect('/');
        }
    }

    public function removerFoto(): void
    {
        Authorization::requireRoles(['Administrador', 'Gerente', 'Atendente']);

        if (!Csrf::validate((string) Request::input('_csrf'))) {
            http_response_code(419);
            echo 'CSRF';
            return;
        }

        $sapatoId = (int) Request::input('sapato_id', 0);
        $fotoIndex = (int) Request::input('foto_index', 0);
        $orcamentoId = (int) Request::input('orcamento_id', 0);
        $osId = (int) Request::input('os_id', 0);

        $stmt = DB::pdo()->prepare('SELECT * FROM sapatos WHERE id = :id');
        $stmt->execute(['id' => $sapatoId]);
        $sapato = $stmt->fetch();

        if (!$sapato) {
            Flash::add('error', 'Sapato não encontrado.');
            Response::redirect('/');
        }

        $fotos = json_decode((string) ($sapato['fotos'] ?? '[]'), true) ?: [];

        if (isset($fotos[$fotoIndex])) {
            UploadService::removerFoto($fotos[$fotoIndex]);
            array_splice($fotos, $fotoIndex, 1);

            $stmt = DB::pdo()->prepare('UPDATE sapatos SET fotos = :fotos WHERE id = :id');
            $stmt->execute(['fotos' => json_encode($fotos), 'id' => $sapatoId]);

            Flash::add('success', 'Foto removida.');
        }

        if ($orcamentoId > 0) {
            Response::redirect('/orcamentos/edit?id=' . $orcamentoId);
        } elseif ($osId > 0) {
            Response::redirect('/os/edit?id=' . $osId);
        } else {
            Response::redirect('/');
        }
    }

    private function recalcularOrcamento(int $orcamentoId): void
    {
        if ($orcamentoId <= 0) {
            return;
        }

        $stmt = DB::pdo()->prepare('SELECT COALESCE(SUM(valor), 0) as total FROM sapatos WHERE orcamento_id = :id');
        $stmt->execute(['id' => $orcamentoId]);
        $total = (float) $stmt->fetch()['total'];

        $stmt = DB::pdo()->prepare('SELECT desconto FROM orcamentos WHERE id = :id');
        $stmt->execute(['id' => $orcamentoId]);
        $row = $stmt->fetch();
        $desconto = $row ? (float) $row['desconto'] : 0;

        $final = max(0, $total - $desconto);

        $stmt = DB::pdo()->prepare('UPDATE orcamentos SET valor_total = :total, valor_final = :final, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['total' => $total, 'final' => $final, 'id' => $orcamentoId]);
    }

    private function recalcularOS(int $osId): void
    {
        if ($osId <= 0) {
            return;
        }

        $stmt = DB::pdo()->prepare('SELECT COALESCE(SUM(valor), 0) as total FROM sapatos WHERE os_id = :id');
        $stmt->execute(['id' => $osId]);
        $total = (float) $stmt->fetch()['total'];

        $stmt = DB::pdo()->prepare('UPDATE ordens_servico SET valor_total = :total, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['total' => $total, 'id' => $osId]);
    }
}
