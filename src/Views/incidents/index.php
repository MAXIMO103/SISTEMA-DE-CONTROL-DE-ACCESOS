<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h3 class="fw-bold mb-0" style="color: var(--coede-gold-light);">
      <i class="bi bi-shield-exclamation"></i> Bitácora de Incidencias y Novedades de Guardia
    </h3>
  </div>
  <div>
    <a href="<?= url('/incidents/create') ?>" class="btn btn-coede-gold btn-sm px-3 fw-bold">
      <i class="bi bi-plus-circle-fill"></i> Registrar Nueva Incidencia
    </a>
  </div>
</div>

<!-- Filtros de Incidencias -->
<div class="coede-card p-3 mb-4">
  <form method="GET" action="<?= url('/incidents') ?>" class="row g-2 align-items-end">
    <div class="col-12 col-md-5">
      <label class="form-label">Buscar por Código, Hecho o Referencia</label>
      <div class="input-group input-group-sm">
        <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-search"></i></span>
        <input type="text" name="q" class="form-control form-control-sm" placeholder="INC-2026-0001, DNI, placa, tipo de hecho..." value="<?= e($search) ?>">
      </div>
    </div>

    <div class="col-6 col-md-3">
      <label class="form-label">Estado de Caso</label>
      <select name="status" class="form-select form-select-sm">
        <option value="TODOS" <?= $status === 'TODOS' ? 'selected' : '' ?>>Todos los Estados</option>
        <option value="PENDIENTE" <?= $status === 'PENDIENTE' ? 'selected' : '' ?>>Pendientes (Prioridad)</option>
        <option value="EN_REVISION" <?= $status === 'EN_REVISION' ? 'selected' : '' ?>>En Revisión</option>
        <option value="CERRADA" <?= $status === 'CERRADA' ? 'selected' : '' ?>>Cerradas / Resueltas</option>
      </select>
    </div>

    <div class="col-6 col-md-2 d-flex gap-1">
      <button type="submit" class="btn btn-coede-gold btn-sm w-100" title="Filtrar">
        <i class="bi bi-funnel-fill"></i> Filtrar
      </button>
      <a href="<?= url('/incidents') ?>" class="btn btn-outline-secondary btn-sm" title="Limpiar">
        <i class="bi bi-x-circle"></i>
      </a>
    </div>
  </form>
</div>

<!-- Tabla de Incidencias -->
<div class="coede-card overflow-hidden">
  <div class="table-responsive">
    <table class="table coede-table mb-0 align-middle">
      <thead>
        <tr>
          <th>Código</th>
          <th>Fecha y Hora</th>
          <th>Tipo de Hecho</th>
          <th>Referencias</th>
          <th>Estado</th>
          <th>Evidencias</th>
          <th>Registrado Por</th>
          <th class="text-end">Expediente</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($incidents)): ?>
          <tr>
            <td colspan="8" class="text-center py-4 text-muted">
              <i class="bi bi-shield-check fs-3 d-block mb-2 text-success"></i>
              No se registran incidencias con los criterios seleccionados.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($incidents as $inc): ?>
            <tr>
              <!-- Código Consecutivo Anual -->
              <td class="font-monospace fw-bold fs-6" style="color: var(--coede-gold-light);">
                <?= e($inc['incident_code']) ?>
              </td>

              <!-- Fecha -->
              <td class="small font-monospace"><?= e($inc['created_at']) ?></td>

              <!-- Tipo de Hecho -->
              <td class="fw-semibold text-white">
                <?= e($inc['event_type']) ?>
              </td>

              <!-- Referencias DNI o Placa -->
              <td class="small">
                <?php if (!empty($inc['ref_dni'])): ?>
                  <span class="badge bg-dark border border-secondary text-info me-1">DNI <?= e($inc['ref_dni']) ?></span>
                <?php endif; ?>
                <?php if (!empty($inc['ref_plate'])): ?>
                  <span class="badge bg-dark border border-secondary text-warning">Placa <?= e($inc['ref_plate']) ?></span>
                <?php endif; ?>
                <?php if (empty($inc['ref_dni']) && empty($inc['ref_plate'])): ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>

              <!-- Estado -->
              <td>
                <?php if ($inc['status'] === 'PENDIENTE'): ?>
                  <span class="badge bg-danger">Pendiente</span>
                <?php elseif ($inc['status'] === 'EN_REVISION'): ?>
                  <span class="badge bg-warning text-dark">En Revisión</span>
                <?php else: ?>
                  <span class="badge bg-success">Cerrada / Resuelta</span>
                <?php endif; ?>
              </td>

              <!-- Evidencias -->
              <td>
                <?php if ((int)$inc['evidence_count'] > 0): ?>
                  <span class="badge bg-secondary">
                    <i class="bi bi-paperclip"></i> <?= $inc['evidence_count'] ?> adjunto(s)
                  </span>
                <?php else: ?>
                  <span class="text-muted small">Sin adjunto</span>
                <?php endif; ?>
              </td>

              <!-- Usuario Registrador -->
              <td class="small text-muted"><?= e($inc['creator_name']) ?></td>

              <!-- Acciones -->
              <td class="text-end">
                <a href="<?= url('/incidents/' . $inc['id']) ?>" class="btn btn-outline-warning btn-sm py-1 px-2">
                  <i class="bi bi-folder2-open"></i> Ver Expediente
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

