<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Estoque;
use App\Views\JsonView;
use App\Core\Request;
use PDO;
use Exception;

final class EstoqueController {
    private Estoque $modelEstoque;

    public function __construct(PDO $pdo) {
        $this->modelEstoque = new Estoque($pdo);
    }

    public function movimentar(): void {
        try {
            $dados = Request::input();

            $idEletronico = isset($dados['id_eletronico']) ? (int)$dados['id_eletronico'] : 0;
            $tipo         = isset($dados['tipo']) ? strtoupper(trim((string)$dados['tipo'])) : '';
            $quantidade   = isset($dados['quantidade']) ? (int)$dados['quantidade'] : 0;
            $observacao   = isset($dados['observacao']) ? trim((string)$dados['observacao']) : null;

            // 1. Validação de id_eletronico (> 0)
            if ($idEletronico <= 0) {
                JsonView::render([
                    'erro' => true,
                    'mensagem' => 'O campo id_eletronico deve ser um inteiro maior que zero.'
                ], 422);
                return;
            }

            // 2. Validação do tipo (ENTRADA ou SAIDA)
            if (!in_array($tipo, ['ENTRADA', 'SAIDA'], true)) {
                JsonView::render([
                    'erro' => true,
                    'mensagem' => "O campo tipo deve ser obrigatoriamente 'ENTRADA' ou 'SAIDA'."
                ], 422);
                return;
            }

            // 3. Validação de quantidade (> 0)
            if ($quantidade <= 0) {
                JsonView::render([
                    'erro' => true,
                    'mensagem' => 'O campo quantidade deve ser um inteiro maior que zero.'
                ], 422);
                return;
            }

            // Executar movimentação
            $resultado = $this->modelEstoque->movimentar($idEletronico, $tipo, $quantidade, $observacao);

            JsonView::render([
                'erro' => false,
                'mensagem' => 'Movimentação de estoque realizada com sucesso!',
                'dados' => $resultado
            ], 200);

        } catch (Exception $e) {
            if ($e->getMessage() === 'PRODUTO_NAO_ENCONTRADO') {
                JsonView::render([
                    'erro' => true,
                    'mensagem' => 'Eletrónico não encontrado ou inativo.'
                ], 404);
                return;
            }

            if ($e->getMessage() === 'SALDO_INSUFFICIENTE') {
                JsonView::render([
                    'erro' => true,
                    'mensagem' => 'Saldo em estoque insuficiente para realizar esta saída.'
                ], 409);
                return;
            }

            JsonView::render([
                'erro' => true,
                'mensagem' => 'Erro interno ao realizar movimentação de estoque.'
            ], 500);
        }
    }

    public function historico(): void {
        try {
            $idParam = $_GET['id'] ?? null;
            $idEletronico = null;

            if ($idParam !== null && $idParam !== '') {
                if (!is_numeric($idParam) || (int)$idParam <= 0) {
                    JsonView::render([
                        'erro' => true,
                        'mensagem' => 'O parâmetro de busca id deve ser um número inteiro maior que zero.'
                    ], 422);
                    return;
                }
                $idEletronico = (int)$idParam;
            }

            $listaMovimentacoes = $this->modelEstoque->historico($idEletronico);

            JsonView::render([
                'erro' => false,
                'dados' => $listaMovimentacoes
            ], 200);

        } catch (Exception $e) {
            JsonView::render([
                'erro' => true,
                'mensagem' => 'Erro interno ao consultar o histórico de movimentações.'
            ], 500);
        }
    }
}