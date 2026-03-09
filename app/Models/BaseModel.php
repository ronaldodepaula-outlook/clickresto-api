<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    protected $guarded = ['id'];

    /**
     * Indica se o modelo deve ser filtrado por empresa_id nos controllers.
     */
    public bool $empresaScoped = false;
}
