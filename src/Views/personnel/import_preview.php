<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h3 class="fw-bold mb-1" style="color: var(--coede-gold-light);">
      <i class="bi bi-file-earmark-check-fill"></i> Vista Previa de Validación de Padrón Excel
    </h3>
    <p class="text-muted small mb-0">Revise las filas validadas, discrepancias críticas y ausentes antes de confirmar la importación.</p>
  </div>
  <a href="<?= url('/personnel/import') ?>" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> Cancelar / Cargar Otro
  </a>
</div>

<!-- Resumen Cuantitativo -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="coede-card p-3 text-center">
      <div class="fs-4 fw-bold text-white"><?= $preview['total_rows'] ?></div>
      <div class="small text-muted text-uppercase">Filas Procesadas</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="coede-card p-3 text-center border-success">
      <div class="fs-4 fw-bold text-success"><?= $preview['valid_rows'] ?></div>
      <div class="small text-muted text-uppercase">Filas Válidas</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="coede-card p-3 text-center border-warning">
      <div class="fs-4 fw-bold text-warning"><?= $preview['critical_rows'] ?></div>
      <div class="small text-muted text-uppercase">Cambios de Grado/Nombre</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="coede-card p-3 text-center border-danger">
      <div class="fs-4 fw-bold text-danger"><?= $preview['observed_rows'] ?></div>
      <div class="small text-muted text-uppercase">Filas Observadas</div>
    </div>
  </div>
</div>

<form action="<?= url('/personnel/import-confirm') ?>" method="POST">
  <?= csrf_field() ?>

  <!-- Tabla de Previsualización -->
  <div class="coede-card mb-4 overflow-hidden">
    <div class="coede-card-header">
      <h6 class="coede-card-title mb-0">Detalle de Registros por Fila</h6>
    </div>
    <div class="table-responsive">
      <table class="table coede-table align-middle">
        <thead>
          <tr>
            <th>Fila</th>
            <th>Acción Detectada</th>
            <th>DNI</th>
            <th>CIP / CIV</th>
            <th>Grado / Nombres</th>
            <th>Vigencia</th>
            <th>Observaciones y Validación</th>
            <th class="text-center">Aprobar Cambio</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($preview['items'] as $item): ?>
            <tr class="<?= $item['action_type'] === 'OBSERVADO' ? 'table-danger bg-opacity-10' : ($item['requires_approval'] ? 'table-warning bg-opacity-10' : '') ?>">
              <td><?= $item['row_number'] ?></td>
              <td>
                <?php if ($item['action_type'] === 'NUEVO'): ?>
                  <span class="badge bg-success">Nuevo Registro</span>
                <?php elseif ($item['action_type'] === 'RENOVACION'): ?>
                  <span class="badge bg-info text-dark">Renovación Vigencia</span>
                <?php elseif ($item['action_type'] === 'TRASLADO'): ?>
                  <span class="badge bg-primary">Traslado Dependencia</span>
                <?php elseif ($item['action_type'] === 'MODIFICACION_CRITICA'): ?>
                  <span class="badge bg-warning text-dark">Cambio Grado/Nombre</span>
                <?php elseif ($item['action_type'] === 'SIN_CAMBIOS'): ?>
                  <span class="badge bg-secondary">Sin Cambios</span>
                <?php else: ?>
                  <span class="badge bg-danger">Observado / Inválido</span>
                <?php endif; ?>
              </td>
              <td class="font-monospace fw-bold"><?= e($item['dni']) ?></td>
              <td><?= e($item['cip_civ'] ?? '-') ?></td>
              <td>
                <?= e($item['rank_grade'] ? $item['rank_grade'] . ' ' : '') ?>
                <?= e($item['surnames']) ?>, <?= e($item['names']) ?>
              </td>
              <td class="small">
                <?= e($item['auth_start']) ?> al <?= e($item['auth_end']) ?>
              </td>
              <td class="small">
                <?php if (!empty($item['observations'])): ?>
                  <ul class="mb-0 ps-3">
                    <?php foreach ($item['observations'] as $obs): ?>
                      <li class="<?= $item['action_type'] === 'OBSERVADO' ? 'text-danger' : 'text-warning' ?>">
                        <?= e($obs) ?>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php else: ?>
                  <span class="text-success"><i class="bi bi-check2"></i> Válido</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php if ($item['requires_approval']): ?>
                  <div class="form-check d-inline-block">
                    <input class="form-check-input" type="checkbox" name="approved_dnis[]" value="<?= e($item['dni']) ?>" id="chk_<?= $item['dni'] ?>">
                    <label class="form-check-label small text-warning" for="chk_<?= $item['dni'] ?>">Aprobar</label>
                  </div>
                <?php elseif ($item['action_type'] === 'OBSERVADO'): ?>
                  <span class="text-danger small"><i class="bi bi-x-circle"></i> Omitida</span>
                <?php else: ?>
                  <span class="text-success small"><i class="bi bi-check-all"></i> En Bloque</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Ausentes de la relación anterior -->
  <?php if (!empty($preview['absent_items'])): ?>
    <div class="coede-card mb-4 border-info">
      <div class="coede-card-header bg-dark">
        <h6 class="coede-card-title text-info mb-0">
          <i class="bi bi-person-dash-fill"></i> Personal Ausente de la Nueva Relación (<?= count($preview['absent_items']) ?>)
        </h6>
      </div>
      <div class="p-3">
        <div class="alert alert-dark text-muted small mb-3">
          <i class="bi bi-shield-lock"></i> <strong>Regla Institucional:</strong> Los registros no presentes en el archivo actual se conservan en su estado vigente y <strong>nunca se dan de baja automáticamente</strong>.
        </div>
        <ul class="list-group list-group-flush small">
          <?php foreach ($preview['absent_items'] as $ab): ?>
            <li class="list-group-item bg-transparent text-white border-secondary d-flex justify-content-between align-items-center">
              <span><?= e($ab['rank_grade']) ?> &bull; <?= e($ab['full_name']) ?> (DNI <?= e($ab['dni']) ?>)</span>
              <span class="badge bg-secondary"><?= e($ab['note']) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  <?php endif; ?>

  <div class="d-flex justify-content-end gap-3 mb-5">
    <a href="<?= url('/personnel/import') ?>" class="btn btn-outline-secondary">Descartar y Regresar</a>
    <button type="submit" class="btn btn-coede-gold px-4 py-2 fs-6 fw-bold">
      <i class="bi bi-check-circle-fill"></i> Confirmar y Aplicar Importación en Padrón
    </button>
  </div>
</form>

