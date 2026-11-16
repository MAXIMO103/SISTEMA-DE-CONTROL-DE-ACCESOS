<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h3 class="fw-bold mb-0" style="color: var(--coede-gold-light);">
      <i class="bi bi-newspaper"></i> Síntesis de Información Diaria (SID)
    </h3>
  </div>
  
  <!-- Menú Desplegable de Exportación Oficial (Accesible a Administrador y Servicio) -->
  <div class="d-flex gap-2">
    <a href="<?= url('/sid/history') ?>" class="btn btn-outline-secondary btn-sm px-3">
      <i class="bi bi-calendar3"></i> Histórico
    </a>

    <div class="dropdown">
      <button class="btn btn-coede-gold btn-sm px-3 dropdown-toggle fw-bold" type="button" id="exportSidMenu" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-download"></i> Exportar Reporte
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="exportSidMenu" style="background-color: var(--coede-surface-elevated); border: 1px solid var(--coede-border);">
        <li>
          <a class="dropdown-item text-white small" href="<?= url('/sid/export-pdf?department=' . urlencode($selectedDept) . '&threat=' . urlencode($selectedThreat)) ?>" target="_blank">
            <i class="bi bi-file-earmark-pdf-fill text-danger me-2"></i> Boletín Institucional (PDF con Portada)
          </a>
        </li>
        <li>
          <a class="dropdown-item text-white small" href="<?= url('/sid/export-excel?department=' . urlencode($selectedDept) . '&threat=' . urlencode($selectedThreat)) ?>">
            <i class="bi bi-file-earmark-excel-fill text-success me-2"></i> Tabla de Inteligencia (Excel)
          </a>
        </li>
      </ul>
    </div>
  </div>
</div>

<!-- Formulario de Consulta: DOS SELECTORES ÚNICAMENTE + BOTÓN -->
<div class="coede-card p-4 mb-4">
  <form method="GET" action="<?= url('/sid') ?>" class="row g-3 align-items-end">
    <input type="hidden" name="search" value="1">

    <div class="col-12 col-md-5">
      <label class="form-label fw-bold text-white">
        <i class="bi bi-geo-alt-fill text-warning"></i> Departamento del Perú <span class="text-danger">*</span>
      </label>
      <select name="department" class="form-select form-select-lg fw-semibold" required>
        <?php foreach ($departamentos as $dept): ?>
          <option value="<?= e($dept) ?>" <?= $selectedDept === $dept ? 'selected' : '' ?>>
            <?= e($dept) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-12 col-md-5">
      <label class="form-label fw-bold text-white">
        <i class="bi bi-exclamation-diamond-fill text-warning"></i> Catálogo de Amenaza Monitoreada <span class="text-danger">*</span>
      </label>
      <select name="threat" class="form-select form-select-lg fw-semibold" required>
        <?php foreach ($amenazas as $thName => $thDesc): ?>
          <option value="<?= e($thName) ?>" <?= $selectedThreat === $thName ? 'selected' : '' ?>>
            <?= e($thName) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-12 col-md-2">
      <button type="submit" class="btn btn-coede-gold btn-lg w-100 fw-bold text-uppercase shadow">
        <i class="bi bi-search"></i> BUSCAR NOTICIAS
      </button>
    </div>
  </form>

  <div class="text-muted small mt-2">
    <i class="bi bi-info-circle"></i> Alcance: Noticias públicas de las últimas 24 horas móviles en hora de Lima. No se elaboran listas de personas ni perfiles individuales.
  </div>
</div>

