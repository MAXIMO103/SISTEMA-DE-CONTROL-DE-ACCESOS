<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h3 class="fw-bold mb-1" style="color: var(--coede-gold-light);">
      <i class="bi bi-clock-history"></i> Histórico de Consultas y Síntesis SID
    </h3>
    <p class="text-muted small mb-0">Repositorio centralizado de inteligencia de fuentes abiertas &bull; COEDE</p>
  </div>
  <a href="<?= url('/sid') ?>" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> Volver a Búsqueda Diaria
  </a>
</div>

<!-- Filtros de Histórico -->
<div class="coede-card p-3 mb-4">
  <form method="GET" action="<?= url('/sid/history') ?>" class="row g-2 align-items-end">
    
    <div class="col-12 col-md-3">
      <label class="form-label">Departamento</label>
      <select name="department" class="form-select form-select-sm">
        <option value="TODOS">Todos los Departamentos</option>
        <?php foreach ($departamentos as $d): ?>
          <option value="<?= e($d) ?>" <?= $department === $d ? 'selected' : '' ?>><?= e($d) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-12 col-md-3">
      <label class="form-label">Amenaza</label>
      <select name="threat" class="form-select form-select-sm">
        <option value="TODAS">Todas las Amenazas</option>
        <?php foreach ($amenazas as $thName => $thDesc): ?>
          <option value="<?= e($thName) ?>" <?= $threat === $thName ? 'selected' : '' ?>><?= e($thName) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-6 col-md-2">
      <label class="form-label">Desde</label>
      <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($startDate) ?>">
    </div>

    <div class="col-6 col-md-2">
      <label class="form-label">Hasta</label>
      <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($endDate) ?>">
    </div>

    <div class="col-12 col-md-2 d-flex gap-1">
      <button type="submit" class="btn btn-coede-gold btn-sm w-100" title="Filtrar">
        <i class="bi bi-funnel-fill"></i> Filtrar
      </button>
      <a href="<?= url('/sid/history') ?>" class="btn btn-outline-secondary btn-sm" title="Limpiar">
        <i class="bi bi-x-circle"></i>
      </a>
    </div>

  </form>
</div>

<!-- Tabla Histórica -->
<div class="coede-card overflow-hidden">
  <div class="table-responsive">
    <table class="table coede-table align-middle mb-0">
      <thead>
        <tr>
          <th>Fecha Hecho</th>
          <th>Departamento</th>
          <th>Amenaza</th>
          <th>Titular de la Síntesis</th>
          <th>Fuentes</th>
          <th>Fecha Registro</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($events)): ?>
          <tr>
            <td colspan="7" class="text-center py-4 text-muted">
              No se encontraron noticias en el periodo seleccionado.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($events as $e): ?>
            <tr>
              <td class="small font-monospace"><?= e($e['event_date']) ?></td>
              <td class="fw-bold text-info"><?= e($e['department']) ?></td>
              <td class="small text-muted"><?= e($e['threat_type']) ?></td>
              <td class="small fw-semibold text-white"><?= e($e['headline']) ?></td>
              <td>
                <span class="badge bg-secondary"><?= $e['sources_count'] ?> fuente(s)</span>
              </td>
              <td class="small font-monospace text-muted"><?= e($e['created_at']) ?></td>
              <td>
                <?php if ($e['is_hidden']): ?>
                  <span class="badge bg-danger">Oculta</span>
                <?php else: ?>
                  <span class="badge bg-success">Pública</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

