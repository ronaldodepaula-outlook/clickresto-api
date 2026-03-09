<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CozinhaEstacao extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_cozinha_estacoes';
    public bool $empresaScoped = true;
}
