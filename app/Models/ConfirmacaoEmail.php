<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ConfirmacaoEmail extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_confirmacoes_email';
    public bool $empresaScoped = true;
}
