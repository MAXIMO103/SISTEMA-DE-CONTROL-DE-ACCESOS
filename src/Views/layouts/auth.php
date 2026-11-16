<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'Autenticación - COEDE') ?></title>
  <link rel="stylesheet" href="<?= url('assets/css/bootstrap.min.css') ?>">
  <link rel="stylesheet" href="<?= url('assets/css/bootstrap-icons.css') ?>">
  <link rel="stylesheet" href="<?= url('assets/css/coede-theme.css') ?>">
  <style>
    body {
      background: radial-gradient(circle at 50% 30%, #1d2d22 0%, #0d130f 80%, #060907 100%);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
    }
  </style>
</head>
<body>
  <?= $content ?>
  <script src="<?= url('assets/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>

