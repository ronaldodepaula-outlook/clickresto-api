<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Configuracao extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_configuracoes';
    public bool $empresaScoped = true;
}
