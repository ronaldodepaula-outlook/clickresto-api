<?php

namespace App\Http\Controllers\Mesas;

use App\Http\Controllers\BaseCrudController;
use App\Models\Mesa;

class MesaController extends BaseCrudController
{
    protected string $modelClass = Mesa::class;

    protected array $rules = [
        'numero' => 'required|integer|min:1',
        'status' => 'required|in:livre,ocupada'
    ];

    protected array $filterable = ['status'];
}
