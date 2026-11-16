<div class="row justify-content-center">
  <div class="col-12 col-lg-8">
    <div class="coede-card">
      <div class="coede-card-header">
        <h5 class="coede-card-title">
          <i class="bi bi-shield-plus"></i> Registro de Novedad / Incidencia en Guardia
        </h5>
        <a href="<?= url('/incidents') ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left"></i> Volver
        </a>
      </div>

      <div class="card-body p-4">
        
        <div class="alert alert-dark border-secondary small mb-4">
          <i class="bi bi-info-circle-fill text-warning"></i>
          El sistema asignará automáticamente un código consecutivo anual correlativo <strong>(ej. INC-2026-0002)</strong> garantizando unicidad en concurrencia. El operador no puede modificar ni borrar registros previos una vez asentados.
        </div>

        <form action="<?= url('/incidents/store') ?>" method="POST" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" id="webcamPhotoData" name="webcam_photo" value="">

          <!-- Tipo de Hecho -->
          <div class="mb-3">
            <label class="form-label">Tipo de Hecho / Novedad <span class="text-danger">*</span></label>
            <select name="event_type" class="form-select" required>
              <option value="Intento de ingreso no autorizado">Intento de ingreso no autorizado</option>
              <option value="Documento de autorización vencido" selected>Documento de autorización vencido</option>
              <option value="Vehículo sin registro o placa no coincidente">Vehículo sin registro o placa no coincidente</option>
              <option value="Negativa a identificarse o presentar credencial">Negativa a identificarse o presentar credencial</option>
              <option value="Alteración del orden en inmediaciones de guardia">Alteración del orden en inmediaciones de guardia</option>
              <option value="Falta de respeto a la autoridad de guardia">Falta de respeto a la autoridad de guardia</option>
              <option value="Porte de objetos u equipos no declarados">Porte de objetos u equipos no declarados</option>
              <option value="Incidente perimétrico o daño a propiedad">Incidente perimétrico o daño a propiedad</option>
              <option value="Otra novedad de seguridad">Otra novedad de seguridad</option>
            </select>
          </div>

          <!-- Referencias Opcionales -->
          <div class="row mb-3">
            <div class="col-12 col-md-6">
              <label class="form-label">DNI de Referencia (Sintético / Opcional)</label>
              <input type="text" name="ref_dni" class="form-control font-monospace" maxlength="8" placeholder="ej. 45892134" value="<?= e($refDni) ?>">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Placa de Referencia (Opcional)</label>
              <input type="text" name="ref_plate" class="form-control font-monospace text-uppercase" placeholder="ej. B6A-912" value="<?= e($refPlate) ?>">
            </div>
          </div>

          <!-- Descripción del Hecho -->
          <div class="mb-3">
            <label class="form-label">Descripción Detallada del Hecho <span class="text-danger">*</span></label>
            <textarea name="description" class="form-control" rows="3" required placeholder="Detalle exhaustivo de lo acontecido en la puerta principal..."></textarea>
          </div>

          <!-- Acción Adoptada -->
          <div class="mb-4">
            <label class="form-label">Acción Adoptada por la Guardia <span class="text-danger">*</span></label>
            <textarea name="action_taken" class="form-control" rows="2" required placeholder="Medidas inmediatas ejecutadas por el personal de servicio (retención, comunicación a jefatura, etc.)."></textarea>
          </div>

          <!-- Evidencia Opcional (Archivo o Cámara Web con Permiso) -->
          <div class="p-3 mb-4 rounded border border-secondary bg-dark bg-opacity-25">
            <div class="fw-bold text-warning small mb-3">
              <i class="bi bi-camera"></i> Evidencia Opcional (Archivo o Cámara del Dispositivo)
            </div>

            <!-- Pestañas para elegir método -->
            <ul class="nav nav-pills nav-fill mb-3" id="evidenceTabs" role="tablist">
              <li class="nav-item">
                <button class="nav-link active py-1 small" id="tabUpload" data-bs-toggle="pill" data-bs-target="#panelUpload" type="button">
                  <i class="bi bi-file-earmark-arrow-up"></i> Cargar Archivo
                </button>
              </li>
              <li class="nav-item">
                <button class="nav-link py-1 small" id="tabCamera" data-bs-toggle="pill" data-bs-target="#panelCamera" type="button">
                  <i class="bi bi-camera-video"></i> Capturar con Cámara
                </button>
              </li>
            </ul>

            <div class="tab-content">
              <!-- Panel Archivo -->
              <div class="tab-pane fade show active" id="panelUpload">
                <input type="file" name="evidence_file" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf">
                <div class="form-text text-muted small">Admite imágenes JPEG/PNG o documentos PDF hasta 8 MB. Archivo privado sin acceso público.</div>
              </div>

              <!-- Panel Cámara -->
              <div class="tab-pane fade text-center" id="panelCamera">
                <div class="mb-2">
                  <video id="webcamVideo" width="320" height="240" autoplay playsinline class="rounded border border-secondary bg-black" style="display: none;"></video>
                  <canvas id="webcamCanvas" width="320" height="240" style="display: none;"></canvas>
                  <img id="webcamPreview" src="" alt="Captura" class="rounded border border-warning" style="display: none; max-width: 320px;">
                </div>
                
                <div class="d-flex justify-content-center gap-2">
                  <button type="button" id="btnStartCamera" class="btn btn-outline-info btn-sm">
                    <i class="bi bi-camera-video"></i> Iniciar Cámara
                  </button>
                  <button type="button" id="btnCapturePhoto" class="btn btn-warning btn-sm" style="display: none;">
                    <i class="bi bi-record-circle"></i> Capturar Fotografía
                  </button>
                  <button type="button" id="btnRetakePhoto" class="btn btn-outline-secondary btn-sm" style="display: none;">
                    <i class="bi bi-arrow-repeat"></i> Repetir
                  </button>
                </div>
                <div id="cameraStatus" class="small text-muted mt-2">Requiere autorización explícita del navegador.</div>
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2">
            <a href="<?= url('/incidents') ?>" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-coede-gold px-4 fw-bold">
              <i class="bi bi-shield-check"></i> Asentar Incidencia en Bitácora
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>

