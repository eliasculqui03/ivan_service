<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Roles extends Model
{
    protected $fillable = [
        'name',
        'status',
    ];
       public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,        // modelo relacionado
            'roles_users',      // tabla pivote (tu tabla real)
            'id_role',          // FK de roles en pivote
            'id_user'           // FK de users en pivote
        );
    }
}
