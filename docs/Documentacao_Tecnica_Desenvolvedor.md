**Documentacao Tecnica do Desenvolvedor**

**1. Objetivo do sistema**
API SaaS multiempresa para operacao de restaurantes. Oferece cadastro e controle de usuarios, mesas, comandas, pedidos, cozinha, caixa, estoque, delivery e relatorios.

**2. Arquitetura e stack**
- Backend: Laravel
- Banco: MySQL
- Autenticacao: JWT via `auth:api`
- Padrao de rotas: REST (apiResource) + rotas especificas

**3. Estrutura do projeto**
- `app/Http/Controllers`: Controllers dos modulos
- `app/Models`: Modelos Eloquent
- `routes/api.php`: Rotas da API v1
- `database/migrations`: Estrutura do banco
- `public/swagger`: Swagger UI e OpenAPI
- `postman/`: Colecoes Postman

**4. Autenticacao e sessao**
- Login em `/api/v1/auth/login`
- Token JWT retornado no login
- Token utilizado com `Authorization: Bearer <token>`
- Usuario autenticado: `/api/v1/auth/me`

**5. Multiempresa**
- Modelos com `empresaScoped = true` usam filtro por `empresa_id` automaticamente no `BaseCrudController`
- `empresa_id` e obtido do usuario logado ou `X-Empresa-Id` (header opcional)
- Se nao houver `empresa_id`, retorna `400 empresa_id nao informado`

**6. Padrao CRUD**
Controllers que extendem `BaseCrudController` herdam:
- `index` (lista com filtros e paginacao)
- `show` (detalhe por id)
- `store` (criar)
- `update` (atualizar)
- `destroy` (excluir)

**7. Boas praticas**
- Validar entrada via `Request::validate`
- Aplicar `empresa_id` via `injectEmpresaId`
- Filtrar consultas por `applyEmpresaScope`
- Usar `paginate` quando a lista for grande

**8. Principais rotas (exemplos)**
- Auth: `/api/v1/auth/*`
- Categorias: `/api/v1/categorias`
- Produtos: `/api/v1/produtos`
- Pedidos: `/api/v1/pedidos`
- Cozinha: `/api/v1/cozinha-itens`
- Caixa: `/api/v1/caixas`

**9. Swagger**
Documentacao em:
- `public/swagger/index.html`
- `public/swagger/openapi.json`

**10. Postman**
Colecoes:
- `postman/ClickResto-Completo.postman_collection.json`
- `postman/ClickResto-Smartphone.postman_collection.json`

**11. Como adicionar um novo endpoint**
1. Criar metodo no controller.
2. Definir validacoes.
3. Aplicar escopo de empresa se necessario.
4. Adicionar rota em `routes/api.php` antes do `apiResource` quando houver conflito.
5. Atualizar Swagger e Postman se aplicavel.

**12. Padrões de resposta**
- CRUD retorna JSON do modelo
- Listas usam paginacao do Laravel
- Erros usam status HTTP apropriados