<!-- Listado de Noticias Sintetizadas Consecutivas -->
<div class="sid-results-container">
  
  <?php if (empty($events)): ?>
    <div class="coede-card p-5 text-center text-muted">
      <i class="bi bi-newspaper fs-1 text-secondary d-block mb-2"></i>
      <h5 class="text-white">Sin acontecimientos registrados en las últimas 24 horas</h5>
      <p class="small text-muted mb-0">La consulta se ejecutó exitosamente. No se reportaron hechos públicos relevantes en <?= e($selectedDept) ?> para la amenaza '<?= e($selectedThreat) ?>'.</p>
    </div>
  <?php else: ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="small text-muted">
        Mostrando <strong class="text-white"><?= count($events) ?></strong> acontecimientos públicos sintetizados &bull; Ordenados de más reciente a más antiguo
      </div>
    </div>

    <?php $counter = 1; ?>
    <?php foreach ($events as $evt): ?>
      <?php if ($evt['is_hidden'] && !$isAdmin) continue; ?>
      
      <div class="card coede-card mb-4 shadow <?= $evt['is_hidden'] ? 'border-danger bg-danger bg-opacity-10' : '' ?>" id="newsCard_<?= $evt['id'] ?>">
        
        <!-- Encabezado de Noticia con Numeración Consecutiva -->
        <div class="coede-card-header d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-warning text-dark fw-bold fs-6">
              NOTICIA N.º <?= str_pad((string)$counter, 2, '0', STR_PAD_LEFT) ?>
            </span>
            <span class="text-secondary small font-monospace">
              <i class="bi bi-clock"></i> <?= e($evt['event_date']) ?> (Lima)
            </span>
            <span class="badge bg-dark border border-secondary text-info small">
              <?= e($evt['department']) ?>
            </span>
          </div>

          <!-- Controles Editoriales (Solo Administrador) -->
          <?php if ($isAdmin): ?>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-warning btn-sm btn-edit-editorial" 
                    data-id="<?= $evt['id'] ?>"
                    data-headline="<?= e($evt['headline']) ?>"
                    data-synthesis="<?= e($evt['synthesis']) ?>">
              <i class="bi bi-pencil-square"></i> Editar Síntesis
            </button>

            <?php if (!$evt['is_hidden']): ?>
              <button type="button" class="btn btn-outline-danger btn-sm btn-hide-news" data-id="<?= $evt['id'] ?>">
                <i class="bi bi-eye-slash"></i> Ocultar
              </button>
            <?php else: ?>
              <button type="button" class="btn btn-outline-success btn-sm btn-restore-news" data-id="<?= $evt['id'] ?>">
                <i class="bi bi-eye"></i> Recuperar
              </button>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>

        <div class="card-body p-4">
          
          <?php if ($evt['is_hidden']): ?>
            <div class="alert alert-danger py-1 px-2 small mb-3">
              <i class="bi bi-eye-slash-fill"></i> <strong>NOTICIA OCULTADA DEL BOLETÍN:</strong> <?= e($evt['hide_reason'] ?? 'No especificado') ?>
            </div>
          <?php endif; ?>

          <!-- Encabezado Estructurado: QUÉ / QUIÉN / DÓNDE -->
          <div class="p-2 mb-3 rounded bg-dark border-start border-4 border-warning">
            <div class="small text-warning fw-bold text-uppercase">Encabezado Institucional (QUÉ / QUIÉN / DÓNDE)</div>
            <div class="fw-bold text-white fs-6 mt-1" id="hl_<?= $evt['id'] ?>">
              <?= e($evt['headline']) ?>
            </div>
          </div>

          <!-- Párrafo Continuo de Síntesis de Inteligencia -->
          <div class="mb-3 text-justify">
            <div class="small text-muted text-uppercase fw-bold mb-1">Síntesis Analítica de Información (QUÉ, QUIÉN, CUÁNDO, DÓNDE, CÓMO, POR QUÉ y PARA QUÉ)</div>
            <p class="text-white fs-6 mb-0" style="line-height: 1.6; text-align: justify;" id="synth_<?= $evt['id'] ?>">
              <?= nl2br(e($evt['synthesis'])) ?>
            </p>
          </div>

          <!-- Fuentes y Enlaces Originales -->
          <div class="p-3 rounded border border-secondary bg-dark bg-opacity-50">
            <div class="small fw-bold text-warning mb-2">
              <i class="bi bi-link-45deg"></i> Fuentes y Medios Verificados:
            </div>
            <ul class="list-unstyled mb-0 small">
              <?php foreach ($evt['sources'] as $src): ?>
                <li class="mb-1 d-flex align-items-center justify-content-between flex-wrap">
                  <div>
                    <span class="badge bg-secondary me-2"><?= e($src['media_name']) ?></span>
                    <a href="<?= e($src['source_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-info text-decoration-underline font-monospace">
                      <?= e($src['source_url']) ?>
                    </a>
                  </div>
                  <span class="text-muted" style="font-size: 0.7rem;"><?= e($src['published_at']) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>

        </div>
      </div>
      <?php $counter++; ?>
    <?php endforeach; ?>

  <?php endif; ?>

</div>

