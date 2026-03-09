<?php

namespace App\Http\Controllers\Produtos;

use App\Http\Controllers\BaseCrudController;
use App\Models\Categoria;
use App\Models\Produto;
use Illuminate\Http\Request;

class CategoriaController extends BaseCrudController
{
    protected string $modelClass = Categoria::class;

    protected array $rules = [
        'nome' => 'required|string|max:120',
        'descricao' => 'nullable|string|max:255',
        'ativo' => 'required|boolean'
    ];

    protected array $filterable = ['ativo'];

    public function listagem(Request $request)
    {
        $empresaId = $this->resolveEmpresaId($request);
        if (!$empresaId) {
            abort(400, 'empresa_id nao informado');
        }

        $query = Categoria::query()
            ->where('tb_categorias.empresa_id', $empresaId)
            ->leftJoin('tb_produtos', function ($join) use ($empresaId) {
                $join->on('tb_produtos.categoria_id', '=', 'tb_categorias.id')
                    ->where('tb_produtos.empresa_id', $empresaId);
            })
            ->select([
                'tb_categorias.id',
                'tb_categorias.empresa_id',
                'tb_categorias.nome',
                'tb_categorias.descricao',
                'tb_categorias.ativo',
                'tb_categorias.created_at',
                'tb_categorias.updated_at',
            ])
            ->selectRaw('COUNT(tb_produtos.id) as itens')
            ->groupBy(
                'tb_categorias.id',
                'tb_categorias.empresa_id',
                'tb_categorias.nome',
                'tb_categorias.descricao',
                'tb_categorias.ativo',
                'tb_categorias.created_at',
                'tb_categorias.updated_at'
            )
            ->orderBy('tb_categorias.nome');

        $categorias = $query->get()->map(function ($categoria) {
            return [
                'id' => $categoria->id,
                'empresa_id' => $categoria->empresa_id,
                'categoria' => $categoria->nome,
                'descricao' => $categoria->descricao,
                'ativo' => (bool) $categoria->ativo,
                'created_at' => $categoria->created_at,
                'updated_at' => $categoria->updated_at,
                'itens' => (int) $categoria->itens,
                'status' => $categoria->ativo ? 'Ativa' : 'Oculta',
                'acoes' => $categoria->ativo ? ['editar', 'ocultar', 'excluir'] : ['editar', 'ativar', 'excluir'],
            ];
        });

        return response()->json($categorias);
    }

    public function totais(Request $request)
    {
        $empresaId = $this->resolveEmpresaId($request);
        if (!$empresaId) {
            abort(400, 'empresa_id nao informado');
        }

        $categorias = Categoria::query()
            ->from('tb_categorias as C')
            ->join('tb_produtos as P', function ($join) use ($empresaId) {
                $join->on('P.empresa_id', '=', 'C.empresa_id')
                    ->on('P.categoria_id', '=', 'C.id');
            })
            ->where('C.empresa_id', $empresaId)
            ->select([
                'C.id',
                'C.empresa_id',
                'C.nome',
                'C.descricao',
                'C.ativo',
            ])
            ->selectRaw('COUNT(*) as total_produtos')
            ->groupBy('C.id', 'C.empresa_id', 'C.nome', 'C.descricao', 'C.ativo')
            ->orderBy('C.nome')
            ->get();

        return response()->json($categorias);
    }

    public function produtosPorCategoria(Request $request, int $id)
    {
        $empresaId = $this->resolveEmpresaId($request);
        if (!$empresaId) {
            abort(400, 'empresa_id nao informado');
        }

        $categoria = Categoria::query()
            ->where('empresa_id', $empresaId)
            ->findOrFail($id);

        $produtos = Produto::query()
            ->where('empresa_id', $empresaId)
            ->where('categoria_id', $categoria->id)
            ->orderBy('nome')
            ->paginate((int) $request->input('per_page', 15));

        return response()->json([$categoria, $produtos]);
    }
}
