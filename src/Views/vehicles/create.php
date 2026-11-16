<div class="row justify-content-center">
  <div class="col-12 col-lg-8">
    <div class="coede-card">
      <div class="coede-card-header">
        <h5 class="coede-card-title">
          <i class="bi bi-truck-front-fill"></i> Registro de Vehículo Institucional / Particular
        </h5>
        <a href="<?= url('/vehicles') ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left"></i> Volver
        </a>
      </div>

      <div class="card-body p-4">
        <form action="<?= url('/vehicles/store') ?>" method="POST">
          <?= csrf_field() ?>

          <div class="row mb-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Categoría del Vehículo <span class="text-danger">*</span></label>
              <select name="category" id="vehCategorySelect" class="form-select" required>
                <option value="PARTICULAR" selected>Particular (Personal Militar o Civil Autorizado)</option>
                <option value="OFICIAL">Oficial (Perteneciente a Unidad Militar COEDE)</option>
              </select>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Número de Placa (Único) <span class="text-danger">*</span></label>
              <input type="text" name="plate" class="form-control text-uppercase font-monospace fw-bold" placeholder="B6A-912 o EP-12401" required>
            </div>
          </div>

          <!-- Campos de Personal Propietario (Autocompletado desde MySQL por DNI) -->
          <div id="particularOwnerSection" class="p-3 mb-3 rounded border border-secondary bg-dark bg-opacity-25">
            <div class="fw-bold text-warning small mb-2">
              <i class="bi bi-person-bounding-box"></i> Datos del Conductor / Responsable (Personal Interno COEDE)
            </div>
            
            <div class="row g-2 align-items-end mb-2">
              <div class="col-12 col-md-6">
                <label class="form-label">DNI del Responsable</label>
                <div class="input-group">
                  <input type="text" name="owner_dni" id="ownerDniInput" class="form-control" maxlength="8" placeholder="45892134">
                  <button type="button" id="btnLookupPersonnel" class="btn btn-coede-gold btn-sm">
                    <i class="bi bi-search"></i> Vincular DNI
                  </button>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">CIP / CIV</label>
                <input type="text" name="owner_cip_civ" id="ownerCipInput" class="form-control" placeholder="CIP-104921">
              </div>
            </div>

            <div class="mb-2">
              <label class="form-label">Apellidos y Nombres del Responsable</label>
              <input type="text" name="owner_name" id="ownerNameInput" class="form-control" placeholder="CRICRI_2026">
              <div id="lookupFeedback" class="small mt-1 text-muted"></div>
            </div>
          </div>

          <!-- Características del Vehículo -->
          <div class="row mb-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Marca <span class="text-danger">*</span></label>
              <input type="text" name="make" class="form-control" placeholder="Toyota, Hyundai, Kia..." required>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Modelo <span class="text-danger">*</span></label>
              <input type="text" name="model" class="form-control" placeholder="Hilux, Tucson, Sportage..." required>
            </div>
          </div>

          <!-- Dependencia -->
          <div class="mb-3">
            <label class="form-label">Dependencia / Unidad Responsable <span class="text-danger">*</span></label>
            <select name="unit_id" id="vehUnitSelect" class="form-select" required>
              <?php foreach ($units as $u): ?>
                <option value="<?= $u['id'] ?>"><?= e($u['name']) ?> (<?= e($u['code']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Fechas de Autorización -->
          <div class="row mb-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Inicio de Vigencia <span class="text-danger">*</span></label>
              <input type="date" name="auth_start" class="form-control" value="<?= date('Y-01-01') ?>" required>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Fin de Vigencia <span class="text-danger">*</span></label>
              <input type="date" name="auth_end" class="form-control" value="<?= date('Y-12-31') ?>" required>
            </div>
          </div>

          <!-- Notas -->
          <div class="mb-4">
            <label class="form-label">Observaciones / Detalles de Asignación</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Estacionamiento asignado, vehículo de servicio o comandancia."></textarea>
          </div>

          <div class="d-flex justify-content-end gap-2">
            <a href="<?= url('/vehicles') ?>" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-coede-gold px-4 fw-bold">
              <i class="bi bi-save"></i> Guardar Vehículo
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  const catSelect = document.getElementById('vehCategorySelect');
  const ownerSec = document.getElementById('particularOwnerSection');

  catSelect.addEventListener('change', function() {
    if (this.value === 'OFICIAL') {
      ownerSec.style.opacity = '0.5';
    } else {
      ownerSec.style.opacity = '1';
    }
  });

  // Autocompletado de personal por DNI desde MySQL
  document.getElementById('btnLookupPersonnel').addEventListener('click', function() {
    const dni = document.getElementById('ownerDniInput').value.trim();
    const feedback = document.getElementById('lookupFeedback');
    if (!dni || dni.length !== 8) {
      feedback.className = 'small mt-1 text-danger';
      feedback.textContent = 'Ingrese un DNI válido de 8 dígitos para consultar el padrón.';
      return;
    }

    feedback.className = 'small mt-1 text-info';
    feedback.textContent = 'Consultando padrón interno...';

    fetch(`<?= url('/vehicles/lookup-personnel') ?>?dni=${dni}`)
      .then(res => res.json())
      .then(data => {
        if (data.found) {
          document.getElementById('ownerCipInput').value = data.cip_civ || '';
          document.getElementById('ownerNameInput').value = data.full_name || '';
          document.getElementById('vehUnitSelect').value = data.unit_id;
          feedback.className = 'small mt-1 text-success';
          feedback.textContent = `✓ Vinculado con éxito: ${data.full_name} (${data.unit_name})`;
        } else {
          feedback.className = 'small mt-1 text-warning';
          feedback.textContent = data.message || 'Personal no encontrado. Puede ingresar los datos manualmente.';
        }
      })
      .catch(err => {
        feedback.className = 'small mt-1 text-danger';
        feedback.textContent = 'Error al consultar servidor.';
      });
  });
</script>