<!-- Modal Editorial (Solo Administrador) -->
<div class="modal fade" id="editorialModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content text-white" style="background-color: var(--coede-surface-elevated); border: 2px solid var(--coede-gold);">
      <div class="modal-header border-bottom border-warning">
        <h5 class="modal-title text-warning">
          <i class="bi bi-pencil-square"></i> Edición Editorial de Noticia SID
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="editEventId">

        <div class="mb-3">
          <label class="form-label fw-bold">Encabezado (QUÉ, QUIÉN y DÓNDE)</label>
          <input type="text" id="editHeadline" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold">Párrafo Continuo de Síntesis</label>
          <textarea id="editSynthesis" class="form-control" rows="5" required></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label small text-muted">Justificación del Cambio / Nota de Versión</label>
          <input type="text" id="editNotes" class="form-control form-control-sm" placeholder="ej. Corrección de ubicación geográfica conforme a comunicado del MTC">
        </div>
      </div>
      <div class="modal-footer border-top border-secondary">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" id="btnSaveEditorial" class="btn btn-coede-gold fw-bold">
          <i class="bi bi-save"></i> Guardar y Versionar Síntesis
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Ocultar Noticia (Solo Administrador) -->
<div class="modal fade" id="hideNewsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-white" style="background-color: var(--coede-surface-elevated); border: 1px solid var(--coede-border);">
      <div class="modal-header border-bottom border-danger">
        <h5 class="modal-title text-danger">
          <i class="bi bi-eye-slash-fill"></i> Ocultar Noticia del Boletín
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="hideEventId">
        <p class="small text-muted">
          Al ocultar una noticia, esta quedará excluida del boletín oficial y de las exportaciones regulares. Debe especificar el motivo de exclusión.
        </p>
        <div class="mb-3">
          <label class="form-label fw-bold">Motivo de Exclusión <span class="text-danger">*</span></label>
          <textarea id="hideReason" class="form-control" rows="3" placeholder="ej. Hecho irrelevante para doctrina institucional / Desmentido por fuente primaria..." required></textarea>
        </div>
      </div>
      <div class="modal-footer border-top border-secondary">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" id="btnConfirmHide" class="btn btn-danger">Confirmar Ocultamiento</button>
      </div>
    </div>
  </div>
</div>

<script>
  // Control Editorial AJAX
  const editModal = new bootstrap.Modal(document.getElementById('editorialModal'));
  const hideModal = new bootstrap.Modal(document.getElementById('hideNewsModal'));

  document.querySelectorAll('.btn-edit-editorial').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('editEventId').value = btn.dataset.id;
      document.getElementById('editHeadline').value = btn.dataset.headline;
      document.getElementById('editSynthesis').value = btn.dataset.synthesis;
      document.getElementById('editNotes').value = '';
      editModal.show();
    });
  });

  document.getElementById('btnSaveEditorial')?.addEventListener('click', async () => {
    const id = document.getElementById('editEventId').value;
    const headline = document.getElementById('editHeadline').value.trim();
    const synthesis = document.getElementById('editSynthesis').value.trim();
    const notes = document.getElementById('editNotes').value.trim();
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!headline || !synthesis) {
      alert('Encabezado y síntesis son obligatorios.');
      return;
    }

    try {
      const res = await fetch(`<?= url('/sid') ?>/${id}/editorial`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': token
        },
        body: JSON.stringify({ headline, synthesis, edit_notes: notes })
      });
      const data = await res.json();
      if (data.success) {
        document.getElementById(`hl_${id}`).textContent = headline;
        document.getElementById(`synth_${id}`).textContent = synthesis;
        editModal.hide();
        alert('Síntesis actualizada exitosamente.');
      } else {
        alert(data.message || 'Error al actualizar.');
      }
    } catch (e) {
      alert('Error de conexión.');
    }
  });

  document.querySelectorAll('.btn-hide-news').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('hideEventId').value = btn.dataset.id;
      document.getElementById('hideReason').value = '';
      hideModal.show();
    });
  });

  document.getElementById('btnConfirmHide')?.addEventListener('click', async () => {
    const id = document.getElementById('hideEventId').value;
    const reason = document.getElementById('hideReason').value.trim();
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!reason) {
      alert('Debe especificar un motivo.');
      return;
    }

    try {
      const res = await fetch(`<?= url('/sid') ?>/${id}/visibility`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': token
        },
        body: JSON.stringify({ hide: 1, reason })
      });
      const data = await res.json();
      if (data.success) {
        hideModal.hide();
        window.location.reload();
      } else {
        alert(data.message || 'Error al ocultar.');
      }
    } catch (e) {
      alert('Error de conexión.');
    }
  });

  document.querySelectorAll('.btn-restore-news').forEach(btn => {
    btn.addEventListener('click', async () => {
      const id = btn.dataset.id;
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      if (!confirm('¿Desea restaurar esta noticia en el boletín público?')) return;

      try {
        const res = await fetch(`<?= url('/sid') ?>/${id}/visibility`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token
          },
          body: JSON.stringify({ hide: 0, reason: 'Restauración administrativa' })
        });
        const data = await res.json();
        if (data.success) {
          window.location.reload();
        } else {
          alert(data.message);
        }
      } catch (e) {
        alert('Error de conexión.');
      }
    });
  });
</script>

