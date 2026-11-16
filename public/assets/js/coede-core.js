/**
 * SISTEMA WEB COEDE - Funciones Principales de Cliente
 * Polling de Notificaciones, Inactividad de 15 Minutos y Control de Sesión
 */

function getAppUrl(path) {
  if (window.COEDE && typeof window.COEDE.url === 'function') {
    return window.COEDE.url(path);
  }
  const metaBase = document.querySelector('meta[name="base-url"]')?.getAttribute('content');
  const base = (metaBase !== null && metaBase !== undefined) ? metaBase.replace(/\/+$/, '') : '';
  const cleanPath = String(path || '').replace(/^\/+/, '');
  return base ? `${base}/${cleanPath}` : `/${cleanPath}`;
}

document.addEventListener('DOMContentLoaded', () => {
  initSessionWatcher();
  initNotificationPolling();
});

/**
 * Control de inactividad de 15 minutos con aviso previo al minuto 14 (60s antes)
 */
function initSessionWatcher() {
  const modalEl = document.getElementById('timeoutWarningModal');
  if (!modalEl) return;

  const warningModal = new bootstrap.Modal(modalEl);
  const countdownEl = document.getElementById('countdownTimer');
  const btnExtend = document.getElementById('btnExtendSession');

  let warningShown = false;
  let countdownInterval = null;

  async function checkSession() {
    try {
      const res = await fetch(getAppUrl('api/session/check'));
      if (!res.ok) return;
      const data = await res.json();

      if (!data.authenticated) {
        window.location.href = getAppUrl('login');
        return;
      }

      const remaining = data.remaining_seconds;

      if (remaining <= 60 && !warningShown) {
        warningShown = true;
        warningModal.show();
        let secondsLeft = remaining;
        countdownEl.textContent = secondsLeft;

        countdownInterval = setInterval(() => {
          secondsLeft--;
          if (countdownEl) countdownEl.textContent = Math.max(0, secondsLeft);
          if (secondsLeft <= 0) {
            clearInterval(countdownInterval);
            window.location.href = getAppUrl('login');
          }
        }, 1000);
      }
    } catch (e) {
      console.warn('Error al verificar sesión:', e);
    }
  }

  // Comprobar cada 15 segundos
  setInterval(checkSession, 15000);

  // Botón para extender sesión
  btnExtend?.addEventListener('click', async () => {
    try {
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      const res = await fetch(getAppUrl('api/session/ping'), {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': token,
          'Content-Type': 'application/json'
        }
      });
      if (res.ok) {
        warningShown = false;
        clearInterval(countdownInterval);
        warningModal.hide();
      }
    } catch (e) {
      console.error('Error al extender sesión:', e);
    }
  });
}

/**
 * Polling razonable de la campana superior (cada 30 segundos)
 */
function initNotificationPolling() {
  const badge = document.getElementById('notifBadge');
  const list = document.getElementById('notifList');
  const totalLabel = document.getElementById('notifTotalLabel');
  if (!badge) return;

  async function poll() {
    try {
      const res = await fetch(getAppUrl('api/notifications/poll'));
      if (!res.ok) return;
      const data = await res.json();

      const count = data.unread_count || 0;
      if (count > 0) {
        badge.textContent = count;
        badge.classList.remove('d-none');
        if (totalLabel) totalLabel.textContent = `${count} nuevas`;
      } else {
        badge.classList.add('d-none');
        if (totalLabel) totalLabel.textContent = '0 nuevas';
      }

      if (list) {
        let html = '';
        if (data.notifications && data.notifications.length > 0) {
          data.notifications.forEach(n => {
            html += `
              <div class="p-2 border-bottom border-dark border-opacity-50">
                <div class="fw-bold text-white" style="font-size: 0.8rem;">${n.title}</div>
                <div class="text-muted" style="font-size: 0.75rem;">${n.message}</div>
                <div class="text-secondary" style="font-size: 0.65rem;">${n.created_at}</div>
              </div>
            `;
          });
        }

        if (data.open_incidents > 0) {
          html += `
            <div class="p-2 border-bottom border-dark bg-danger bg-opacity-10">
              <div class="fw-bold text-danger" style="font-size: 0.8rem;"><i class="bi bi-exclamation-triangle-fill"></i> ${data.open_incidents} Incidencias Pendientes</div>
              <div class="text-muted" style="font-size: 0.75rem;">Existen casos de guardia sin cerrar.</div>
            </div>
          `;
        }

        if (data.expiring_count > 0) {
          html += `
            <div class="p-2 border-bottom border-dark bg-warning bg-opacity-10">
              <div class="fw-bold text-warning" style="font-size: 0.8rem;"><i class="bi bi-clock-fill"></i> ${data.expiring_count} Autorizaciones por Vencer</div>
              <div class="text-muted" style="font-size: 0.75rem;">Personal con menos de 7 días de vigencia.</div>
            </div>
          `;
        }

        if (!html) {
          html = '<div class="text-center py-3 text-secondary">Sin alertas pendientes</div>';
        }

        list.innerHTML = html;
      }
    } catch (e) {
      console.warn('Error en polling de notificaciones:', e);
    }
  }

  // Polling cada 30 segundos
  poll();
  setInterval(poll, 30000);
}

