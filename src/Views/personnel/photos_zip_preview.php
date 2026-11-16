<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h3 class="fw-bold mb-1" style="color: var(--coede-gold-light);">
      <i class="bi bi-file-earmark-check-fill"></i> Validación de Paquete ZIP de Fotografías
    </h3>
    <p class="text-muted small mb-0">Comprobación de DNI en padrón, reemplazos y prevención de archivos inválidos.</p>
  </div>
  <a href="<?= url('/personnel/photos-zip') ?>" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> Descartar y Cargar Otro
  </a>
</div>

<form action="<?= url('/personnel/photos-zip-confirm') ?>" method="POST">
  <?= csrf_field() ?>

  <div class="coede-card mb-4 overflow-hidden">
    <div class="table-responsive">
      <table class="table coede-table align-middle">
        <thead>
          <tr>
            <th style="width: 50px;">
              <input class="form-check-input" type="checkbox" id="selectAllPhotos" checked>
            </th>
            <th>Archivo</th>
            <th>DNI Extraído</th>
            <th>Personal Coincidente</th>
            <th>Estado de Validación</th>
            <th>Detalle</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($preview['items'] as $it): ?>
            <tr class="<?= in_array($it['status'], ['INVALIDO', 'NO_ENCONTRADO', 'RECHAZADO', 'DUPLICADO']) ? 'table-danger bg-opacity-10' : '' ?>">
              <td>
                <?php if (in_array($it['status'], ['COINCIDENCIA', 'REEMPLAZO'])): ?>
                  <input class="form-check-input photo-chk" type="checkbox" name="selected_indices[]" value="<?= $it['zip_index'] ?>" checked>
                <?php else: ?>
                  <span class="text-danger"><i class="bi bi-x-circle"></i></span>
                <?php endif; ?>
              </td>
              <td class="font-monospace small"><?= e($it['filename']) ?></td>
              <td class="font-monospace fw-bold" style="color: var(--coede-gold-light);">
                <?= e($it['dni'] ?? '-') ?>
              </td>
              <td><?= e($it['full_name'] ?? '-') ?></td>
              <td>
                <?php if ($it['status'] === 'COINCIDENCIA'): ?>
                  <span class="badge bg-success">Coincidencia Exacta</span>
                <?php elseif ($it['status'] === 'REEMPLAZO'): ?>
                  <span class="badge bg-warning text-dark">Actualización de Foto</span>
                <?php elseif ($it['status'] === 'NO_ENCONTRADO'): ?>
                  <span class="badge bg-secondary">DNI No en Padrón</span>
                <?php else: ?>
                  <span class="badge bg-danger"><?= e($it['status']) ?></span>
                <?php endif; ?>
              </td>
              <td class="small text-muted"><?= e($it['reason']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-3 mb-5">
    <a href="<?= url('/personnel/photos-zip') ?>" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-coede-gold px-4 py-2 fs-6 fw-bold">
      <i class="bi bi-check-circle-fill"></i> Confirmar Carga y Asociar al Padrón
    </button>
  </div>
</form>

<script>
  document.getElementById('selectAllPhotos')?.addEventListener('change', function() {
    const chks = document.querySelectorAll('.photo-chk');
    chks.forEach(c => c.checked = this.checked);
  });
</script>

