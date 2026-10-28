# Gestão de Estoque EletroMVC - API RESTful

API desenvolvida em PHP MVC nativo com PDO para gestão de estoque de eletrónicos (Fase 3)[cite: 8].

## Integrantes
* Guilherme Calori
* Eduardo Teixeira

## Requisitos e Tecnologias
* PHP 8+
* Apache / XAMPP
* MySQL Workbench
* Postman ou Thunder Client

## Configuração do Ambiente
1. Importar o ficheiro SQL fornecido para o MySQL Workbench no banco `gestao_estoque_eletro`[cite: 9].
2. Configurar as credenciais do banco no ficheiro `config/config.php`.
3. Iniciar o serviço Apache e MySQL no XAMPP[cite: 9].

## URL Base Local
http://localhost/GestaoEstoqueEletroMVC/public

## Rotas Mapeadas (Sem Autenticação)
* `GET /eletronico` - Listar eletrónicos e saldos
* `POST /eletronico` - Cadastrar eletrónico e estoque inicial
* `PUT /eletronico` - Atualizar dados do eletrónico
* `DELETE /eletronico?id={id}` - Desativação lógica do produto
* `PUT /estoque` - Movimentar estoque (entrada ou saída)
* `GET /movimentacoes` - Consultar histórico de movimentações