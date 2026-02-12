(function () {
  const flashes = window.__FLASHES__ || [];
  flashes.forEach(f => {
    const typeMap = {
      success: 'success',
      error: 'error',
      warning: 'warning',
      info: 'info'
    };
    showToast(f.message, typeMap[f.type] || 'info');
  });
})();
