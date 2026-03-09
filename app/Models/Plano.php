<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plano extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_planos';
    public bool $empresaScoped = false;

    public function empresas()
    {
        return $this->hasMany(Empresa::class, 'plano_id');
    }

    public function assinaturas()
    {
        return $this->hasMany(Assinatura::class, 'plano_id');
    }
}
