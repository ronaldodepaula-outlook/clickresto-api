**Documentacao Tecnica Fluxo Operacional**

**1. Onboarding**
- Cadastro publico: cria empresa, usuario e assinatura.
- Confirmacao de email (quando habilitado).

**2. Login e sessao**
- Usuario autentica via `/auth/login`.
- Token JWT e usado em todas as chamadas.

**3. Cadastro base**
- Planos e permissoes (admin).
- Usuarios e perfis (admin da empresa).
- Configuracoes da empresa.

**4. Cardapio**
- Categorias e produtos.
- Imagens e opcoes de produto.
- Estoque por produto.

**5. Atendimento no salao**
1. Selecionar mesa.
2. Abrir comanda.
3. Abrir pedido (tipo mesa).
4. Adicionar itens ao pedido.
5. Enviar pedido para cozinha.

**6. Cozinha**
- Itens aparecem por estacao.
- Status do item: recebido, preparo, pronto.
- Listagem por usuario quando necessario.

**7. Caixa e pagamento**
1. Abrir caixa com saldo inicial.
2. Registrar pagamentos por pedido.
3. Fechar pedido quando total pago >= total do pedido.
4. Fechar caixa com saldo final.

**8. Delivery**
- Cadastro de entregadores.
- Criacao de entregas por pedido.
- Controle de status da entrega.

**9. Relatorios**
- Vendas do dia.
- Vendas por produto.
- Vendas por forma de pagamento.
- Ticket medio.
- Pedidos por canal.
- Movimentacoes do dia com itens e pagamentos.

**10. Fluxo resumido (mobile)**
1. Listar mesas livres.
2. Criar comanda.
3. Abrir pedido.
4. Listar categorias e produtos.
5. Adicionar itens e complementos.
6. Enviar para cozinha.
7. Receber pagamento.
8. Fechar pedido.

