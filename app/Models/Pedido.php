<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pedido extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_pedidos';
    public bool $empresaScoped = true;
}
