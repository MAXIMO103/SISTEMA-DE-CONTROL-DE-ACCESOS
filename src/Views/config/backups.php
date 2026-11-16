<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h3 class="fw-bold mb-1" style="color: var(--coede-gold-light);">
      <i class="bi bi-archive-fill"></i> Copias de Seguridad y Respaldos del Sistema
    </h3>
    <p class="text-muted small mb-0">Almacenamiento privado fuera de public &bull; Retención automática de 90 días &bull; COEDE</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= url('/config') ?>" class="btn btn-outline-secondary btn-sm px-3">
      <i class="bi bi-arrow-left"></i> Volver a Configuración
    </a>
    <form action="<?= url('/config/backups/create') ?>" method="POST" class="d-inline" onsubmit="return confirm('¿Desea generar una copia de seguridad completa ahora?');">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-coede-gold btn-sm px-3 fw-bold">
        <i class="bi bi-cloud-arrow-up-fill"></i> Generar Respaldo Manual Ahora
      </button>
    </form>
  </div>
</div>

<!-- Instrucciones de Programación en Windows Task Scheduler -->
<div class="alert alert-dark border-secondary p-3 mb-4">
  <div class="fw-bold text-warning mb-1">
    <i class="bi bi-windows"></i> Automatización Diaria mediante el Programador de Tareas de Windows (Task Scheduler)
  </div>
  <p class="small text-muted mb-2">
    Para activar la copia automática diaria a las 02:00 horas, configure una tarea programada en Windows ejecutando el siguiente comando:
  </p>
  <div class="bg-black p-2 rounded font-monospace small text-success user-select-all mb-2">
    php "c:\Users\crisj\OneDrive\Escritorio\EXAMEN\scripts\backup_cron.php"
  </div>
  <div class="small text-secondary">
    El script verifica espacio en disco (&gt;50 MB), comprime el volcado SQL de MySQL, fotografías privadas y evidencias, purga respaldos anteriores a 90 días y emite alertas a la campana superior si ocurre algún fallo.
  </div>
</div>

<!-- Tabla de Respaldos Realizados -->
<div class="coede-card overflow-hidden">
  <div class="table-responsive">
    <table class="table coede-table align-middle mb-0">
      <thead>
        <tr>
          <th>Fecha y Hora</th>
          <th>Archivo de Respaldo</th>
          <th>Tamaño</th>
          <th>Origen</th>
          <th>Estado</th>
          <th>Detalles</th>
          <th class="text-end">Descarga</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($runs)): ?>
          <tr>
            <td colspan="7" class="text-center py-4 text-muted">
              No hay registros de copias de seguridad generadas.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($runs as $r): ?>
            <tr>
              <td class="font-monospace text-white small"><?= e($r['created_at']) ?></td>
              <td class="font-monospace fw-bold text-warning small"><?= e($r['filename']) ?></td>
              <td class="small font-monospace"><?= round($r['file_size'] / (1024 * 1024), 2) ?> MB</td>
              <td>
                <span class="badge bg-secondary"><?= e($r['triggered_by']) ?></span>
              </td>
              <td>
                <?php if ($r['status'] === 'EXITO'): ?>
                  <span class="badge bg-success">Completado</span>
                <?php else: ?>
                  <span class="badge bg-danger">Fallo</span>
                <?php endif; ?>
              </td>
              <td class="small text-muted"><?= e($r['details']) ?></td>
              <td class="text-end">
                <?php if ($r['status'] === 'EXITO' && !empty($r['filename'])): ?>
                  <a href="<?= url('/config/backups/download/' . urlencode($r['filename'])) ?>" class="btn btn-outline-warning btn-sm py-1 px-2" title="Descargar paquete ZIP cifrado/privado">
                    <i class="bi bi-download"></i> Descargar
                  </a>
                <?php else: ?>
                  <span class="text-muted small">-</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

