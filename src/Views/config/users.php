<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h3 class="fw-bold mb-1" style="color: var(--coede-gold-light);">
      <i class="bi bi-person-gear"></i> Gestión de Cuentas Institucionales y Permisos
    </h3>
    <p class="text-muted small mb-0">Asignación de roles ADMINISTRADOR y SERVICIO &bull; Políticas de sesión única y contraseñas</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= url('/config') ?>" class="btn btn-outline-secondary btn-sm px-3">
      <i class="bi bi-arrow-left"></i> Volver a Configuración
    </a>
    <button type="button" class="btn btn-coede-gold btn-sm px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#modalCreateUser">
      <i class="bi bi-person-plus-fill"></i> Crear Nueva Cuenta
    </button>
  </div>
</div>

<div class="coede-card overflow-hidden">
  <div class="table-responsive">
    <table class="table coede-table align-middle mb-0">
      <thead>
        <tr>
          <th>ID</th>
          <th>Usuario</th>
          <th>Nombre del Titular</th>
          <th>Correo Electrónico</th>
          <th>Rol Asignado</th>
          <th>Estado</th>
          <th>Fecha Creación</th>
          <th class="text-end">Operaciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td class="font-monospace text-muted"><?= $u['id'] ?></td>
            <td class="font-monospace fw-bold text-warning"><?= e($u['username']) ?></td>
            <td class="fw-semibold text-white"><?= e($u['name']) ?></td>
            <td class="small text-muted"><?= e($u['email'] ?? '-') ?></td>
            <td>
              <span class="badge <?= $u['role_name'] === 'ADMINISTRADOR' ? 'bg-warning text-dark' : 'bg-success' ?>">
                <?= e($u['role_name']) ?>
              </span>
            </td>
            <td>
              <span class="badge <?= $u['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                <?= $u['is_active'] ? 'Activo' : 'Desactivado' ?>
              </span>
            </td>
            <td class="small text-muted font-monospace"><?= e($u['created_at']) ?></td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <!-- Restablecer Clave -->
                <button type="button" class="btn btn-outline-warning btn-sm btn-reset-pwd" 
                        data-id="<?= $u['id'] ?>" data-username="<?= e($u['username']) ?>" title="Restablecer Contraseña">
                  <i class="bi bi-key-fill"></i> Clave
                </button>

                <!-- Activar / Desactivar -->
                <?php if ($u['id'] != \App\Core\Auth::id()): ?>
                  <form action="<?= url("/config/users/{$u['id']}/toggle-status") ?>" method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de cambiar el estado de esta cuenta?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-<?= $u['is_active'] ? 'danger' : 'success' ?> btn-sm" title="<?= $u['is_active'] ? 'Desactivar Cuenta' : 'Activar Cuenta' ?>">
                      <i class="bi bi-power"></i>
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Crear Usuario -->
<div class="modal fade" id="modalCreateUser" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-white" style="background-color: var(--coede-surface-elevated); border: 2px solid var(--coede-gold);">
      <div class="modal-header border-bottom border-warning">
        <h5 class="modal-title text-warning">
          <i class="bi bi-person-plus-fill"></i> Crear Nueva Cuenta Institucional
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="<?= url('/config/users/create') ?>" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nombre de Usuario (Único) <span class="text-danger">*</span></label>
            <input type="text" name="username" class="form-control" placeholder="ej. guardia1, operador2" required autocomplete="off">
          </div>
          <div class="mb-3">
            <label class="form-label">Nombre Completo del Titular <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="ej. SO2 EP Pérez Ramos, Juan" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Correo Institucional (Opcional)</label>
            <input type="email" name="email" class="form-control" placeholder="juan.perez@coede.mil.pe">
          </div>
          <div class="mb-3">
            <label class="form-label">Rol Institucional <span class="text-danger">*</span></label>
            <select name="role_id" class="form-select" required>
              <?php foreach ($roles as $r): ?>
                <option value="<?= $r['id'] ?>" <?= $r['name'] === 'SERVICIO' ? 'selected' : '' ?>>
                  <?= e($r['name']) ?> &bull; <?= e($r['description']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Contraseña Inicial <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" placeholder="Mínimo 8 caracteres" required minlength="8" autocomplete="new-password">
            <div class="form-text text-muted">Se almacenará cifrada con algoritmo BCRYPT.</div>
          </div>
        </div>
        <div class="modal-footer border-top border-secondary">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-coede-gold fw-bold">Guardar Cuenta</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Restablecer Contraseña -->
<div class="modal fade" id="modalResetPassword" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-white" style="background-color: var(--coede-surface-elevated); border: 2px solid var(--coede-gold);">
      <div class="modal-header border-bottom border-warning">
        <h5 class="modal-title text-warning">
          <i class="bi bi-key-fill"></i> Restablecer Contraseña de Cuenta
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="formResetPwd" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body">
          <p class="small text-muted mb-3">
            Restableciendo contraseña para el usuario: <strong id="resetTargetUsername" class="text-warning"></strong>.<br>
            Se revocarán automáticamente todas las sesiones abiertas de esta cuenta.
          </p>
          <div class="mb-3">
            <label class="form-label">Nueva Contraseña <span class="text-danger">*</span></label>
            <input type="password" name="new_password" class="form-control" placeholder="Mínimo 8 caracteres" required minlength="8" autocomplete="new-password">
          </div>
        </div>
        <div class="modal-footer border-top border-secondary">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-coede-gold fw-bold">Aplicar Nueva Contraseña</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  const resetModal = new bootstrap.Modal(document.getElementById('modalResetPassword'));
  document.querySelectorAll('.btn-reset-pwd').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      const u = btn.dataset.username;
      document.getElementById('resetTargetUsername').textContent = u;
      document.getElementById('formResetPwd').action = `/config/users/${id}/reset-password`;
      resetModal.show();
    });
  });
</script>

