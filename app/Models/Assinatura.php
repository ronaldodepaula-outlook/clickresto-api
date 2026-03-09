<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Assinatura extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_assinaturas';
    public bool $empresaScoped = true;

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function plano()
    {
        return $this->belongsTo(Plano::class, 'plano_id');
    }
}
