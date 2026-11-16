<div class="row g-4">
  <!-- Tarjeta Principal de Ficha -->
  <div class="col-12 col-lg-4">
    <div class="coede-card p-4 text-center">
      
      <!-- Contenedor de Fotografía -->
      <div class="mx-auto mb-3 personnel-photo-box">
        <?php if (!empty($person['photo_path'])): ?>
          <img src="<?= url('/media/photo/' . $person['dni']) ?>" alt="Fotografía Oficial">
        <?php else: ?>
          <div class="personnel-photo-placeholder">
            <i class="bi bi-camera fs-1 d-block mb-1"></i>
            FOTOGRAFÍA PENDIENTE
            <div class="small text-muted fw-normal mt-1">Verificar identidad y solicitar fotografía</div>
          </div>
        <?php endif; ?>
      </div>

      <h5 class="fw-bold text-white mb-1">
        <?= e($person['surnames']) ?>, <?= e($person['names']) ?>
      </h5>

      <div class="mb-3">
        <?php if ($person['category'] === 'MILITAR'): ?>
          <span class="badge badge-militar fs-6"><?= e($person['rank_grade'] ?? 'Militar') ?></span>
        <?php else: ?>
          <span class="badge badge-civil fs-6">Personal Civil</span>
        <?php endif; ?>
      </div>

      <ul class="list-group list-group-flush text-start small mb-3">
        <li class="list-group-item bg-transparent text-muted d-flex justify-content-between px-0">
          <span>DNI:</span>
          <span class="text-white fw-bold font-monospace"><?= e($person['dni']) ?></span>
        </li>
        <li class="list-group-item bg-transparent text-muted d-flex justify-content-between px-0">
          <span>CIP / CIV:</span>
          <span class="text-white fw-bold"><?= e($person['cip_civ'] ?? '-') ?></span>
        </li>
        <li class="list-group-item bg-transparent text-muted d-flex justify-content-between px-0">
          <span>Dependencia:</span>
          <span class="text-white fw-bold"><?= e($person['unit_name']) ?></span>
        </li>
        <li class="list-group-item bg-transparent text-muted d-flex justify-content-between px-0">
          <span>Estado en Padrón:</span>
          <span class="badge <?= $person['is_active'] ? 'bg-success' : 'bg-danger' ?>">
            <?= $person['is_active'] ? 'Activo' : 'Inactivo' ?>
          </span>
        </li>
      </ul>

      <!-- Estado de Vigencia -->
      <div class="p-3 rounded border <?= (int)$person['days_left'] < 0 ? 'border-danger bg-danger bg-opacity-10' : 'border-success bg-success bg-opacity-10' ?>">
        <div class="fw-bold text-uppercase small" style="color: var(--coede-gold-light);">Vigencia de Autorización</div>
        <div class="text-white mt-1">
          <?= e($person['auth_start'] ?? '-') ?> al <?= e($person['auth_end'] ?? '-') ?>
        </div>
        <div class="mt-2">
          <?php $d = (int)$person['days_left']; ?>
          <?php if ($d < 0): ?>
            <span class="badge badge-vencido">Vencida hace <?= abs($d) ?> días</span>
            <div class="small text-danger mt-1">No equivale a restricción expresa; requiere renovación administrativa.</div>
          <?php elseif ($d <= 7): ?>
            <span class="badge badge-por-vencer">¡Atención! Vence en <?= $d ?> días</span>
          <?php else: ?>
            <span class="badge badge-vigente">Vigente (<?= $d ?> días restantes)</span>
          <?php endif; ?>
        </div>
      </div>

      <div class="mt-3">
        <a href="<?= url('/personnel') ?>" class="btn btn-outline-secondary btn-sm w-100">
          <i class="bi bi-arrow-left"></i> Volver al Padrón
        </a>
      </div>
    </div>
  </div>

  <!-- Pestañas de Historial de Movimientos y Solicitudes de Foto -->
  <div class="col-12 col-lg-8">
    <div class="coede-card p-4">
      <h5 class="fw-bold mb-3" style="color: var(--coede-gold-light);">
        <i class="bi bi-clock-history"></i> Historial de Accesos por Puerta Principal
      </h5>

      <?php if (empty($movements)): ?>
        <div class="alert alert-dark text-muted text-center py-4">
          No hay registros de movimientos en puerta principal para este documento.
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table coede-table align-middle">
            <thead>
              <tr>
                <th>Fecha / Hora</th>
                <th>Tipo</th>
                <th>Modalidad</th>
                <th>Decisión Operador</th>
                <th>Observación</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($movements as $m): ?>
                <tr>
                  <td class="small"><?= e($m['recorded_at']) ?></td>
                  <td>
                    <span class="badge <?= $m['movement_type'] === 'ENTRADA' ? 'bg-success' : 'bg-primary' ?>">
                      <?= e($m['movement_type']) ?>
                    </span>
                  </td>
                  <td class="small"><?= e($m['target_type']) ?></td>
                  <td>
                    <span class="badge <?= $m['operator_decision'] === 'AUTORIZADO' ? 'badge-vigente' : 'badge-vencido' ?>">
                      <?= e($m['operator_decision']) ?>
                    </span>
                  </td>
                  <td class="small text-muted"><?= e($m['observation'] ?? '-') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <!-- Solicitudes de Fotografía Registradas -->
      <h5 class="fw-bold mt-4 mb-3" style="color: var(--coede-gold-light);">
        <i class="bi bi-camera"></i> Registro de Solicitudes de Fotografía
      </h5>
      <?php if (empty($photoRequests)): ?>
        <div class="small text-muted">No se registran solicitudes de fotografía para este trabajador.</div>
      <?php else: ?>
        <div class="list-group list-group-flush">
          <?php foreach ($photoRequests as $pr): ?>
            <div class="list-group-item bg-transparent text-white border-secondary px-0">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <span class="badge <?= $pr['status'] === 'COMPLETADA' ? 'bg-success' : 'badge-por-vencer' ?> me-2">
                    <?= e($pr['status']) ?>
                  </span>
                  <span class="small text-muted">Solicitado por <?= e($pr['requester_name']) ?> el <?= e($pr['requested_at']) ?></span>
                </div>
              </div>
              <div class="small text-secondary mt-1"><?= e($pr['notes'] ?? 'Sin nota') ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

