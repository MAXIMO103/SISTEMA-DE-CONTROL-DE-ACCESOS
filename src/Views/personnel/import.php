<div class="row justify-content-center">
  <div class="col-12 col-lg-8">
    <div class="coede-card">
      <div class="coede-card-header">
        <h5 class="coede-card-title">
          <i class="bi bi-file-earmark-excel-fill"></i> Importación Masiva de Padrón Excel
        </h5>
        <a href="<?= url('/personnel') ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left"></i> Volver al Padrón
        </a>
      </div>

      <div class="card-body p-4">
        
        <!-- Descarga de Plantillas Oficiales -->
        <div class="alert alert-dark border-warning p-3 mb-4">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
              <div class="fw-bold text-warning mb-1">
                <i class="bi bi-download"></i> Plantillas Oficiales de Excel COEDE
              </div>
              <div class="small text-muted">Descargue el formato correspondiente antes de realizar la carga.</div>
            </div>
            <div class="d-flex gap-2">
              <a href="<?= url('/personnel/template?type=militar') ?>" class="btn btn-coede-gold btn-sm">
                <i class="bi bi-filetype-xlsx"></i> Plantilla Militar
              </a>
              <a href="<?= url('/personnel/template?type=civil') ?>" class="btn btn-coede-olive btn-sm">
                <i class="bi bi-filetype-xlsx"></i> Plantilla Civil
              </a>
            </div>
          </div>
        </div>

        <!-- Formulario de Carga y Vista Previa -->
        <form action="<?= url('/personnel/import-preview') ?>" method="POST" enctype="multipart/form-data">
          <?= csrf_field() ?>

          <div class="mb-3">
            <label class="form-label">Tipo de Padrón a Cargar <span class="text-danger">*</span></label>
            <select name="category" class="form-select" required>
              <option value="MILITAR" selected>Personal Militar (Columnas: DNI, CIP, GRADO, APELLIDOS, NOMBRES, CODIGO_UNIDAD, INICIO_VIGENCIA, FIN_VIGENCIA)</option>
              <option value="CIVIL">Personal Civil (Columnas: DNI, CIV, APELLIDOS, NOMBRES, CODIGO_UNIDAD, INICIO_VIGENCIA, FIN_VIGENCIA)</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Dependencia / Unidad de Referencia <span class="text-danger">*</span></label>
            <select name="unit_id" class="form-select" required>
              <?php foreach ($units as $u): ?>
                <option value="<?= $u['id'] ?>"><?= e($u['name']) ?> (<?= e($u['code']) ?>)</option>
              <?php endforeach; ?>
            </select>
            <div class="form-text text-muted">Se comparará esta relación para detectar ausentes de la lista anterior (no se darán de baja automáticamente).</div>
          </div>

          <div class="mb-4">
            <label class="form-label">Archivo Excel (.xlsx / .xls) <span class="text-danger">*</span></label>
            <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
          </div>

          <div class="d-flex justify-content-end gap-2">
            <a href="<?= url('/personnel') ?>" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-coede-gold px-4 fw-bold">
              <i class="bi bi-search"></i> Previsualizar y Validar Archivo
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>

