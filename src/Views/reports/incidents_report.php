<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h3 class="fw-bold mb-1" style="color: var(--coede-gold-light);">
      <i class="bi bi-shield-exclamation"></i> Reporte Consolidado de Incidencias y Novedades
    </h3>
    <p class="text-muted small mb-0">Estadísticas y archivo formal de casos registrados en Puerta Principal &bull; COEDE</p>
  </div>
  <a href="<?= url('/reports') ?>" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> Volver al Dashboard
  </a>
</div>

<!-- Filtros de Incidencias -->
<div class="coede-card p-3 mb-4">
  <form method="GET" action="<?= url('/reports/incidents') ?>" class="row g-2 align-items-end">
    
    <div class="col-6 col-md-3">
      <label class="form-label">Estado de Resolución</label>
      <select name="status" class="form-select form-select-sm">
        <option value="TODOS" <?= $filters['status'] === 'TODOS' ? 'selected' : '' ?>>Todos</option>
        <option value="PENDIENTE" <?= $filters['status'] === 'PENDIENTE' ? 'selected' : '' ?>>Pendiente</option>
        <option value="EN_REVISION" <?= $filters['status'] === 'EN_REVISION' ? 'selected' : '' ?>>En Revisión</option>
        <option value="CERRADA" <?= $filters['status'] === 'CERRADA' ? 'selected' : '' ?>>Cerrada / Resuelta</option>
      </select>
    </div>

    <div class="col-6 col-md-3">
      <label class="form-label">Tipo de Hecho</label>
      <input type="text" name="event_type" class="form-control form-control-sm" placeholder="Palabra clave..." value="<?= e($filters['event_type']) ?>">
    </div>

    <div class="col-6 col-md-2">
      <label class="form-label">Desde</label>
      <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($filters['start_date']) ?>">
    </div>

    <div class="col-6 col-md-2">
      <label class="form-label">Hasta</label>
      <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($filters['end_date']) ?>">
    </div>

    <div class="col-12 col-md-2 d-flex gap-1">
      <button type="submit" class="btn btn-coede-gold btn-sm w-100 fw-bold">
        <i class="bi bi-funnel-fill"></i> Filtrar
      </button>
      <a href="<?= url('/reports/incidents') ?>" class="btn btn-outline-secondary btn-sm" title="Limpiar">
        <i class="bi bi-x-circle"></i>
      </a>
    </div>

  </form>
</div>

<!-- Tabla Consolidada -->
<div class="coede-card overflow-hidden">
  <div class="table-responsive">
    <table class="table coede-table align-middle mb-0">
      <thead>
        <tr>
          <th>Código</th>
          <th>Fecha y Hora</th>
          <th>Tipo de Hecho</th>
          <th>Estado</th>
          <th>Medida Adoptada</th>
          <th>Registrado Por</th>
          <th>Resuelto Por</th>
          <th class="text-end">Expediente</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($incidents)): ?>
          <tr>
            <td colspan="8" class="text-center py-4 text-muted">
              No se encontraron incidencias en el rango seleccionado.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($incidents as $inc): ?>
            <tr>
              <td class="font-monospace fw-bold text-warning"><?= e($inc['incident_code']) ?></td>
              <td class="small font-monospace"><?= e($inc['created_at']) ?></td>
              <td class="fw-semibold text-white"><?= e($inc['event_type']) ?></td>
              <td>
                <?php if ($inc['status'] === 'PENDIENTE'): ?>
                  <span class="badge bg-danger">Pendiente</span>
                <?php elseif ($inc['status'] === 'EN_REVISION'): ?>
                  <span class="badge bg-warning text-dark">En Revisión</span>
                <?php else: ?>
                  <span class="badge bg-success">Cerrada</span>
                <?php endif; ?>
              </td>
              <td class="small text-muted"><?= e($inc['resolution_measure'] ?? 'Pendiente de resolución') ?></td>
              <td class="small text-muted"><?= e($inc['creator_name']) ?></td>
              <td class="small text-muted"><?= e($inc['closer_name'] ?? '-') ?></td>
              <td class="text-end">
                <a href="<?= url('/incidents/' . $inc['id']) ?>" class="btn btn-outline-light btn-sm py-1 px-2">
                  <i class="bi bi-eye"></i> Detalle
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

