<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Response;

class BaseCrudController extends Controller
{
    protected string $modelClass;
    protected array $rules = [];
    protected array $updateRules = [];
    protected array $filterable = [];

    public function index(Request $request)
    {
        $query = ($this->modelClass)::query();

        $this->applyEmpresaScope($query, $request);

        foreach ($this->filterable as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        return $query->paginate($perPage);
    }

    public function show(Request $request, int $id)
    {
        $query = ($this->modelClass)::query();
        $this->applyEmpresaScope($query, $request);

        $item = $query->findOrFail($id);

        return $item;
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules);
        $this->injectEmpresaId($data, $request);

        $item = ($this->modelClass)::create($data);

        return response()->json($item, Response::HTTP_CREATED);
    }

    public function update(Request $request, int $id)
    {
        $rules = $this->updateRules ?: $this->buildUpdateRules($this->rules);
        $data = $request->validate($rules);

        $query = ($this->modelClass)::query();
        $this->applyEmpresaScope($query, $request);

        $item = $query->findOrFail($id);
        $item->fill($data);
        $item->save();

        return $item;
    }

    public function destroy(Request $request, int $id)
    {
        $query = ($this->modelClass)::query();
        $this->applyEmpresaScope($query, $request);

        $item = $query->findOrFail($id);
        $item->delete();

        return response()->noContent();
    }

    protected function applyEmpresaScope($query, Request $request): void
    {
        /** @var Model $model */
        $model = new $this->modelClass();
        $empresaScoped = property_exists($model, 'empresaScoped') && $model->empresaScoped;

        if ($empresaScoped) {
            $empresaId = $this->resolveEmpresaId($request);
            if (!$empresaId) {
                abort(Response::HTTP_BAD_REQUEST, 'empresa_id nao informado');
            }

            $query->where('empresa_id', $empresaId);
        }
    }

    protected function injectEmpresaId(array &$data, Request $request): void
    {
        /** @var Model $model */
        $model = new $this->modelClass();
        $empresaScoped = property_exists($model, 'empresaScoped') && $model->empresaScoped;

        if ($empresaScoped) {
            $empresaId = $this->resolveEmpresaId($request);
            if (!$empresaId) {
                abort(Response::HTTP_BAD_REQUEST, 'empresa_id nao informado');
            }

            $data['empresa_id'] = $empresaId;
        }
    }

    protected function resolveEmpresaId(Request $request): ?int
    {
        $empresaId = $request->header('X-Empresa-Id');
        if ($empresaId) {
            return (int) $empresaId;
        }

        $user = $request->user();
        if ($user && isset($user->empresa_id)) {
            return (int) $user->empresa_id;
        }

        return null;
    }

    protected function buildUpdateRules(array $rules): array
    {
        $updated = [];
        foreach ($rules as $field => $rule) {
            if (is_array($rule)) {
                $updated[$field] = array_merge(['sometimes'], $rule);
                continue;
            }

            $updated[$field] = 'sometimes|' . $rule;
        }

        return $updated;
    }
}
