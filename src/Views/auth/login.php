<div class="card coede-card shadow-lg p-3" style="max-width: 440px; width: 100%; border: 1px solid var(--coede-border);">
  <div class="card-body p-4 text-center">
    
    <!-- Escudo Institucional Oficial -->
    <div class="mb-3 d-flex justify-content-center">
      <div class="coede-shield-badge-lg">
        <img src="<?= url('img/ESCUDO COEDE.jpeg') ?>" alt="Escudo COEDE" class="coede-shield-img">
      </div>
    </div>

    <!-- Título Institucional -->
    <h4 class="fw-bold mb-1" style="color: var(--coede-gold-light); letter-spacing: 2px;">COEDE</h4>
    <p class="text-muted small text-uppercase mb-4" style="letter-spacing: 1px; font-size: 0.72rem;">
      Comando de Educación y Doctrina del Ejército<br>
      <span class="text-secondary">Control de Acceso, SID y Operaciones Beta</span>
    </p>

    <!-- Alertas de Error -->
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger text-start py-2 px-3 small d-flex align-items-center gap-2 mb-3" role="alert">
        <i class="bi bi-exclamation-octagon-fill fs-5"></i>
        <div><?= e($error) ?></div>
      </div>
    <?php endif; ?>

    <!-- Formulario de Inicio de Sesión -->
    <form action="<?= url('/login') ?>" method="POST" class="text-start">
      <?= csrf_field() ?>

      <div class="mb-3">
        <label for="username" class="form-label">
          <i class="bi bi-person-badge"></i> Usuario Institucional
        </label>
        <div class="input-group">
          <span class="input-group-text bg-dark border-secondary text-secondary">
            <i class="bi bi-person-fill"></i>
          </span>
          <input type="text" class="form-control" id="username" name="username" placeholder="ej. admin o servicio" required autofocus autocomplete="username">
        </div>
      </div>

      <div class="mb-4">
        <label for="password" class="form-label">
          <i class="bi bi-shield-lock"></i> Contraseña de Acceso
        </label>
        <div class="input-group">
          <span class="input-group-text bg-dark border-secondary text-secondary">
            <i class="bi bi-key-fill"></i>
          </span>
          <input type="password" class="form-control" id="password" name="password" placeholder="••••••••••••" required autocomplete="current-password">
          <button class="btn btn-outline-secondary border-secondary text-muted" type="button" id="togglePasswordBtn" title="Mostrar/Ocultar contraseña">
            <i class="bi bi-eye" id="togglePasswordIcon"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-coede-gold w-100 py-2 fs-6 text-uppercase fw-bold shadow">
        <i class="bi bi-box-arrow-in-right"></i> Ingresar al Sistema
      </button>
    </form>

    <div class="mt-4 pt-3 border-top border-dark text-muted" style="font-size: 0.7rem;">
      <i class="bi bi-shield-shaded"></i> Sistema de demostración local con datos sintéticos.<br>
      Uso exclusivo de personal autorizado en guardia principal.
    </div>
  </div>
</div>

<script>
  document.getElementById('togglePasswordBtn')?.addEventListener('click', function() {
    const pwdInput = document.getElementById('password');
    const icon = document.getElementById('togglePasswordIcon');
    if (pwdInput.type === 'password') {
      pwdInput.type = 'text';
      icon.className = 'bi bi-eye-slash';
    } else {
      pwdInput.type = 'password';
      icon.className = 'bi bi-eye';
    }
  });
</script>

