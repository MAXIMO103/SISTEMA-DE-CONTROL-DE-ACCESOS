<div class="row g-4">
  <div class="col-12 col-lg-5">
    <div class="coede-card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="badge <?= $vehicle['category'] === 'OFICIAL' ? 'bg-success' : 'bg-info text-dark' ?> fs-6">
          Vehículo <?= e($vehicle['category']) ?>
        </span>
        <span class="badge <?= $vehicle['is_active'] ? 'bg-success' : 'bg-danger' ?>">
          <?= $vehicle['is_active'] ? 'Activo' : 'Inactivo' ?>
        </span>
      </div>

      <div class="text-center p-3 mb-3 bg-dark rounded border border-warning">
        <div class="small text-muted text-uppercase">Placa Oficial Registrada</div>
        <div class="font-monospace fw-bold display-6" style="color: var(--coede-gold-light); letter-spacing: 3px;">
          <?= e($vehicle['plate']) ?>
        </div>
        <div class="fw-bold text-white mt-1"><?= e($vehicle['make']) ?> <?= e($vehicle['model']) ?></div>
      </div>

      <ul class="list-group list-group-flush small mb-3">
        <li class="list-group-item bg-transparent text-muted d-flex justify-content-between px-0">
          <span>Dependencia Responsable:</span>
          <span class="text-white fw-bold"><?= e($vehicle['unit_name']) ?></span>
        </li>
        <?php if ($vehicle['category'] === 'PARTICULAR'): ?>
        <li class="list-group-item bg-transparent text-muted d-flex justify-content-between px-0">
          <span>Conductor / Responsable:</span>
          <span class="text-white fw-bold"><?= e($vehicle['owner_name'] ?? '-') ?></span>
        </li>
        <li class="list-group-item bg-transparent text-muted d-flex justify-content-between px-0">
          <span>DNI / CIP:</span>
          <span class="text-white font-monospace"><?= e($vehicle['owner_dni'] ?? '-') ?> / <?= e($vehicle['owner_cip_civ'] ?? '-') ?></span>
        </li>
        <?php endif; ?>
        <li class="list-group-item bg-transparent text-muted d-flex justify-content-between px-0">
          <span>Fecha de Registro:</span>
          <span class="text-white"><?= e($vehicle['created_at']) ?></span>
        </li>
      </ul>

      <!-- Vigencia -->
      <div class="p-3 rounded border <?= (int)$vehicle['days_left'] < 0 ? 'border-danger bg-danger bg-opacity-10' : 'border-success bg-success bg-opacity-10' ?>">
        <div class="fw-bold small text-uppercase" style="color: var(--coede-gold-light);">Autorización de Circulación / Estacionamiento</div>
        <div class="text-white mt-1">
          <?= e($vehicle['auth_start'] ?? '-') ?> al <?= e($vehicle['auth_end'] ?? '-') ?>
        </div>
        <div class="mt-2">
          <?php $d = (int)$vehicle['days_left']; ?>
          <?php if ($d < 0): ?>
            <span class="badge badge-vencido">Vencido hace <?= abs($d) ?> días</span>
          <?php elseif ($d <= 7): ?>
            <span class="badge badge-por-vencer">¡Por vencer en <?= $d ?> días!</span>
          <?php else: ?>
            <span class="badge badge-vigente">Vigente (<?= $d ?> días)</span>
          <?php endif; ?>
        </div>
      </div>

      <div class="mt-3">
        <a href="<?= url('/vehicles') ?>" class="btn btn-outline-secondary btn-sm w-100">
          <i class="bi bi-arrow-left"></i> Volver al Padrón
        </a>
      </div>
    </div>
  </div>

  <!-- Historial de Movimientos de la Placa -->
  <div class="col-12 col-lg-7">
    <div class="coede-card p-4">
      <h5 class="fw-bold mb-3" style="color: var(--coede-gold-light);">
        <i class="bi bi-clock-history"></i> Accesos en Puerta Principal Registrados para Placa <?= e($vehicle['plate']) ?>
      </h5>

      <?php if (empty($movements)): ?>
        <div class="alert alert-dark text-muted text-center py-4">
          No hay movimientos registrados para esta placa vehicular.
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table coede-table align-middle">
            <thead>
              <tr>
                <th>Fecha / Hora</th>
                <th>Tipo</th>
                <th>Conductor</th>
                <th>Destino</th>
                <th>Decisión</th>
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
                  <td class="small fw-semibold text-white"><?= e($m['person_name']) ?></td>
                  <td class="small text-muted"><?= e($m['destination'] ?? '-') ?></td>
                  <td>
                    <span class="badge <?= $m['operator_decision'] === 'AUTORIZADO' ? 'badge-vigente' : 'badge-vencido' ?>">
                      <?= e($m['operator_decision']) ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

