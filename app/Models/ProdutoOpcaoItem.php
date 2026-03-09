<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProdutoOpcaoItem extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_produto_opcao_itens';
    public bool $empresaScoped = false;
}
