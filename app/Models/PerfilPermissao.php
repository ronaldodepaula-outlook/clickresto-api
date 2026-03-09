<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PerfilPermissao extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_perfil_permissoes';
    public bool $empresaScoped = false;
}
