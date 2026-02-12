<?php

use App\Core\Csrf;

?>
<style>
.login-page {
  min-height: calc(100vh - 56px - 40px);
  display: flex;
  align-items: center;
  justify-content: center;
}
.login-card {
  border: none;
  border-radius: 16px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.1);
  overflow: hidden;
}
.login-card .card-header {
  background: linear-gradient(135deg, #008bcd 0%, #0069a8 100%);
  color: white;
  padding: 2rem;
  text-align: center;
}
.login-card .card-body {
  padding: 2rem;
}
.login-brand {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}
.login-subtitle {
  opacity: 0.9;
  font-size: 0.9rem;
}
.login-form .form-control {
  border-radius: 10px;
  padding: 0.75rem 1rem;
  border: 2px solid #e9ecef;
  transition: all 0.2s;
}
.login-form .form-control:focus {
  border-color: #008bcd;
  box-shadow: 0 0 0 0.2rem rgba(0, 139, 205, 0.15);
}
.login-btn {
  border-radius: 10px;
  padding: 0.875rem;
  font-weight: 600;
  font-size: 1rem;
  background: linear-gradient(135deg, #008bcd 0%, #0069a8 100%);
  border: none;
  transition: all 0.2s;
}
.login-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 139, 205, 0.3);
}
.login-footer-link {
  color: #6c757d;
  text-decoration: none;
  font-size: 0.875rem;
  transition: color 0.2s;
}
.login-footer-link:hover {
  color: #008bcd;
}
</style>

<div class="login-page">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
        <div class="card login-card">
          <div class="card-header">
            <div class="login-brand">👞 ShoesAZ</div>
            <div class="login-subtitle">Sistema de Gestão para Sapataria</div>
          </div>
          <div class="card-body">
            <form method="post" action="login" class="login-form vstack gap-3">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
              
              <div>
                <label class="form-label fw-medium">Usuário</label>
                <input class="form-control" name="login" autocomplete="username" placeholder="Digite seu usuário" required autofocus>
              </div>
              
              <div>
                <label class="form-label fw-medium">Senha</label>
                <input class="form-control" type="password" name="senha" autocomplete="current-password" placeholder="Digite sua senha" required>
              </div>
              
              <button class="btn btn-primary login-btn w-100" type="submit">
                Entrar no Sistema
              </button>
            </form>
            
            <div class="text-center mt-3">
              <a href="<?= \App\Core\View::url('/recuperar-senha') ?>" class="login-footer-link">
                Esqueceu sua senha?
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
