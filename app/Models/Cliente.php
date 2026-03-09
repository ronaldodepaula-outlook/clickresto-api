<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cliente extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_clientes';
    public bool $empresaScoped = true;
}
