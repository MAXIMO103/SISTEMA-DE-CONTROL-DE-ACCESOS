<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'Sistema Web COEDE') ?></title>
  <link rel="stylesheet" href="<?= url('assets/css/bootstrap.min.css') ?>">
  <link rel="stylesheet" href="<?= url('assets/css/bootstrap-icons.css') ?>">
  <link rel="stylesheet" href="<?= url('assets/css/coede-theme.css') ?>">
  <meta name="csrf-token" content="<?= csrf_token() ?>">
  <meta name="base-url" content="<?= url('') ?>">
  <script>
    window.COEDE = window.COEDE || {};
    window.COEDE.baseUrl = "<?= rtrim(url(''), '/') ?>";
    window.COEDE.url = function(path) {
      var base = window.COEDE.baseUrl || '';
      var clean = String(path || '').replace(/^\/+/, '');
      return base ? base + '/' + clean : '/' + clean;
    };
  </script>
</head>
<body>

  <!-- Barra Superior Institucional -->
  <header class="coede-navbar">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <a href="<?= url('/') ?>" class="coede-brand">
        <div class="coede-shield-badge">
          <img src="<?= url('img/ESCUDO COEDE.jpeg') ?>" alt="Escudo COEDE" class="coede-shield-img">
        </div>
        <div class="coede-brand-text">
          <span class="coede-brand-title">COEDE</span>
          <span class="coede-brand-sub">Comando de Educación y Doctrina del Ejército</span>
        </div>
      </a>

      <!-- Menú de Navegación -->
      <nav class="d-none d-lg-flex align-items-center gap-1">
        <a href="<?= url('/') ?>" class="coede-nav-link <?= ($_SERVER['REQUEST_URI'] ?? '') === '/' ? 'active' : '' ?>">
          <i class="bi bi-grid-fill"></i> Portal
        </a>
        <a href="<?= url('/gate') ?>" class="coede-nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/gate') ? 'active' : '' ?>">
          <i class="bi bi-door-open-fill"></i> Control de Puerta
        </a>
        <a href="<?= url('/incidents') ?>" class="coede-nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/incidents') ? 'active' : '' ?>">
          <i class="bi bi-shield-exclamation"></i> Incidencias
        </a>
        <a href="<?= url('/sid') ?>" class="coede-nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/sid') ? 'active' : '' ?>">
          <i class="bi bi-newspaper"></i> SID / Noticias
        </a>

        <?php if (\App\Core\Auth::isAdmin()): ?>
        <a href="<?= url('/personnel') ?>" class="coede-nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/personnel') ? 'active' : '' ?>">
          <i class="bi bi-people-fill"></i> Personal
        </a>
        <a href="<?= url('/vehicles') ?>" class="coede-nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/vehicles') ? 'active' : '' ?>">
          <i class="bi bi-truck-front-fill"></i> Vehículos
        </a>
        <a href="<?= url('/reports') ?>" class="coede-nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/reports') ? 'active' : '' ?>">
          <i class="bi bi-graph-up-arrow"></i> Reportes
        </a>
        <a href="<?= url('/config') ?>" class="coede-nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/config') ? 'active' : '' ?>">
          <i class="bi bi-gear-fill"></i> Configuración
        </a>
        <?php endif; ?>
      </nav>

      <!-- Zona de Usuario, Notificaciones y Cierre de Sesión -->
      <div class="d-flex align-items-center gap-3">
        
        <!-- Campana Superior Administrativa con Polling -->
        <div class="dropdown">
          <button class="btn btn-coede-olive position-relative py-1 px-2" type="button" id="notifBell" data-bs-toggle="dropdown" aria-expanded="false" title="Centro de Alertas">
            <i class="bi bi-bell-fill"></i>
            <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">
              0
            </span>
          </button>
          <div class="dropdown-menu dropdown-menu-end shadow p-2" aria-labelledby="notifBell" style="width: 320px; background-color: var(--coede-surface-elevated); border: 1px solid var(--coede-border);">
            <div class="d-flex justify-content-between align-items-center px-2 py-1 border-bottom border-secondary mb-2">
              <span class="fw-bold text-warning small text-uppercase"><i class="bi bi-broadcast"></i> Alertas Institucionales</span>
              <span id="notifTotalLabel" class="badge bg-secondary">0 nuevas</span>
            </div>
            <div id="notifList" class="small text-muted" style="max-height: 260px; overflow-y: auto;">
              <div class="text-center py-3 text-secondary">Sin alertas pendientes</div>
            </div>
            <?php if (\App\Core\Auth::isAdmin()): ?>
            <div class="border-top border-secondary pt-2 mt-2 text-center">
              <a href="<?= url('/incidents') ?>" class="small text-warning text-decoration-none">Ver todas las incidencias &raquo;</a>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Perfil de Usuario -->
        <?php $currentUser = \App\Core\Auth::user(); ?>
        <?php if ($currentUser): ?>
        <div class="d-none d-md-flex flex-column text-end" style="line-height: 1.15;">
          <span class="text-white fw-bold small"><?= e($currentUser['name']) ?></span>
          <span class="badge <?= $currentUser['role_name'] === 'ADMINISTRADOR' ? 'bg-warning text-dark' : 'bg-success' ?> align-self-end mt-1" style="font-size: 0.65rem;">
            <?= e($currentUser['role_name']) ?>
          </span>
        </div>
        <a href="<?= url('/logout') ?>" class="btn btn-outline-danger btn-sm py-1 px-2" title="Cerrar Sesión Segura">
          <i class="bi bi-box-arrow-right"></i>
        </a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <!-- Mensajes Flash de Retroalimentación -->
  <div class="container-fluid px-4 mt-3">
    <?php if ($flashSuccess = \App\Core\Session::getFlash('success')): ?>
      <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <div><?= e($flashSuccess) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if ($flashError = \App\Core\Session::getFlash('error')): ?>
      <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div><?= e($flashError) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
  </div>

  <!-- Contenido Principal -->
  <main class="container-fluid px-4 py-3 flex-grow-1">
    <?= $content ?>
  </main>

  <!-- Modal de Advertencia de Inactividad (14 minutos) -->
  <div class="modal fade" id="timeoutWarningModal" tabindex="-1" aria-labelledby="timeoutModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content text-white">
        <div class="modal-header border-bottom border-warning">
          <h5 class="modal-title text-warning" id="timeoutModalLabel">
            <i class="bi bi-clock-history"></i> ADVERTENCIA DE SEGURIDAD
          </h5>
        </div>
        <div class="modal-body">
          <p class="mb-2">Su sesión en el Sistema Web COEDE está a punto de expirar por <strong>15 minutos de inactividad</strong>.</p>
          <div class="alert alert-dark text-center fw-bold fs-4 text-warning border-warning">
            Cierre automático en: <span id="countdownTimer">60</span> segundos
          </div>
          <p class="small text-muted mb-0">Haga clic en <strong>Continuar Sesión</strong> para mantener su trabajo activo.</p>
        </div>
        <div class="modal-footer border-top border-secondary">
          <a href="<?= url('/logout') ?>" class="btn btn-outline-danger">Cerrar Sesión Ahora</a>
          <button type="button" id="btnExtendSession" class="btn btn-coede-gold">Continuar Sesión</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Pie Institucional Fijo -->
  <footer class="mt-auto py-2 text-center text-secondary border-top border-dark" style="background-color: var(--coede-darker); font-size: 0.75rem;">
    <div class="container">
      COMANDO DE EDUCACIÓN Y DOCTRINA DEL EJÉRCITO (COEDE) &bull; SISTEMA DE GESTIÓN DE ACCESO, SID Y OPERACIONES &bull; VERSIÓN BETA 2026
    </div>
  </footer>

  <script src="<?= url('assets/js/bootstrap.bundle.min.js') ?>"></script>
  <script src="<?= url('assets/js/sweetalert2.all.min.js') ?>"></script>
  <script src="<?= url('assets/js/coede-core.js') ?>"></script>
</body>
</html>

