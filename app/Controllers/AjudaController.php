<?php
namespace App\Controllers;

use App\Core\Authorization;
use App\Core\View;

class AjudaController
{
    public function index(): void
    {
        Authorization::requireLogin();
        
        $ajudaFile = dirname(__DIR__, 2) . '/GUIA_AJUDA.md';
        $conteudo = '';
        
        if (file_exists($ajudaFile)) {
            $conteudo = file_get_contents($ajudaFile);
        }
        
        View::render('ajuda/index', [
            'pageTitle' => 'Guia de Ajuda',
            'conteudo' => $conteudo
        ]);
    }
}
