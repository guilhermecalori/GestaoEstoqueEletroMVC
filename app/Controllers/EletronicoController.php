<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Eletronico;
use App\Views\JsonView;
use PDO;
use Exception;

final class EletronicoController {
    private Eletronico $modelEletronico;

    public function __construct(PDO $pdo) {
        $this->modelEletronico = new Eletronico($pdo);
    }

    public function listar(): void {
        try {
            $listaProdutos = $this->modelEletronico->listar();

            JsonView::render([
                'erro' => false,
                'dados' => $listaProdutos
            ], 200);

        } catch (Exception $e) {
            JsonView::render([
                'erro' => true,
                'mensagem' => 'Erro interno ao listar os eletrônicos.'
            ], 500);
        }
    }
}