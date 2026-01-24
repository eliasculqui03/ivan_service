<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Obtener roles del usuario
        $roles = DB::table('roles_users')
            ->join('roles', 'roles.id', '=', 'roles_users.id_role')
            ->where('roles_users.id_user', $this->id)
            ->select('roles.id', 'roles.name')
            ->get();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar_url,
            'language' => $this->language,
            'timezone' => $this->timezone,
            'status' => $this->status,
            'status_texto' => $this->status == 1 ? 'Activo' : 'Inactivo',
            'notifications_enabled' => $this->notifications_enabled,
            'marketing_consent' => $this->marketing_consent,
            'last_login_at' => $this->last_login_at?->toISOString(),
            'last_activity_at' => $this->last_activity_at?->toISOString(),
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'roles' => $roles,
            'es_medico' => $this->medico()->exists(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}