<script>
  let videoStream = null;
  const video = document.getElementById('webcamVideo');
  const canvas = document.getElementById('webcamCanvas');
  const preview = document.getElementById('webcamPreview');
  const hiddenInput = document.getElementById('webcamPhotoData');
  const btnStart = document.getElementById('btnStartCamera');
  const btnCapture = document.getElementById('btnCapturePhoto');
  const btnRetake = document.getElementById('btnRetakePhoto');
  const camStatus = document.getElementById('cameraStatus');

  btnStart?.addEventListener('click', async () => {
    try {
      videoStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
      video.srcObject = videoStream;
      video.style.display = 'inline-block';
      preview.style.display = 'none';
      btnStart.style.display = 'none';
      btnCapture.style.display = 'inline-block';
      camStatus.textContent = 'Cámara conectada. Apunte al documento o evidencia y haga clic en Capturar.';
      camStatus.className = 'small text-success mt-2';
    } catch (err) {
      camStatus.textContent = 'No se pudo acceder a la cámara (verifique permisos o use la opción de cargar archivo).';
      camStatus.className = 'small text-danger mt-2';
    }
  });

  btnCapture?.addEventListener('click', () => {
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, 320, 240);
    const dataUrl = canvas.toDataURL('image/jpeg', 0.85);

    hiddenInput.value = dataUrl;
    preview.src = dataUrl;
    preview.style.display = 'inline-block';
    video.style.display = 'none';

    // Detener flujo de cámara
    if (videoStream) {
      videoStream.getTracks().forEach(t => t.stop());
    }

    btnCapture.style.display = 'none';
    btnRetake.style.display = 'inline-block';
    camStatus.textContent = '✓ Evidencia fotográfica capturada correctamente.';
  });

  btnRetake?.addEventListener('click', () => {
    hiddenInput.value = '';
    btnRetake.style.display = 'none';
    btnStart.click();
  });
</script>

