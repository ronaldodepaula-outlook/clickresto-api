<?php

namespace App\Http\Controllers\Usuarios;

use App\Http\Controllers\BaseCrudController;
use App\Models\Perfil;
use Illuminate\Http\Request;

class PerfilController extends BaseCrudController
{
    protected string $modelClass = Perfil::class;

    protected array $rules = [
        'nome' => 'required|string|max:50|unique:tb_perfis,nome',
        'descricao' => 'nullable|string|max:200'
    ];

    protected array $filterable = ['nome'];

    public function update(Request $request, int $id)
    {
        $rules = $this->buildUpdateRules($this->rules);
        $rules['nome'] = 'sometimes|string|max:50|unique:tb_perfis,nome,' . $id;

        $data = $request->validate($rules);

        $perfil = Perfil::findOrFail($id);
        $perfil->fill($data);
        $perfil->save();

        return $perfil;
    }
}
