<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\ClienteEndereco;
use App\Models\Comanda;
use App\Models\CozinhaEstacao;
use App\Models\Empresa;
use App\Models\FormaPagamento;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Produto;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::first();
        if (!$empresa) {
            return;
        }

        $usuario = Usuario::where('empresa_id', $empresa->id)->first();
        if (!$usuario) {
            return;
        }

        $categorias = [
            'Bebidas' => ['Agua', 'Refrigerante', 'Suco Natural'],
            'Lanches' => ['Hamburguer', 'Cheeseburger', 'X-Bacon'],
            'Pratos' => ['Prato Feito', 'Parmegiana', 'Feijoada'],
            'Sobremesas' => ['Pudim', 'Sorvete', 'Mousse'],
        ];

        $categoriaIds = [];
        foreach ($categorias as $cat => $produtos) {
            $categoria = Categoria::updateOrCreate(
                ['empresa_id' => $empresa->id, 'nome' => $cat],
                ['ativo' => true]
            );
            $categoriaIds[$cat] = $categoria->id;

            foreach ($produtos as $idx => $nomeProduto) {
                Produto::updateOrCreate(
                    ['empresa_id' => $empresa->id, 'nome' => $nomeProduto],
                    [
                        'categoria_id' => $categoria->id,
                        'descricao' => $nomeProduto . ' delicioso',
                        'preco' => 10 + ($idx * 5),
                        'custo' => 5 + ($idx * 2),
                        'ativo' => true,
                    ]
                );
            }
        }

        for ($i = 1; $i <= 10; $i++) {
            Mesa::updateOrCreate(
                ['empresa_id' => $empresa->id, 'numero' => $i],
                ['status' => 'livre']
            );
        }

        $formas = ['Dinheiro', 'Cartao Credito', 'Cartao Debito', 'Pix', 'Voucher'];
        foreach ($formas as $nome) {
            FormaPagamento::updateOrCreate(
                ['empresa_id' => $empresa->id, 'nome' => $nome],
                []
            );
        }

        CozinhaEstacao::updateOrCreate(
            ['empresa_id' => $empresa->id, 'nome' => 'Geral'],
            []
        );

        $cliente = Cliente::updateOrCreate(
            ['empresa_id' => $empresa->id, 'telefone' => '11999999999'],
            ['nome' => 'Cliente Exemplo', 'email' => 'cliente@exemplo.com']
        );

        ClienteEndereco::updateOrCreate(
            ['cliente_id' => $cliente->id, 'endereco' => 'Rua Exemplo'],
            ['numero' => '123', 'bairro' => 'Centro', 'cidade' => 'Sao Paulo', 'referencia' => 'Proximo ao parque']
        );

        Comanda::updateOrCreate(
            ['empresa_id' => $empresa->id, 'numero' => 'C001'],
            ['status' => 'aberta']
        );

        $mesa = Mesa::where('empresa_id', $empresa->id)->first();
        $produto = Produto::where('empresa_id', $empresa->id)->first();
        if ($mesa && $produto) {
            $pedido = Pedido::create([
                'empresa_id' => $empresa->id,
                'usuario_id' => $usuario->id,
                'mesa_id' => $mesa->id,
                'tipo' => 'mesa',
                'status' => 'aberto',
                'total' => $produto->preco,
            ]);

            PedidoItem::create([
                'pedido_id' => $pedido->id,
                'produto_id' => $produto->id,
                'quantidade' => 1,
                'preco' => $produto->preco,
            ]);
        }
    }
}
