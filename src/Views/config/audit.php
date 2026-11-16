<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h3 class="fw-bold mb-1" style="color: var(--coede-gold-light);">
      <i class="bi bi-shield-lock-fill"></i> Registro de Auditoría de Solo Lectura
    </h3>
    <p class="text-muted small mb-0">Trazabilidad forense inmutable en aplicación &bull; Sin privilegios de borrado &bull; COEDE</p>
  </div>
  <a href="<?= url('/config') ?>" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> Volver a Configuración
  </a>
</div>

<!-- Filtros de Auditoría -->
<div class="coede-card p-3 mb-4">
  <form method="GET" action="<?= url('/config/audit') ?>" class="row g-2 align-items-end">
    
    <div class="col-12 col-md-4">
      <label class="form-label">Tipo de Acción</label>
      <select name="action" class="form-select form-select-sm">
        <option value="TODAS" <?= $action === 'TODAS' ? 'selected' : '' ?>>Todas las Acciones</option>
        <option value="LOGIN_SUCCESS" <?= $action === 'LOGIN_SUCCESS' ? 'selected' : '' ?>>Inicios de Sesión Exitosos</option>
        <option value="LOGIN_FAILED" <?= $action === 'LOGIN_FAILED' ? 'selected' : '' ?>>Intentos Fallidos de Inicio</option>
        <option value="RECORD_MOVEMENT" <?= $action === 'RECORD_MOVEMENT' ? 'selected' : '' ?>>Registro de Movimientos Puerta</option>
        <option value="CREATE_INCIDENT" <?= $action === 'CREATE_INCIDENT' ? 'selected' : '' ?>>Creación de Incidencias</option>
        <option value="CLOSE_INCIDENT" <?= $action === 'CLOSE_INCIDENT' ? 'selected' : '' ?>>Cierre de Incidencias</option>
        <option value="IMPORT_PERSONNEL" <?= $action === 'IMPORT_PERSONNEL' ? 'selected' : '' ?>>Importaciones de Personal</option>
        <option value="EDIT_SID_NEWS" <?= $action === 'EDIT_SID_NEWS' ? 'selected' : '' ?>>Ediciones Editoriales SID</option>
        <option value="BACKUP_CREATED" <?= $action === 'BACKUP_CREATED' ? 'selected' : '' ?>>Generación de Respaldos</option>
      </select>
    </div>

    <div class="col-12 col-md-4">
      <label class="form-label">Entidad Afectada</label>
      <select name="entity" class="form-select form-select-sm">
        <option value="TODAS" <?= $entity === 'TODAS' ? 'selected' : '' ?>>Todas las Entidades</option>
        <option value="users" <?= $entity === 'users' ? 'selected' : '' ?>>Usuarios y Sesiones (users)</option>
        <option value="movements_demo" <?= $entity === 'movements_demo' ? 'selected' : '' ?>>Movimientos de Puerta (movements_demo)</option>
        <option value="incidents_demo" <?= $entity === 'incidents_demo' ? 'selected' : '' ?>>Incidencias (incidents_demo)</option>
        <option value="personnel_demo" <?= $entity === 'personnel_demo' ? 'selected' : '' ?>>Personal (personnel_demo)</option>
        <option value="vehicles_demo" <?= $entity === 'vehicles_demo' ? 'selected' : '' ?>>Vehículos (vehicles_demo)</option>
        <option value="news_events" <?= $entity === 'news_events' ? 'selected' : '' ?>>Noticias SID (news_events)</option>
        <option value="backup_runs" <?= $entity === 'backup_runs' ? 'selected' : '' ?>>Respaldos (backup_runs)</option>
      </select>
    </div>

    <div class="col-12 col-md-4 d-flex gap-1">
      <button type="submit" class="btn btn-coede-gold btn-sm w-100 fw-bold">
        <i class="bi bi-funnel-fill"></i> Filtrar Eventos
      </button>
      <a href="<?= url('/config/audit') ?>" class="btn btn-outline-secondary btn-sm" title="Limpiar">
        <i class="bi bi-x-circle"></i>
      </a>
    </div>

  </form>
</div>

<!-- Tabla de Registros Forenses -->
<div class="coede-card overflow-hidden">
  <div class="table-responsive">
    <table class="table coede-table align-middle mb-0" style="font-size: 0.85rem;">
      <thead>
        <tr>
          <th>ID</th>
          <th>Fecha y Hora</th>
          <th>Usuario Responsable</th>
          <th>Acción Registrada</th>
          <th>Entidad</th>
          <th>IP / Agente</th>
          <th>Valores Anteriores / Nuevos</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
          <tr>
            <td colspan="7" class="text-center py-4 text-muted">
              No hay registros de auditoría que coincidan con el filtro.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($logs as $l): ?>
            <tr>
              <td class="font-monospace text-muted"><?= $l['id'] ?></td>
              <td class="font-monospace text-white small"><?= e($l['created_at']) ?></td>
              <td>
                <?php if ($l['user_name']): ?>
                  <span class="text-white fw-bold"><?= e($l['user_name']) ?></span>
                  <div class="small text-muted font-monospace"><?= e($l['username']) ?></div>
                <?php else: ?>
                  <span class="text-secondary">Anónimo / Sistema</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($l['action'] === 'LOGIN_SUCCESS'): ?>
                  <span class="badge bg-success">LOGIN EXITOSO</span>
                <?php elseif ($l['action'] === 'LOGIN_FAILED'): ?>
                  <span class="badge bg-danger">LOGIN FALLIDO</span>
                <?php elseif (str_contains($l['action'], 'DELETE') || str_contains($l['action'], 'HIDE')): ?>
                  <span class="badge bg-warning text-dark"><?= e($l['action']) ?></span>
                <?php else: ?>
                  <span class="badge bg-dark border border-secondary text-info"><?= e($l['action']) ?></span>
                <?php endif; ?>
              </td>
              <td class="font-monospace small text-muted"><?= e($l['entity']) ?> #<?= e($l['entity_id'] ?? '-') ?></td>
              <td class="font-monospace small text-secondary" style="font-size: 0.72rem;">
                <?= e($l['ip_address']) ?>
              </td>
              <td class="small" style="max-width: 300px;">
                <?php if ($l['new_values']): ?>
                  <pre class="mb-0 text-muted p-1 bg-dark rounded font-monospace" style="font-size: 0.7rem; white-space: pre-wrap;"><?= e($l['new_values']) ?></pre>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

