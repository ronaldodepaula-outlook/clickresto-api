**ClickResto API**
API SaaS multiempresa para gestao de restaurantes (PDV, mesas, comandas, delivery, cozinha, caixa, estoque e relatorios).

**Visao geral**
Projeto backend em Laravel para operacoes de restaurante com controle por empresa, usuarios e permissoes. A API segue padrao REST, com autenticacao JWT e rotas organizadas por modulos.

**Tecnologias**
- PHP 8+
- Laravel
- MySQL
- JWT (auth:api)

**Requisitos**
- PHP com extensoes comuns do Laravel
- Composer
- Banco MySQL
- (Opcional) XAMPP para ambiente local

**Configuracao rapida**
1. Copie `.env.example` para `.env` e ajuste credenciais de banco.
2. Instale dependencias:
```bash
composer install
```
3. Execute migracoes e seeders quando aplicavel:
```bash
php artisan migrate
php artisan db:seed
```
4. Rode o servidor local:
```bash
php artisan serve
```

**Documentacao**
- Documentacao tecnica do desenvolvedor: `docs/Documentacao_Tecnica_Desenvolvedor.md`
- Documentacao tecnica da base de dados: `docs/Documentacao_Tecnica_Base_Dados.md`
- Documentacao tecnica do fluxo operacional: `docs/Documentacao_Tecnica_Fluxo_Operacional.md`
- Documentacao comercial para GitHub: `docs/Documentacao_Comercial_GitHub.md`

**Swagger**
Disponivel em `public/swagger/index.html` e `public/swagger/openapi.json`.

**Postman**
Colecoes em `postman/`:
- `postman/ClickResto-Completo.postman_collection.json`
- `postman/ClickResto-Smartphone.postman_collection.json`

**Observacoes**
- Rotas com escopo de empresa usam `empresa_id` do usuario logado.
- Se o token nao possuir `empresa_id`, endpoints com escopo retornam erro `400`.
