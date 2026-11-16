<div class="row mb-4">
  <div class="col-12">
    <div class="coede-card p-4">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
          <h3 class="fw-bold mb-0" style="color: var(--coede-gold-light);">
            <i class="bi bi-shield-check"></i> Bienvenido, <?= e($user['name']) ?>
          </h3>
        </div>
        <div class="text-md-end">
          <span class="badge bg-dark border border-secondary px-3 py-2 text-warning fs-6">
            <i class="bi bi-clock"></i> <span id="homeLiveClock"><?= date('d/m/Y • H:i:s') ?></span>
          </span>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  (function() {
    function updateHomeClock() {
      const el = document.getElementById('homeLiveClock');
      if (!el) return;
      const now = new Date();
      const formatter = new Intl.DateTimeFormat('es-PE', {
        timeZone: 'America/Lima',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
      });
      const parts = formatter.formatToParts(now);
      const p = {};
      parts.forEach(part => { p[part.type] = part.value; });
      el.textContent = `${p.day}/${p.month}/${p.year} • ${p.hour}:${p.minute}:${p.second}`;
    }
    updateHomeClock();
    setInterval(updateHomeClock, 1000);
  })();
</script>

<!-- Menú de Módulos Operativos -->
<div class="row g-3 g-md-4">
  
  <!-- 1. Control de Puerta -->
  <div class="col-12 col-md-6 col-xl-4">
    <div class="card coede-card h-100 shadow-sm border-top border-4 border-success">
      <div class="card-body p-3 p-lg-4 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="p-2 px-3 rounded-3" style="background-color: rgba(30, 126, 70, 0.2); color: #4ade80;">
              <i class="bi bi-door-open-fill fs-2"></i>
            </div>
            <span class="badge bg-success">Operativo</span>
          </div>
          <h5 class="card-title fw-bold text-white mb-3">Control de Puerta y Asistencia</h5>
        </div>
        <div>
          <a href="<?= url('/gate') ?>" class="btn btn-coede-gold w-100 py-2 fw-bold text-uppercase">
            <i class="bi bi-arrow-right-circle-fill"></i> Acceder a Puerta
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- 2. Registro de Incidencias -->
  <div class="col-12 col-md-6 col-xl-4">
    <div class="card coede-card h-100 shadow-sm border-top border-4 border-warning">
      <div class="card-body p-3 p-lg-4 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="p-2 px-3 rounded-3" style="background-color: rgba(202, 138, 4, 0.2); color: #facc15;">
              <i class="bi bi-shield-exclamation fs-2"></i>
            </div>
            <span class="badge bg-warning text-dark">Prioridad Alta</span>
          </div>
          <h5 class="card-title fw-bold text-white mb-3">Incidencias y Novedades</h5>
        </div>
        <div>
          <a href="<?= url('/incidents') ?>" class="btn btn-coede-olive w-100 py-2 fw-bold text-uppercase">
            <i class="bi bi-file-earmark-text-fill"></i> Gestionar Incidencias
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- 3. SID - Síntesis de Información Diaria -->
  <div class="col-12 col-md-6 col-xl-4">
    <div class="card coede-card h-100 shadow-sm border-top border-4 border-info">
      <div class="card-body p-3 p-lg-4 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="p-2 px-3 rounded-3" style="background-color: rgba(14, 165, 233, 0.2); color: #38bdf8;">
              <i class="bi bi-newspaper fs-2"></i>
            </div>
            <span class="badge bg-info text-dark">Información Pública</span>
          </div>
          <h5 class="card-title fw-bold text-white mb-3">SID — Síntesis Diaria</h5>
        </div>
        <div>
          <a href="<?= url('/sid') ?>" class="btn btn-coede-olive w-100 py-2 fw-bold text-uppercase">
            <i class="bi bi-search"></i> Consultar SID
          </a>
        </div>
      </div>
    </div>
  </div>

  <?php if ($isAdmin): ?>
  <!-- 4. Registro de Personal (Admin) -->
  <div class="col-12 col-md-6 col-xl-4">
    <div class="card coede-card h-100 shadow-sm border-top border-4 border-secondary">
      <div class="card-body p-3 p-lg-4 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="p-2 px-3 rounded-3" style="background-color: rgba(100, 116, 139, 0.2); color: #94a3b8;">
              <i class="bi bi-people-fill fs-2"></i>
            </div>
            <span class="badge bg-secondary">Administración</span>
          </div>
          <h5 class="card-title fw-bold text-white mb-3">Padrón de Personal</h5>
        </div>
        <div>
          <a href="<?= url('/personnel') ?>" class="btn btn-outline-light w-100 py-2 fw-bold text-uppercase">
            <i class="bi bi-person-lines-fill"></i> Padrón de Personal
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- 5. Registro de Vehículos (Admin) -->
  <div class="col-12 col-md-6 col-xl-4">
    <div class="card coede-card h-100 shadow-sm border-top border-4 border-secondary">
      <div class="card-body p-3 p-lg-4 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="p-2 px-3 rounded-3" style="background-color: rgba(100, 116, 139, 0.2); color: #94a3b8;">
              <i class="bi bi-truck-front-fill fs-2"></i>
            </div>
            <span class="badge bg-secondary">Administración</span>
          </div>
          <h5 class="card-title fw-bold text-white mb-3">Padrón de Vehículos</h5>
        </div>
        <div>
          <a href="<?= url('/vehicles') ?>" class="btn btn-outline-light w-100 py-2 fw-bold text-uppercase">
            <i class="bi bi-car-front"></i> Padrón Vehicular
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- 6. Reportes y Estadísticas (Admin) -->
  <div class="col-12 col-md-6 col-xl-4">
    <div class="card coede-card h-100 shadow-sm border-top border-4 border-primary">
      <div class="card-body p-3 p-lg-4 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="p-2 px-3 rounded-3" style="background-color: rgba(59, 130, 246, 0.2); color: #60a5fa;">
              <i class="bi bi-graph-up-arrow fs-2"></i>
            </div>
            <span class="badge bg-primary">Estadísticas</span>
          </div>
          <h5 class="card-title fw-bold text-white mb-3">Reportes y Dashboard</h5>
        </div>
        <div>
          <a href="<?= url('/reports') ?>" class="btn btn-outline-light w-100 py-2 fw-bold text-uppercase">
            <i class="bi bi-bar-chart-line-fill"></i> Ver Reportes
          </a>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>

