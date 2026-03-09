**Documentacao Tecnica Base de Dados**

**Visao geral**
Banco MySQL organizado por modulos, com chave estrangeira e integracao entre pedidos, itens, cozinha, caixa e pagamentos. A maioria das tabelas possui `created_at` e `updated_at`.

**Tabela: tb_planos**
- Campos: id, nome, limite_usuarios, limite_produtos, valor, ativo, created_at, updated_at
- Relacao: 1:N com `tb_empresas`

**Tabela: tb_empresas**
- Campos: id, plano_id, nome, nome_fantasia, cnpj, telefone, email, endereco, cidade, estado, status, created_at, updated_at
- Relacao: N:1 com `tb_planos`
- Relacao: 1:N com usuarios, categorias, produtos e demais entidades por `empresa_id`

**Tabela: tb_assinaturas**
- Campos: id, empresa_id, plano_id, data_inicio, data_fim, status, created_at, updated_at
- Relacao: N:1 com `tb_empresas` e `tb_planos`

**Tabela: tb_usuarios**
- Campos: id, empresa_id, nome, email, senha, ativo, created_at, updated_at
- Relacao: N:1 com `tb_empresas`

**Tabela: tb_perfis**
- Campos: id, nome, descricao, created_at, updated_at

**Tabela: tb_permissoes**
- Campos: id, nome, descricao, created_at, updated_at

**Tabela: tb_usuario_perfis**
- Campos: id, usuario_id, perfil_id, created_at, updated_at

**Tabela: tb_perfil_permissoes**
- Campos: id, perfil_id, permissao_id, created_at, updated_at

**Tabela: tb_categorias**
- Campos: id, empresa_id, nome, descricao, ativo, created_at, updated_at
- Relacao: N:1 com `tb_empresas`

**Tabela: tb_produtos**
- Campos: id, empresa_id, categoria_id, nome, descricao, preco, custo, codigo_barras, ativo, created_at, updated_at
- Relacao: N:1 com `tb_empresas` e `tb_categorias`

**Tabela: tb_produto_imagens**
- Campos: id, produto_id, url, created_at, updated_at

**Tabela: tb_produto_opcoes**
- Campos: id, produto_id, nome, created_at, updated_at

**Tabela: tb_produto_opcao_itens**
- Campos: id, opcao_id, nome, preco_adicional, created_at, updated_at

**Tabela: tb_mesas**
- Campos: id, empresa_id, numero, status, created_at, updated_at

**Tabela: tb_comandas**
- Campos: id, empresa_id, numero, status, created_at, updated_at

**Tabela: tb_clientes**
- Campos: id, empresa_id, nome, telefone, email, created_at, updated_at

**Tabela: tb_cliente_enderecos**
- Campos: id, cliente_id, endereco, numero, bairro, cidade, referencia, created_at, updated_at

**Tabela: tb_entregadores**
- Campos: id, empresa_id, nome, telefone, ativo, created_at, updated_at

**Tabela: tb_pedidos**
- Campos: id, empresa_id, usuario_id, mesa_id, comanda_id, cliente_id, tipo, status, total, criado_em, created_at, updated_at
- Relacao: N:1 com `tb_usuarios`, `tb_mesas`, `tb_comandas`, `tb_clientes`

**Tabela: tb_pedido_itens**
- Campos: id, pedido_id, produto_id, quantidade, preco, observacao, created_at, updated_at

**Tabela: tb_pedido_item_opcoes**
- Campos: id, pedido_item_id, opcao_item_id, created_at, updated_at

**Tabela: tb_entregas**
- Campos: id, pedido_id, entregador_id, taxa, status, created_at, updated_at

**Tabela: tb_cozinha_estacoes**
- Campos: id, empresa_id, nome, created_at, updated_at

**Tabela: tb_cozinha_itens**
- Campos: id, pedido_item_id, estacao_id, status, created_at, updated_at

**Tabela: tb_formas_pagamento**
- Campos: id, empresa_id, nome, created_at, updated_at

**Tabela: tb_pagamentos**
- Campos: id, pedido_id, forma_pagamento_id, valor, troco, created_at, updated_at

**Tabela: tb_caixas**
- Campos: id, empresa_id, usuario_id, aberto_em, fechado_em, saldo_inicial, saldo_final, created_at, updated_at

**Tabela: tb_caixa_movimentos**
- Campos: id, caixa_id, tipo, valor, descricao, criado_em, created_at, updated_at

**Tabela: tb_estoque**
- Campos: id, empresa_id, produto_id, quantidade, created_at, updated_at

**Tabela: tb_estoque_movimentos**
- Campos: id, estoque_id, tipo, quantidade, descricao, created_at, updated_at

**Tabela: tb_configuracoes**
- Campos: id, empresa_id, chave, valor, created_at, updated_at

**Tabela: tb_confirmacoes_email**
- Campos: id, empresa_id, usuario_id, token, expira_em, confirmado_em, created_at, updated_at

**Observacoes**
- Varios relacionamentos dependem de `empresa_id` para multiempresa.
- Colunas adicionadas por migracoes posteriores: `tb_categorias.descricao`, `tb_pagamentos.troco`, `tb_pedidos.created_at`, `tb_pedidos.updated_at`.
