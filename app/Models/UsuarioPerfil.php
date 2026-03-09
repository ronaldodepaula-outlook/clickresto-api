<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class UsuarioPerfil extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_usuario_perfis';
    public bool $empresaScoped = false;
}
