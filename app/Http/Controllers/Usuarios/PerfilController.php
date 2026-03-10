<?php

namespace App\Http\Controllers\Usuarios;

use App\Http\Controllers\BaseCrudController;
use App\Models\Perfil;
use Illuminate\Http\Request;
use App\Services\AuthService;

class PerfilController extends BaseCrudController
{
    public function __construct(private AuthService $authService) {}

    protected string $modelClass = Perfil::class;

    protected array $rules = [
        'nome' => 'required|string|max:50|unique:tb_perfis,nome',
        'descricao' => 'nullable|string|max:200'
    ];

    protected array $filterable = ['nome'];

    public function index(Request $request)
    {
        $query = Perfil::query();

        $user = $request->user();
        if ($user && ! $this->authService->isAdminMaster($user)) {
            $query->where('id', '!=', 1)
                ->where('nome', '!=', 'admin_master');
        }

        foreach ($this->filterable as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        return $query->paginate($perPage);
    }

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
