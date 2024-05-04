<?php

require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use DiDom\Document;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Hexlet\Code\Url\Url;
use Hexlet\Code\Url\UrlChecks;
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
$app->addErrorMiddleware(true, true, true);

$app->add(TwigMiddleware::createFromContainer($app));

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) use ($router) {
    $messages = $this->get('flash')->getMessages();

    $params = [
        'flash' => $messages,
        'currentPage' => $router->urlFor('index')
    ];
    return $this->get('view')->render($response, 'index.twig', $params);
})->setName('index');

$app->get('/urls', function ($request, $response) use ($router) {
    try {
        $urls = Url::getAll();
    } catch (\Exception | \PDOException $e) {
        $this->get('flash')->addMessage('danger', $e->getMessage());
        return $response->withRedirect($router->urlFor('index'));
    }

    $params = [
        'router' => $router,
        'urls' => $urls,
        'currentPage' => $router->urlFor('url.index')
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
            'name' => $parsedUrl,
            'currentPage' => $router->urlFor('index')
        ];
        return $this->get('view')->render($response->withStatus(422), 'index.twig', $params);
    }

    try {
        $url = Url::byName($parsedUrl);

        if ($url->getId() > 0) {
            $this->get('flash')->addMessage('success', 'Страница уже существует');
            return $response->withRedirect($router->urlFor('url.show', ['id' => (string)$url->getId()]));
        }

        $urlId = $url->setName($parsedUrl)->store()->getId();
    } catch (\Exception | \PDOException $e) {
        $this->get('flash')->addMessage('danger', $e->getMessage());
        return $response->withRedirect($router->urlFor('index'));
    }

    if ($urlId <= 0) {
        $this->get('flash')->addMessage('danger', 'Что-то пошло не так');
        return $response->withRedirect($router->urlFor('index'));
    }

    $this->get('flash')->addMessage('success', 'Страница успешно добавлена');

    return $response->withRedirect($router->urlFor('url.show', ['id' => (string)$urlId]));
})->setName('url.store');

$app->get('/urls/{id:[0-9]+}', function ($request, $response, $args) use ($router) {
    $id = $args['id'];

    try {
        $url = Url::byId($id);

        if (!$url->getId()) {
            return $this->get('view')->render($response->withStatus(404), 'error404.twig');
        }
    } catch (\Exception | \PDOException $e) {
        $this->get('flash')->addMessage('danger', $e->getMessage());
        return $response->withRedirect($router->urlFor('index'));
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

    $statusCode = null;
    $responseBody = '';
    try {
        $url = Url::byId($id);

        if ($url->getId() === null) {
            $this->get('flash')->addMessage('danger', 'URL с указанным идентификатором не найден');
            return $response->withRedirect($router->urlFor('index'));
        }

        $guzzleClient = new Client();
        $guzzleResponse = $guzzleClient->request('GET', $url->getName(), ['connect_timeout' => 3]);
        $statusCode = $guzzleResponse->getStatusCode();
        $responseBody = (string) $guzzleResponse->getBody();
    } catch (ConnectException $e) {
        $statusCode = $e->getCode();

        if (!$statusCode) {
            $this->get('flash')->addMessage('danger', 'Произошла ошибка при проверке, не удалось подключиться');
            return $response->withRedirect($router->urlFor('url.show', ['id' => $id]));
        }
    } catch (RequestException $e) {
        $statusCode = $e->getCode();

        if ($e->hasResponse()) {
            $guzzleResponse = $e->getResponse();
            if ($guzzleResponse instanceof \Psr\Http\Message\ResponseInterface) {
                $statusCode = $guzzleResponse->getStatusCode();
                $responseBody = (string) $guzzleResponse->getBody();
            }
        }
    }

    if (!$statusCode) {
        $this->get('flash')->addMessage('danger', 'Произошла ошибка при проверке, не удалось подключиться');
        return $response->withRedirect($router->urlFor('url.show', ['id' => $id]));
    }

    $document = new Document($responseBody);
    $documentTitle = optional($document->first('title'))->text() ?? '';
    $documentH1 = optional($document->first('h1'))->text() ?? '';
    $documentDescription = optional($document->first('meta[name="description"]'))->attr('content') ?? '';

    try {
        $urlCheck = new UrlChecks();
        $urlCheckId = $urlCheck->setUrlId($id)->setStatusCode((int)$statusCode)->setH1($documentH1)
            ->setTitle($documentTitle)->setDescription($documentDescription)->store()->getId();
    } catch (\Exception | \PDOException $e) {
        $this->get('flash')->addMessage('danger', $e->getMessage());
        return $response->withRedirect($router->urlFor('index'));
    }

    if ($urlCheckId <= 0) {
        $this->get('flash')->addMessage('danger', 'Что-то пошло не так');
        return $response->withRedirect($router->urlFor('index'));
    }

    if ($statusCode >= 400) {
        $this->get('flash')->addMessage('warning', 'Проверка была выполнена успешно, но сервер ответил с ошибкой');
    } else {
        $this->get('flash')->addMessage('success', 'Страница успешно проверена');
    }

    return $response->withRedirect($router->urlFor('url.show', ['id' => $id]));
})->setName('url.check');

$app->run();
