<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Mesa extends BaseModel
{
    use HasFactory;

    protected $table = 'tb_mesas';
    public bool $empresaScoped = true;
}
