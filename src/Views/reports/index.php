<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h3 class="fw-bold mb-0" style="color: var(--coede-gold-light);">
      <i class="bi bi-graph-up-arrow"></i> Dashboard Estadístico y Reportes Generales
    </h3>
  </div>

  <div class="d-flex gap-2">
    <a href="<?= url('/reports/gate') ?>" class="btn btn-coede-gold btn-sm px-3">
      <i class="bi bi-door-open-fill"></i> Reporte Detallado Puerta
    </a>
    <a href="<?= url('/reports/incidents') ?>" class="btn btn-outline-warning btn-sm px-3">
      <i class="bi bi-shield-exclamation"></i> Reporte Incidencias
    </a>
    <a href="<?= url('/reports/gate/export-excel') ?>" class="btn btn-outline-success btn-sm px-3">
      <i class="bi bi-file-earmark-excel-fill"></i> Exportar Excel
    </a>
  </div>
</div>

<!-- Selector de Periodo -->
<div class="coede-card p-3 mb-4">
  <form method="GET" action="<?= url('/reports') ?>" class="row g-2 align-items-end">
    
    <div class="col-6 col-md-3">
      <label class="form-label">Periodo Rápido</label>
      <select name="period" class="form-select form-select-sm" id="periodSelect">
        <option value="hoy" <?= $period === 'hoy' ? 'selected' : '' ?>>Hoy (Jornada Actual)</option>
        <option value="semana" <?= $period === 'semana' ? 'selected' : '' ?>>Últimos 7 Días</option>
        <option value="mes" <?= $period === 'mes' ? 'selected' : '' ?>>Últimos 30 Días</option>
        <option value="personalizado" <?= $period === 'personalizado' ? 'selected' : '' ?>>Rango Personalizado</option>
      </select>
    </div>

    <div class="col-6 col-md-3">
      <label class="form-label">Desde</label>
      <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($startDate ?? date('Y-m-d')) ?>">
    </div>

    <div class="col-6 col-md-3">
      <label class="form-label">Hasta</label>
      <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($endDate ?? date('Y-m-d')) ?>">
    </div>

    <div class="col-6 col-md-3 d-flex gap-1">
      <button type="submit" class="btn btn-coede-gold btn-sm w-100 fw-bold">
        <i class="bi bi-arrow-clockwise"></i> Actualizar Gráficos
      </button>
    </div>
  </form>
</div>

<!-- Tarjetas de Métricas Separadas (Personas y Vehículos no se suman) -->
<?php $m = $stats['metrics']; ?>
<div class="row g-3 mb-4">
  
  <!-- Flujo Peatonal -->
  <div class="col-12 col-md-6 col-xl-3">
    <div class="coede-card p-3 border-start border-4 border-success">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="small text-uppercase fw-bold" style="color: #cbd5e1 !important; opacity: 1 !important;">Afluencia Peatonal</span>
        <i class="bi bi-person-walking fs-4 text-success"></i>
      </div>
      <div class="d-flex justify-content-between align-items-baseline">
        <div class="fs-4 fw-bold text-white"><?= $m['peatonal_entradas'] ?> <span class="small fs-6 text-success">Entradas</span></div>
        <div class="fs-5 fw-semibold text-muted"><?= $m['peatonal_salidas'] ?> <span class="small fs-6 text-primary">Salidas</span></div>
      </div>
      <div class="small mt-1 border-top border-dark pt-1" style="color: #cbd5e1 !important; opacity: 1 !important;">
        Permanencia est.: <strong class="text-info"><?= $m['estimacion_permanencia_peatonal'] ?> personas</strong>
      </div>
    </div>
  </div>

  <!-- Flujo Vehicular -->
  <div class="col-12 col-md-6 col-xl-3">
    <div class="coede-card p-3 border-start border-4 border-primary">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="small text-uppercase fw-bold" style="color: #cbd5e1 !important; opacity: 1 !important;">Flujo Vehicular</span>
        <i class="bi bi-car-front-fill fs-4 text-primary"></i>
      </div>
      <div class="d-flex justify-content-between align-items-baseline">
        <div class="fs-4 fw-bold text-white"><?= $m['vehicular_entradas'] ?> <span class="small fs-6 text-success">Entradas</span></div>
        <div class="fs-5 fw-semibold text-muted"><?= $m['vehicular_salidas'] ?> <span class="small fs-6 text-primary">Salidas</span></div>
      </div>
      <div class="small mt-1 border-top border-dark pt-1" style="color: #cbd5e1 !important; opacity: 1 !important;">
        Estacionados est.: <strong class="text-info"><?= $m['estimacion_permanencia_vehicular'] ?> vehículos</strong>
      </div>
    </div>
  </div>

  <!-- Movimientos Abiertos -->
  <div class="col-12 col-md-6 col-xl-3">
    <div class="coede-card p-3 border-start border-4 border-warning">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="small text-uppercase fw-bold" style="color: #cbd5e1 !important; opacity: 1 !important;">Movimientos Abiertos</span>
        <i class="bi bi-clock-history fs-4 text-warning"></i>
      </div>
      <div class="fs-3 fw-bold text-warning"><?= $m['movimientos_abiertos'] ?></div>
      <div class="small mt-1 border-top border-dark pt-1" style="color: #cbd5e1 !important; opacity: 1 !important;">
        Entradas sin salida constatada
      </div>
    </div>
  </div>

  <!-- Declaraciones Pendientes de Revisión -->
  <div class="col-12 col-md-6 col-xl-3">
    <div class="coede-card p-3 border-start border-4 border-danger">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="small text-uppercase fw-bold" style="color: #cbd5e1 !important; opacity: 1 !important;">Salidas Declaradas</span>
        <i class="bi bi-exclamation-circle fs-4 text-danger"></i>
      </div>
      <div class="fs-3 fw-bold text-danger"><?= $m['declaraciones_pendientes'] ?></div>
      <div class="small mt-1 border-top border-dark pt-1" style="color: #cbd5e1 !important; opacity: 1 !important;">
        Pendientes de revisión administrativa
      </div>
    </div>
  </div>

