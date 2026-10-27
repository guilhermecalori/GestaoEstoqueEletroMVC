<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use Exception;

final class Estoque {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function movimentar(int $idEletronico, string $tipo, int $quantidade, ?string $observacao = null): array {
        try {
            // 1. Iniciar Transação
            $this->pdo->beginTransaction();

            // 2. Consulta com FOR UPDATE para travar o registo e evitar condições de corrida
            $sqlConsulta = "
                SELECT e.id_eletronico, e.ativo, COALESCE(est.quantidade_atual, 0) AS quantidade_atual
                FROM eletronicos e
                LEFT JOIN estoque est ON e.id_eletronico = est.id_eletronico
                WHERE e.id_eletronico = :id_eletronico
                FOR UPDATE
            ";

            $stmtE = $this->pdo->prepare($sqlConsulta);
            $stmtE->execute([':id_eletronico' => $idEletronico]);
            $produto = $stmtE->fetch(PDO::FETCH_ASSOC);

            // 3. Verificar se o produto existe e está ativo
            if (!$produto || (int)$produto['ativo'] !== 1) {
                throw new Exception('PRODUTO_NAO_ENCONTRADO');
            }

            $saldoAnterior = (int)$produto['quantidade_atual'];

            // 4. Regras de cálculo de saldo
            if ($tipo === 'SAIDA') {
                if ($saldoAnterior < $quantidade) {
                    throw new Exception('SALDO_INSUFFICIENTE');
                }
                $saldoPosterior = $saldoAnterior - $quantidade;
            } else { // ENTRADA
                $saldoPosterior = $saldoAnterior + $quantidade;
            }

            // 5. Atualizar ou criar registo na tabela estoque
            $sqlEstoque = "
                INSERT INTO estoque (id_eletronico, quantidade_atual)
                VALUES (:id_eletronico, :quantidade_atual)
                ON DUPLICATE KEY UPDATE quantidade_atual = :quantidade_atual
            ";
            $stmtEst = $this->pdo->prepare($sqlEstoque);
            $stmtEst->execute([
                ':id_eletronico'   => $idEletronico,
                ':quantidade_atual'=> $saldoPosterior
            ]);

            // 6. Inserir histórico em movimentacoes
            $sqlMov = "
                INSERT INTO movimentacoes (id_eletronico, tipo, quantidade, saldo_anterior, saldo_posterior, observacao)
                VALUES (:id_eletronico, :tipo, :quantidade, :saldo_anterior, :saldo_posterior, :observacao)
            ";
            $stmtMov = $this->pdo->prepare($sqlMov);
            $stmtMov->execute([
                ':id_eletronico'   => $idEletronico,
                ':tipo'            => $tipo,
                ':quantidade'      => $quantidade,
                ':saldo_anterior'  => $saldoAnterior,
                ':saldo_posterior' => $saldoPosterior,
                ':observacao'      => $observacao
            ]);

            // 7. Commit da transação
            $this->pdo->commit();

            return [
                'id_eletronico'   => $idEletronico,
                'saldo_anterior'  => $saldoAnterior,
                'saldo_posterior' => $saldoPosterior,
                'tipo'            => $tipo,
                'quantidade'      => $quantidade
            ];

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function historico(?int $idEletronico = null): array {
        $sql = "
            SELECT 
                m.id_movimentacao,
                m.id_eletronico,
                e.nome AS nome_eletronico,
                e.numero_lote,
                m.tipo,
                m.quantidade,
                m.saldo_anterior,
                m.saldo_posterior,
                m.observacao,
                m.data_movimentacao
            FROM movimentacoes m
            INNER JOIN eletronicos e ON m.id_eletronico = e.id_eletronico
        ";

        if ($idEletronico !== null) {
            $sql .= " WHERE m.id_eletronico = :id_eletronico";
        }

        $sql .= " ORDER BY m.data_movimentacao DESC, m.id_movimentacao DESC";

        $stmt = $this->pdo->prepare($sql);

        if ($idEletronico !== null) {
            $stmt->bindValue(':id_eletronico', $idEletronico, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}