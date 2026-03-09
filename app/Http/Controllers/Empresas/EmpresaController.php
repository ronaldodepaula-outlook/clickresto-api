<?php

namespace App\Http\Controllers\Empresas;

use App\Http\Controllers\BaseCrudController;
use App\Models\Empresa;
use Illuminate\Http\Request;

class EmpresaController extends BaseCrudController
{
    protected string $modelClass = Empresa::class;

    protected array $rules = [
        'nome' => 'required|string|max:150',
        'nome_fantasia' => 'nullable|string|max:150',
        'cnpj' => 'nullable|string|max:20',
        'telefone' => 'nullable|string|max:20',
        'email' => 'nullable|email|max:120',
        'endereco' => 'nullable|string|max:200',
        'cidade' => 'nullable|string|max:100',
        'estado' => 'nullable|string|max:50',
        'plano_id' => 'nullable|exists:tb_planos,id',
        'status' => 'required|in:ativo,suspenso'
    ];

    protected array $filterable = ['status', 'plano_id'];

    /**
     * Lista todas as empresas com detalhes para admin_master
     * Inclui usuários gerentes, plano atual e status da assinatura
     */
    public function adminList(Request $request)
    {
        $query = Empresa::with([
            'usuarios' => function ($query) {
                $query->select('id', 'empresa_id', 'nome', 'email', 'ativo');
            },
            'plano' => function ($query) {
                // ajustar campos de acordo com a estrutura real da tabela tb_planos
                $query->select('id', 'nome', 'limite_usuarios', 'limite_produtos', 'valor', 'ativo');
            },
            'assinaturaAtiva' => function ($query) {
                $query->select('id', 'empresa_id', 'plano_id', 'data_inicio', 'data_fim', 'status');
            }
        ]);

        // Filtros opcionais
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('plano_id')) {
            $query->where('plano_id', $request->input('plano_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                  ->orWhere('nome_fantasia', 'like', "%{$search}%")
                  ->orWhere('cnpj', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $empresas = $query->paginate($perPage);

        // Formatar resposta com dados calculados
        $empresas->getCollection()->transform(function ($empresa) {
            $assinaturaAtiva = $empresa->assinaturaAtiva;

            return [
                'id' => $empresa->id,
                'nome' => $empresa->nome,
                'nome_fantasia' => $empresa->nome_fantasia,
                'cnpj' => $empresa->cnpj,
                'telefone' => $empresa->telefone,
                'email' => $empresa->email,
                'endereco' => $empresa->endereco,
                'cidade' => $empresa->cidade,
                'estado' => $empresa->estado,
                'status' => $empresa->status,
                'criado_em' => $empresa->criado_em,
                'plano' => $empresa->plano,
                'assinatura' => $assinaturaAtiva ? [
                    'id' => $assinaturaAtiva->id,
                    'data_inicio' => $assinaturaAtiva->data_inicio,
                    'data_fim' => $assinaturaAtiva->data_fim,
                    'status' => $assinaturaAtiva->status,
                    'dias_restantes' => now()->diffInDays($assinaturaAtiva->data_fim, false),
                ] : null,
                'usuarios' => $empresa->usuarios->map(function ($usuario) {
                    return [
                        'id' => $usuario->id,
                        'nome' => $usuario->nome,
                        'email' => $usuario->email,
                        'ativo' => $usuario->ativo,
                    ];
                }),
                'total_usuarios' => $empresa->usuarios->count(),
            ];
        });

        return $empresas;
    }
}
