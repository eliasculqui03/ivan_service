<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolesUsers extends Model
{

    protected $fillable = [
        'id_role',
        'id_user',
    ];
}
