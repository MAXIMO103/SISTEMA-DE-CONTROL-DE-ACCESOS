<div class="row justify-content-center py-5">
  <div class="col-12 col-md-6 text-center">
    <div class="coede-card p-5 border-warning">
      <div class="display-1 fw-bold text-warning mb-3">
        <i class="bi bi-question-diamond-fill"></i> 404
      </div>
      <h3 class="fw-bold text-white mb-2">Recurso No Encontrado</h3>
      <p class="text-muted mb-4">
        La ruta solicitada <code><?= e($requestedUri ?? '') ?></code> no existe en el sistema institucional.
      </p>
      <div class="d-flex justify-content-center gap-3">
        <a href="<?= url('/') ?>" class="btn btn-coede-gold px-4">
          <i class="bi bi-house-door-fill"></i> Volver al Portal
        </a>
      </div>
    </div>
  </div>
</div>

