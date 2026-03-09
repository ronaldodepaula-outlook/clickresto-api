<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Empresa extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_empresas';
    public bool $empresaScoped = false;

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'empresa_id');
    }

    public function plano()
    {
        return $this->belongsTo(Plano::class, 'plano_id');
    }

    public function assinaturas()
    {
        return $this->hasMany(Assinatura::class, 'empresa_id');
    }

    public function assinaturaAtiva()
    {
        return $this->hasOne(Assinatura::class, 'empresa_id')->where('status', 'ativo')->latest();
    }
}
