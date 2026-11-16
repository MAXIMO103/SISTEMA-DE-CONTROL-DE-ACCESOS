<div class="row justify-content-center">
  <div class="col-12 col-lg-8">
    <div class="coede-card">
      <div class="coede-card-header">
        <h5 class="coede-card-title">
          <i class="bi bi-person-plus-fill"></i> Registro Individual de Personal
        </h5>
        <a href="<?= url('/personnel') ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left"></i> Volver al Padrón
        </a>
      </div>

      <div class="card-body p-4">
        <form action="<?= url('/personnel/store') ?>" method="POST" enctype="multipart/form-data">
          <?= csrf_field() ?>

          <!-- Categoría -->
          <div class="row mb-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Categoría Institucional <span class="text-danger">*</span></label>
              <select name="category" id="categorySelect" class="form-select" required>
                <option value="MILITAR" selected>Personal Militar</option>
                <option value="CIVIL">Personal Civil</option>
              </select>
            </div>
            <div class="col-12 col-md-6" id="rankGroup">
              <label class="form-label">Grado Militar <span class="text-danger">*</span></label>
              <select name="rank_grade" id="rankGrade" class="form-select">
                <option value="General de División">General de División</option>
                <option value="General de Brigada">General de Brigada</option>
                <option value="Coronel" selected>Coronel</option>
                <option value="Teniente Coronel">Teniente Coronel</option>
                <option value="Mayor">Mayor</option>
                <option value="Capitán">Capitán</option>
                <option value="Teniente">Teniente</option>
                <option value="Subteniente / Alférez">Subteniente / Alférez</option>
                <option value="Técnico Jefe Superior">Técnico Jefe Superior</option>
                <option value="Técnico Jefe">Técnico Jefe</option>
                <option value="Técnico 1ra">Técnico 1ra</option>
                <option value="Técnico 2da">Técnico 2da</option>
                <option value="Técnico 3ra">Técnico 3ra</option>
                <option value="Suboficial 1ra">Suboficial 1ra</option>
                <option value="Suboficial 2da">Suboficial 2da</option>
                <option value="Suboficial 3ra">Suboficial 3ra</option>
                <option value="Cadete">Cadete</option>
                <option value="Alumno">Alumno</option>
              </select>
            </div>
          </div>

          <!-- Identificadores -->
          <div class="row mb-3">
            <div class="col-12 col-md-6">
              <label class="form-label">DNI (8 Dígitos) <span class="text-danger">*</span></label>
              <input type="text" name="dni" class="form-control" maxlength="8" pattern="\d{8}" placeholder="08928374" required>
              <div class="form-text text-muted">Se valida como texto de 8 dígitos preservando ceros iniciales.</div>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label" id="cipCivLabel">CIP Militar</label>
              <input type="text" name="cip_civ" class="form-control" placeholder="CIP-104921 o CIV-0482">
            </div>
          </div>

          <!-- Nombres -->
          <div class="row mb-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Apellidos Completos <span class="text-danger">*</span></label>
              <input type="text" name="surnames" class="form-control" placeholder="CRICRI_2026" required>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Nombres Completos <span class="text-danger">*</span></label>
              <input type="text" name="names" class="form-control" placeholder="CRICRI_2026" required>
            </div>
          </div>

          <!-- Dependencia -->
          <div class="mb-3">
            <label class="form-label">Dependencia / Unidad de Destino <span class="text-danger">*</span></label>
            <select name="unit_id" class="form-select" required>
              <?php foreach ($units as $u): ?>
                <option value="<?= $u['id'] ?>"><?= e($u['name']) ?> (<?= e($u['code']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Fechas de Vigencia -->
          <div class="row mb-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Inicio de Autorización <span class="text-danger">*</span></label>
              <input type="date" name="auth_start" class="form-control" value="<?= date('Y-01-01') ?>" required>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Fin de Autorización <span class="text-danger">*</span></label>
              <input type="date" name="auth_end" class="form-control" value="<?= date('Y-12-31') ?>" required>
            </div>
          </div>

          <!-- Fotografía Opcional -->
          <div class="mb-3">
            <label class="form-label">Fotografía del Trabajador (Opcional - JPEG hasta 4MB)</label>
            <input type="file" name="photo" class="form-control" accept="image/jpeg,image/jpg">
            <div class="form-text text-muted">Si no se adjunta foto, el sistema la marcará como 'FOTOGRAFÍA PENDIENTE'. Almacenada en directorio privado fuera de la web.</div>
          </div>

          <!-- Notas -->
          <div class="mb-4">
            <label class="form-label">Observaciones / Notas de Registro</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Detalles de autorización de comando, resolución o contrato CAS."></textarea>
          </div>

          <div class="d-flex justify-content-end gap-2">
            <a href="<?= url('/personnel') ?>" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-coede-gold px-4 fw-bold">
              <i class="bi bi-save"></i> Guardar en Padrón
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  document.getElementById('categorySelect').addEventListener('change', function() {
    const isMilitar = this.value === 'MILITAR';
    const rankGroup = document.getElementById('rankGroup');
    const cipCivLabel = document.getElementById('cipCivLabel');
    if (isMilitar) {
      rankGroup.style.display = 'block';
      cipCivLabel.textContent = 'CIP Militar';
    } else {
      rankGroup.style.display = 'none';
      cipCivLabel.textContent = 'Identificador Civil (CIV)';
    }
  });
</script>

