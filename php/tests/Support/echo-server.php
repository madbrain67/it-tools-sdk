<?php

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', \PHP_URL_PATH);

if ('/status/418' === $path) {
    http_response_code(418);
    header('Content-Type: application/json');
    echo '{"message":"Je suis une theiere."}';

    return;
}

if ('/redirect' === $path) {
    http_response_code(302);
    header('Location: http://127.0.0.1:1/ailleurs');

    return;
}

if ('/slow' === $path) {
    usleep(1_500_000);
    echo '{}';

    return;
}

$files = [];
foreach ($_FILES as $name => $info) {
    $names = (array) $info['name'];
    $tmp = (array) $info['tmp_name'];
    $types = (array) $info['type'];
    foreach ($names as $i => $fileName) {
        $files[] = [
            'field' => $name,
            'index' => $i,
            'name' => $fileName,
            'type' => $types[$i],
            'content' => file_get_contents($tmp[$i]),
        ];
    }
}

header('Content-Type: application/json');
header('X-Echo: ok');
echo json_encode([
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'headers' => array_change_key_case(getallheaders(), \CASE_LOWER),
    'post' => $_POST,
    'files' => $files,
    'body' => file_get_contents('php://input'),
], \JSON_UNESCAPED_UNICODE);
