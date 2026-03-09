<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Perfil extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_perfis';
    public bool $empresaScoped = false;
}
