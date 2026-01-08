<?php

declare(strict_types=1);

use App\Infrastructure\AppFactory;
use DI\Container;
use Slim\App;

require __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');

$app = (new AppFactory())->create();

/** @var callable(App<Container>): void $registerRoutes */
$registerRoutes = require __DIR__ . '/../config/routes.php';
$registerRoutes($app);

$app->run();
