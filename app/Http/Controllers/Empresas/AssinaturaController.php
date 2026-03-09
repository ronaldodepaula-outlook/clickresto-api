<?php

namespace App\Http\Controllers\Empresas;

use App\Http\Controllers\BaseCrudController;
use App\Models\Assinatura;

class AssinaturaController extends BaseCrudController
{
    protected string $modelClass = Assinatura::class;

    protected array $rules = [
        'plano_id' => 'required|exists:tb_planos,id',
        'data_inicio' => 'required|date',
        'data_fim' => 'nullable|date|after_or_equal:data_inicio',
        'status' => 'required|string|max:50'
    ];

    protected array $filterable = ['plano_id', 'status'];
}
