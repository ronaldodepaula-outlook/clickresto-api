<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Permissao extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_permissoes';
    public bool $empresaScoped = false;
}
