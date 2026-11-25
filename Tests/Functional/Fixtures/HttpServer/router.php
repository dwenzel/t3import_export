<?php

declare(strict_types=1);

/**
 * Router for the built-in web server of PHP in functional tests
 */
$port = (int)$_SERVER['SERVER_PORT'];
switch (parse_url((string)$_SERVER['REQUEST_URI'], PHP_URL_PATH)) {
    case '/redirect':
        header('Location: /target.txt', true, 302);
        break;
    case '/redirect-to-other-host':
        header('Location: http://localhost:' . $port . '/target.txt', true, 302);
        break;
    case '/target.txt':
        header('Content-Type: text/plain');
        echo 'target';
        break;
    default:
        http_response_code(404);
}
