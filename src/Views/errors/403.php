<div class="row justify-content-center py-5">
  <div class="col-12 col-md-6 text-center">
    <div class="coede-card p-5 border-danger">
      <div class="display-1 fw-bold text-danger mb-3">
        <i class="bi bi-shield-x"></i> 403
      </div>
      <h3 class="fw-bold text-white mb-2"><?= e($title ?? 'Acceso Restringido') ?></h3>
      <p class="text-muted mb-4">
        <?= e($message ?? 'Su rol de usuario no posee los privilegios requeridos para acceder a este módulo institucional.') ?>
      </p>
      <div class="d-flex justify-content-center gap-3">
        <a href="<?= url('/') ?>" class="btn btn-coede-gold px-4">
          <i class="bi bi-house-door-fill"></i> Ir al Portal
        </a>
      </div>
    </div>
  </div>
</div>

