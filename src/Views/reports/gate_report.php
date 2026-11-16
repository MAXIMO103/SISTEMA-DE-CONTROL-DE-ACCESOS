<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h3 class="fw-bold mb-1" style="color: var(--coede-gold-light);">
      <i class="bi bi-door-open-fill"></i> Reporte Detallado de Control de Puerta Principal
    </h3>
    <p class="text-muted small mb-0">Auditoría cronológica de accesos peatonales y vehiculares &bull; COEDE</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= url('/reports') ?>" class="btn btn-outline-secondary btn-sm px-3">
      <i class="bi bi-arrow-left"></i> Volver al Dashboard
    </a>
    <a href="<?= url('/reports/gate/export-excel?' . http_build_query($filters)) ?>" class="btn btn-coede-gold btn-sm px-3 fw-bold">
      <i class="bi bi-file-earmark-excel-fill"></i> Descargar Excel
    </a>
  </div>
</div>

<!-- Filtros de Consulta -->
<div class="coede-card p-3 mb-4">
  <form method="GET" action="<?= url('/reports/gate') ?>" class="row g-2 align-items-end">
    
    <div class="col-6 col-md-2">
      <label class="form-label">Flujo</label>
      <select name="movement_type" class="form-select form-select-sm">
        <option value="TODOS" <?= $filters['movement_type'] === 'TODOS' ? 'selected' : '' ?>>Todos</option>
        <option value="ENTRADA" <?= $filters['movement_type'] === 'ENTRADA' ? 'selected' : '' ?>>Entradas</option>
        <option value="SALIDA" <?= $filters['movement_type'] === 'SALIDA' ? 'selected' : '' ?>>Salidas</option>
      </select>
    </div>

    <div class="col-6 col-md-2">
      <label class="form-label">Modalidad</label>
      <select name="target_type" class="form-select form-select-sm">
        <option value="TODOS" <?= $filters['target_type'] === 'TODOS' ? 'selected' : '' ?>>Todas</option>
        <option value="PEATONAL" <?= $filters['target_type'] === 'PEATONAL' ? 'selected' : '' ?>>Peatonal</option>
        <option value="VEHICULAR" <?= $filters['target_type'] === 'VEHICULAR' ? 'selected' : '' ?>>Vehicular</option>
      </select>
    </div>

    <div class="col-6 col-md-2">
      <label class="form-label">Condición</label>
      <select name="is_visitor" class="form-select form-select-sm">
        <option value="TODOS" <?= $filters['is_visitor'] === 'TODOS' ? 'selected' : '' ?>>Todos</option>
        <option value="0" <?= $filters['is_visitor'] === '0' ? 'selected' : '' ?>>Personal COEDE</option>
        <option value="1" <?= $filters['is_visitor'] === '1' ? 'selected' : '' ?>>Visitantes</option>
      </select>
    </div>

    <div class="col-6 col-md-2">
      <label class="form-label">DNI</label>
      <input type="text" name="dni" class="form-control form-control-sm font-monospace" placeholder="8 dígitos" value="<?= e($filters['dni']) ?>">
    </div>

    <div class="col-6 col-md-2">
      <label class="form-label">Desde</label>
      <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($filters['start_date']) ?>">
    </div>

    <div class="col-6 col-md-2">
      <label class="form-label">Hasta</label>
      <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($filters['end_date']) ?>">
    </div>

    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
      <a href="<?= url('/reports/gate') ?>" class="btn btn-outline-secondary btn-sm">Limpiar Filtros</a>
      <button type="submit" class="btn btn-coede-gold btn-sm px-4 fw-bold">
        <i class="bi bi-funnel-fill"></i> Aplicar Filtros
      </button>
    </div>

  </form>
</div>

<!-- Tabla Detallada -->
<div class="coede-card overflow-hidden">
  <div class="table-responsive">
    <table class="table coede-table align-middle mb-0">
      <thead>
        <tr>
          <th>Fecha / Hora</th>
          <th>Flujo</th>
          <th>Modalidad</th>
          <th>Condición</th>
          <th>DNI</th>
          <th>Personal / Conductor</th>
          <th>Dependencia</th>
          <th>Placa</th>
          <th>Destino</th>
          <th>Decisión</th>
          <th>Salida Declarada</th>
          <th>Operador</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($movements)): ?>
          <tr>
            <td colspan="12" class="text-center py-4 text-muted">
              No se registraron movimientos en el rango seleccionado.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($movements as $m): ?>
            <tr>
              <td class="small font-monospace"><?= e($m['recorded_at']) ?></td>
              <td>
                <span class="badge <?= $m['movement_type'] === 'ENTRADA' ? 'bg-success' : 'bg-primary' ?>">
                  <?= e($m['movement_type']) ?>
                </span>
              </td>
              <td class="small"><?= e($m['target_type']) ?></td>
              <td>
                <span class="badge <?= $m['is_visitor'] ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                  <?= $m['is_visitor'] ? 'Visita' : 'Personal' ?>
                </span>
              </td>
              <td class="font-monospace fw-bold"><?= e($m['dni']) ?></td>
              <td class="small fw-semibold text-white"><?= e($m['person_name']) ?></td>
              <td class="small text-muted"><?= e($m['unit_name'] ?? '-') ?></td>
              <td class="font-monospace text-warning"><?= e($m['plate'] ?? '-') ?></td>
              <td class="small text-muted"><?= e($m['destination'] ?? '-') ?></td>
              <td>
                <span class="badge <?= $m['operator_decision'] === 'AUTORIZADO' ? 'badge-vigente' : 'badge-vencido' ?>">
                  <?= e($m['operator_decision']) ?>
                </span>
              </td>
              <td>
                <?php if (!empty($m['declared_gate'])): ?>
                  <span class="badge bg-warning text-dark" title="<?= e($m['decl_obs']) ?>">
                    <?= e($m['declared_gate']) ?>
                  </span>
                <?php else: ?>
                  <span class="text-muted small">Constatada</span>
                <?php endif; ?>
              </td>
              <td class="small text-muted"><?= e($m['operator_name'] ?? $m['operator_username'] ?? 'Sistema') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

