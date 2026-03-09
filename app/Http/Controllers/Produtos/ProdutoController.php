<?php

namespace App\Http\Controllers\Produtos;

use App\Http\Controllers\BaseCrudController;
use App\Models\Produto;
use Illuminate\Http\Request;

class ProdutoController extends BaseCrudController
{
    protected string $modelClass = Produto::class;

    protected array $rules = [
        'categoria_id' => 'required|exists:tb_categorias,id',
        'nome' => 'required|string|max:150',
        'descricao' => 'nullable|string',
        'preco' => 'required|numeric|min:0',
        'custo' => 'nullable|numeric|min:0',
        'codigo_barras' => 'nullable|string|max:50',
        'ativo' => 'required|boolean'
    ];

    protected array $filterable = ['categoria_id', 'ativo'];

    public function listagem(Request $request)
    {
        $empresaId = $this->resolveEmpresaId($request);
        if (!$empresaId) {
            abort(400, 'empresa_id nao informado');
        }

        $produtos = Produto::query()
            ->from('tb_produtos as P')
            ->join('tb_categorias as C', function ($join) {
                $join->on('C.id', '=', 'P.categoria_id')
                    ->on('C.empresa_id', '=', 'P.empresa_id');
            })
            ->leftJoin('tb_estoque as E', function ($join) {
                $join->on('E.produto_id', '=', 'P.id')
                    ->on('E.empresa_id', '=', 'P.empresa_id');
            })
            ->where('P.empresa_id', $empresaId)
            ->select([
                'P.id',
                'P.empresa_id',
                'P.nome as produto',
                'C.nome as categoria',
                'P.preco',
                'P.ativo',
                'E.quantidade as estoque_quantidade',
            ])
            ->orderBy('P.nome')
            ->get()
            ->map(function ($produto) {
                $quantidade = $produto->estoque_quantidade;
                if ($quantidade === null) {
                    $statusEstoque = 'Sem estoque';
                } elseif ((float) $quantidade < 10) {
                    $statusEstoque = 'Baixo';
                } else {
                    $statusEstoque = 'Disponivel';
                }

                return [
                    'id' => $produto->id,
                    'empresa_id' => $produto->empresa_id,
                    'produto' => $produto->produto,
                    'categoria' => $produto->categoria,
                    'preco' => (float) $produto->preco,
                    'status' => $produto->ativo ? 'Ativo' : 'Inativo',
                    'estoque' => $statusEstoque,
                    'estoque_quantidade' => $quantidade !== null ? (float) $quantidade : null,
                    'acoes' => $produto->ativo ? ['editar', 'inativar', 'excluir'] : ['editar', 'ativar', 'excluir'],
                ];
            });

        return response()->json($produtos);
    }
}
