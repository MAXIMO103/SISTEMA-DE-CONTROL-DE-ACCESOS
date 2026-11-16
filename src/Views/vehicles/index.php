<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h3 class="fw-bold mb-0" style="color: var(--coede-gold-light);">
      <i class="bi bi-truck-front-fill"></i> Padrón Oficial de Vehículos
    </h3>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <a href="<?= url('/vehicles/create') ?>" class="btn btn-coede-gold btn-sm px-3">
      <i class="bi bi-plus-circle-fill"></i> Nuevo Vehículo
    </a>
    <a href="<?= url('/vehicles/export-excel') ?>" class="btn btn-outline-success btn-sm px-3">
      <i class="bi bi-download"></i> Exportar Padrón Vehicular
    </a>
  </div>
</div>

<!-- Filtros de Búsqueda -->
<div class="coede-card p-3 mb-4">
  <form method="GET" action="<?= url('/vehicles') ?>" class="row g-2 align-items-end">
    
    <div class="col-12 col-md-4">
      <label class="form-label">Buscar Vehículo</label>
      <div class="input-group input-group-sm">
        <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-search"></i></span>
        <input type="text" name="q" class="form-control form-control-sm" placeholder="Placa, DNI, propietario, marca, modelo..." value="<?= e($filters['q']) ?>">
      </div>
    </div>

    <div class="col-6 col-md-2">
      <label class="form-label">Categoría</label>
      <select name="category" class="form-select form-select-sm">
        <option value="TODOS" <?= $filters['category'] === 'TODOS' ? 'selected' : '' ?>>Todas</option>
        <option value="PARTICULAR" <?= $filters['category'] === 'PARTICULAR' ? 'selected' : '' ?>>Particular</option>
        <option value="OFICIAL" <?= $filters['category'] === 'OFICIAL' ? 'selected' : '' ?>>Oficial</option>
      </select>
    </div>

    <div class="col-6 col-md-3">
      <label class="form-label">Dependencia / Unidad</label>
      <select name="unit_id" class="form-select form-select-sm">
        <option value="TODOS">Todas las Unidades</option>
        <?php foreach ($units as $u): ?>
          <option value="<?= $u['id'] ?>" <?= $filters['unit_id'] == $u['id'] ? 'selected' : '' ?>>
            <?= e($u['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-6 col-md-2">
      <label class="form-label">Vigencia</label>
      <select name="validity" class="form-select form-select-sm">
        <option value="TODOS" <?= $filters['validity'] === 'TODOS' ? 'selected' : '' ?>>Todas</option>
        <option value="VIGENTE" <?= $filters['validity'] === 'VIGENTE' ? 'selected' : '' ?>>Vigente</option>
        <option value="POR_VENCER" <?= $filters['validity'] === 'POR_VENCER' ? 'selected' : '' ?>>Por Vencer (≤30d)</option>
        <option value="VENCIDO" <?= $filters['validity'] === 'VENCIDO' ? 'selected' : '' ?>>Vencido</option>
      </select>
    </div>

    <div class="col-6 col-md-1 d-flex gap-1">
      <button type="submit" class="btn btn-coede-gold btn-sm w-100" title="Filtrar">
        <i class="bi bi-funnel-fill"></i>
      </button>
      <a href="<?= url('/vehicles') ?>" class="btn btn-outline-secondary btn-sm" title="Limpiar">
        <i class="bi bi-x-circle"></i>
      </a>
    </div>
  </form>
</div>

<!-- Tabla de Vehículos -->
<div class="coede-card overflow-hidden">
  <div class="table-responsive">
    <table class="table coede-table mb-0 align-middle">
      <thead>
        <tr>
          <th>Categoría</th>
          <th>Placa</th>
          <th>Marca / Modelo</th>
          <th>Propietario / Asignación</th>
          <th>Dependencia Responsable</th>
          <th>Vigencia de Autorización</th>
          <th class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($vehicles)): ?>
          <tr>
            <td colspan="7" class="text-center py-4 text-muted">
              <i class="bi bi-truck fs-3 d-block mb-2"></i>
              No se encontraron vehículos registrados con los criterios solicitados.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($vehicles as $v): ?>
            <tr>
              <td>
                <?php if ($v['category'] === 'OFICIAL'): ?>
                  <span class="badge bg-success">Oficial Militar</span>
                <?php else: ?>
                  <span class="badge bg-info text-dark">Particular Autorizado</span>
                <?php endif; ?>
              </td>

              <td class="font-monospace fw-bold fs-6" style="color: var(--coede-gold-light);">
                <?= e($v['plate']) ?>
              </td>

              <td class="fw-semibold text-white">
                <?= e($v['make']) ?> <?= e($v['model']) ?>
              </td>

              <td>
                <?php if ($v['category'] === 'PARTICULAR'): ?>
                  <div><?= e($v['owner_name'] ?? 'Propietario no especificado') ?></div>
                  <div class="small text-muted font-monospace">DNI: <?= e($v['owner_dni'] ?? '-') ?></div>
                <?php else: ?>
                  <span class="text-muted small">Asignado a: <?= e($v['unit_name']) ?></span>
                <?php endif; ?>
              </td>

              <td class="small text-muted"><?= e($v['unit_name']) ?></td>

              <td>
                <?php if ($v['auth_end']): ?>
                  <div class="small fw-semibold">Hasta: <?= e($v['auth_end']) ?></div>
                  <?php $d = (int)$v['days_left']; ?>
                  <?php if ($d < 0): ?>
                    <span class="badge badge-vencido">Vencido hace <?= abs($d) ?>d</span>
                  <?php elseif ($d <= 7): ?>
                    <span class="badge badge-por-vencer">¡Vence en <?= $d ?> días!</span>
                  <?php elseif ($d <= 30): ?>
                    <span class="badge badge-por-vencer">Por vencer (<?= $d ?>d)</span>
                  <?php else: ?>
                    <span class="badge badge-vigente">Vigente (<?= $d ?>d)</span>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="badge bg-secondary">Sin Autorización</span>
                <?php endif; ?>
              </td>

              <td class="text-end">
                <a href="<?= url('/vehicles/' . $v['id']) ?>" class="btn btn-outline-warning btn-sm py-1 px-2" title="Ver Ficha">
                  <i class="bi bi-eye"></i> Ficha
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

