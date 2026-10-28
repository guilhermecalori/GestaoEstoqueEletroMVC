<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Views\JsonView;
use App\Core\Request;
use PDO;
use PDOException;
use Exception;

final class EstoqueController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Rota 5: PUT /estoque
     * Realiza a movimentação de entrada ou saída de estoque
     */
    public function movimentar(): void {
        try {
            $dados = Request::input();

            // 1. Extração dos campos
            $idEletronico = isset($dados['id_eletronico']) ? (int)$dados['id_eletronico'] : 0;
            $tipo         = isset($dados['tipo']) ? trim(mb_strtolower((string)$dados['tipo'])) : '';
            $quantidade   = isset($dados['quantidade']) ? (int)$dados['quantidade'] : 0;
            $observacao   = isset($dados['observacao']) ? trim((string)$dados['observacao']) : 'Movimentação manual via API';

            // 2. Validações de entrada (HTTP 422)
            if ($idEletronico <= 0) {
                JsonView::render([
                    'erro' => true,
                    'mensagem' => 'O campo id_eletronico é obrigatório e deve ser um inteiro maior que zero.'
                ], 422);
                return;
            }

            if (!in_array($tipo, ['entrada', 'saida'], true)) {
                JsonView::render([
                    'erro' => true,
                    'mensagem' => 'O tipo de movimentação é obrigatório e deve ser "entrada" ou "saida".'
                ], 422);
                return;
            }

            if ($quantidade <= 0) {
                JsonView::render([
                    'erro' => true,
                    'mensagem' => 'A quantidade deve ser um inteiro maior que zero.'
                ], 422);
                return;
            }

            // 3. Início da Transação PDO
            $this->pdo->beginTransaction();

            // 4. Bloqueia a linha do produto e consulta a quantidade atual no estoque (FOR UPDATE)
            $stmtSelect = $this->pdo->prepare("
                SELECT quantidade_atual 
                FROM estoque 
                WHERE id_eletronico = :id 
                FOR UPDATE
            ");
            $stmtSelect->execute([':id' => $idEletronico]);
            $estoqueAtual = $stmtSelect->fetchColumn();

            // Se o produto não existe na tabela de estoque (HTTP 404)
            if ($estoqueAtual === false) {
                $this->pdo->rollBack();
                JsonView::render([
                    'erro' => true,
                    'mensagem' => 'Eletrónico não encontrado no estoque.'
                ], 404);
                return;
            }

            $saldoAtual = (int)$estoqueAtual;

            // 5. Verificação de Saldo Insuficiente na Saída (HTTP 409 Conflict)
            if ($tipo === 'saida' && $saldoAtual < $quantidade) {
                $this->pdo->rollBack();
                JsonView::render([
                    'erro' => true,
                    'mensagem' => "Saldo insuficiente para realizar a saída. Estoque atual: {$saldoAtual}."
                ], 409);
                return;
            }

            // 6. Cálculo do novo saldo
            $novoSaldo = ($tipo === 'entrada') ? ($saldoAtual + $quantidade) : ($saldoAtual - $quantidade);

            // 7. Atualização do saldo na tabela estoque
            $stmtUpdate = $this->pdo->prepare("
                UPDATE estoque 
                SET quantidade_atual = :novo_saldo 
                WHERE id_eletronico = :id
            ");
            $stmtUpdate->execute([
                ':novo_saldo' => $novoSaldo,
                ':id'         => $idEletronico
            ]);

            // 8. Inserção no histórico com as colunas corretas: saldo_anterior e saldo_posterior
            $stmtInsert = $this->pdo->prepare("
                INSERT INTO movimentacoes (id_eletronico, tipo, quantidade, saldo_anterior, saldo_posterior, observacao) 
                VALUES (:id_eletronico, :tipo, :quantidade, :saldo_anterior, :saldo_posterior, :observacao)
            ");
            $stmtInsert->execute([
                ':id_eletronico'   => $idEletronico,
                ':tipo'            => $tipo,
                ':quantidade'      => $quantidade,
                ':saldo_anterior'  => $saldoAtual,
                ':saldo_posterior' => $novoSaldo,
                ':observacao'      => $observacao
            ]);

            // 9. Confirmação da Transação
            $this->pdo->commit();

            // 10. Resposta de sucesso (HTTP 200)
            JsonView::render([
                'erro' => false,
                'mensagem' => "Movimentação de {$tipo} realizada com sucesso!",
                'saldo_anterior' => $saldoAtual,
                'novo_saldo' => $novoSaldo
            ], 200);

        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            JsonView::render([
                'erro' => true,
                'mensagem' => 'SQL Error ao movimentar estoque: ' . $e->getMessage()
            ], 500);

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            JsonView::render([
                'erro' => true,
                'mensagem' => 'Exception Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rota 6: GET /movimentacoes
     * Consulta o histórico de movimentações de estoque
     */
    public function historico(): void {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    m.id_movimentacao,
                    m.id_eletronico,
                    e.nome AS eletronico_nome,
                    m.tipo,
                    m.quantidade,
                    m.saldo_anterior,
                    m.saldo_posterior,
                    m.observacao,
                    m.data_movimentacao
                FROM movimentacoes m
                INNER JOIN eletronicos e ON e.id_eletronico = m.id_eletronico
                ORDER BY m.id_movimentacao DESC
            ");

            $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

            JsonView::render([
                'erro' => false,
                'dados' => $historico
            ], 200);

        } catch (Exception $e) {
            JsonView::render([
                'erro' => true,
                'mensagem' => 'Erro ao consultar histórico: ' . $e->getMessage()
            ], 500);
        }
    }
}