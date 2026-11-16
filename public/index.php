<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Application;

$app = new Application();

$routes = require_once dirname(__DIR__) . '/src/routes.php';
$routes($app->getRouter());

$app->run();

