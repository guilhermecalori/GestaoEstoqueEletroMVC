# Diário de Decisões e Aprendizagem - Gestão de Estoque EletroMVC

## 28/10/2026 - Fundação do Projeto
* **Integrantes e papéis:** Driver: Guilherme Calori | Navigator: Eduardo Teixeira
* **Problema:** Mapeamento inicial das tabelas e garantia de que o banco local `gestao_estoque_eletro` estava em conformidade com o MVC.
* **Nossa tentativa:** Importamos o arquivo SQL via MySQL Workbench e validamos as credenciais no `config.php`.
* **Prompt enviado à IA:** "Como validar se a conexão PDO com MySQL está funcionando no Front Controller sem expor erros em produção?"
* **Sugestão da IA:** Tratar exceções de PDO no `index.php` e utilizar o `JsonView` para retornar mensagens amigáveis em JSON com HTTP 500.
* **Decisão da dupla:** Aceitamos a sugestão para manter o padrão REST em todas as falhas de infraestrutura.
* **Teste realizado:** Requisição para uma rota inexistente para testar o fallback de erro 404 e conexão PDO.
* **Resultado obtido:** HTTP 404 Not Found em JSON.
* **Evidência:** Estrutura MVC respondendo corretamente via Apache/XAMPP.
* **O que aprendemos:** A importância de isolar as configurações do PDO para garantir transações seguras no backend.

---

## 28/10/2026 - GET /eletronico
* **Integrantes e papéis:** Driver: Guilherme Calori | Navigator: Eduardo Teixeira
* **Problema:** Listar todos os eletrônicos ativos trazendo o saldo atual de estoque associado.
* **Nossa tentativa:** Criamos a consulta no `EletronicoController.php` utilizando `INNER JOIN` entre a tabela `eletronicos` e a tabela `estoque`.
* **Prompt enviado à IA:** "Como fazer um JOIN performático em PDO para buscar o eletrônico e seu saldo mantendo o filtro ativo?"
* **Sugestão da IA:** Utilizar `INNER JOIN` na tabela `estoque` filtrando por `ativo = 1` para garantir que apenas produtos válidos retornem.
* **Decisão da dupla:** Aceitamos a abordagem para que a resposta em JSON já entregue o catálogo pronto para o consumo.
* **Teste realizado:** Requisição `GET http://localhost/GestaoEstoqueEletroMVC/public/eletronico` no Thunder Client.
* **Resultado obtido:** HTTP 200 OK | Lista de produtos cadastrados com seus respetivos saldos.
* **Evidência:** Retorno do array JSON contendo status 200.
* **O que aprendemos:** A relação entre chaves primárias e estrangeiras no PDO facilita a modelagem da resposta sem necessidade de queries secundárias.

---

## 28/10/2026 - POST /eletronico
* **Integrantes e papéis:** Driver: Guilherme Calori | Navigator: Eduardo Teixeira
* **Problema:** Cadastrar um novo eletrônico e inicializar automaticamente o seu registo correspondente na tabela de estoque.
* **Nossa tentativa:** Inserimos o produto no banco e usamos o `lastInsertId()` para criar a linha na tabela `estoque`.
* **Prompt enviado à IA:** "Como garantir que se o cadastro do estoque falhar após inserir o eletrônico, o produto não fique órfão?"
* **Sugestão da IA:** Envolver ambos os `INSERT` em uma transação PDO (`beginTransaction`, `commit` e `rollBack`).
* **Decisão da dupla:** Adotamos a transação para preservar a integridade referencial do banco.
* **Teste realizado:** Requisição `POST /eletronico` com payload contendo `nome`, `preco` e `estoque_inicial`.
* **Resultado obtido:** HTTP 201 Created | Mensagem de sucesso e confirmação da criação do produto e estoque.
* **Evidência:** Registo visível na tabela `eletronicos` e na tabela `estoque` do MySQL Workbench.
* **O que aprendemos:** O uso de transações PDO evita inconsistência de dados quando uma operação depende de duas tabelas diferentes.

---

## 28/10/2026 - PUT /eletronico
* **Integrantes e papéis:** Driver: Guilherme Calori | Navigator: Eduardo Teixeira
* **Problema:** Atualizar os dados cadastrais (nome, marca, preço) de um eletrônico existente.
* **Nossa tentativa:** Recebemos os dados via `Request::input()` e executamos o `UPDATE` pelo ID do eletrônico.
* **Prompt enviado à IA:** "Qual status HTTP devemos retornar no PUT de atualização e como tratar o caso de ID inexistente?"
* **Sugestão da IA:** Validar se a linha foi afetada (`rowCount()`). Se for 0 e o registro não existir, retornar HTTP 404; caso atualizado, HTTP 200.
* **Decisão da dupla:** Aceitamos a lógica e adicionamos a verificação prévia de existência do registo.
* **Teste realizado:** Requisição `PUT /eletronico` enviando o `id` e novos valores para os campos.
* **Resultado obtido:** HTTP 200 OK | `{"erro": false, "mensagem": "Eletrónico atualizado com sucesso!"}`.
* **Evidência:** Alteração nos campos verificada via `SELECT` no MySQL Workbench.
* **O que aprendemos:** Prepared Statements com nomes parametrizados (`:nome`, `:id`) evitam SQL Injection e garantem tipagem correta.

