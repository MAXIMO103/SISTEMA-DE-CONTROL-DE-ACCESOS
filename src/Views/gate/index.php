<?php
/**
 * SISTEMA WEB COEDE - Módulo de Control de Acceso (Puerta Principal)
 * Rediseño Minimalista, Automatizado y Táctico Militar
 */
?>

<style>
  /* Color de texto para etiquetas secundarias de Control de Puerta */
  .gate-cockpit .gate-sec-label,
  .gate-cockpit #btnResetForm,
  .gate-cockpit #dniCounter,
  .gate-cockpit #vigenciaLabel,
  .gate-cockpit #vigenciaDateText {
    color: #cbd5e1 !important;
    opacity: 1 !important;
  }
</style>

<div class="gate-cockpit">
  
  <!-- 1. Encabezado Institucional Militar con Reloj en Tiempo Real y Usuario -->
  <div class="gate-header-bar d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
      <div class="bg-dark p-2 rounded border border-warning d-none d-sm-block">
        <i class="bi bi-shield-fill-check fs-3" style="color: var(--coede-gold-light);"></i>
      </div>
      <div>
        <div class="d-flex align-items-center gap-2">
          <h5 class="fw-bold mb-0 text-white letter-spacing-1">
            CONTROL DE ACCESO MILITAR &bull; PUERTA PRINCIPAL
          </h5>
          <span class="badge bg-success bg-opacity-25 text-success border border-success small py-1">
            SISTEMA ACTIVO
          </span>
        </div>
      </div>
    </div>

    <!-- Centro: Reloj Militar Táctico en Tiempo Real -->
    <div class="tactical-clock" id="tacticalClock">
      <span class="live-dot" title="Tiempo Real Sincronizado"></span>
      <span id="clockDate" class="text-uppercase">CARGANDO...</span>
      <span class="text-secondary">|</span>
      <span id="clockTime" class="fs-5 text-white fw-bold">--:--:--</span>
      <span class="badge bg-secondary small py-0 px-1 text-dark fw-bold">LIMA</span>
    </div>

    <!-- Derecha: Operador Conectado y Botón Discreto de Incidencias -->
    <div class="d-flex align-items-center gap-3">
      <div class="text-end d-none d-md-block">
        <div class="small text-white fw-bold">
          <i class="bi bi-person-badge text-warning"></i> <?= e($user['name'] ?? 'CRICRI_2026') ?>
        </div>
        <div class="small text-muted" style="font-size: 0.72rem;">
          ROL: <span class="text-warning"><?= e($user['role_name'] ?? 'SERVICIO') ?></span>
        </div>
      </div>
      
      <!-- Botón Discreto de Registro de Incidencias -->
      <button type="button" id="btnOpenIncidentModal" class="btn btn-outline-warning btn-sm fw-bold px-3 py-2 shadow-sm">
        <i class="bi bi-shield-exclamation text-warning me-1"></i> Registrar Incidencia
      </button>
    </div>
  </div>

  <!-- 2. Área de Operaciones en Dos Columnas (100% Pantalla, Sin Desplazamientos Innecesarios) -->
  <div class="row g-3 flex-grow-1 align-items-stretch">
    
    <!-- ========================================== -->
    <!-- COLUMNA IZQUIERDA: IDENTIFICACIÓN Y REGISTRO -->
    <!-- ========================================== -->
    <div class="col-12 col-lg-5 d-flex flex-column">
      <div class="coede-card p-4 flex-grow-1 d-flex flex-column justify-content-between scanner-card" id="scannerCard">
        
        <form id="gateForm" autocomplete="off" class="d-flex flex-column justify-content-between h-100">
          <?= csrf_field() ?>
          
          <!-- Hidden inputs para lógica de seguridad y auditoría -->
          <input type="hidden" id="openMovementId" name="open_movement_id" value="">
          <input type="hidden" id="hasExitDeclaration" name="has_exit_declaration" value="0">
          <input type="hidden" id="declaredGate" name="declared_gate" value="">
          <input type="hidden" id="declaredTime" name="declared_time" value="">
          <input type="hidden" id="declaredObservation" name="declared_observation" value="">
          <input type="hidden" id="isVisitorInput" name="is_visitor" value="0">
          <input type="hidden" id="personnelIdHidden" value="">
          
          <!-- Campos de identidad sincronizados en segundo plano para el backend -->
          <input type="hidden" id="inputPersonName" name="person_name" value="">
          <input type="hidden" id="inputRankOrType" name="rank_or_type" value="">
          <input type="hidden" id="inputUnitName" name="unit_name" value="">
          
          <div>
            <!-- Selector Táctico de Flujo: ENTRADA / SALIDA -->
            <div class="flow-pill-group mb-3">
              <button type="button" id="btnFlowIn" class="flow-pill-btn active-in">
                <i class="bi bi-box-arrow-in-right fs-5"></i> ENTRADA
              </button>
              <button type="button" id="btnFlowOut" class="flow-pill-btn">
                <i class="bi bi-box-arrow-right fs-5"></i> SALIDA
              </button>
              <input type="hidden" name="movement_type" id="flowMovementType" value="ENTRADA">
            </div>

            <!-- Selector Sutil de Modalidad: Peatonal / Vehicular -->
            <div class="d-flex justify-content-between align-items-center mb-3 px-1">
              <span class="small fw-bold text-uppercase gate-sec-label" style="color: #cbd5e1; opacity: 1;">
                <i class="bi bi-toggles"></i> Modalidad de Acceso:
              </span>
              <div class="btn-group btn-group-sm" role="group">
                <input type="radio" class="btn-check" name="target_type" id="modePedestrian" value="PEATONAL" checked>
                <label class="btn btn-outline-secondary px-3 fw-bold" for="modePedestrian">
                  <i class="bi bi-person-walking"></i> Peatonal
                </label>
                <input type="radio" class="btn-check" name="target_type" id="modeVehicular" value="VEHICULAR">
                <label class="btn btn-outline-secondary px-3 fw-bold" for="modeVehicular">
                  <i class="bi bi-car-front-fill"></i> Vehicular
                </label>
              </div>
            </div>

            <!-- Entrada Principal de Identificación: DNI (Escáner o Teclado) -->
            <div class="mb-3 position-relative">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="inputDni" class="form-label mb-0 text-white fw-bold">
                  <i class="bi bi-upc-scan text-warning"></i> DNI DEL CIUDADANO / EFECTIVO
                </label>
                <button type="button" id="btnResetForm" class="btn btn-link btn-sm p-0 text-decoration-none" style="color: #cbd5e1; opacity: 1;" title="Limpiar y preparar para siguiente lectura">
                  <i class="bi bi-arrow-counterclockwise"></i> Limpiar
                </button>
              </div>

              <div class="position-relative">
                <input type="text" id="inputDni" name="dni" 
                       class="form-control input-dni-tactical" 
                       placeholder="••••••••" 
                       maxlength="8" 
                       pattern="[0-9]*"
                       inputmode="numeric"
                       autofocus 
                       autocomplete="off">
                <div id="scannerSpinner" class="position-absolute end-0 top-50 translate-middle-y me-3 d-none">
                  <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                </div>
              </div>
              <div class="d-flex justify-content-between small mt-1 px-1">
                <span class="gate-sec-label" style="color: #cbd5e1; opacity: 1;"><i class="bi bi-qr-code"></i> Lector USB o teclado</span>
                <span id="dniCounter" style="color: #cbd5e1; opacity: 1;">0 / 8 dígitos</span>
              </div>
            </div>

            <!-- Campo Dinámico: Placa Vehicular (Visible solo si se selecciona Vehicular) -->
            <div id="plateSection" class="dynamic-form-section">
              <div class="p-3 rounded border border-secondary bg-dark bg-opacity-50">
                <label for="inputPlate" class="form-label text-warning mb-1">
                  <i class="bi bi-car-front"></i> Placa del Vehículo
                </label>
                <input type="text" id="inputPlate" name="plate" 
                       class="form-control font-monospace text-uppercase fs-5 fw-bold text-center" 
                       placeholder="ej. B6A-912" 
                       maxlength="10" 
                       autocomplete="off">
                <div class="small text-muted mt-1" id="plateHelperText">
                  Se verificará autorización de ingreso vehicular institucional o particular.
                </div>
              </div>
            </div>

            <!-- Formulario Dinámico: Campos Exclusivos para VISITANTES -->
            <div id="visitorSection" class="dynamic-form-section">
              <div class="p-3 rounded border border-warning bg-dark bg-opacity-25">
                <div class="d-flex align-items-center justify-content-between text-warning small fw-bold mb-2">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-person-plus-fill"></i> DATOS DE VISITA (CONSULTA RENIEC)
                  </div>
                  <span id="reniecStatusPill" class="badge bg-info bg-opacity-25 text-info border border-info small" style="font-size: 0.68rem;">RENIEC</span>
                </div>

                <!-- Notificación y campo para ingreso manual si no se encuentra en RENIEC -->
                <div id="reniecManualNotice" class="d-none mb-2">
                  <div class="alert alert-warning py-1 px-2 small mb-2 d-flex align-items-center gap-2" style="font-size: 0.72rem;">
                    <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                    <span id="reniecManualNoticeText">DNI no encontrado en RENIEC. Ingrese los datos manualmente.</span>
                  </div>
                  <div class="mb-2">
                    <label class="form-label text-warning small fw-semibold mb-1" style="font-size: 0.72rem;">
                      Apellidos y Nombres (Ingreso Manual)
                    </label>
                    <input type="text" id="inputManualName" class="form-control form-control-sm text-uppercase fw-bold" placeholder="APELLIDOS Y NOMBRES COMPLETOS">
                  </div>
                </div>

                <div class="row g-2">
                  <div class="col-12 col-md-6">
                    <label class="form-label" style="font-size: 0.72rem;">Destino Interno COEDE</label>
                    <input type="text" id="inputDestination" name="destination" class="form-control form-control-sm" placeholder="ej. Jefatura / Pabellón B">
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label" style="font-size: 0.72rem;">Motivo de Visita</label>
                    <input type="text" id="inputObservation" name="observation" class="form-control form-control-sm" placeholder="ej. Trámite administrativo">
                  </div>
                </div>
              </div>
            </div>

            <!-- Formulario Dinámico: Autorización de Vehículo Especial / No Registrado -->
            <div id="specialAuthSection" class="dynamic-form-section">
              <div class="p-3 rounded border border-danger bg-dark bg-opacity-25">
                <div class="d-flex align-items-center gap-2 text-danger small fw-bold mb-2">
                  <i class="bi bi-shield-lock-fill"></i> AUTORIZACIÓN ESPECIAL DE SEGURIDAD
                </div>
                <div class="row g-2">
                  <div class="col-6">
                    <label class="form-label" style="font-size: 0.72rem;">Autoridad que Autoriza</label>
                    <input type="text" id="inputAuthority" name="authority_authorizer" class="form-control form-control-sm" placeholder="ej. Mayor Guardia">
                  </div>
                  <div class="col-6">
                    <label class="form-label" style="font-size: 0.72rem;">Medio</label>
                    <select id="inputAuthMedium" name="auth_medium" class="form-select form-select-sm">
                      <option value="Teléfono IP / Anexo">Teléfono IP / Anexo</option>
                      <option value="Radio de Guardia">Radio de Guardia</option>
                      <option value="Oficio / Memorándum">Oficio / Memorándum</option>
                      <option value="Pase Temporal Escrito">Pase Temporal Escrito</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <!-- BOTÓN PRINCIPAL INDISPENSABLE (Se eliminaron botones redundantes) -->
          <div class="mt-4 pt-2 border-top border-secondary">
            <button type="submit" id="btnSubmitMovement" class="btn-action-primary btn-action-in">
              <i class="bi bi-box-arrow-in-right"></i>
              <span id="btnActionLabel">Registrar Ingreso en Puerta</span>
            </button>
            <div class="text-center small text-muted mt-2">
              <span class="badge bg-dark text-muted border border-secondary px-2 py-1">
                <i class="bi bi-shield-check"></i> Procedimiento Militar Seguro &bull; Registro con Firma de Operador
              </span>
            </div>
          </div>

        </form>

      </div>
    </div>

    <!-- ========================================== -->
    <!-- COLUMNA DERECHA: RESULTADOS, FOTOGRAFÍA Y ESTADO -->
    <!-- ========================================== -->
    <div class="col-12 col-lg-7 d-flex flex-column">
      
      <!-- Gran Semáforo Lumínico de Estado -->
      <div id="gateBeacon" class="gate-beacon-indicator beacon-idle mb-3">
        <i class="bi bi-shield-shaded fs-3" id="beaconIcon"></i>
        <span id="beaconText">ESPERANDO IDENTIFICACIÓN (DNI O LECTOR)</span>
      </div>

      <!-- Tarjeta de Identidad Militar Oficial -->
      <div class="military-id-card p-4 flex-grow-1 d-flex flex-column justify-content-between">
        
        <div>
          <!-- Cabecera de la Ficha -->
          <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-secondary">
            <div class="d-flex align-items-center gap-2">
              <div class="coede-shield-badge-sm">
                <img src="<?= url('img/ESCUDO COEDE.jpeg') ?>" alt="Escudo COEDE" class="coede-shield-img">
              </div>
              <span class="small fw-bold text-uppercase letter-spacing-1 text-warning">
                FICHA DE IDENTIDAD Y CONTROL MILITAR
              </span>
            </div>
            <div id="cardStatusBadge">
              <span class="badge bg-secondary text-dark fw-bold">EN ESPERA</span>
            </div>
          </div>

          <!-- Cuerpo: Fotografía a la Izquierda y Datos Oficiales a la Derecha -->
          <div class="row g-4 align-items-center">
            
            <!-- Fotografía Oficial / Placeholder -->
            <div class="col-12 col-sm-auto text-center">
              <div class="military-photo-frame mx-auto" id="photoFrame">
                <div id="photoPlaceholder" class="personnel-photo-placeholder">
                  <i class="bi bi-person-bounding-box fs-1 d-block mb-1 text-secondary"></i>
                  <span class="text-muted">SIN CONSULTA</span>
                </div>
                <img id="photoImg" src="" alt="Fotografía Oficial" class="w-100 h-100 object-fit-cover rounded" style="display: none; width: 100%; height: 100%; object-fit: cover;">
                <div class="military-photo-ribbon" id="photoRibbon">
                  EJÉRCITO DEL PERÚ
                </div>
              </div>

              <!-- Botón discreto para solicitar fotografía si está pendiente -->
              <button type="button" id="btnRequestPhotoAction" class="btn btn-outline-warning btn-sm mt-2 d-none">
                <i class="bi bi-camera"></i> Solicitar Fotografía
              </button>
            </div>

            <!-- Datos Personales Extraídos de MySQL o Adaptador -->
            <div class="col-12 col-sm">
              
              <!-- Nombres y Apellidos -->
              <div class="mb-3">
                <div class="small text-uppercase fw-semibold gate-sec-label" style="font-size: 0.72rem; color: #cbd5e1; opacity: 1;">
                  Apellidos y Nombres
                </div>
                <h4 class="fw-bold text-white mb-0" id="cardPersonName">
                  ---
                </h4>
              </div>

              <!-- Grado / Condición y CIP/CIV -->
              <div class="row g-2 mb-3">
                <div class="col-6">
                  <div class="small text-uppercase fw-semibold gate-sec-label" style="font-size: 0.72rem; color: #cbd5e1; opacity: 1;">
                    Grado / Condición
                  </div>
                  <div class="fw-bold text-warning fs-6" id="cardRank">
                    ---
                  </div>
                </div>
                <div class="col-6">
                  <div class="small text-uppercase fw-semibold gate-sec-label" style="font-size: 0.72rem; color: #cbd5e1; opacity: 1;">
                    Identificador (CIP / CIV)
                  </div>
                  <div class="font-monospace fw-bold text-white fs-6" id="cardCipCiv">
                    ---
                  </div>
                </div>
              </div>

              <!-- Dependencia / Unidad y DNI -->
              <div class="row g-2 mb-3">
                <div class="col-7">
                  <div class="small text-uppercase fw-semibold gate-sec-label" style="font-size: 0.72rem; color: #cbd5e1; opacity: 1;">
                    Unidad / Dependencia
                  </div>
                  <div class="fw-semibold text-white small" id="cardUnit">
                    ---
                  </div>
                </div>
                <div class="col-5">
                  <div class="small text-uppercase fw-semibold gate-sec-label" style="font-size: 0.72rem; color: #cbd5e1; opacity: 1;">
                    DNI Confirmado
                  </div>
                  <div class="font-monospace fw-bold text-white fs-6" id="cardDni">
                    --------
                  </div>
                </div>
              </div>

              <!-- Barra de Vigencia de Autorización -->
              <div class="p-2 rounded bg-dark bg-opacity-75 border border-secondary" id="vigenciaContainer">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="small fw-bold" id="vigenciaLabel" style="font-size: 0.72rem; color: #cbd5e1; opacity: 1;">ESTADO DE VIGENCIA:</span>
                  <span id="vigenciaTextBadge" class="badge bg-secondary text-dark">No consultado</span>
                </div>
                <div class="small" id="vigenciaDateText" style="font-size: 0.75rem; color: #cbd5e1; opacity: 1;">
                  Esperando ingreso de credencial militar o documento nacional.
                </div>
              </div>

            </div>

          </div>
        </div>

        <!-- Alertas y Notificaciones en Tiempo Real -->
        <div id="messagesContainer" class="mt-3">
          <div class="alert alert-dark text-muted small mb-0 text-center py-2 border-secondary">
            <i class="bi bi-info-circle me-1"></i> Digite el DNI o utilice el lector de código de barras para verificación automática instantánea.
          </div>
        </div>

      </div>

    </div>

  </div>

  <!-- 3. Panel Colapsable de Movimientos Recientes (Para no sobrecargar la pantalla principal) -->
  <div class="mt-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <button class="btn btn-sm btn-outline-secondary text-muted" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRecentMovements">
        <i class="bi bi-clock-history me-1"></i> Ver últimos movimientos de guardia (<?= count($recentMovements) ?>)
      </button>
      <span class="small text-muted">Puerta Principal &bull; Registro Continuo</span>
    </div>

    <div class="collapse" id="collapseRecentMovements">
      <div class="coede-card overflow-hidden">
        <div class="table-responsive" style="max-height: 220px;">
          <table class="table coede-table align-middle mb-0">
            <thead>
              <tr>
                <th>Hora</th>
                <th>Flujo</th>
                <th>Modalidad</th>
                <th>Condición</th>
                <th>DNI</th>
                <th>Personal / Visitante</th>
                <th>Placa</th>
                <th>Destino</th>
                <th>Operador</th>
              </tr>
            </thead>
            <tbody id="recentMovementsTbody">
              <?php foreach ($recentMovements as $rm): ?>
                <tr>
                  <td class="small font-monospace"><?= date('H:i:s', strtotime($rm['recorded_at'])) ?></td>
                  <td>
                    <span class="badge <?= $rm['movement_type'] === 'ENTRADA' ? 'bg-success' : 'bg-primary' ?>">
                      <?= e($rm['movement_type']) ?>
                    </span>
                  </td>
                  <td class="small"><?= e($rm['target_type']) ?></td>
                  <td>
                    <span class="badge <?= $rm['is_visitor'] ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                      <?= $rm['is_visitor'] ? 'Visitante' : 'Personal' ?>
                    </span>
                  </td>
                  <td class="font-monospace fw-bold"><?= e($rm['dni']) ?></td>
                  <td class="small fw-semibold text-white"><?= e($rm['person_name']) ?></td>
                  <td class="font-monospace text-warning"><?= e($rm['plate'] ?? '-') ?></td>
                  <td class="small text-muted"><?= e($rm['destination'] ?? '-') ?></td>
                  <td class="small text-muted"><?= e($rm['operator_name'] ?? $rm['operator_username'] ?? 'Sistema') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- ============================================================== -->
