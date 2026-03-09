<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CaixaMovimento extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_caixa_movimentos';
    public bool $empresaScoped = false;
}
