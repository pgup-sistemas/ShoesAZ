function showToast(message, type = 'info') {
  const colors = {
    success: '#28a745',
    error: '#dc3545',
    warning: '#ffc107',
    info: '#008bcd'
  };

  const container = document.getElementById('toast-container');
  if (!container) return;

  const wrapper = document.createElement('div');
  wrapper.innerHTML = `
    <div class="toast align-items-center text-white border-0" style="background-color: ${colors[type] || colors.info}" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fechar"></button>
      </div>
    </div>
  `.trim();

  const el = wrapper.firstElementChild;
  container.appendChild(el);

  const toast = new bootstrap.Toast(el, { delay: 5000 });
  toast.show();

  el.addEventListener('hidden.bs.toast', () => el.remove());
}