<!-- MODAL DE INCIDENCIAS RÁPIDAS CON CÁMARA Y ARCHIVOS (SWEET/BS5) -->
<!-- ============================================================== -->
<div class="modal fade" id="incidentModal" tabindex="-1" aria-labelledby="incidentModalLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content text-white" style="background-color: var(--coede-surface-elevated); border: 2px solid var(--coede-gold);">
      
      <div class="modal-header border-bottom border-warning py-3">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-shield-exclamation text-warning fs-4"></i>
          <h5 class="modal-title text-warning fw-bold mb-0" id="incidentModalLabel">
            REGISTRAR INCIDENCIA DE SEGURIDAD &bull; PUERTA PRINCIPAL
          </h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" id="btnCloseIncidentModal"></button>
      </div>

      <form id="incidentQuickForm" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" id="incWebcamPhoto" name="webcam_photo" value="">

        <div class="modal-body p-4">
          <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
              <label class="form-label text-warning">Tipo de Hecho / Suceso <span class="text-danger">*</span></label>
              <select class="form-select" id="incEventType" name="event_type" required>
                <option value="">Seleccione tipo de incidencia...</option>
                <option value="Intento de ingreso con credencial no autorizada">Intento de ingreso con credencial no autorizada</option>
                <option value="Vehículo no registrado o sospechoso en perímetro">Vehículo no registrado o sospechoso en perímetro</option>
                <option value="Evasión de control de guardia / Salida no constatada">Evasión de control de guardia / Salida no constatada</option>
                <option value="Altercado o falta de respeto a la autoridad de guardia">Altercado o falta de respeto a la autoridad de guardia</option>
                <option value="Documento nacional o CIP presuntamente alterado">Documento nacional o CIP presuntamente alterado</option>
                <option value="Negativa a identificarse o registrarse">Negativa a identificarse o registrarse</option>
                <option value="Incumplimiento de normas de seguridad de guarnición">Incumplimiento de normas de seguridad de guarnición</option>
                <option value="Otro suceso relevante">Otro suceso relevante</option>
              </select>
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label text-warning">Nivel de Gravedad <span class="text-danger">*</span></label>
              <select class="form-select" id="incSeverity" name="severity">
                <option value="BAJA">Baja (Observación de rutina)</option>
                <option value="MEDIA" selected>Media (Intervención de guardia)</option>
                <option value="ALTA">Alta (Retención / Comunicación a Jefatura)</option>
                <option value="CRITICA">Crítica (Alerta general de guarnición)</option>
              </select>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label">DNI de Referencia (Si aplica)</label>
              <input type="text" id="incRefDni" name="ref_dni" class="form-control font-monospace" placeholder="DNI involucrado..." maxlength="8">
            </div>
            <div class="col-6">
              <label class="form-label">Placa de Referencia (Si aplica)</label>
              <input type="text" id="incRefPlate" name="ref_plate" class="form-control font-monospace text-uppercase" placeholder="Placa involucrada...">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label text-warning">Descripción Circunstanciada de los Hechos <span class="text-danger">*</span></label>
            <textarea id="incDescription" name="description" class="form-control" rows="3" required placeholder="Detalle qué ocurrió, quién intervino, disposición adoptada..."></textarea>
          </div>

          <!-- Pestañas de Evidencia: Carga de Archivo o Captura con Cámara Web -->
          <div class="p-3 rounded border border-secondary bg-dark bg-opacity-50">
            <label class="form-label text-white fw-bold mb-2">
              <i class="bi bi-camera-fill text-warning"></i> Adjuntar Evidencia Fotográfica (Opcional)
            </label>

            <ul class="nav nav-pills nav-fill mb-3" id="evidenceTabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active py-2" id="fileTab" data-bs-toggle="pill" data-bs-target="#fileTabPane" type="button" role="tab">
                  <i class="bi bi-folder2-open"></i> Archivo desde Disco
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link py-2" id="webcamTab" data-bs-toggle="pill" data-bs-target="#webcamTabPane" type="button" role="tab">
                  <i class="bi bi-webcam"></i> Cámara del Puesto
                </button>
              </li>
            </ul>

            <div class="tab-content" id="evidenceTabContent">
              <!-- Carga de Archivo -->
              <div class="tab-pane fade show active" id="fileTabPane" role="tabpanel">
                <input type="file" id="incEvidenceFile" name="evidence_file" class="form-control" accept="image/jpeg,image/png">
                <div class="small text-muted mt-1">Archivos JPG o PNG permitidos. Máximo 10 MB.</div>
              </div>

              <!-- Captura con Cámara Web en Vivo -->
              <div class="tab-pane fade text-center" id="webcamTabPane" role="tabpanel">
                <div class="position-relative d-inline-block bg-black rounded overflow-hidden mb-2" style="max-width: 380px; width: 100%; min-height: 220px;">
                  <video id="webcamVideo" autoplay playsinline style="width: 100%; display: none;"></video>
                  <canvas id="webcamCanvas" style="display: none;"></canvas>
                  <img id="webcamPreview" src="" alt="Captura de cámara" style="width: 100%; display: none;">
                  <div id="webcamPrompt" class="py-5 text-muted small">
                    <i class="bi bi-camera-video fs-1 d-block mb-2"></i>
                    Haga clic en <strong>Iniciar Cámara</strong> para activar el lente del puesto.
                  </div>
                </div>
                <div>
                  <button type="button" id="btnStartWebcam" class="btn btn-sm btn-outline-info me-2">
                    <i class="bi bi-power"></i> Iniciar Cámara
                  </button>
                  <button type="button" id="btnSnapWebcam" class="btn btn-sm btn-warning me-2" style="display: none;">
                    <i class="bi bi-camera"></i> Tomar Fotografía
                  </button>
                  <button type="button" id="btnRetakeWebcam" class="btn btn-sm btn-outline-secondary" style="display: none;">
                    <i class="bi bi-arrow-repeat"></i> Repetir Foto
                  </button>
                </div>
              </div>
            </div>

          </div>

        </div>

        <div class="modal-footer border-top border-secondary py-3">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" id="btnSaveIncident" class="btn btn-warning fw-bold px-4">
            <i class="bi bi-check-circle-fill"></i> Registrar y Elevar Incidencia
          </button>
        </div>

      </form>

    </div>
  </div>
</div>

<!-- Modal para Solicitud Administrativa de Fotografía -->
<div class="modal fade" id="requestPhotoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-white" style="background-color: var(--coede-surface-elevated); border: 1px solid var(--coede-border);">
      <div class="modal-header border-bottom border-secondary">
        <h5 class="modal-title text-warning">
          <i class="bi bi-camera"></i> Solicitar Fotografía Oficial
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="small text-muted">
          Esta acción enviará una notificación a la bandeja del Administrador para que digitalice la fotografía oficial de este trabajador.
        </p>
        <div class="mb-3">
          <label class="form-label">Nota o Motivo</label>
          <textarea id="photoReqNote" class="form-control" rows="2" placeholder="Se solicitó al efectivo que regularice su fotografía en personal..."></textarea>
        </div>
      </div>
      <div class="modal-footer border-top border-secondary">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" id="btnSendPhotoRequest" class="btn btn-coede-gold">Enviar Solicitud</button>
      </div>
    </div>
  </div>
</div>

<script src="<?= url('assets/js/gate-control.js') ?>"></script>
