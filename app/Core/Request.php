<?php

declare(strict_types=1);

namespace App\Core;

class Request {
    public static function method(): string {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public static function path(): string {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);

        if (strpos($path, $scriptName) === 0) {
            $path = substr($path, strlen($scriptName));
        }

        return '/' . trim($path, '/');
    }

    public static function input(): array {
        $json = file_get_contents('php://input');
        $data = json_decode((string)$json, true);

        return is_array($data) ? $data : $_REQUEST;
    }
}