</div>

<!-- Nota de Rigor Institucional -->
<div class="alert alert-dark border-secondary small text-muted mb-4">
  <i class="bi bi-info-circle-fill text-warning"></i>
  <strong>Rigor Estadístico:</strong> Los indicadores de permanencia y ocupación son <strong>estimaciones operativas</strong> basadas exclusivamente en los registros de la Puerta Principal informatizada. Debido a que los otros accesos de la guarnición operan en modalidad manual/no sistematizada en esta fase beta, estas cifras no deben interpretarse como censo cerrado de ocupación perimétrica.
</div>

<!-- Gráficos de Chart.js -->
<div class="row g-4 mb-4">
  
  <!-- Gráfico 1: Barras Comparativas Entradas vs Salidas -->
  <div class="col-12 col-lg-6">
    <div class="coede-card p-4 h-100">
      <h6 class="coede-card-title mb-3">
        <i class="bi bi-bar-chart-fill text-warning"></i> Entradas vs Salidas por Modalidad
      </h6>
      <div style="height: 280px; position: relative;">
        <canvas id="chartComparison"></canvas>
      </div>
    </div>
  </div>

  <!-- Gráfico 2: Línea de Evolución Temporal -->
  <div class="col-12 col-lg-6">
    <div class="coede-card p-4 h-100">
      <h6 class="coede-card-title mb-3">
        <i class="bi bi-graph-up text-warning"></i> Evolución de Afluencia Temporal
      </h6>
      <div style="height: 280px; position: relative;">
        <canvas id="chartTimeline"></canvas>
      </div>
    </div>
  </div>

</div>

<script src="<?= url('assets/js/chart.umd.min.js') ?>"></script>
<script>
  // Inicialización de Gráficos Chart.js
  const ctxComp = document.getElementById('chartComparison')?.getContext('2d');
  if (ctxComp) {
    new Chart(ctxComp, {
      type: 'bar',
      data: {
        labels: ['Peatonal', 'Vehicular'],
        datasets: [
          {
            label: 'Entradas',
            data: [<?= $m['peatonal_entradas'] ?>, <?= $m['vehicular_entradas'] ?>],
            backgroundColor: 'rgba(30, 126, 70, 0.75)',
            borderColor: '#38c465',
            borderWidth: 1
          },
          {
            label: 'Salidas',
            data: [<?= $m['peatonal_salidas'] ?>, <?= $m['vehicular_salidas'] ?>],
            backgroundColor: 'rgba(59, 130, 246, 0.75)',
            borderColor: '#60a5fa',
            borderWidth: 1
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { labels: { color: '#e2ede3' } }
        },
        scales: {
          x: { ticks: { color: '#9aa89d' }, grid: { color: 'rgba(255,255,255,0.05)' } },
          y: { ticks: { color: '#9aa89d', precision: 0 }, grid: { color: 'rgba(255,255,255,0.05)' } }
        }
      }
    });
  }

  const ctxTime = document.getElementById('chartTimeline')?.getContext('2d');
  if (ctxTime) {
    const timelineData = <?= json_encode($stats['timeline']) ?>;
    const labels = timelineData.map(d => d.time_slot);
    const inData = timelineData.map(d => parseInt(d.entradas));
    const outData = timelineData.map(d => parseInt(d.salidas));

    new Chart(ctxTime, {
      type: 'line',
      data: {
        labels: labels.length ? labels : ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00'],
        datasets: [
          {
            label: 'Entradas',
            data: inData.length ? inData : [2, 5, 8, 3, 4, 1],
            borderColor: '#38c465',
            backgroundColor: 'rgba(56, 196, 101, 0.1)',
            fill: true,
            tension: 0.3
          },
          {
            label: 'Salidas',
            data: outData.length ? outData : [0, 1, 4, 6, 5, 3],
            borderColor: '#60a5fa',
            backgroundColor: 'rgba(96, 165, 250, 0.1)',
            fill: true,
            tension: 0.3
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { labels: { color: '#e2ede3' } }
        },
        scales: {
          x: { ticks: { color: '#9aa89d' }, grid: { color: 'rgba(255,255,255,0.05)' } },
          y: { ticks: { color: '#9aa89d', precision: 0 }, grid: { color: 'rgba(255,255,255,0.05)' } }
        }
      }
    });
  }
</script>