---

## 28/10/2026 - DELETE /eletronico?id=1
* **Integrantes e papéis:** Driver: Guilherme Calori | Navigator: Eduardo Teixeira
* **Problema:** Desativar um produto do sistema sem apagar o seu histórico de movimentações (exclusão lógica).
* **Nossa tentativa:** Inicialmente pensamos em rodar `DELETE FROM eletronicos`, mas isso quebraria as chaves estrangeiras do histórico.
* **Prompt enviado à IA:** "Como implementar exclusão lógica no PHP em uma tabela com a coluna `ativo`?"
* **Sugestão da IA:** Fazer um `UPDATE eletronicos SET ativo = 0 WHERE id_eletronico = :id`.
* **Decisão da dupla:** Adotamos a exclusão lógica para manter a rastreabilidade e integridade do histórico.
* **Teste realizado:** Requisição `DELETE http://localhost/GestaoEstoqueEletroMVC/public/eletronico?id=1`.
* **Resultado obtido:** HTTP 200 OK | Confirmando a desativação lógica.
* **Evidência:** Campo `ativo` alterado para `0` no banco de dados e produto ocultado da listagem do `GET /eletronico`.
* **O que aprendemos:** A exclusão lógica preserva a integridade de dados históricos sem quebrar referências no banco.

---

## 28/10/2026 - PUT /estoque
* **Integrantes e papéis:** Driver: Guilherme Calori | Navigator: Eduardo Teixeira
* **Problema:** Erro 500 no MySQL por incompatibilidade nos nomes das colunas de saldo (`saldo_anterior` e `saldo_posterior`) na tabela `movimentacoes`.
* **Nossa tentativa:** Tentamos omitir as colunas de saldo no `INSERT`, mas a coluna `saldo_anterior` exigia preenchimento (`NOT NULL`).
* **Prompt enviado à IA:** "SQL Error ao movimentar estoque: General error: 1364 Field 'saldo_anterior' doesn't have a default value"
* **Sugestão da IA:** Obter o saldo atual com `SELECT ... FOR UPDATE`, calcular o novo saldo e inserir ambos na tabela `movimentacoes` usando as colunas `saldo_anterior` e `saldo_posterior`.
* **Decisão da dupla:** Aceitar e adaptar o código no `EstoqueController.php` para calcular e guardar os dois saldos dentro da transação.
* **Teste realizado:** Requisição `PUT /estoque` enviando entrada de 5 unidades.
* **Resultado obtido:** HTTP 200 OK | `{"erro": false, "mensagem": "Movimentação de entrada realizada com sucesso!", "saldo_anterior": 8, "novo_saldo": 13}`.
* **Evidência:** Sucesso no Thunder Client (HTTP 200) e atualização do saldo de 8 para 13 confirmada no banco.
* **O que aprendemos:** Usar `FOR UPDATE` em transações previne problemas de concorrência e garante que os saldos anterior e posterior fiquem gravados com precisão.

---

## 28/10/2026 - GET /movimentacoes
* **Integrantes e papéis:** Driver: Guilherme Calori | Navigator: Eduardo Teixeira
* **Problema:** Erro `1146 Table 'gestao_estoque_eletro.eletronico' doesn't exist` ao consultar o histórico de movimentações.
* **Nossa tentativa:** Fizemos um `INNER JOIN` ligando a tabela `movimentacoes` à tabela no singular `eletronico`.
* **Prompt enviado à IA:** "Erro ao consultar histórico: SQLSTATE[42S02]: Base table or view not found: 1146 Table 'gestao_estoque_eletro.eletronico' doesn't exist"
* **Sugestão da IA:** Ajustar o nome da tabela no `JOIN` do SQL para o plural correto do banco (`eletronicos`).
* **Decisão da dupla:** Corrigimos o comando SQL no método `historico()` do `EstoqueController.php`.
* **Teste realizado:** Requisição `GET http://localhost/GestaoEstoqueEletroMVC/public/movimentacoes` no Thunder Client.
* **Resultado obtido:** HTTP 200 OK | Lista com todo o histórico contendo `eletronico_nome`, `tipo`, `quantidade`, `saldo_anterior`, `saldo_posterior` e `data_movimentacao`.
* **Evidência:** Retorno JSON estruturado exibindo a movimentação registrada com o produto "Notebook Vision 14".
* **O que aprendemos:** Verificar os nomes das tabelas e colunas diretamente no esquemático do MySQL evita divergências no mapeamento ORM/DAO do código.