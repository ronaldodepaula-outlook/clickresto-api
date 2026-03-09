<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Caixa extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_caixas';
    public bool $empresaScoped = true;
}
