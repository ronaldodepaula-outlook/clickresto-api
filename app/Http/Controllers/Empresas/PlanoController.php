<?php

namespace App\Http\Controllers\Empresas;

use App\Http\Controllers\BaseCrudController;
use App\Models\Plano;
use Illuminate\Http\Request;

class PlanoController extends BaseCrudController
{
    protected string $modelClass = Plano::class;

    protected array $rules = [
        'nome' => 'required|string|max:100',
        'limite_usuarios' => 'required|integer|min:0',
        'limite_produtos' => 'required|integer|min:0',
        'valor' => 'required|numeric|min:0',
        'ativo' => 'required|boolean'
    ];

    protected array $filterable = ['nome'];

    public function publicIndex(Request $request)
    {
        $query = Plano::query()->where('ativo', true);

        return $query->orderBy('valor')->get();
    }
}
