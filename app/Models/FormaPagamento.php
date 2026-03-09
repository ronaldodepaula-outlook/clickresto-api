<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class FormaPagamento extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_formas_pagamento';
    public bool $empresaScoped = true;
}
