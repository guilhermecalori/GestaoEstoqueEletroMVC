<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

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
}