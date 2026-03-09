<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class EstoqueMovimento extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_estoque_movimentos';
    public bool $empresaScoped = false;
}
