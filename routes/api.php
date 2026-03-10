<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Empresas\PlanoController;
use App\Http\Controllers\Empresas\EmpresaController;
use App\Http\Controllers\Empresas\AssinaturaController;
use App\Http\Controllers\Usuarios\UsuarioController;
use App\Http\Controllers\Usuarios\PerfilController;
use App\Http\Controllers\Usuarios\PermissaoController;
use App\Http\Controllers\Usuarios\UsuarioPerfilController;
use App\Http\Controllers\Usuarios\PerfilPermissaoController;
use App\Http\Controllers\Produtos\CategoriaController;
use App\Http\Controllers\Produtos\ProdutoController;
use App\Http\Controllers\Produtos\ProdutoImagemController;
use App\Http\Controllers\Produtos\ProdutoOpcaoController;
use App\Http\Controllers\Produtos\ProdutoOpcaoItemController;
use App\Http\Controllers\Mesas\MesaController;
use App\Http\Controllers\Comandas\ComandaController;
use App\Http\Controllers\Clientes\ClienteController;
use App\Http\Controllers\Clientes\ClienteEnderecoController;
use App\Http\Controllers\Delivery\EntregadorController;
use App\Http\Controllers\Delivery\EntregaController;
use App\Http\Controllers\Pedidos\PedidoController;
use App\Http\Controllers\Pedidos\PedidoItemController;
use App\Http\Controllers\Pedidos\PedidoItemOpcaoController;
use App\Http\Controllers\Cozinha\CozinhaEstacaoController;
use App\Http\Controllers\Cozinha\CozinhaItemController;
use App\Http\Controllers\Pagamentos\FormaPagamentoController;
use App\Http\Controllers\Pagamentos\PagamentoController;
use App\Http\Controllers\Caixa\CaixaController;
use App\Http\Controllers\Caixa\MovimentoCaixaController;
use App\Http\Controllers\Estoque\EstoqueController;
use App\Http\Controllers\Estoque\EstoqueMovimentoController;
use App\Http\Controllers\Configuracoes\ConfiguracaoController;
use App\Http\Controllers\Relatorios\RelatorioController;
use App\Http\Controllers\Notificacoes\NotificacaoController;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);
        Route::middleware('auth:api')->post('refresh', [AuthController::class, 'refresh']);
        Route::middleware('auth:api')->get('me', [AuthController::class, 'me']);
        Route::middleware('auth:api')->post('logout', [AuthController::class, 'logout']);
    });

    Route::post('public/cadastro', [AuthController::class, 'publicCadastro']);
    Route::post('public/cadastro-trial', [AuthController::class, 'publicCadastroTrial'])->middleware('throttle:5,1');
    Route::get('public/planos', [PlanoController::class, 'publicIndex']);
    Route::get('public/confirmar-email', [AuthController::class, 'confirmarEmail']);

    Route::middleware(['auth:api', 'check.admin'])->group(function () {
        Route::apiResource('planos', PlanoController::class);
        Route::apiResource('perfis', PerfilController::class);
        Route::apiResource('permissoes', PermissaoController::class);
        Route::apiResource('perfil-permissoes', PerfilPermissaoController::class);
        Route::get('empresas/admin-list', [EmpresaController::class, 'adminList']);
    });

    Route::middleware(['auth:api', 'check.admin_empresa'])->group(function () {
        Route::get('saas/overview', [\App\Http\Controllers\Admin\SaasController::class, 'overview']);
        Route::get('dashboard/operational', [\App\Http\Controllers\Admin\DashboardController::class, 'operational']);
    });

    Route::middleware(['auth:api', 'empresa.ativa', 'check.admin'])->group(function () {
        Route::apiResource('empresas', EmpresaController::class);
        Route::apiResource('assinaturas', AssinaturaController::class);
        Route::apiResource('usuario-perfis', UsuarioPerfilController::class);
    });

    Route::middleware(['auth:api', 'empresa.ativa'])->group(function () {
        Route::apiResource('usuarios', UsuarioController::class);

        Route::get('categorias/listagem', [CategoriaController::class, 'listagem']);
        Route::get('categorias/totais', [CategoriaController::class, 'totais']);
        Route::get('prod_categorias/{id}', [CategoriaController::class, 'produtosPorCategoria']);
        Route::apiResource('categorias', CategoriaController::class);
        Route::get('produtos/listagem', [ProdutoController::class, 'listagem']);
        Route::apiResource('produtos', ProdutoController::class);
        Route::apiResource('produto-imagens', ProdutoImagemController::class);
        Route::apiResource('produto-opcoes', ProdutoOpcaoController::class);
        Route::apiResource('produto-opcao-itens', ProdutoOpcaoItemController::class);

        Route::apiResource('mesas', MesaController::class);
        Route::apiResource('comandas', ComandaController::class);

        Route::apiResource('clientes', ClienteController::class);
        Route::apiResource('cliente-enderecos', ClienteEnderecoController::class);

        Route::apiResource('entregadores', EntregadorController::class);
        Route::apiResource('entregas', EntregaController::class);

        Route::post('pedidos/abrir', [PedidoController::class, 'abrir']);
        Route::post('pedidos/enviar-cozinha-auto', [PedidoController::class, 'enviarCozinhaAuto']);
        Route::get('pedidos/usuario', [PedidoController::class, 'pedidosUsuario']);
        Route::post('pedidos/{pedido}/itens', [PedidoController::class, 'adicionarItem']);
        Route::post('pedidos/{pedido}/enviar-cozinha', [PedidoController::class, 'enviarCozinha']);
        Route::apiResource('pedidos', PedidoController::class);
        Route::patch('pedidos/{pedido}/status', [PedidoController::class, 'atualizarStatus']);
        Route::post('pedidos/{pedido}/fechar', [PedidoController::class, 'fechar']);

        Route::apiResource('pedido-itens', PedidoItemController::class);
        Route::apiResource('pedido-item-opcoes', PedidoItemOpcaoController::class);

        Route::apiResource('cozinha-estacoes', CozinhaEstacaoController::class);
        Route::get('cozinha-itens/pedidos', [CozinhaItemController::class, 'pedidos']);
        Route::get('cozinha-itens/pedidos-usuario', [CozinhaItemController::class, 'pedidosPorUsuario']);
        Route::get('cozinha-itens/pedidos-usuario-preparo', [CozinhaItemController::class, 'pedidosPreparoPorUsuario']);
        Route::patch('cozinha-itens/{id}/status', [CozinhaItemController::class, 'atualizarStatus']);
        Route::apiResource('cozinha-itens', CozinhaItemController::class);

        Route::apiResource('formas-pagamento', FormaPagamentoController::class);
        Route::apiResource('pagamentos', PagamentoController::class);

        Route::apiResource('caixas', CaixaController::class);
        Route::post('caixas/abrir', [CaixaController::class, 'abrir']);
        Route::post('caixas/{caixa}/fechar', [CaixaController::class, 'fechar']);
        Route::apiResource('caixa-movimentos', MovimentoCaixaController::class);

        Route::apiResource('estoque', EstoqueController::class);
        Route::apiResource('estoque-movimentos', EstoqueMovimentoController::class);

        Route::apiResource('configuracoes', ConfiguracaoController::class);
        Route::apiResource('notificacoes', NotificacaoController::class);
        Route::post('notificacoes/{id}/lida', [NotificacaoController::class, 'marcarLida']);
        Route::post('notificacoes/ler-multiplas', [NotificacaoController::class, 'marcarLidas']);

        Route::get('relatorios/vendas-dia', [RelatorioController::class, 'vendasDia']);
        Route::get('relatorios/vendas-produto', [RelatorioController::class, 'vendasProduto']);
        Route::get('relatorios/vendas-pagamento', [RelatorioController::class, 'vendasFormaPagamento']);
        Route::get('relatorios/ticket-medio', [RelatorioController::class, 'ticketMedio']);
        Route::get('relatorios/pedidos-por-canal', [RelatorioController::class, 'pedidosPorCanal']);
        Route::get('relatorios/movimentacoes-dia', [RelatorioController::class, 'movimentacoesDia']);
    });
});
