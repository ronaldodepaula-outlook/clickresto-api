<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Usuario extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $table = 'tb_usuarios';
    public bool $empresaScoped = true;

    protected $guarded = ['id'];

    protected $hidden = ['senha'];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function perfis()
    {
        return $this->belongsToMany(Perfil::class, 'tb_usuario_perfis', 'usuario_id', 'perfil_id');
    }

    public function getAuthPassword()
    {
        return $this->senha;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'empresa_id' => $this->empresa_id,
        ];
    }
}
