<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Request.php';
require_once __DIR__ . '/../app/Views/JsonView.php';
require_once __DIR__ . '/../app/Models/Eletronico.php';
require_once __DIR__ . '/../app/Models/Estoque.php';
require_once __DIR__ . '/../app/Controllers/EletronicoController.php';
require_once __DIR__ . '/../app/Controllers/EstoqueController.php';

use App\Core\Database;
use App\Core\Request;
use App\Views\JsonView;
use App\Controllers\EletronicoController;
use App\Controllers\EstoqueController;

try {
    $pdo = Database::connection();
} catch (\Exception $e) {
    JsonView::render([
        'erro' => true,
        'mensagem' => 'Falha na conexão com o banco de dados.'
    ], 500);
}

$metodo = Request::method();
$rota = Request::path();

// Normalização da rota
$caminhoLimpo = str_replace('/public', '', $rota);
if ($caminhoLimpo === '' || $caminhoLimpo === '/') {
    $caminhoLimpo = '/';
}

$chaveRota = $metodo . ' ' . $caminhoLimpo;

// Roteamento
switch ($chaveRota) {
    case 'GET /health':
        JsonView::render(['status' => 'API rodando com sucesso!'], 200);
        break;

    case 'GET /eletronico':
        $controller = new EletronicoController($pdo);
        $controller->listar();
        break;

    case 'POST /eletronico':
        $controller = new EletronicoController($pdo);
        $controller->criar();
        break;

    case 'PUT /eletronico':
        $controller = new EletronicoController($pdo);
        $controller->atualizar();
        break;

    case 'DELETE /eletronico':
        $controller = new EletronicoController($pdo);
        $controller->excluir();
        break;

    case 'PUT /estoque':
        $controller = new EstoqueController($pdo);
        $controller->movimentar();
        break;

    case 'GET /movimentacoes':
        $controller = new EstoqueController($pdo);
        $controller->historico();
        break;

    default:
        JsonView::render([
            'erro' => true,
            'mensagem' => 'Rota não encontrada.'
        ], 404);
        break;
}