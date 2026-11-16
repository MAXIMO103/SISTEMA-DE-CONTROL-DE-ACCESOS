/**
 * SISTEMA WEB COEDE - Control de Acceso Militar (Puerta Principal)
 * Lógica UI/UX Minimalista, Táctica y Automatizada
 */

document.addEventListener('DOMContentLoaded', () => {

  // ==========================================
  // 1. RELOJ TÁCTICO MILITAR EN TIEMPO REAL
  // ==========================================
  const clockDate = document.getElementById('clockDate');
  const clockTime = document.getElementById('clockTime');

  const meses = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];

  function updateTacticalClock() {
    const now = new Date();
    const day = String(now.getDate()).padStart(2, '0');
    const month = meses[now.getMonth()];
    const year = now.getFullYear();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');

    if (clockDate) clockDate.textContent = `${day} ${month} ${year}`;
    if (clockTime) clockTime.textContent = `${hours}:${minutes}:${seconds}`;
  }
  updateTacticalClock();
  setInterval(updateTacticalClock, 1000);

  // ==========================================
  // 2. REFERENCIAS A ELEMENTOS DEL DOM
  // ==========================================
  const scannerCard = document.getElementById('scannerCard');
  const inputDni = document.getElementById('inputDni');
  const dniCounter = document.getElementById('dniCounter');
  const scannerSpinner = document.getElementById('scannerSpinner');
  const btnResetForm = document.getElementById('btnResetForm');

  const btnFlowIn = document.getElementById('btnFlowIn');
  const btnFlowOut = document.getElementById('btnFlowOut');
  const flowMovementType = document.getElementById('flowMovementType');
  const btnSubmitMovement = document.getElementById('btnSubmitMovement');
  const btnActionLabel = document.getElementById('btnActionLabel');

  const modePedestrian = document.getElementById('modePedestrian');
  const modeVehicular = document.getElementById('modeVehicular');
  const plateSection = document.getElementById('plateSection');
  const inputPlate = document.getElementById('inputPlate');

  const visitorSection = document.getElementById('visitorSection');
  const reniecManualNotice = document.getElementById('reniecManualNotice');
  const inputManualName = document.getElementById('inputManualName');
  const specialAuthSection = document.getElementById('specialAuthSection');

  const gateBeacon = document.getElementById('gateBeacon');
  const beaconIcon = document.getElementById('beaconIcon');
  const beaconText = document.getElementById('beaconText');

  // Tarjeta de Identidad
  const cardPersonName = document.getElementById('cardPersonName');
  const cardRank = document.getElementById('cardRank');
  const cardCipCiv = document.getElementById('cardCipCiv');
  const cardUnit = document.getElementById('cardUnit');
  const cardDni = document.getElementById('cardDni');
  const cardStatusBadge = document.getElementById('cardStatusBadge');
  const photoPlaceholder = document.getElementById('photoPlaceholder');
  const photoImg = document.getElementById('photoImg');
  const photoRibbon = document.getElementById('photoRibbon');
  const btnRequestPhotoAction = document.getElementById('btnRequestPhotoAction');
  const vigenciaTextBadge = document.getElementById('vigenciaTextBadge');
  const vigenciaDateText = document.getElementById('vigenciaDateText');
  const messagesContainer = document.getElementById('messagesContainer');

  // Campos ocultos de sincronización con backend
  const gateForm = document.getElementById('gateForm');
  const inputPersonName = document.getElementById('inputPersonName');
  const inputRankOrType = document.getElementById('inputRankOrType');
  const inputUnitName = document.getElementById('inputUnitName');
  const isVisitorInput = document.getElementById('isVisitorInput');
  const personnelIdHidden = document.getElementById('personnelIdHidden');
  const openMovementId = document.getElementById('openMovementId');
  const hasExitDeclaration = document.getElementById('hasExitDeclaration');
  const declaredGate = document.getElementById('declaredGate');
  const declaredTime = document.getElementById('declaredTime');
  const declaredObservation = document.getElementById('declaredObservation');

  const recentMovementsTbody = document.getElementById('recentMovementsTbody');
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  // Configuración base de SweetAlert2 Militar
  const CoedeSwal = typeof Swal !== 'undefined' ? Swal.mixin({
    customClass: {
      popup: 'coede-swal',
      confirmButton: 'btn-coede-swal-confirm',
      cancelButton: 'btn-coede-swal-cancel'
    },
    buttonsStyling: false,
    backdrop: `rgba(12, 16, 13, 0.85)`
  }) : null;

  // Helper para resolver URLs relativas en subcarpetas (ej: /coede_beta/)
  function getAppUrl(path) {
    if (window.COEDE && typeof window.COEDE.url === 'function') {
      return window.COEDE.url(path);
    }
    const metaBase = document.querySelector('meta[name="base-url"]')?.getAttribute('content');
    const base = (metaBase !== null && metaBase !== undefined) ? metaBase.replace(/\/+$/, '') : '';
    const cleanPath = String(path || '').replace(/^\/+/, '');
    return base ? `${base}/${cleanPath}` : `/${cleanPath}`;
  }

  // ==========================================
  // 3. SELECTOR TÁCTICO DE FLUJO (ENTRADA/SALIDA)
  // ==========================================
  function setFlow(flow) {
    if (flow === 'ENTRADA') {
      flowMovementType.value = 'ENTRADA';
      btnFlowIn.className = 'flow-pill-btn active-in';
      btnFlowOut.className = 'flow-pill-btn';
      btnSubmitMovement.className = 'btn-action-primary btn-action-in';
      btnActionLabel.textContent = 'Registrar Ingreso en Puerta';
    } else {
      flowMovementType.value = 'SALIDA';
      btnFlowOut.className = 'flow-pill-btn active-out';
      btnFlowIn.className = 'flow-pill-btn';
      btnSubmitMovement.className = 'btn-action-primary btn-action-out';
      btnActionLabel.textContent = 'Registrar Salida en Puerta';
    }
  }

  btnFlowIn?.addEventListener('click', () => setFlow('ENTRADA'));
  btnFlowOut?.addEventListener('click', () => setFlow('SALIDA'));

  // ==========================================
  // 4. MODALIDAD PEATONAL / VEHICULAR
  // ==========================================
  function updateModalidad() {
    const isVeh = modeVehicular.checked;
    if (isVeh) {
      plateSection.classList.add('show');
      inputPlate.focus();
    } else {
      plateSection.classList.remove('show');
      inputPlate.value = '';
      specialAuthSection.classList.remove('show');
      inputDni.focus();
    }
  }

  modePedestrian?.addEventListener('change', updateModalidad);
  modeVehicular?.addEventListener('change', updateModalidad);

  // ==========================================
  // 5. ENTRADA DNI AUTOMATIZADA (LECTOR USB / TECLADO)
  // ==========================================
  let lookupTimeout = null;

  inputDni?.addEventListener('input', (e) => {
    // Permitir sólo dígitos
    inputDni.value = inputDni.value.replace(/[^0-9]/g, '');
    const len = inputDni.value.length;
    if (dniCounter) dniCounter.textContent = `${len} / 8 dígitos`;

    if (len === 8) {
      clearTimeout(lookupTimeout);
      executeLookup();
    }
  });

  inputDni?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      clearTimeout(lookupTimeout);
      executeLookup();
    }
  });

  inputPlate?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      executeLookup();
    }
  });

  inputManualName?.addEventListener('input', () => {
    const val = inputManualName.value.toUpperCase();
    inputPersonName.value = val;
    cardPersonName.textContent = val || 'DATOS PENDIENTES';
  });

  btnResetForm?.addEventListener('click', resetGateForm);

  // ==========================================
  // 6. EJECUTAR CONSULTA RÁPIDA (AJAX)
  // ==========================================
  async function executeLookup() {
    const dni = inputDni.value.trim();
    const plate = inputPlate.value.trim();

    if (!dni && !plate) return;

    // Efecto visual de escaneo láser
    scannerCard.classList.add('scanning');
    if (scannerSpinner) scannerSpinner.classList.remove('d-none');
    messagesContainer.innerHTML = `
      <div class="alert alert-dark text-warning small mb-0 py-2 border-warning text-center">
        <span class="spinner-border spinner-border-sm me-2"></span> Verificando registros institucionales y antecedentes...
      </div>
    `;

    try {
      const url = getAppUrl(`api/gate/lookup?dni=${encodeURIComponent(dni)}&plate=${encodeURIComponent(plate)}`);
      const res = await fetch(url, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });
      const data = await res.json();

      scannerCard.classList.remove('scanning');
      if (scannerSpinner) scannerSpinner.classList.add('d-none');

      if (!res.ok) {
        messagesContainer.innerHTML = `<div class="alert alert-danger small py-2 mb-0">${data.error || 'Error al consultar.'}</div>`;
        return;
      }

      applyLookupResults(data);

    } catch (err) {
      scannerCard.classList.remove('scanning');
      if (scannerSpinner) scannerSpinner.classList.add('d-none');
      messagesContainer.innerHTML = `
        <div class="alert alert-danger small py-2 mb-0">
          <i class="bi bi-wifi-off me-1"></i> Error de conexión con el servidor de base de datos.
        </div>
      `;
    }
  }

  // ==========================================
  // GESTIÓN DINÁMICA DE FOTOGRAFÍA DE EFECTIVO
  // ==========================================
  function renderEfectivoPhoto(dni, backendPhotoUrl) {
    const cleanDni = String(dni || '').trim();
    if (!cleanDni) {
      showPhotoReniecFallback();
      return;
    }

    // Ruta pública principal: /img/efectivo/{dni}.jpg
    const primaryUrl = getAppUrl(`img/efectivo/${encodeURIComponent(cleanDni)}.jpg`);
    const fallbackUrl = backendPhotoUrl && backendPhotoUrl !== primaryUrl ? backendPhotoUrl : null;

    const imgProbe = new Image();
    imgProbe.onload = function() {
      photoPlaceholder.style.display = 'none';
      photoImg.src = primaryUrl;
      photoImg.className = 'w-100 h-100 object-fit-cover rounded';
      photoImg.style.display = 'block';
      photoImg.style.width = '100%';
      photoImg.style.height = '100%';
      photoImg.style.objectFit = 'cover';
      btnRequestPhotoAction?.classList.add('d-none');
    };
    imgProbe.onerror = function() {
      if (fallbackUrl) {
        const secondaryProbe = new Image();
        secondaryProbe.onload = function() {
          photoPlaceholder.style.display = 'none';
          photoImg.src = fallbackUrl;
          photoImg.className = 'w-100 h-100 object-fit-cover rounded';
          photoImg.style.display = 'block';
          photoImg.style.width = '100%';
          photoImg.style.height = '100%';
          photoImg.style.objectFit = 'cover';
          btnRequestPhotoAction?.classList.add('d-none');
        };
        secondaryProbe.onerror = function() {
          showPhotoReniecFallback();
        };
        secondaryProbe.src = fallbackUrl;
      } else {
        showPhotoReniecFallback();
      }
    };
    imgProbe.src = primaryUrl;
  }

  function showPhotoReniecFallback() {
    photoImg.style.display = 'none';
    photoPlaceholder.style.display = 'block';
    photoPlaceholder.innerHTML = `
      <i class="bi bi-person-badge fs-2 d-block text-info mb-1"></i>
      <span class="text-info fw-bold small">CONSULTA RENIEC</span>
    `;
    btnRequestPhotoAction?.classList.add('d-none');
  }

  // ==========================================
  // 7. APLICAR RESULTADOS EN LA FICHA MILITAR
  // ==========================================
  function applyLookupResults(data) {
    const dni = inputDni.value.trim();

    // 1. Semáforo Lumínico
    updateBeacon(data);

    // 2. Personal Registrado en MySQL
    if (data.personnel) {
      const p = data.personnel;
      cardPersonName.textContent = `${p.surnames}, ${p.names}`;
      inputPersonName.value = `${p.surnames}, ${p.names}`;

      const rankStr = p.category === 'MILITAR' ? (p.rank_grade || 'Militar') : 'Personal Civil';
      cardRank.textContent = rankStr;
      inputRankOrType.value = rankStr;

      const cipStr = p.cip_or_civ ? `${p.category === 'MILITAR' ? 'CIP' : 'CIV'}: ${p.cip_or_civ}` : 'NO REGISTRADO';
      cardCipCiv.textContent = cipStr;

      cardUnit.textContent = p.unit_name || 'COEDE';
      inputUnitName.value = p.unit_name || 'COEDE';

      cardDni.textContent = p.dni;
      isVisitorInput.value = '0';
      personnelIdHidden.value = p.id;

      cardStatusBadge.innerHTML = `<span class="badge ${p.category === 'MILITAR' ? 'badge-militar' : 'badge-civil'} fw-bold">${p.category}</span>`;
      photoRibbon.textContent = p.category === 'MILITAR' ? 'EJÉRCITO DEL PERÚ' : 'PERSONAL CIVIL EP';

      // Fotografía Dinámica (comprueba /img/efectivo/{dni}.jpg con fallback a CONSULTA RENIEC)
      renderEfectivoPhoto(p.dni, data.photo_url);

      // Vigencia
      const authEnd = p.auth_end;
      if (authEnd) {
        const days = Math.ceil((new Date(authEnd) - new Date()) / 86400000);
        if (days < 0) {
          vigenciaTextBadge.className = 'badge badge-vencido';
          vigenciaTextBadge.textContent = 'VENCIDA';
          vigenciaDateText.innerHTML = `Autorización finalizó el <strong class="text-danger">${authEnd}</strong>. Requiere anuencia de Jefatura.`;
        } else if (days <= 7) {
          vigenciaTextBadge.className = 'badge badge-por-vencer';
          vigenciaTextBadge.textContent = `VENCE EN ${days} DÍAS`;
          vigenciaDateText.innerHTML = `Vence el <strong class="text-warning">${authEnd}</strong> (${days} días restantes).`;
        } else {
          vigenciaTextBadge.className = 'badge badge-vigente';
          vigenciaTextBadge.textContent = 'VIGENTE';
          vigenciaDateText.innerHTML = `Autorización válida hasta el <strong>${authEnd}</strong>.`;
        }
      }

      // Ocultar sección de visitantes si es personal institucional
      visitorSection.classList.remove('show');
      if (reniecManualNotice) reniecManualNotice.classList.add('d-none');
      if (inputManualName) inputManualName.value = '';

    } else if (data.visitor) {
      // 3. Visitante Externo (Consulta RENIEC)
      const v = data.visitor;
      cardPersonName.textContent = v.full_name;
      inputPersonName.value = v.full_name;

      cardRank.textContent = 'VISITANTE EXTERNO';
      inputRankOrType.value = 'VISITANTE EXTERNO';

      cardCipCiv.textContent = 'NO APLICA';
      cardUnit.textContent = 'Ciudadano / Procedencia Externa';
      inputUnitName.value = 'Visita Externa';

      cardDni.textContent = dni || v.dni;
      isVisitorInput.value = '1';
      personnelIdHidden.value = '';

      cardStatusBadge.innerHTML = `<span class="badge bg-warning text-dark fw-bold">VISITANTE</span>`;
      photoRibbon.textContent = 'VISITANTE EXTERNO';

      // Fotografía Dinámica (comprueba /img/efectivo/{dni}.jpg con fallback a CONSULTA RENIEC)
      renderEfectivoPhoto(dni || v.dni, data.photo_url);

      vigenciaTextBadge.className = 'badge bg-warning text-dark';
      vigenciaTextBadge.textContent = 'PASE TEMPORAL';
      vigenciaDateText.textContent = 'Acceso provisional sujeto a justificación y pase en guardia.';

      // Desplegar campos indispensables de visitante
      visitorSection.classList.add('show');
      if (reniecManualNotice) reniecManualNotice.classList.add('d-none');
      if (inputManualName) inputManualName.value = '';
      document.getElementById('inputDestination')?.focus();

    } else if (!data.personnel && !data.visitor && dni) {
      // 4. DNI no encontrado en RENIEC (Ingreso Manual de Datos)
      cardPersonName.textContent = 'DATOS PENDIENTES';
      inputPersonName.value = '';

      cardRank.textContent = 'VISITANTE EXTERNO';
      inputRankOrType.value = 'VISITANTE EXTERNO';

      cardCipCiv.textContent = 'NO APLICA';
      cardUnit.textContent = 'Ciudadano / Procedencia Externa';
      inputUnitName.value = 'Visita Externa';

      cardDni.textContent = dni;
      isVisitorInput.value = '1';
      personnelIdHidden.value = '';

      cardStatusBadge.innerHTML = `<span class="badge bg-warning text-dark fw-bold">VISITANTE</span>`;
      photoRibbon.textContent = 'VISITANTE EXTERNO';

      // Fotografía Dinámica (comprueba /img/efectivo/{dni}.jpg con fallback a CONSULTA RENIEC)
      renderEfectivoPhoto(dni, data.photo_url);

      vigenciaTextBadge.className = 'badge bg-secondary text-dark';
      vigenciaTextBadge.textContent = 'REGISTRO MANUAL';
      vigenciaDateText.textContent = 'DNI no encontrado en RENIEC. Ingrese los datos manualmente.';

      // Desplegar sección de visitante con aviso y campo manual
      visitorSection.classList.add('show');
      if (reniecManualNotice) reniecManualNotice.classList.remove('d-none');
      if (inputManualName) {
        inputManualName.value = '';
        inputManualName.focus();
      }
    }

    // 4. Vehículo y Placa
    if (modeVehicular.checked) {
      if (data.vehicle) {
        specialAuthSection.classList.remove('show');
      } else if (inputPlate.value.trim()) {
        // Vehículo no registrado -> desplegar autorización del Área de Seguridad
        specialAuthSection.classList.add('show');
      }
    }

    // 5. Mensajes de Alerta y Seguridad
    renderAlertMessages(data);

    // 6. Tratamiento de Salida Pendiente Previa (Flujo Entrada)
    if (flowMovementType.value === 'ENTRADA' && data.open_movement) {
      openMovementId.value = data.open_movement.id;
      showExitPendingSweetAlert(data.open_movement);
    } else {
      openMovementId.value = '';
    }

    // 7. Alerta si el Acceso es DENEGADO
    if (data.status_recommendation === 'DENEGADO') {
      showAccessDeniedSweetAlert(data);
    }
  }

  // ==========================================
  // 8. SEMÁFORO LUMÍNICO DE ALTA VISIBILIDAD
  // ==========================================
  function updateBeacon(data) {
    gateBeacon.className = 'gate-beacon-indicator';

    if (data.status_recommendation === 'AUTORIZADO') {
      gateBeacon.classList.add('beacon-authorized');
      beaconIcon.className = 'bi bi-check-circle-fill fs-3 text-success';
      beaconText.textContent = 'ACCESO AUTORIZADO • PROCEDIMIENTO CONFORME';
      btnSubmitMovement.className = (flowMovementType.value === 'SALIDA') ? 'btn-action-primary btn-action-out' : 'btn-action-primary btn-action-in';
      btnSubmitMovement.disabled = false;
    } else if (data.status_recommendation === 'DENEGADO') {
      gateBeacon.classList.add('beacon-denied');
      beaconIcon.className = 'bi bi-slash-circle-fill fs-3 text-danger';
      beaconText.textContent = 'ACCESO DENEGADO • ALERTA DE SEGURIDAD';
      btnSubmitMovement.className = 'btn-action-primary btn-action-danger';
      btnSubmitMovement.disabled = true;
    } else {
      gateBeacon.classList.add('beacon-warning');
      beaconIcon.className = 'bi bi-exclamation-triangle-fill fs-3 text-warning';
      beaconText.textContent = data.status_label || 'PENDIENTE DE AUTORIZACIÓN';
      btnSubmitMovement.className = (flowMovementType.value === 'SALIDA') ? 'btn-action-primary btn-action-out' : 'btn-action-primary btn-action-in';
      btnSubmitMovement.disabled = false;
    }
  }

  // ==========================================
  // 9. RENDERIZAR ALERTAS EN TIEMPO REAL
  // ==========================================
  function renderAlertMessages(data) {
    let html = '';
    if (data.messages && data.messages.length > 0) {
      data.messages.forEach(m => {
        let cls = 'alert-info border-info';
        let icon = 'bi-info-circle-fill';
        if (m.includes('DENEGADO') || m.includes('ALERTA') || m.includes('NO AUTORIZADO')) {
          cls = 'alert-danger border-danger';
          icon = 'bi-shield-slash-fill';
        } else if (m.includes('SALIDA PENDIENTE') || m.includes('vencid') || m.includes('FOTOGRAFÍA')) {
          cls = 'alert-warning border-warning';
          icon = 'bi-exclamation-triangle-fill';
        }
        html += `<div class="alert ${cls} py-2 px-3 small mb-2 d-flex align-items-center gap-2"><i class="bi ${icon}"></i> <div>${m}</div></div>`;
      });
    }
    messagesContainer.innerHTML = html || `
      <div class="alert alert-success small mb-0 py-2 text-center border-success d-flex align-items-center justify-content-center gap-2">
        <i class="bi bi-shield-check fs-5"></i> <span>Sin observaciones restrictivas de seguridad. Proceda con el registro.</span>
      </div>
    `;
  }

  // ==========================================
  // 10. VENTANAS EMERGENTES (SWEETALERT2)
  // ==========================================

  // Alerta de Acceso Denegado (Rojo)
  function showAccessDeniedSweetAlert(data) {
    if (!CoedeSwal) return;

    const alertMsg = data.messages?.find(m => m.includes('ALERTA')) || 'Este individuo o vehículo cuenta con una restricción activa en el puesto de guardia.';

    CoedeSwal.fire({
      icon: 'error',
      title: 'ACCESO DENEGADO',
      html: `
        <div class="text-start mt-2">
          <p class="text-danger fw-bold mb-2"><i class="bi bi-shield-x"></i> RESTRICCIÓN DE SEGURIDAD SIMULADA:</p>
          <div class="p-3 rounded bg-dark border border-danger text-white small mb-3">
            ${alertMsg}
          </div>
          <p class="small text-muted mb-0">
            <strong>Instrucción Operativa:</strong> Prohíba el ingreso inmediatamente, retenga el documento si corresponde y comunique la situación a la Jefatura de Guardia mediante el botón de Incidencia.
          </p>
        </div>
      `,
      confirmButtonText: '<i class="bi bi-check2"></i> Entendido (Mantener Bloqueo)',
      allowOutsideClick: false
    });
  }

  // Alerta Interactiva de Salida Pendiente (Amarillo)
  function showExitPendingSweetAlert(openMov) {
    if (!CoedeSwal) return;

    CoedeSwal.fire({
      icon: 'warning',
      title: 'SALIDA PREVIA PENDIENTE DETECTADA',
      html: `
        <div class="text-start mt-2">
          <div class="alert alert-warning text-dark small py-2 mb-3">
            El sistema registra un <strong>ingreso previo abierto</strong> el <strong>${openMov.recorded_at}</strong> sin registro de salida.
          </div>
          <label class="form-label text-warning small fw-bold mb-1">¿Por qué puerta o circunstancia salió?</label>
          <select id="swalDeclaredGate" class="form-select form-select-sm mb-2 bg-dark text-white border-secondary">
            <option value="Puerta 2 (Auxiliar - No informatizada)">Puerta 2 (Auxiliar - No informatizada)</option>
            <option value="Puerta 3 (Vehicular Pesada)">Puerta 3 (Vehicular Pesada)</option>
            <option value="Salida peatonal por alta afluencia">Salida peatonal por alta afluencia</option>
            <option value="Comisión de servicio exterior">Comisión de servicio exterior</option>
            <option value="Otra puerta / Declaración verbal">Otra puerta / Declaración verbal</option>
          </select>

          <label class="form-label text-warning small fw-bold mb-1">Hora o Momento Aproximado de Salida</label>
          <input type="text" id="swalApproxTime" class="form-control form-control-sm mb-2 bg-dark text-white border-secondary" placeholder="ej. Ayer 18:30 hrs o mediodía">

          <label class="form-label text-warning small fw-bold mb-1">Observación</label>
          <input type="text" id="swalDeclaredObs" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Manifestación del ciudadano...">
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: '<i class="bi bi-file-earmark-check"></i> Documentar Salida y Continuar Entrada',
      cancelButtonText: 'Cancelar',
      focusConfirm: false,
      preConfirm: () => {
        const gate = document.getElementById('swalDeclaredGate')?.value || 'Puerta no constatada';
        const time = document.getElementById('swalApproxTime')?.value || 'Hora no precisada';
        const obs = document.getElementById('swalDeclaredObs')?.value || 'Declaración registrada por guardia al intentar nuevo ingreso';
        return { gate, time, obs };
      }
    }).then((result) => {
      if (result.isConfirmed && result.value) {
        hasExitDeclaration.value = '1';
        declaredGate.value = result.value.gate;
        declaredTime.value = result.value.time;
        declaredObservation.value = result.value.obs;

        const infoDiv = document.createElement('div');
        infoDiv.className = 'alert alert-info py-2 px-3 small mb-2 d-flex align-items-center gap-2 border-info';
        infoDiv.innerHTML = '<i class="bi bi-check2-circle fs-5"></i> <div><strong>Salida Previa Documentada:</strong> Se cerrará el movimiento anterior y se registrará la nueva entrada.</div>';
        messagesContainer.prepend(infoDiv);
      }
    });
  }

  // ==========================================
  // 11. REGISTRO DEL MOVIMIENTO (SUBMIT AJAX)
  // ==========================================
  gateForm?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const dni = inputDni.value.trim();
    if (!dni) {
      if (CoedeSwal) {
        CoedeSwal.fire({
          icon: 'warning',
          title: 'DNI Requerido',
          text: 'Por favor ingrese el DNI o escanee el código de barras.'
        });
      } else {
        alert('Por favor ingrese el DNI.');
      }
      inputDni.focus();
      return;
    }

    btnSubmitMovement.disabled = true;
    const originalText = btnActionLabel.textContent;
    btnActionLabel.textContent = 'Procesando registro...';

    const formData = new FormData(gateForm);

    try {
      const res = await fetch(getAppUrl('api/gate/movement'), {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken
        },
        body: formData
      });

      const data = await res.json();
      btnSubmitMovement.disabled = false;
      btnActionLabel.textContent = originalText;

      if (data.success) {
        const isEntry = flowMovementType.value === 'ENTRADA';

        // SweetAlert2 Verde para Entrada / Azul para Salida
        if (CoedeSwal) {
          CoedeSwal.fire({
            icon: isEntry ? 'success' : 'info',
            title: isEntry ? '¡INGRESO REGISTRADO!' : '¡SALIDA REGISTRADA!',
            html: `
              <div class="text-center mt-2">
                <div class="fs-5 text-white fw-bold mb-1">${cardPersonName.textContent}</div>
                <div class="text-warning small mb-3">${cardRank.textContent} &bull; ${cardUnit.textContent}</div>
                <div class="p-2 rounded bg-dark border border-secondary text-muted small">
                  ${data.message || 'Movimiento documentado con éxito en el puesto de guardia.'}
                </div>
              </div>
            `,
            timer: 2500,
            showConfirmButton: false
          });
        }

        // Agregar fila en la tabla de recientes
        prependRecentMovement({
          recorded_at: new Date().toLocaleTimeString('es-PE'),
          movement_type: flowMovementType.value,
          target_type: modeVehicular.checked ? 'VEHICULAR' : 'PEATONAL',
          is_visitor: isVisitorInput.value === '1',
          dni: dni,
          person_name: cardPersonName.textContent,
          plate: inputPlate.value.trim() || '-',
          destination: document.getElementById('inputDestination')?.value || '-',
          operator_name: 'Usted'
        });

        // Limpiar formulario para el siguiente efectivo
        resetGateForm();

      } else {
        if (CoedeSwal) {
          CoedeSwal.fire({
            icon: 'error',
            title: 'No se pudo registrar',
            text: data.message || 'Error al procesar el movimiento en guardia.'
          });
        } else {
          alert(data.message || 'Error al registrar.');
        }
      }

    } catch (err) {
      btnSubmitMovement.disabled = false;
      btnActionLabel.textContent = originalText;
      if (CoedeSwal) {
        CoedeSwal.fire({
          icon: 'error',
          title: 'Error de Red',
          text: 'Fallo al comunicar con el servidor institucional.'
        });
      } else {
        alert('Error de conexión con el servidor.');
      }
    }
  });

  // Agregar fila dinámica a la tabla de recientes
  function prependRecentMovement(m) {
    if (!recentMovementsTbody) return;
    const tr = document.createElement('tr');
    tr.className = 'table-active';
    tr.innerHTML = `
      <td class="small font-monospace">${m.recorded_at}</td>
      <td>
        <span class="badge ${m.movement_type === 'ENTRADA' ? 'bg-success' : 'bg-primary'}">
          ${m.movement_type}
        </span>
      </td>
      <td class="small">${m.target_type}</td>
      <td>
        <span class="badge ${m.is_visitor ? 'bg-warning text-dark' : 'bg-secondary'}">
          ${m.is_visitor ? 'Visitante' : 'Personal'}
        </span>
      </td>
      <td class="font-monospace fw-bold">${m.dni}</td>
      <td class="small fw-semibold text-white">${m.person_name}</td>
      <td class="font-monospace text-warning">${m.plate}</td>
      <td class="small text-muted">${m.destination}</td>
      <td class="small text-muted">${m.operator_name}</td>
    `;
    recentMovementsTbody.prepend(tr);
  }

  // ==========================================
  // 12. RESETEAR FORMULARIO RÁPIDO
  // ==========================================
  function resetGateForm() {
    inputDni.value = '';
    inputPlate.value = '';
    if (dniCounter) dniCounter.textContent = '0 / 8 dígitos';

    inputPersonName.value = '';
    inputRankOrType.value = '';
    inputUnitName.value = '';
    isVisitorInput.value = '0';
    personnelIdHidden.value = '';
    openMovementId.value = '';
    hasExitDeclaration.value = '0';
    declaredGate.value = '';
    declaredTime.value = '';
    declaredObservation.value = '';

    cardPersonName.textContent = '---';
    cardRank.textContent = '---';
    cardCipCiv.textContent = '---';
    cardUnit.textContent = '---';
    cardDni.textContent = '--------';
    cardStatusBadge.innerHTML = '<span class="badge bg-secondary text-dark fw-bold">EN ESPERA</span>';

    photoImg.style.display = 'none';
    photoPlaceholder.style.display = 'block';
    photoPlaceholder.innerHTML = '<i class="bi bi-person-bounding-box fs-1 d-block mb-1 text-secondary"></i><span class="text-muted">SIN CONSULTA</span>';
    photoRibbon.textContent = 'EJÉRCITO DEL PERÚ';
    btnRequestPhotoAction.classList.add('d-none');

    vigenciaTextBadge.className = 'badge bg-secondary text-dark';
    vigenciaTextBadge.textContent = 'No consultado';
    vigenciaDateText.textContent = 'Esperando ingreso de credencial militar o documento nacional.';

    visitorSection.classList.remove('show');
    if (reniecManualNotice) reniecManualNotice.classList.add('d-none');
    if (inputManualName) inputManualName.value = '';
    specialAuthSection.classList.remove('show');

    gateBeacon.className = 'gate-beacon-indicator beacon-idle';
    beaconIcon.className = 'bi bi-shield-shaded fs-3';
    beaconText.textContent = 'ESPERANDO IDENTIFICACIÓN (DNI O LECTOR)';
    btnSubmitMovement.disabled = false;
    btnSubmitMovement.className = (flowMovementType.value === 'SALIDA') ? 'btn-action-primary btn-action-out' : 'btn-action-primary btn-action-in';

    messagesContainer.innerHTML = `
      <div class="alert alert-dark text-muted small mb-0 text-center py-2 border-secondary">
        <i class="bi bi-info-circle me-1"></i> Digite el DNI o utilice el lector de código de barras para verificación automática instantánea.
      </div>
    `;

    inputDni.focus();
  }

  // ==========================================
  // 13. SOLICITUD DE FOTOGRAFÍA OFICIAL
  // ==========================================
  const requestPhotoModalEl = document.getElementById('requestPhotoModal');
  const requestPhotoModal = requestPhotoModalEl && typeof bootstrap !== 'undefined' ? new bootstrap.Modal(requestPhotoModalEl) : null;
  const btnSendPhotoReq = document.getElementById('btnSendPhotoRequest');

  btnRequestPhotoAction?.addEventListener('click', () => {
    requestPhotoModal?.show();
  });

  btnSendPhotoReq?.addEventListener('click', async () => {
    const pId = personnelIdHidden.value;
    const notes = document.getElementById('photoReqNote')?.value || '';

    if (!pId) return;

    try {
      const res = await fetch(getAppUrl('api/gate/request-photo'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ personnel_id: pId, notes: notes })
      });
      const resp = await res.json();
      if (resp.success) {
        requestPhotoModal?.hide();
        if (CoedeSwal) {
          CoedeSwal.fire({
            icon: 'success',
            title: 'Solicitud Enviada',
            text: 'Se notificó a la bandeja del Administrador para digitalizar la fotografía.',
            timer: 2000,
            showConfirmButton: false
          });
        } else {
          alert('Solicitud enviada.');
        }
      } else {
        alert(resp.message || 'Error al enviar solicitud.');
      }
    } catch (e) {
      alert('Error de conexión.');
    }
  });

  // ==========================================
  // 14. MODAL DE INCIDENCIAS CON CÁMARA Y ARCHIVOS
  // ==========================================
  const incidentModalEl = document.getElementById('incidentModal');
  const incidentModal = incidentModalEl && typeof bootstrap !== 'undefined' ? new bootstrap.Modal(incidentModalEl) : null;
  const btnOpenIncidentModal = document.getElementById('btnOpenIncidentModal');
  const incidentQuickForm = document.getElementById('incidentQuickForm');
  const incRefDni = document.getElementById('incRefDni');
  const incRefPlate = document.getElementById('incRefPlate');
  const incWebcamPhoto = document.getElementById('incWebcamPhoto');

  // Webcam Elements
  const webcamVideo = document.getElementById('webcamVideo');
  const webcamCanvas = document.getElementById('webcamCanvas');
  const webcamPreview = document.getElementById('webcamPreview');
  const webcamPrompt = document.getElementById('webcamPrompt');
  const btnStartWebcam = document.getElementById('btnStartWebcam');
  const btnSnapWebcam = document.getElementById('btnSnapWebcam');
  const btnRetakeWebcam = document.getElementById('btnRetakeWebcam');
  let webcamStream = null;

  btnOpenIncidentModal?.addEventListener('click', () => {
    // Prellenar DNI y placa si están en pantalla
    if (inputDni.value.trim().length === 8) {
      incRefDni.value = inputDni.value.trim();
    }
    if (inputPlate.value.trim()) {
      incRefPlate.value = inputPlate.value.trim();
    }
    incidentModal?.show();
  });

  // Iniciar Stream de Cámara Web
  btnStartWebcam?.addEventListener('click', async () => {
    try {
      webcamStream = await navigator.mediaDevices.getUserMedia({
        video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'environment' },
        audio: false
      });
      webcamVideo.srcObject = webcamStream;
      webcamVideo.style.display = 'block';
      webcamPrompt.style.display = 'none';
      webcamPreview.style.display = 'none';
      btnStartWebcam.style.display = 'none';
      btnSnapWebcam.style.display = 'inline-block';
      btnRetakeWebcam.style.display = 'none';
    } catch (err) {
      alert('No se pudo acceder a la cámara web. Asegúrese de otorgar permisos al navegador.');
    }
  });

  // Tomar Foto
  btnSnapWebcam?.addEventListener('click', () => {
    if (!webcamVideo) return;
    const width = webcamVideo.videoWidth || 640;
    const height = webcamVideo.videoHeight || 480;

    webcamCanvas.width = width;
    webcamCanvas.height = height;
    const ctx = webcamCanvas.getContext('2d');
    ctx.drawImage(webcamVideo, 0, 0, width, height);

    const dataUrl = webcamCanvas.toDataURL('image/jpeg', 0.85);
    incWebcamPhoto.value = dataUrl;

    webcamPreview.src = dataUrl;
    webcamPreview.style.display = 'block';
    webcamVideo.style.display = 'none';

    btnSnapWebcam.style.display = 'none';
    btnRetakeWebcam.style.display = 'inline-block';

    // Detener cámara temporalmente
    stopWebcamStream();
  });

  // Repetir Foto
  btnRetakeWebcam?.addEventListener('click', () => {
    incWebcamPhoto.value = '';
    btnStartWebcam.click();
  });

  function stopWebcamStream() {
    if (webcamStream) {
      webcamStream.getTracks().forEach(track => track.stop());
      webcamStream = null;
    }
  }

  // Al cerrar el modal de incidencias, detener cámara y resetear
  incidentModalEl?.addEventListener('hidden.bs.modal', () => {
    stopWebcamStream();
    if (webcamVideo) webcamVideo.style.display = 'none';
    if (webcamPreview) webcamPreview.style.display = 'none';
    if (webcamPrompt) webcamPrompt.style.display = 'block';
    if (btnStartWebcam) btnStartWebcam.style.display = 'inline-block';
    if (btnSnapWebcam) btnSnapWebcam.style.display = 'none';
    if (btnRetakeWebcam) btnRetakeWebcam.style.display = 'none';
  });

  // Guardar Incidencia vía AJAX
  incidentQuickForm?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const saveBtn = document.getElementById('btnSaveIncident');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Registrando...';

    const formData = new FormData(incidentQuickForm);

    try {
      const res = await fetch(getAppUrl('incidents/store'), {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: formData
      });

      const data = await res.json();
      saveBtn.disabled = false;
      saveBtn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Registrar y Elevar Incidencia';

      if (data.success) {
        incidentModal?.hide();
        incidentQuickForm.reset();
        incWebcamPhoto.value = '';

        if (CoedeSwal) {
          CoedeSwal.fire({
            icon: 'success',
            title: `INCIDENCIA REGISTRADA (${data.code})`,
            html: `
              <div class="mt-2 text-center">
                <p class="text-white mb-2">La incidencia se ha registrado y elevado con código atómico correlativo.</p>
                <div class="p-2 rounded bg-dark border border-warning text-warning small font-monospace">
                  CÓDIGO OFICIAL: ${data.code}
                </div>
              </div>
            `,
            timer: 3000,
            showConfirmButton: false
          });
        } else {
          alert(`Incidencia ${data.code} registrada.`);
        }
      } else {
        if (CoedeSwal) {
          CoedeSwal.fire({
            icon: 'error',
            title: 'Error al registrar',
            text: data.message || 'Complete los campos obligatorios.'
          });
        } else {
          alert(data.message || 'Error al guardar.');
        }
      }

    } catch (err) {
      saveBtn.disabled = false;
      saveBtn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Registrar y Elevar Incidencia';
      alert('Error de conexión al enviar la incidencia.');
    }
  });

});
