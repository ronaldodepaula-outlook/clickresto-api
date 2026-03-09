<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Entregador extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_entregadores';
    public bool $empresaScoped = true;
}
