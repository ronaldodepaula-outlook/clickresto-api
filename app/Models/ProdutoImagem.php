<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProdutoImagem extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_produto_imagens';
    public bool $empresaScoped = false;
}
