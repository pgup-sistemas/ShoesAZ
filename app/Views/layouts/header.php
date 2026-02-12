<?php

use App\Core\Auth;
use App\Core\App;
use App\Core\Csrf;

$pageTitle = $pageTitle ?? '';
$flashes = $flashes ?? [];
$user = Auth::user();

?><!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars(App::config('name', 'ShoesAZ') . ($pageTitle ? ' - ' . $pageTitle : ''), ENT_QUOTES, 'UTF-8') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= \App\Core\View::url('/public/assets/css/style.css') ?>">
  <link rel="stylesheet" href="<?= \App\Core\View::url('/public/assets/css/toast.css') ?>">
  <link rel="icon" type="image/x-icon" href="<?= \App\Core\View::url('/public/favicon.ico') ?>">
</head>
<body>
<nav class="navbar navbar-expand navbar-dark" style="background:#008bcd;">
  <div class="container-fluid">
    <?php if ($user): ?>
      <button class="btn btn-outline-light btn-sm d-none d-md-block me-2" id="sidebar-toggle" type="button" title="Recolher/Expandir menu">
        <span id="sidebar-icon">☰</span>
      </button>
      <button class="btn btn-outline-light btn-sm d-md-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobile-sidebar" aria-controls="mobile-sidebar">
        ☰
      </button>
    <?php endif; ?>
    <a class="navbar-brand" href="<?= \App\Core\View::url('/') ?>">ShoesAZ</a>
    
    <?php if ($user): ?>
      <form class="d-none d-md-flex mx-auto" style="max-width: 400px; width: 100%;" action="<?= \App\Core\View::url('/busca') ?>" method="get">
        <div class="input-group">
          <input type="search" class="form-control form-control-sm" name="q" placeholder="Buscar..." aria-label="Buscar" required>
          <button class="btn btn-light btn-sm" type="submit">🔍</button>
        </div>
      </form>
    <?php endif; ?>
    
    <div class="d-flex gap-2 align-items-center ms-auto">
      <?php if ($user): ?>
        <div class="d-flex align-items-center">
          <a href="<?= \App\Core\View::url('/ajuda') ?>" class="btn btn-outline-light btn-sm me-3" title="Guia de Ajuda">
            <i class="bi bi-question-circle"></i>
          </a>
          <span class="navbar-text me-3"><?= htmlspecialchars($user['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
          <form method="post" action="<?= \App\Core\View::url('/logout') ?>" class="d-inline">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
            <button class="btn btn-light btn-sm" type="submit">Sair</button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- Offcanvas Mobile Menu -->
<?php if ($user): ?>
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobile-sidebar" style="width: 280px;">
  <div class="offcanvas-header bg-light border-bottom">
    <h5 class="offcanvas-title">Menu</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-0">
    <?php require __DIR__ . '/sidebar.php'; ?>
  </div>
</div>
<?php endif; ?>

<div class="container-fluid">
  <div class="row flex-nowrap">
    <?php if ($user): ?>
      <aside class="col-md-2 d-none d-md-block bg-light border-end sidebar-container" id="sidebar" style="min-height: calc(100vh - 56px);">
        <?php require __DIR__ . '/sidebar.php'; ?>
      </aside>
      <main class="col-12 col-md p-3 main-content" id="main-content">
    <?php else: ?>
      <main class="col-12 p-3">
    <?php endif; ?>

      <div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080"></div>
      <script>
        window.__FLASHES__ = <?= json_encode($flashes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
      </script>
