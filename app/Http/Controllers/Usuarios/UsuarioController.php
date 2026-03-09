<?php

namespace App\Http\Controllers\Usuarios;

use App\Http\Controllers\BaseCrudController;
use App\Models\Empresa;
use App\Models\Plano;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class UsuarioController extends BaseCrudController
{
    protected string $modelClass = Usuario::class;

    protected array $rules = [
        'nome' => 'required|string|max:150',
        'email' => 'required|email|max:150|unique:tb_usuarios,email',
        'senha' => 'required|string|min:6',
        'ativo' => 'required|boolean',
    ];

    protected array $filterable = ['email', 'ativo'];

    public function store(Request $request)
    {
        $data = $request->validate($this->rules);
        $data['senha'] = Hash::make($data['senha']);

        $empresaId = $this->resolveEmpresaId($request);
        if (!$empresaId) {
            return response()->json(['message' => 'empresa_id nao informado'], Response::HTTP_BAD_REQUEST);
        }

        $empresa = Empresa::findOrFail($empresaId);
        $plano = Plano::find($empresa->plano_id);
        if ($plano && $plano->limite_usuarios > 0) {
            $ativos = Usuario::where('empresa_id', $empresaId)->where('ativo', true)->count();
            if ($ativos >= $plano->limite_usuarios) {
                return response()->json([
                    'message' => 'Limite de usuarios do plano atingido',
                    'limite_usuarios' => $plano->limite_usuarios,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $data['empresa_id'] = $empresaId;

        $user = Usuario::create($data);

        return response()->json($user, 201);
    }

    public function update(Request $request, int $id)
    {
        $rules = $this->buildUpdateRules($this->rules);
        $rules['email'] = 'sometimes|email|max:150|unique:tb_usuarios,email,' . $id;
        $rules['senha'] = 'sometimes|string|min:6';

        $data = $request->validate($rules);
        if (isset($data['senha'])) {
            $data['senha'] = Hash::make($data['senha']);
        }

        $query = Usuario::query();
        $this->applyEmpresaScope($query, $request);

        $user = $query->findOrFail($id);
        $user->fill($data);
        $user->save();

        return $user;
    }
}
