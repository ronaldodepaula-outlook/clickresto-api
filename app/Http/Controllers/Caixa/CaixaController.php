<?php

namespace App\Http\Controllers\Caixa;

use App\Http\Controllers\BaseCrudController;
use App\Models\Caixa;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaixaController extends BaseCrudController
{
    protected string $modelClass = Caixa::class;

    protected array $rules = [
        'usuario_id' => 'required|exists:tb_usuarios,id',
        'aberto_em' => 'nullable|date',
        'fechado_em' => 'nullable|date',
        'saldo_inicial' => 'required|numeric|min:0',
        'saldo_final' => 'nullable|numeric|min:0',
    ];

    protected array $filterable = ['usuario_id'];

    public function abrir(Request $request)
    {
        $data = $request->validate([
            'usuario_id' => 'nullable|exists:tb_usuarios,id',
            'saldo_inicial' => 'required|numeric|min:0',
        ]);

        if (!isset($data['usuario_id']) && $request->user()) {
            $data['usuario_id'] = $request->user()->id;
        }

        $data['aberto_em'] = now();
        $data['saldo_final'] = null;

        $this->injectEmpresaId($data, $request);

        $caixa = Caixa::create($data);

        return response()->json($caixa, Response::HTTP_CREATED);
    }

    public function fechar(Request $request, int $id)
    {
        $data = $request->validate([
            'saldo_final' => 'required|numeric|min:0',
        ]);

        $query = Caixa::query();
        $this->applyEmpresaScope($query, $request);
        $caixa = $query->findOrFail($id);

        $caixa->saldo_final = $data['saldo_final'];
        $caixa->fechado_em = now();
        $caixa->save();

        return $caixa;
    }
}
