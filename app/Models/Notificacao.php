<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notificacao extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_notificacoes';
    public bool $empresaScoped = true;

    protected $casts = [
        'payload' => 'array',
        'enviada_em' => 'datetime',
        'lida_em' => 'datetime',
    ];
}
