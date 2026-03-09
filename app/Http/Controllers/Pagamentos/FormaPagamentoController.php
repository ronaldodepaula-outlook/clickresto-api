<?php

namespace App\Http\Controllers\Pagamentos;

use App\Http\Controllers\BaseCrudController;
use App\Models\FormaPagamento;

class FormaPagamentoController extends BaseCrudController
{
    protected string $modelClass = FormaPagamento::class;

    protected array $rules = [
        'nome' => 'required|string|max:100'
    ];

    protected array $filterable = ['nome'];
}
