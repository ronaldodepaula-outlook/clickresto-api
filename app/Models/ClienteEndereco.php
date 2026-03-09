<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClienteEndereco extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_cliente_enderecos';
    public bool $empresaScoped = false;
}
