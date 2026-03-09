<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Comanda extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_comandas';
    public bool $empresaScoped = true;
}
