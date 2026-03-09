<?php

namespace App\Http\Controllers\Notificacoes;

use App\Http\Controllers\BaseCrudController;
use App\Models\Notificacao;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

class NotificacaoController extends BaseCrudController
{
    protected string $modelClass = Notificacao::class;

    protected array $rules = [
        'pedido_id' => 'nullable|exists:tb_pedidos,id',
        'mesa_id' => 'nullable|exists:tb_mesas,id',
        'comanda_id' => 'nullable|exists:tb_comandas,id',
        'cliente_id' => 'nullable|exists:tb_clientes,id',
        'usuario_id' => 'nullable|exists:tb_usuarios,id',
        'estacao_id' => 'nullable|exists:tb_cozinha_estacoes,id',
        'destino' => 'required|in:cozinha,operacao,mesa,comanda',
        'tipo' => 'required|in:pedido_status,item_status,mensagem',
        'status' => 'nullable|in:pendente,enviada,lida',
        'prioridade' => 'nullable|in:baixa,normal,alta',
        'titulo' => 'nullable|string|max:120',
        'mensagem' => 'nullable|string',
        'payload' => 'nullable|array',
        'enviada_em' => 'nullable|date',
        'lida_em' => 'nullable|date',
    ];

    protected array $filterable = [
        'pedido_id',
        'mesa_id',
        'comanda_id',
        'cliente_id',
        'usuario_id',
        'estacao_id',
        'destino',
        'tipo',
        'status',
        'prioridade',
    ];

    public function index(Request $request)
    {
        $query = ($this->modelClass)::query();
        $this->applyEmpresaScope($query, $request);

        foreach ($this->filterable as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        $query->orderByDesc('created_at')->orderByDesc('id');

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        return $query->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules);
        $this->injectEmpresaId($data, $request);
        $this->validateDestino($data);

        $item = Notificacao::create($data);

        return response()->json($item, Response::HTTP_CREATED);
    }

    public function update(Request $request, int $id)
    {
        $rules = $this->updateRules ?: $this->buildUpdateRules($this->rules);
        $data = $request->validate($rules);

        $query = Notificacao::query();
        $this->applyEmpresaScope($query, $request);

        $item = $query->findOrFail($id);
        $item->fill($data);
        $this->validateDestino($item->toArray());
        $item->save();

        return $item;
    }

    public function marcarLida(Request $request, int $id)
    {
        $data = $request->validate([
            'lida_em' => 'nullable|date',
        ]);

        $query = Notificacao::query();
        $this->applyEmpresaScope($query, $request);

        $item = $query->findOrFail($id);
        $item->status = 'lida';
        $item->lida_em = $data['lida_em'] ?? Carbon::now();
        $item->save();

        return $item;
    }

    public function marcarLidas(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'lida_em' => 'nullable|date',
        ]);

        $query = Notificacao::query();
        $this->applyEmpresaScope($query, $request);

        $lidaEm = $data['lida_em'] ?? Carbon::now();
        $updated = $query
            ->whereIn('id', $data['ids'])
            ->update([
                'status' => 'lida',
                'lida_em' => $lidaEm,
                'updated_at' => Carbon::now(),
            ]);

        return [
            'ids' => $data['ids'],
            'lida_em' => $lidaEm,
            'updated' => $updated,
        ];
    }

    protected function validateDestino(array $data): void
    {
        $destino = $data['destino'] ?? null;
        if (!$destino) {
            return;
        }

        $errors = [];
        if ($destino === 'cozinha' && empty($data['estacao_id'])) {
            $errors['estacao_id'][] = 'estacao_id obrigatorio quando destino=cozinha';
        }
        if ($destino === 'operacao' && empty($data['usuario_id'])) {
            $errors['usuario_id'][] = 'usuario_id obrigatorio quando destino=operacao';
        }
        if ($destino === 'mesa' && empty($data['mesa_id'])) {
            $errors['mesa_id'][] = 'mesa_id obrigatorio quando destino=mesa';
        }
        if ($destino === 'comanda' && empty($data['comanda_id'])) {
            $errors['comanda_id'][] = 'comanda_id obrigatorio quando destino=comanda';
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
}
