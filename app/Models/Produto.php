<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Produto extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_produtos';
    public bool $empresaScoped = true;
}
