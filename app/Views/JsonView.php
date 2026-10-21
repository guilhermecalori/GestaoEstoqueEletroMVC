<?php

declare(strict_types=1);

namespace App\Views;

class JsonView {
    public static function render(mixed $dados, int $codigo = 200): void {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}