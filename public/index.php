<?php
declare(strict_types=1);

use Slim\Factory\AppFactory;
use Slim\Views\TwigMiddleware;
use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php'; //Load every vendor (libs)

$dotenv = new Dotenv();

$dotenv->load(__DIR__ . '/../.env'); //Save SQL vars in local

require_once __DIR__ . '/../config/dependencies.php'; //Create container and set controllers to each array key

AppFactory::setContainer($container);

$app = AppFactory::create();

$app->add(TwigMiddleware::createFromContainer($app));

$app->addBodyParsingMiddleware();

$app->addRoutingMiddleware();

$app->addErrorMiddleware(true, false, false);

require_once __DIR__ . '/../config/routing.php'; //Set controllers and functions to each route the user GETS/POST/ADD

$app->run();