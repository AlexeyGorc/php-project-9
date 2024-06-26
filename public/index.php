<?php

require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use DiDom\Document;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Hexlet\Code\Url\Url;
use Hexlet\Code\Url\UrlCheck;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Valitron\Validator;

session_start();

$container = new Container();
AppFactory::setContainer($container);

$container->set('view', function () {
    return Twig::create(__DIR__ . '/../templates');
});

$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$app = AppFactory::create();

$customErrorHandler = function (
    \Psr\Http\Message\ServerRequestInterface $request,
    \Throwable $exception,
    bool $displayErrorDetail,
    bool $logErrors,
    bool $logErrorDetails
) use ($app) {
    $response = $app->getResponseFactory()->createResponse();
    $router = $app->getRouteCollector()->getRouteParser();

    if ($exception instanceof \PDOException) {
        $this->get('flash')->addMessage('danger', $exception->getMessage());
        return $response->withHeader('Location', $router->urlFor('index'))->withStatus(302);
    }

    $response->getBody()->write($exception->getMessage());

    return $response;
};

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setDefaultErrorHandler($customErrorHandler);

$app->add(TwigMiddleware::createFromContainer($app));

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    $messages = $this->get('flash')->getMessages();

    $params = [
        'flash' => $messages
    ];
    return $this->get('view')->render($response, 'index.twig', $params);
})->setName('index');

$app->get('/urls', function ($request, $response) use ($router) {
    $urls = Url::getAll();

    $params = [
        'router' => $router,
        'urls' => $urls
    ];

    return $this->get('view')->render($response, 'urls/index.twig', $params);
})->setName('url.index');

$app->post('/urls', function ($request, $response) use ($router) {
    $parsedUrl = $request->getParsedBodyParam('url')['name'];

    $v = new Valitron\Validator(['name' => $parsedUrl]);
    $v->rule('required', 'name')->message('URL не должен быть пустым');
    $v->rule('lengthMax', 'name', 255)->message('Некорректный URL. 255');
    $v->rule('url', 'name')->message('Некорректный URL');

    if (!$v->validate()) {
        $params = [
            'errors' => $v->errors(),
            'name' => $parsedUrl
        ];
        return $this->get('view')->render($response->withStatus(422), 'index.twig', $params);
    }

    $url = Url::findOrCreate($parsedUrl);

    if ($url->exists()) {
        $this->get('flash')->addMessage('success', 'Страница уже существует');
        return $response->withRedirect($router->urlFor('url.show', ['id' => (string)$url->getId()]));
    }

    $urlId = $url->setName($parsedUrl)->store()->getId();

    $this->get('flash')->addMessage('success', 'Страница успешно добавлена');

    return $response->withRedirect($router->urlFor('url.show', ['id' => (string)$urlId]));
})->setName('url.store');

$app->get('/urls/{id:[0-9]+}', function ($request, $response, $args) use ($router) {
    $id = $args['id'];

    $url = Url::findById($id);

    if (!$url->getId()) {
        return $this->get('view')->render($response->withStatus(404), 'error404.twig');
    }

    $messages = $this->get('flash')->getMessages();

    $params = [
        'flash' => $messages,
        'url' => $url,
        'routeUrlCheck' => $router->urlFor('url.check', ['id' => (string)$url->getId()])
    ];
    return $this->get('view')->render($response, 'urls/show.twig', $params);
})->setName('url.show');

$app->post('/urls/{id:[0-9]+}/checks', function ($request, $response, $args) use ($router) {
    $id = $args['id'];

    $url = Url::findById($id);

    if ($url->getId() === null) {
        $this->get('flash')->addMessage('danger', 'URL с указанным идентификатором не найден');
        return $response->withRedirect($router->urlFor('index'));
    }

    try {
        $guzzleClient = new Client(['connect_timeout' => 3]);
        $guzzleResponse = $guzzleClient->request('GET', $url->getName());
        $guzzleResponse->getStatusCode();
        $statusCode = $guzzleResponse->getStatusCode();
        $responseBody = (string) $guzzleResponse->getBody();

        $document = new Document($responseBody);
        $documentTitle = optional($document->first('title'))->text();
        $documentH1 = optional($document->first('h1'))->text();
        $documentDescription = optional($document->first('meta[name="description"]'))->attr('content');

        if (!$statusCode) {
            $this->get('flash')->addMessage('danger', 'Произошла ошибка при проверке, не удалось подключиться');
            return $response->withRedirect($router->urlFor('url.show', ['id' => $id]));
        }

        $urlCheck = new UrlCheck();
        $urlCheckId = $urlCheck->setUrlId($id)->setStatusCode((int)$statusCode)->setH1($documentH1)
            ->setTitle($documentTitle)->setDescription($documentDescription)->store()->getId();

        if ($urlCheckId <= 0) {
            $this->get('flash')->addMessage('danger', 'Что-то пошло не так');
            return $response->withRedirect($router->urlFor('index'));
        }

        $this->get('flash')->addMessage('success', 'Страница успешно проверена');
    } catch (ConnectException $e) {
        $statusCode = $e->getCode();
        if (!$statusCode) {
            $this->get('flash')->addMessage('danger', 'Произошла ошибка при проверке, не удалось подключиться');
            return $response->withRedirect($router->urlFor('url.show', ['id' => $id]));
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $guzzleResponse = $e->getResponse();
            if ($guzzleResponse instanceof \Psr\Http\Message\ResponseInterface) {
                $statusCode = $guzzleResponse->getStatusCode();
                $responseBody = (string) $guzzleResponse->getBody();
            }
        }

        $this->get('flash')->addMessage('warning', 'Проверка была выполнена успешно, но сервер ответил с ошибкой');
        return $response->withRedirect($router->urlFor('url.show', ['id' => $id]));
    } catch (\RuntimeException $e) {
        $this->get('flash')->addMessage('danger', $e->getMessage());
        return $response->withRedirect($router->urlFor('url.show', ['id' => $id]));
    }

    return $response->withRedirect($router->urlFor('url.show', ['id' => $id]));
})->setName('url.check');

$app->run();
