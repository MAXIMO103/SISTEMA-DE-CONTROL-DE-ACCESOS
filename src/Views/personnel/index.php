<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h3 class="fw-bold mb-0" style="color: var(--coede-gold-light);">
      <i class="bi bi-people-fill"></i> Padrón Institucional de Personal
    </h3>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <a href="<?= url('/personnel/create') ?>" class="btn btn-coede-gold btn-sm px-3">
      <i class="bi bi-person-plus-fill"></i> Nuevo Personal
    </a>
    <a href="<?= url('/personnel/import') ?>" class="btn btn-coede-olive btn-sm px-3">
      <i class="bi bi-file-earmark-excel-fill"></i> Importar Excel
    </a>
    <a href="<?= url('/personnel/photos-zip') ?>" class="btn btn-coede-olive btn-sm px-3">
      <i class="bi bi-images"></i> Carga ZIP Fotos
    </a>
    <a href="<?= url('/personnel/photo-requests') ?>" class="btn btn-outline-warning btn-sm px-3">
      <i class="bi bi-camera-fill"></i> Solicitudes de Foto
    </a>
    <a href="<?= url('/personnel/export-excel') ?>" class="btn btn-outline-success btn-sm px-3">
      <i class="bi bi-download"></i> Exportar Padrón
    </a>
  </div>
</div>

<!-- Filtros de Búsqueda -->
<div class="coede-card p-3 mb-4">
  <form method="GET" action="<?= url('/personnel') ?>" class="row g-2 align-items-end">
    
    <div class="col-12 col-md-3">
      <label class="form-label">Buscar Personal</label>
      <div class="input-group input-group-sm">
        <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-search"></i></span>
        <input type="text" name="q" class="form-control form-control-sm" placeholder="DNI, nombres, apellidos, CIP/CIV..." value="<?= e($filters['q']) ?>">
      </div>
    </div>

    <div class="col-6 col-md-2">
      <label class="form-label">Categoría</label>
      <select name="category" class="form-select form-select-sm">
        <option value="TODOS" <?= $filters['category'] === 'TODOS' ? 'selected' : '' ?>>Todas</option>
        <option value="MILITAR" <?= $filters['category'] === 'MILITAR' ? 'selected' : '' ?>>Militar</option>
        <option value="CIVIL" <?= $filters['category'] === 'CIVIL' ? 'selected' : '' ?>>Civil</option>
      </select>
    </div>

    <div class="col-6 col-md-2">
      <label class="form-label">Dependencia</label>
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

    <div class="col-6 col-md-2">
      <label class="form-label">Fotografía</label>
      <select name="photo" class="form-select form-select-sm">
        <option value="TODOS" <?= $filters['photo'] === 'TODOS' ? 'selected' : '' ?>>Todas</option>
        <option value="CON_FOTO" <?= $filters['photo'] === 'CON_FOTO' ? 'selected' : '' ?>>Con Fotografía</option>
        <option value="SIN_FOTO" <?= $filters['photo'] === 'SIN_FOTO' ? 'selected' : '' ?>>Foto Pendiente</option>
      </select>
    </div>

    <div class="col-12 col-md-1 d-flex gap-1">
      <button type="submit" class="btn btn-coede-gold btn-sm w-100" title="Aplicar Filtros">
        <i class="bi bi-funnel-fill"></i>
      </button>
      <a href="<?= url('/personnel') ?>" class="btn btn-outline-secondary btn-sm" title="Limpiar Filtros">
        <i class="bi bi-x-circle"></i>
      </a>
    </div>
  </form>
</div>

<!-- Tabla del Padrón -->
<div class="coede-card overflow-hidden">
  <div class="table-responsive">
    <table class="table coede-table mb-0 align-middle">
      <thead>
        <tr>
          <th>Foto</th>
          <th>DNI</th>
          <th>CIP / CIV</th>
          <th>Grado / Nombres y Apellidos</th>
          <th>Dependencia / Unidad</th>
          <th>Vigencia Autorización</th>
          <th>Estado</th>
          <th class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($personnel)): ?>
          <tr>
            <td colspan="8" class="text-center py-4 text-muted">
              <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
              No se encontraron registros de personal con los filtros especificados.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($personnel as $p): ?>
            <tr>
              <!-- Foto / Placeholder -->
              <td style="width: 50px;">
                <?php if (!empty($p['photo_path'])): ?>
                  <img src="<?= url('/media/photo/' . $p['dni']) ?>" alt="Foto" class="rounded border border-warning" style="width: 38px; height: 46px; object-fit: cover;">
                <?php else: ?>
                  <div class="rounded border border-secondary bg-dark d-flex align-items-center justify-content-center text-muted" style="width: 38px; height: 46px;" title="Fotografía Pendiente">
                    <i class="bi bi-person-fill"></i>
                  </div>
                <?php endif; ?>
              </td>

              <!-- DNI -->
              <td class="fw-bold font-monospace" style="color: var(--coede-gold-light);">
                <?= e($p['dni']) ?>
              </td>

              <!-- CIP/CIV -->
              <td class="small">
                <?= e($p['cip_civ'] ?? '-') ?>
              </td>

              <!-- Grado y Nombres -->
              <td>
                <div class="fw-bold text-white">
                  <?php if ($p['category'] === 'MILITAR'): ?>
                    <span class="badge badge-militar me-1"><?= e($p['rank_grade'] ?? 'Militar') ?></span>
                  <?php else: ?>
                    <span class="badge badge-civil me-1">Civil</span>
                  <?php endif; ?>
                  <?= e($p['surnames']) ?>, <?= e($p['names']) ?>
                </div>
              </td>

              <!-- Dependencia -->
              <td class="small text-muted">
                <?= e($p['unit_name']) ?>
              </td>

              <!-- Vigencia y Countdown -->
              <td>
                <?php if ($p['auth_end']): ?>
                  <div class="small fw-semibold">Hasta: <?= e($p['auth_end']) ?></div>
                  <?php $days = (int)$p['days_left']; ?>
                  <?php if ($days < 0): ?>
                    <span class="badge badge-vencido">Vencido hace <?= abs($days) ?>d</span>
                  <?php elseif ($days <= 7): ?>
                    <span class="badge badge-por-vencer">¡Vence en <?= $days ?> días!</span>
                  <?php elseif ($days <= 30): ?>
                    <span class="badge badge-por-vencer">Por vencer (<?= $days ?>d)</span>
                  <?php else: ?>
                    <span class="badge badge-vigente">Vigente (<?= $days ?>d)</span>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="badge bg-secondary">Sin Autorización</span>
                <?php endif; ?>
              </td>

              <!-- Estado de Foto / Activo -->
              <td>
                <?php if (empty($p['photo_path'])): ?>
                  <span class="badge badge-foto-pendiente" title="Fotografía Pendiente">
                    <i class="bi bi-camera"></i> Foto Pendiente
                  </span>
                <?php else: ?>
                  <span class="badge bg-dark border border-success text-success">
                    <i class="bi bi-check-circle"></i> Ficha Completa
                  </span>
                <?php endif; ?>
              </td>

              <!-- Acciones -->
              <td class="text-end">
                <a href="<?= url('/personnel/' . $p['id']) ?>" class="btn btn-outline-warning btn-sm py-1 px-2" title="Ver Ficha e Historial">
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

