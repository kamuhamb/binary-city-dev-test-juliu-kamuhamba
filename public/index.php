<?php
declare(strict_types=1);

// Front controller. Run locally with:
//   php -S localhost:8000 -t public public/index.php

// Let the PHP built-in server serve real static files (css/js) directly.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

// Minimal PSR-4 style autoloader: App\Core\Router => src/Core/Router.php
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $path = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($path)) {
            require $path;
        }
    }
});

use App\Controllers\ClientController;
use App\Controllers\ContactController;
use App\Core\Response;
use App\Core\Router;

$router = new Router();
$clients  = static fn() => new ClientController();
$contacts = static fn() => new ContactController();

// ---- Pages ----
$router->add('GET', '/',                  fn() => $clients()->index());
$router->add('GET', '/clients',           fn() => $clients()->index());
$router->add('GET', '/clients/new',       fn() => $clients()->form());
$router->add('GET', '/clients/{id}',      fn(int $id) => $clients()->form($id));
$router->add('GET', '/contacts',          fn() => $contacts()->index());
$router->add('GET', '/contacts/new',      fn() => $contacts()->form());
$router->add('GET', '/contacts/{id}',     fn(int $id) => $contacts()->form($id));

// ---- Client API (AJAX) ----
$router->add('GET',    '/api/clients',                          fn() => $clients()->apiList());
$router->add('POST',   '/api/clients',                          fn() => $clients()->apiCreate());
$router->add('GET',    '/api/clients/{id}',                     fn(int $id) => $clients()->apiShow($id));
$router->add('PUT',    '/api/clients/{id}',                     fn(int $id) => $clients()->apiUpdate($id));
$router->add('POST',   '/api/clients/{id}/contacts',            fn(int $id) => $clients()->apiLink($id));
$router->add('DELETE', '/api/clients/{id}/contacts/{contactId}', fn(int $id, int $cid) => $clients()->apiUnlink($id, $cid));

// ---- Contact API (AJAX) ----
$router->add('GET',    '/api/contacts',                         fn() => $contacts()->apiList());
$router->add('POST',   '/api/contacts',                         fn() => $contacts()->apiCreate());
$router->add('GET',    '/api/contacts/{id}',                    fn(int $id) => $contacts()->apiShow($id));
$router->add('PUT',    '/api/contacts/{id}',                    fn(int $id) => $contacts()->apiUpdate($id));
$router->add('POST',   '/api/contacts/{id}/clients',            fn(int $id) => $contacts()->apiLink($id));
$router->add('DELETE', '/api/contacts/{id}/clients/{clientId}', fn(int $id, int $cid) => $contacts()->apiUnlink($id, $cid));

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (\Throwable $e) {
    error_log((string) $e);
    if (str_starts_with((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/api/')) {
        Response::json(['error' => 'Something went wrong. Please try again.'], 500);
    }
    http_response_code(500);
    echo 'Something went wrong.';
}
