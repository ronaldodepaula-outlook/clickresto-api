<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PedidoItemOpcao extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_pedido_item_opcoes';
    public bool $empresaScoped = false;
}
