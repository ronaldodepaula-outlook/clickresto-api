<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pagamento extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_pagamentos';
    public bool $empresaScoped = false;
}
