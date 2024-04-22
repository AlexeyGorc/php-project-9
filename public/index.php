<?php

use DI\Container;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();
AppFactory::setContainer($container);

// Set view in container
$container->set('view', function() {
    return Twig::create(__DIR__ . '/../templates');
});

// Create app
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);
$app->add(TwigMiddleware::createFromContainer($app));

// Routes
$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function (Request $request, Response $response) use ($router) {
    $params = [];
    return $this->get('view')->render($response, 'index.twig', $params);
})->setName('index');

$app->run();
