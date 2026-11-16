<div class="row g-4">
  
  <!-- Columna Izquierda: Datos del Expediente de Incidencia -->
  <div class="col-12 col-lg-7">
    <div class="coede-card p-4">
      
      <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-secondary">
        <div>
          <span class="small text-muted text-uppercase">Expediente Oficial de Guardia</span>
          <h4 class="fw-bold mb-0 font-monospace" style="color: var(--coede-gold-light);">
            <?= e($incident['incident_code']) ?>
          </h4>
        </div>
        <div>
          <?php if ($incident['status'] === 'PENDIENTE'): ?>
            <span class="badge bg-danger fs-6">Pendiente</span>
          <?php elseif ($incident['status'] === 'EN_REVISION'): ?>
            <span class="badge bg-warning text-dark fs-6">En Revisión</span>
          <?php else: ?>
            <span class="badge bg-success fs-6">Cerrada / Resuelta</span>
          <?php endif; ?>
        </div>
      </div>

      <div class="mb-3">
        <label class="text-muted small text-uppercase fw-bold">Tipo de Hecho / Clasificación</label>
        <div class="fs-5 fw-bold text-white"><?= e($incident['event_type']) ?></div>
      </div>

      <div class="row mb-3">
        <div class="col-6">
          <label class="text-muted small text-uppercase">Fecha y Hora de Registro</label>
          <div class="fw-semibold text-white"><?= e($incident['created_at']) ?></div>
        </div>
        <div class="col-6">
          <label class="text-muted small text-uppercase">Registrado Por</label>
          <div class="fw-semibold text-white"><?= e($incident['creator_name']) ?></div>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-6">
          <label class="text-muted small text-uppercase">DNI Referenciado</label>
          <div class="font-monospace text-white"><?= e($incident['ref_dni'] ?? 'Ninguno') ?></div>
        </div>
        <div class="col-6">
          <label class="text-muted small text-uppercase">Placa Referenciada</label>
          <div class="font-monospace text-warning"><?= e($incident['ref_plate'] ?? 'Ninguna') ?></div>
        </div>
      </div>

      <div class="mb-3">
        <label class="text-muted small text-uppercase fw-bold">Descripción del Acontecimiento</label>
        <div class="p-3 rounded bg-dark border border-secondary text-white small" style="white-space: pre-wrap;"><?= e($incident['description']) ?></div>
      </div>

      <div class="mb-4">
        <label class="text-muted small text-uppercase fw-bold">Acción Inmediata Adoptada en Guardia</label>
        <div class="p-3 rounded bg-dark border border-secondary text-white small" style="white-space: pre-wrap;"><?= e($incident['action_taken']) ?></div>
      </div>

      <!-- Resolución si está cerrada -->
      <?php if ($incident['status'] === 'CERRADA'): ?>
        <div class="p-3 rounded border border-success bg-success bg-opacity-10 mb-4">
          <div class="fw-bold text-success text-uppercase small mb-1">
            <i class="bi bi-check-circle-fill"></i> Resolución Administrativa de Cierre
          </div>
          <div class="text-white small mb-2">
            <strong>Medida Adoptada:</strong> <?= e($incident['resolution_measure']) ?>
          </div>
          <div class="text-white small mb-2">
            <strong>Explicación de Resolución:</strong> <?= e($incident['resolution_explanation']) ?>
          </div>
          <div class="text-muted small">
            Resuelto por <strong><?= e($incident['closer_name']) ?></strong> el <?= e($incident['closed_at']) ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Evidencias Adjuntas -->
      <h6 class="fw-bold text-warning border-top border-secondary pt-3 mb-3">
        <i class="bi bi-paperclip"></i> Evidencias Registradas (<?= count($evidences) ?>)
      </h6>

      <?php if (empty($evidences)): ?>
        <div class="text-muted small mb-3">No se adjuntaron evidencias físicas o fotográficas para esta incidencia.</div>
      <?php else: ?>
        <div class="row g-3 mb-4">
          <?php foreach ($evidences as $ev): ?>
            <div class="col-12 col-md-6">
              <div class="p-2 rounded border border-secondary bg-dark text-center">
                <?php if (str_starts_with($ev['mime_type'], 'image/')): ?>
                  <img src="<?= url('/media/evidence/' . $ev['id']) ?>" alt="Evidencia" class="img-fluid rounded mb-2" style="max-height: 180px;">
                <?php else: ?>
                  <div class="py-4 text-warning"><i class="bi bi-file-earmark-pdf fs-1"></i></div>
                <?php endif; ?>
                <div class="small text-truncate text-white fw-semibold"><?= e($ev['original_name']) ?></div>
                <div class="text-muted" style="font-size: 0.65rem;">
                  Captura: <?= e($ev['captured_via']) ?> &bull; <?= round($ev['file_size'] / 1024, 1) ?> KB
                </div>
                <div class="mt-2">
                  <a href="<?= url('/media/evidence/' . $ev['id']) ?>" target="_blank" class="btn btn-outline-light btn-sm py-0 px-2 small">
                    <i class="bi bi-arrows-fullscreen"></i> Ver Completo
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div>
        <a href="<?= url('/incidents') ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left"></i> Volver a Bitácora
        </a>
      </div>

    </div>
  </div>

  <!-- Columna Derecha: Observaciones Complementarias y Cierre Administrativo -->
  <div class="col-12 col-lg-5">
    
    <!-- Bitácora de Eventos y Notas Complementarias -->
    <div class="coede-card p-4 mb-4">
      <h5 class="fw-bold mb-3" style="color: var(--coede-gold-light);">
        <i class="bi bi-journal-text"></i> Seguimiento y Observaciones
      </h5>

      <!-- Línea de tiempo -->
      <div class="mb-4" style="max-height: 320px; overflow-y: auto;">
        <?php foreach ($events as $ev): ?>
          <div class="p-2 mb-2 rounded bg-dark border-start border-3 <?= $ev['event_type'] === 'CIERRE' ? 'border-success' : 'border-warning' ?>">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="badge bg-secondary" style="font-size: 0.65rem;"><?= e($ev['event_type']) ?></span>
              <span class="text-muted font-monospace" style="font-size: 0.7rem;"><?= e($ev['created_at']) ?></span>
            </div>
            <div class="small text-white"><?= e($ev['notes']) ?></div>
            <div class="text-muted" style="font-size: 0.65rem;">Por: <?= e($ev['user_name']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Agregar Nota Complementaria (Permitido al Operador y Administrador) -->
      <?php if ($incident['status'] !== 'CERRADA'): ?>
        <form action="<?= url("/incidents/{$incident['id']}/note") ?>" method="POST">
          <?= csrf_field() ?>
          <label class="form-label small text-warning fw-bold">Agregar Observación Complementaria</label>
          <div class="mb-2">
            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Escriba novedad adicional ocurrida con posterioridad..." required></textarea>
          </div>
          <button type="submit" class="btn btn-coede-olive btn-sm w-100 fw-bold">
            <i class="bi bi-plus-circle"></i> Agregar al Expediente
          </button>
        </form>
      <?php endif; ?>
    </div>

    <!-- Panel de Cierre Administrativo (Solo Administrador) -->
    <?php if ($isAdmin && $incident['status'] !== 'CERRADA'): ?>
      <div class="coede-card p-4 border-warning">
        <h5 class="fw-bold text-warning mb-2">
          <i class="bi bi-check2-circle"></i> Resolución de Caso (Administrador)
        </h5>
        <p class="small text-muted mb-3">
          El cierre formal es definitivo y requiere registrar de forma obligatoria la medida de comando adoptada y la explicación de resolución.
        </p>

        <form action="<?= url("/incidents/{$incident['id']}/close") ?>" method="POST">
          <?= csrf_field() ?>

          <div class="mb-3">
            <label class="form-label small fw-bold">Medida Adoptada <span class="text-danger">*</span></label>
            <input type="text" name="resolution_measure" class="form-control form-control-sm" placeholder="ej. Notificación al Comandante de Unidad / Retiro de autorización" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold">Explicación de Resolución <span class="text-danger">*</span></label>
            <textarea name="resolution_explanation" class="form-control form-control-sm" rows="3" placeholder="Fundamentación y trámite efectuado para cerrar la novedad..." required></textarea>
          </div>

          <button type="submit" class="btn btn-success btn-sm w-100 py-2 fw-bold text-uppercase">
            <i class="bi bi-lock-fill"></i> Cerrar Formalmente Incidencia
          </button>
        </form>
      </div>
    <?php endif; ?>

  </div>

</div>

