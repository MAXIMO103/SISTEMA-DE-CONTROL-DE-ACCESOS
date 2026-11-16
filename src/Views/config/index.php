<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h3 class="fw-bold mb-0" style="color: var(--coede-gold-light);">
      <i class="bi bi-gear-fill"></i> Configuración del Sistema y Seguridad Institucional
    </h3>
  </div>
</div>

<div class="row g-4 mb-4">
  
  <!-- Tarjeta 1: Gestión de Cuentas y Accesos -->
  <div class="col-12 col-md-4">
    <div class="coede-card p-3 p-lg-4 h-100 d-flex flex-column justify-content-between border-top border-4 border-warning">
      <div>
        <div class="d-flex align-items-center justify-content-between mb-3">
          <i class="bi bi-people fs-2 text-warning"></i>
          <span class="badge bg-warning text-dark">Control RBAC</span>
        </div>
        <h5 class="fw-bold text-white mb-0">Usuarios y Permisos</h5>
      </div>
      <div class="mt-3 pt-2">
        <a href="<?= url('/config/users') ?>" class="btn btn-coede-gold btn-sm w-100 fw-bold">
          <i class="bi bi-person-gear"></i> Administrar Usuarios
        </a>
      </div>
    </div>
  </div>

  <!-- Tarjeta 2: Registro de Auditoría de Solo Lectura -->
  <div class="col-12 col-md-4">
    <div class="coede-card p-3 p-lg-4 h-100 d-flex flex-column justify-content-between border-top border-4 border-info">
      <div>
        <div class="d-flex align-items-center justify-content-between mb-3">
          <i class="bi bi-journal-check fs-2 text-info"></i>
          <span class="badge bg-info text-dark">Inmutable en App</span>
        </div>
        <h5 class="fw-bold text-white mb-0">Registro de Auditoría</h5>
      </div>
      <div class="mt-3 pt-2">
        <a href="<?= url('/config/audit') ?>" class="btn btn-coede-olive btn-sm w-100 fw-bold">
          <i class="bi bi-shield-lock"></i> Consultar Auditoría
        </a>
      </div>
    </div>
  </div>

  <!-- Tarjeta 3: Copias de Seguridad y Respaldos -->
  <div class="col-12 col-md-4">
    <div class="coede-card p-3 p-lg-4 h-100 d-flex flex-column justify-content-between border-top border-4 border-success">
      <div>
        <div class="d-flex align-items-center justify-content-between mb-3">
          <i class="bi bi-archive-fill fs-2 text-success"></i>
          <span class="badge bg-success">Retención 90 Días</span>
        </div>
        <h5 class="fw-bold text-white mb-0">Copias de Seguridad (Backup)</h5>
      </div>
      <div class="mt-3 pt-2">
        <a href="<?= url('/config/backups') ?>" class="btn btn-outline-light btn-sm w-100 fw-bold">
          <i class="bi bi-cloud-arrow-down-fill"></i> Gestionar Respaldos
        </a>
      </div>
    </div>
  </div>

</div>

<!-- Doctrina y Pensamiento de Inteligencia Militar -->
<div class="coede-card p-4 p-md-5 text-center border border-warning border-opacity-25" style="background: rgba(18, 26, 20, 0.75);">
  <div class="d-inline-flex align-items-center justify-content-center p-3 rounded-circle mb-3" style="background: rgba(212, 160, 23, 0.12); border: 1px solid rgba(212, 160, 23, 0.35);">
    <i class="bi bi-compass-fill fs-2" style="color: var(--coede-gold-light);"></i>
  </div>
  <h5 class="fw-bold letter-spacing-1 mb-3" style="color: var(--coede-gold-light);">
    <i class="bi bi-shield-fill-check me-2"></i>DOCTRINA Y PENSAMIENTO DE INTELIGENCIA
  </h5>
  <div class="mx-auto" style="max-width: 820px;">
    <blockquote class="blockquote mb-3">
      <p class="fs-5 fst-italic text-light mb-0" style="line-height: 1.7; font-family: 'Times New Roman', Georgia, serif;">
        &ldquo;La inteligencia oportuna y precisa no solo anticipa la amenaza, sino que ilumina la decisión del mando; en la vigilancia silenciosa y el control riguroso reside la seguridad de la fuerza.&rdquo;
      </p>
    </blockquote>
    <div class="d-flex align-items-center justify-content-center gap-2 text-warning text-opacity-75 small fw-semibold text-uppercase letter-spacing-1 mt-3">
      <i class="bi bi-award-fill"></i>
      <span>Seguridad &bull; Anticipación &bull; Control Institucional</span>
    </div>
  </div>
</div>
