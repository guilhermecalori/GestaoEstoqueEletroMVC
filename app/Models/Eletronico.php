<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use Exception;

final class Eletronico {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function listar(): array {
        $sql = "
            SELECT 
                e.id_eletronico,
                e.nome,
                e.descricao,
                e.numero_lote,
                e.fabricante,
                e.categoria,
                e.tensao,
                e.conectividade,
                e.preco,
                e.estoque_minimo,
                e.ativo,
                COALESCE(est.quantidade_atual, 0) AS quantidade_atual,
                CASE 
                    WHEN COALESCE(est.quantidade_atual, 0) <= e.estoque_minimo THEN true 
                    ELSE false 
                END AS estoque_baixo
            FROM eletronicos e
            LEFT JOIN estoque est ON e.id_eletronico = est.id_eletronico
            WHERE e.ativo = 1
            ORDER BY e.id_eletronico ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function criarComEstoque(array $dados): int {
        try {
            $this->pdo->beginTransaction();

            $sqlEletronico = "INSERT INTO eletronicos 
                    (nome, descricao, numero_lote, fabricante, categoria, tensao, conectividade, preco, estoque_minimo, ativo) 
                    VALUES 
                    (:nome, :descricao, :numero_lote, :fabricante, :categoria, :tensao, :conectividade, :preco, :estoque_minimo, :ativo)";

            $stmtE = $this->pdo->prepare($sqlEletronico);
            $stmtE->execute([
                ':nome'           => $dados['nome'],
                ':descricao'      => $dados['descricao'],
                ':numero_lote'    => $dados['numero_lote'],
                ':fabricante'     => $dados['fabricante'],
                ':categoria'      => $dados['categoria'],
                ':tensao'         => $dados['tensao'] ?? null,
                ':conectividade'  => $dados['conectividade'] ?? null,
                ':preco'          => $dados['preco'],
                ':estoque_minimo' => $dados['estoque_minimo'],
                ':ativo'          => $dados['ativo'] ?? 1
            ]);

            $idEletronico = (int) $this->pdo->lastInsertId();
            $qtdInicial = (int) ($dados['quantidade_inicial'] ?? 0);

            $sqlEstoque = "INSERT INTO estoque (id_eletronico, quantidade_atual) VALUES (:id_eletronico, :quantidade_atual)";
            $stmtEst = $this->pdo->prepare($sqlEstoque);
            $stmtEst->execute([
                ':id_eletronico'   => $idEletronico,
                ':quantidade_atual'=> $qtdInicial
            ]);

            if ($qtdInicial > 0) {
                $sqlMovimentacao = "INSERT INTO movimentacoes 
                        (id_eletronico, tipo, quantidade, saldo_anterior, saldo_posterior, observacao) 
                        VALUES 
                        (:id_eletronico, 'ENTRADA', :quantidade, 0, :saldo_posterior, 'Cadastro inicial do produto')";

                $stmtMov = $this->pdo->prepare($sqlMovimentacao);
                $stmtMov->execute([
                    ':id_eletronico'   => $idEletronico,
                    ':quantidade'      => $qtdInicial,
                    ':saldo_posterior' => $qtdInicial
                ]);
            }

            $this->pdo->commit();
            return $idEletronico;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function atualizar(array $dados): bool {
        $sql = "UPDATE eletronicos 
                SET nome = :nome,
                    descricao = :descricao,
                    numero_lote = :numero_lote,
                    fabricante = :fabricante,
                    categoria = :categoria,
                    tensao = :tensao,
                    conectividade = :conectividade,
                    preco = :preco,
                    estoque_minimo = :estoque_minimo
                WHERE id_eletronico = :id_eletronico AND ativo = 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_eletronico'  => $dados['id_eletronico'],
            ':nome'           => $dados['nome'],
            ':descricao'      => $dados['descricao'],
            ':numero_lote'    => $dados['numero_lote'],
            ':fabricante'     => $dados['fabricante'],
            ':categoria'      => $dados['categoria'],
            ':tensao'         => $dados['tensao'] ?? null,
            ':conectividade'  => $dados['conectividade'] ?? null,
            ':preco'          => $dados['preco'],
            ':estoque_minimo' => $dados['estoque_minimo']
        ]);

        return $stmt->rowCount() > 0;
    }

    public function excluirLogicamente(int $idEletronico): bool {
        $sql = "UPDATE eletronicos SET ativo = 0 WHERE id_eletronico = :id_eletronico AND ativo = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_eletronico' => $idEletronico]);

        return $stmt->rowCount() > 0;
    }
}