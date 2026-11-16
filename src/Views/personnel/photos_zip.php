<div class="row justify-content-center">
  <div class="col-12 col-lg-8">
    <div class="coede-card">
      <div class="coede-card-header">
        <h5 class="coede-card-title">
          <i class="bi bi-images"></i> Carga Masiva de Fotografías de Personal (ZIP)
        </h5>
        <a href="<?= url('/personnel') ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left"></i> Volver al Padrón
        </a>
      </div>

      <div class="card-body p-4">
        
        <div class="alert alert-dark border-warning p-3 mb-4">
          <h6 class="fw-bold text-warning mb-2">
            <i class="bi bi-shield-lock-fill"></i> Protocolo de Seguridad para Fotografías Institucionales
          </h6>
          <ul class="small text-muted mb-0 ps-3">
            <li>Los archivos contenidos en el ZIP deben estar nombrados exactamente con el DNI de 8 dígitos (ejemplo: <code>45892134.jpg</code>).</li>
            <li>Formato admitido: <strong>JPEG / JPG</strong> únicamente. Máximo 4 MB por fotografía.</li>
            <li>El sistema valida la cabecera binaria real (MIME) y previene Directory Traversal y ZIP bombs (máximo 500 fotos por paquete).</li>
            <li>Las fotografías se almacenan en un directorio privado fuera del directorio público y <strong>no tienen URL pública</strong>.</li>
            <li>No se aplica reconocimiento facial. La consulta es exclusivamente relacional por DNI.</li>
          </ul>
        </div>

        <form action="<?= url('/personnel/photos-zip-preview') ?>" method="POST" enctype="multipart/form-data">
          <?= csrf_field() ?>

          <div class="mb-4">
            <label class="form-label">Paquete ZIP de Fotografías (.zip) <span class="text-danger">*</span></label>
            <input type="file" name="zip_file" class="form-control" accept=".zip" required>
          </div>

          <div class="d-flex justify-content-end gap-2">
            <a href="<?= url('/personnel') ?>" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-coede-gold px-4 fw-bold">
              <i class="bi bi-search"></i> Previsualizar Coincidencias en ZIP
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>

