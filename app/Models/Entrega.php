<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Entrega extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_entregas';
    public bool $empresaScoped = false;
}
