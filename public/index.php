<?php

use DI\Container;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use Hexlet\Code\Connection;
use Hexlet\Code\PgsqlActions;
use Hexlet\Code\PgsqlCreateTable;
use Slim\Flash\Messages;
use Hexlet\Code\Validator;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Exception\ConnectException;
use Carbon\Carbon;

require __DIR__ . '/../vendor/autoload.php';

session_start();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

$container = new Container();
AppFactory::setContainer($container);

// Set view in container
$container->set('view', function() {
    return Twig::create(__DIR__ . '/../templates');
});

$container->set('flash', function() {
    return new SLim\Flash\Messages();
});

$container->set('connection', function(){
    return Connection::get()->connect();
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

$app->get('/createTables', function ($request, $response) {
   $tableCreator = new PgsqlCreateTable($this->get('connection'));
   $tableUrls = $tableCreator->createTableUrls();
   $tableUrlCheck = $tableCreator->createTableUrlChecks();
   return $response;
});

$app->get('/urls', function ($request, $response) {
    $database = new PgsqlActions($this->get('connection'));
    $dataFromDB = $database->query(
        'SELECT MAX(url_checks.created_at) AS created_at, url_checks.status_code, urls.id, urls.name
        FROM urls
        LEFT OUTER JOIN url_checks ON url_checks.url_id = urls.id
        GROUP BY url_checks.url_id, urls.id, url_checks.status_code
        ORDER BY urls.id DESC'
    );
    $params = ['data' => $dataFromDB];
    return $this->get('render')->render($response, 'urls/index.twig', $params);
})->setName('urls');

$app->run();
