    </main>
  </div>
</div>

<!-- Footer -->
<footer class="bg-light border-top mt-auto py-2">
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span class="text-muted small" style="font-size: 0.75rem;">
        <?php 
          $config = require __DIR__ . '/../../config.php';
          $yearStart = $config['app']['year_start'];
          $yearCurrent = $config['app']['year_current'];
          $yearRange = $yearStart === $yearCurrent ? $yearStart : "{$yearStart}–{$yearCurrent}";
          $version = $config['app']['version'];
          $company = $config['app']['company'];
        ?>
        <?= htmlspecialchars($company, ENT_QUOTES, 'UTF-8') ?> &copy; <?= $yearRange ?> - v<?= $version ?> - Todos os direitos reservados
      </span>
      <a href="https://wa.me/5569993882222" target="_blank" class="text-success" style="font-size: 1.25rem; text-decoration: none;" title="Contato WhatsApp">
        💬
      </a>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= \App\Core\View::url('/public/assets/js/toast.js') ?>"></script>
<script src="<?= \App\Core\View::url('/public/assets/js/app.js') ?>"></script>
<script>
(function() {
  const sidebar = document.getElementById('sidebar');
  const toggleBtn = document.getElementById('sidebar-toggle');
  const mainContent = document.getElementById('main-content');

  if (toggleBtn && sidebar) {
    // Restore sidebar state from localStorage
    const isHidden = localStorage.getItem('sidebar-hidden') === 'true';
    if (isHidden) {
      sidebar.classList.add('d-md-none');
      sidebar.classList.remove('d-md-block');
      if (mainContent) {
        mainContent.classList.remove('col-md');
        mainContent.classList.add('col-12');
      }
    }

    toggleBtn.addEventListener('click', function() {
      if (sidebar.classList.contains('d-md-none')) {
        // Mostrar sidebar
        sidebar.classList.remove('d-md-none');
        sidebar.classList.add('d-md-block');
        if (mainContent) {
          mainContent.classList.add('col-md');
          mainContent.classList.remove('col-12');
        }
        localStorage.setItem('sidebar-hidden', 'false');
      } else {
        // Esconder sidebar
        sidebar.classList.add('d-md-none');
        sidebar.classList.remove('d-md-block');
        if (mainContent) {
          mainContent.classList.remove('col-md');
          mainContent.classList.add('col-12');
        }
        localStorage.setItem('sidebar-hidden', 'true');
      }
    });
  }
})();
</script>
</body>
</html>
