(function () {
  const STORAGE_KEY = 'sidebarCollapsed';

  function applySidebarState(collapsed) {
    const shell = document.querySelector('.dashboard-shell');
    const sidebar = document.querySelector('.sidebar');
    const toggle = document.getElementById('sidebarToggle');

    if (!shell || !sidebar || !toggle) {
      return;
    }

    shell.classList.toggle('sidebar-collapsed', collapsed);
    sidebar.classList.toggle('collapsed', collapsed);
    toggle.textContent = collapsed ? '⟩' : '⟨';
    toggle.setAttribute('aria-label', collapsed ? 'Show sidebar' : 'Hide sidebar');

    try {
      localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
    } catch (error) {
      console.warn('Sidebar state could not be saved:', error);
    }
  }

  function bindSwipeGesture() {
    const shell = document.querySelector('.dashboard-shell');
    const sidebar = document.querySelector('.sidebar');

    if (!shell || !sidebar) {
      return;
    }

    let touchStartX = 0;
    let touchStartY = 0;
    let touchEndX = 0;
    let touchEndY = 0;

    document.addEventListener('touchstart', function (event) {
      if (event.touches.length !== 1) {
        return;
      }

      const touch = event.touches[0];
      touchStartX = touch.clientX;
      touchStartY = touch.clientY;
      touchEndX = touchStartX;
      touchEndY = touchStartY;
    }, { passive: true });

    document.addEventListener('touchmove', function (event) {
      if (event.touches.length !== 1) {
        return;
      }

      const touch = event.touches[0];
      touchEndX = touch.clientX;
      touchEndY = touch.clientY;
    }, { passive: true });

    document.addEventListener('touchend', function () {
      const deltaX = touchEndX - touchStartX;
      const deltaY = Math.abs(touchEndY - touchStartY);
      const swipeThreshold = 60;

      if (deltaY > 50) {
        return;
      }

      if (Math.abs(deltaX) < swipeThreshold) {
        return;
      }

      const isCollapsed = shell.classList.contains('sidebar-collapsed');

      if (deltaX > 0 && touchStartX < 30 && isCollapsed) {
        applySidebarState(false);
      }

      if (deltaX < 0 && !isCollapsed) {
        applySidebarState(true);
      }
    }, { passive: true });
  }

  function initSidebar() {
    const shell = document.querySelector('.dashboard-shell');
    const sidebar = document.querySelector('.sidebar');
    const toggle = document.getElementById('sidebarToggle');

    if (!shell || !sidebar || !toggle) {
      return;
    }

    let collapsed = false;
    try {
      collapsed = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (error) {
      collapsed = false;
    }

    applySidebarState(collapsed);
    bindSwipeGesture();

    toggle.addEventListener('click', function () {
      const shouldCollapse = !shell.classList.contains('sidebar-collapsed');
      applySidebarState(shouldCollapse);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSidebar);
  } else {
    initSidebar();
  }
})();
