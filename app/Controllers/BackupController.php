<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Authorization;
use App\Core\DB;
use App\Core\Flash;
use App\Core\Response;
use App\Core\View;

final class BackupController
{
    private string $backupDir;

    public function __construct()
    {
        $this->backupDir = __DIR__ . '/../../backups/';
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    public function index(): void
    {
        Authorization::requireRoles(['Administrador']);

        \App\Core\Breadcrumb::reset();
        \App\Core\Breadcrumb::add('Dashboard', View::url('/'));
        \App\Core\Breadcrumb::add('Backup');

        $backups = $this->listBackups();

        View::render('backup/index', [
            'pageTitle' => 'Backup',
            'backups' => $backups,
        ]);
    }

    public function create(): void
    {
        Authorization::requireRoles(['Administrador']);

        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $this->backupDir . $filename;

        try {
            $this->generateBackup($filepath);
            Flash::add('success', 'Backup criado: ' . $filename);
        } catch (\Exception $e) {
            Flash::add('error', 'Erro ao criar backup: ' . $e->getMessage());
        }

        Response::redirect('/backup');
    }

    public function download(): void
    {
        Authorization::requireRoles(['Administrador']);

        $file = $_GET['file'] ?? '';
        $filepath = $this->backupDir . basename($file);

        if (!file_exists($filepath) || !is_readable($filepath)) {
            Flash::add('error', 'Arquivo não encontrado.');
            Response::redirect('/backup');
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }

    public function delete(): void
    {
        Authorization::requireRoles(['Administrador']);

        $file = $_POST['file'] ?? '';
        $filepath = $this->backupDir . basename($file);

        if (file_exists($filepath) && is_writable($filepath)) {
            unlink($filepath);
            Flash::add('success', 'Backup removido.');
        } else {
            Flash::add('error', 'Não foi possível remover o backup.');
        }

        Response::redirect('/backup');
    }

    private function listBackups(): array
    {
        $backups = [];
        $files = glob($this->backupDir . '*.sql');
        
        foreach ($files as $file) {
            $backups[] = [
                'name' => basename($file),
                'size' => $this->formatBytes(filesize($file)),
                'date' => date('d/m/Y H:i:s', filemtime($file)),
            ];
        }

        usort($backups, fn($a, $b) => strcmp($b['date'], $a['date']));
        return $backups;
    }

    private function generateBackup(string $filepath): void
    {
        $db = DB::pdo();
        $tables = [];

        // Get all tables
        $stmt = $db->query("SHOW TABLES");
        while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $output = "-- ShoesAZ Backup\n";
        $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $output .= "-- Database: shoesaz\n\n";
        $output .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($tables as $table) {
            // Structure
            $stmt = $db->query("SHOW CREATE TABLE `$table`");
            $row = $stmt->fetch();
            $output .= "\n-- Table: $table\n";
            $output .= "DROP TABLE IF EXISTS `$table`;\n";
            $output .= $row['Create Table'] . ";\n\n";

            // Data
            $stmt = $db->query("SELECT * FROM `$table`");
            $rows = $stmt->fetchAll();

            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                $output .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES\n";

                $values = [];
                foreach ($rows as $row) {
                    $rowValues = [];
                    foreach ($row as $value) {
                        $rowValues[] = $this->toSqlLiteral($db, $value);
                    }
                    $values[] = "(" . implode(', ', $rowValues) . ")";
                }
                $output .= implode(",\n", $values) . ";\n\n";
            }
        }

        $output .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        if (file_put_contents($filepath, $output) === false) {
            throw new \Exception('Failed to write backup file');
        }
    }

    private function toSqlLiteral(\PDO $db, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $db->quote((string) $value);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}
