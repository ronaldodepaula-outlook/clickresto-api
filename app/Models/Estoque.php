<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Estoque extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_estoque';
    public bool $empresaScoped = true;
}
