<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PedidoItem extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_pedido_itens';
    public bool $empresaScoped = false;
}
