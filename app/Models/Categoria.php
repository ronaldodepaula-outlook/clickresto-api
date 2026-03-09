<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Categoria extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_categorias';
    public bool $empresaScoped = true;
}
