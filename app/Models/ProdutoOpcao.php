<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProdutoOpcao extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_produto_opcoes';
    public bool $empresaScoped = false;
}
