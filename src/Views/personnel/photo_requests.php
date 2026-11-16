<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h3 class="fw-bold mb-1" style="color: var(--coede-gold-light);">
      <i class="bi bi-camera-fill"></i> Solicitudes de Fotografía Registradas en Puerta
    </h3>
    <p class="text-muted small mb-0">Requerimientos generados por el personal de servicio en guardia &bull; Agrupado por dependencia</p>
  </div>
  <a href="<?= url('/personnel') ?>" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> Volver al Padrón
  </a>
</div>

<?php if (empty($requests)): ?>
  <div class="coede-card p-5 text-center text-muted">
    <i class="bi bi-check2-all fs-1 text-success d-block mb-2"></i>
    <h5 class="text-white">No hay solicitudes de fotografía pendientes</h5>
    <p class="small text-muted mb-0">Todo el personal verificado en puerta cuenta con registro fotográfico al día o no registra observaciones.</p>
  </div>
<?php else: ?>
  
  <div class="alert alert-dark border-warning small mb-4">
    <i class="bi bi-info-circle-fill text-warning"></i>
    Las solicitudes registradas por el personal de guardia en puerta se resuelven automáticamente una vez que el Administrador carga la fotografía mediante la ficha individual o la carga masiva ZIP.
  </div>

  <div class="coede-card overflow-hidden">
    <div class="table-responsive">
      <table class="table coede-table align-middle">
        <thead>
          <tr>
            <th>Dependencia / Unidad</th>
            <th>DNI</th>
            <th>Personal Requerido</th>
            <th>Registrado Por</th>
            <th>Fecha y Hora</th>
            <th>Observación de Guardia</th>
            <th class="text-end">Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($requests as $r): ?>
            <tr>
              <td class="fw-bold text-warning"><?= e($r['unit_name']) ?></td>
              <td class="font-monospace fw-bold text-white"><?= e($r['dni']) ?></td>
              <td>
                <span class="badge <?= $r['category'] === 'MILITAR' ? 'badge-militar' : 'badge-civil' ?> me-1">
                  <?= e($r['rank_grade'] ?? $r['category']) ?>
                </span>
                <?= e($r['surnames']) ?>, <?= e($r['names']) ?>
              </td>
              <td class="small text-muted"><?= e($r['requester_name']) ?></td>
              <td class="small font-monospace"><?= e($r['requested_at']) ?></td>
              <td class="small text-muted"><?= e($r['notes'] ?? 'Sin nota') ?></td>
              <td class="text-end">
                <a href="<?= url('/personnel/' . $r['personnel_id']) ?>" class="btn btn-coede-gold btn-sm py-1 px-2">
                  <i class="bi bi-upload"></i> Cargar Foto
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

