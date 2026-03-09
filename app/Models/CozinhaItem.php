<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CozinhaItem extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_cozinha_itens';
    public bool $empresaScoped = false;
}
