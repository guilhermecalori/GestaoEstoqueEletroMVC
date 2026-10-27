<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Eletronico;
use App\Views\JsonView;
use App\Core\Request;
use PDO;
use PDOException;
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
                'mensagem' => 'Erro interno ao listar os eletrónicos.'
            ], 500);
        }
    }

    public function criar(): void {
        try {
            $dados = Request::input();

            $nome         = isset($dados['nome']) ? trim((string)$dados['nome']) : '';
            $descricao    = isset($dados['descricao']) ? trim((string)$dados['descricao']) : '';
            $numeroLote   = isset($dados['numero_lote']) ? trim((string)$dados['numero_lote']) : '';
            $fabricante   = isset($dados['fabricante']) ? trim((string)$dados['fabricante']) : '';
            $categoria    = isset($dados['categoria']) ? trim((string)$dados['categoria']) : '';

            if (empty($nome) || empty($descricao) || empty($numeroLote) || empty($fabricante) || empty($categoria)) {
                JsonView::render([
                    'erro' => true,
                    'mensagem' => 'Os campos nome, descricao, numero_lote, fabricante e categoria são obrigatórios.'
                ], 422);
                return;
            }

            if (!isset($dados['preco']) || !is_numeric($dados['preco']) || (float)$dados['preco'] < 0) {
                JsonView::render([
                    'erro' => true,
                    'mensagem' => 'O campo preco deve ser um valor numérico maior ou igual a zero.'
                ], 422);
                return;
            }

            if (!isset($dados['estoque_minimo']) || !is_numeric($dados['estoque_minimo']) || (int)$dados['estoque_minimo'] < 0) {
                JsonView::render([
                    'erro' => true,
                    'mensagem' => 'O campo estoque_minimo deve ser um valor inteiro maior ou igual a zero.'
                ], 422);
                return;
            }

            $qtdInicial = $dados['quantidade_inicial'] ?? 0;
            if (!is_numeric($qtdInicial) || (int)$qtdInicial < 0) {
                JsonView::render([
                    'erro' => true,
                    'mensagem' => 'O campo quantidade_inicial deve ser um valor inteiro maior ou igual a zero.'
                ], 422);
                return;
            }

            $dadosTratados = [
                'nome'               => $nome,
                'descricao'          => $descricao,
                'numero_lote'        => $numeroLote,
                'fabricante'         => $fabricante,
                'categoria'          => $categoria,
                'tensao'             => isset($dados['tensao']) ? trim((string)$dados['tensao']) : null,
                'conectividade'      => isset($dados['conectividade']) ? trim((string)$dados['conectividade']) : null,
                'preco'              => (float)$dados['preco'],
                'estoque_minimo'     => (int)$dados['estoque_minimo'],
                'quantidade_inicial' => (int)$qtdInicial,
                'ativo'              => isset($dados['ativo']) ? (int)$dados['ativo'] : 1
            ];

            $idCriado = $this->modelEletronico->criarComEstoque($dadosTratados);

            JsonView::render([
                'erro' => false,
                'mensagem' => 'Eletrónico cadastrado com sucesso!',
                'id_eletronico' => $idCriado
            ], 201);

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                JsonView::render([
                    'erro' => true,
                    'mensagem' => 'O número de lote informado já está cadastrado no sistema.'
                ], 409);
                return;
            }

            JsonView::render([
                'erro' => true,
                'mensagem' => 'Erro de banco de dados ao cadastrar eletrónico.'
            ], 500);

        } catch (Exception $e) {
            JsonView::render([
                'erro' => true,
                'mensagem' => 'Erro interno ao cadastrar eletrónico.'
            ], 500);
        }
    }

    public function atualizar(): void {
        try {
            $dados = Request::input();

            $idEletronico = isset($dados['id_eletronico']) ? (int)$dados['id_eletronico'] : 0;
            if ($idEletronico <= 0) {
                JsonView::render([
                    'erro' => true,
                    'mensagem' => 'O campo id_eletronico é obrigatório e deve ser um inteiro maior que zero.'
                ], 422);
                return;
            }

            $nome       = isset($dados['nome']) ? trim((string)$dados['nome']) : '';
            $descricao  = isset($dados['descricao']) ? trim((string)$dados['descricao']) : '';
            $numeroLote = isset($dados['numero_lote']) ? trim((string)$dados['numero_lote']) : '';
            $fabricante = isset($dados['fabricante']) ? trim((string)$dados['fabricante']) : '';
            $categoria  = isset($dados['categoria']) ? trim((string)$dados['categoria']) : '';

            if (empty($nome) || empty($descricao) || empty($numeroLote) || empty($fabricante) || empty($categoria)) {
                JsonView::render([
                    'erro' => true,
                    'mensagem' => 'Os campos nome, descricao, numero_lote, fabricante e categoria são obrigatórios.'
                ], 422);
                return;
            }

            if (!isset($dados['preco']) || !is_numeric($dados['preco']) || (float)$dados['preco'] < 0) {
                JsonView::render([
                    'erro' => true,
                    'mensagem' => 'O campo preco deve ser um valor numérico maior ou igual a zero.'
                ], 422);
                return;
            }

            if (!isset($dados['estoque_minimo']) || !is_numeric($dados['estoque_minimo']) || (int)$dados['estoque_minimo'] < 0) {
                JsonView::render([
                    'erro' => true,
                    'mensagem' => 'O campo estoque_minimo deve ser um valor inteiro maior ou igual a zero.'
                ], 422);
                return;
            }

            $dadosTratados = [
                'id_eletronico'  => $idEletronico,
                'nome'           => $nome,
                'descricao'      => $descricao,
                'numero_lote'    => $numeroLote,
                'fabricante'     => $fabricante,
                'categoria'      => $categoria,
                'tensao'         => isset($dados['tensao']) ? trim((string)$dados['tensao']) : null,
                'conectividade'  => isset($dados['conectividade']) ? trim((string)$dados['conectividade']) : null,
                'preco'          => (float)$dados['preco'],
                'estoque_minimo' => (int)$dados['estoque_minimo']
            ];

            $sucesso = $this->modelEletronico->atualizar($dadosTratados);

            if ($sucesso) {
                JsonView::render([
                    'erro' => false,
                    'mensagem' => 'Eletrónico atualizado com sucesso!'
                ], 200);
            } else {
                JsonView::render([
                    'erro' => true,
                    'mensagem' => 'Eletrónico não encontrado ou já desativado.'
                ], 404);
            }

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                JsonView::render([
                    'erro' => true,
                    'mensagem' => 'O número de lote informado já pertence a outro produto.'
                ], 409);
                return;
            }

            JsonView::render([
                'erro' => true,
                'mensagem' => 'Erro de banco de dados ao atualizar eletrónico.'
            ], 500);

        } catch (Exception $e) {
            JsonView::render([
                'erro' => true,
                'mensagem' => 'Erro interno ao atualizar eletrónico.'
            ], 500);
        }
    }

    public function excluir(): void {
        try {
            $dados = Request::input();
            $idParam = $_GET['id'] ?? ($dados['id_eletronico'] ?? null);

            if ($idParam === null || !is_numeric($idParam) || (int)$idParam <= 0) {
                JsonView::render([
                    'erro' => true,
                    'mensagem' => 'O identificador do eletrónico (id) deve ser um inteiro maior que zero.'
                ], 422);
                return;
            }

            $idEletronico = (int)$idParam;
            $sucesso = $this->modelEletronico->excluirLogicamente($idEletronico);

            if ($sucesso) {
                JsonView::render([
                    'erro' => false,
                    'mensagem' => 'Eletrónico inativado com sucesso!'
                ], 200);
            } else {
                JsonView::render([
                    'erro' => true,
                    'mensagem' => 'Eletrónico não encontrado ou já inativo.'
                ], 404);
            }

        } catch (Exception $e) {
            JsonView::render([
                'erro' => true,
                'mensagem' => 'Erro interno ao inativar o eletrónico.'
            ], 500);
        }
    }
}