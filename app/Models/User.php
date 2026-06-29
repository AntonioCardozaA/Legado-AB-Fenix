<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'cedula',
        'telefono',
        'puesto',
        'activo',
        'foto_perfil',
        'ultimo_acceso',
    ];

    public const ROLE_ADMIN = 'admin';
    public const ROLE_GERENTE_MANTENIMIENTO = 'gerente_mantenimiento';
    public const ROLE_SUPERVISOR = 'supervisor';
    public const ROLE_INGENIERO_MANTENIMIENTO = 'ingeniero_mantenimiento';
    public const ROLE_TECNICO = 'tecnico';
    public const ROLE_PROGRAMADOR_DE_MANTENIMIENTO = 'programador_de_mantenimiento';

    public const MODULE_LAVADORA = 'lavadora';
    public const MODULE_PASTEURIZADORA = 'pasteurizadora';

    public const PERMISSION_ACCESS_LAVADORA = 'acceder modulo lavadora';
    public const PERMISSION_ACCESS_PASTEURIZADORA = 'acceder modulo pasteurizadora';
    public const PERMISSION_ACCESS_PASTEURIZADORA_MECANICA = 'acceder pasteurizadora mecanica';
    public const PERMISSION_ACCESS_PASTEURIZADORA_CENTRAL_HIDRAULICA = 'acceder pasteurizadora central hidraulica';
    public const PERMISSION_EDIT_ANALYSIS_DATE = 'editar fecha analisis';
    public const PERMISSION_DELETE_ANALYSIS = 'eliminar analisis';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function notificationSettings()
{
    return $this->hasOne(UserNotificationSetting::class);
}

public function getNotificationSettingsAttribute()
{
    return $this->notificationSettings()->firstOrCreate([
        'user_id' => $this->id
    ]);
}

// Método para obtener teléfono formateado para SMS
public function getFormattedPhoneForSmsAttribute()
{
    $settings = $this->notificationSettings;
    if (!$settings || !$settings->phone_number) {
        return null;
    }
    
    // Formatear según el proveedor de SMS (ejemplo para Colombia)
    $phone = preg_replace('/[^0-9]/', '', $settings->phone_number);
    if (substr($phone, 0, 1) === '3') {
        $phone = '57' . $phone; // Código de Colombia
    }
    return $phone;
}

public static function roleLabels(): array
{
    return [
        self::ROLE_ADMIN => 'Administrador',
        self::ROLE_GERENTE_MANTENIMIENTO => 'Gerente de Mantenimiento',
        self::ROLE_SUPERVISOR => 'Supervisor',
        self::ROLE_INGENIERO_MANTENIMIENTO => 'Ingeniero de Mantenimiento',
        self::ROLE_TECNICO => 'Tecnico',
        self::ROLE_PROGRAMADOR_DE_MANTENIMIENTO => 'Programador de Mantenimiento',
    ];
}

public static function modulePermissionMap(): array
{
    return [
        self::MODULE_LAVADORA => self::PERMISSION_ACCESS_LAVADORA,
        self::MODULE_PASTEURIZADORA => self::PERMISSION_ACCESS_PASTEURIZADORA,
    ];
}

public static function pasteurizadoraAreaPermissionMap(): array
{
    return [
        \App\Models\AnalisisPasteurizadora::AREA_MECANICA => self::PERMISSION_ACCESS_PASTEURIZADORA_MECANICA,
        \App\Models\AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA => self::PERMISSION_ACCESS_PASTEURIZADORA_CENTRAL_HIDRAULICA,
    ];
}

public static function elevatedMaintenanceRoles(): array
{
    return [
        self::ROLE_ADMIN,
        self::ROLE_GERENTE_MANTENIMIENTO,
        ...self::supervisorEquivalentRoles(),
    ];
}

public static function supervisorEquivalentRoles(): array
{
    return [
        self::ROLE_SUPERVISOR,
        self::ROLE_PROGRAMADOR_DE_MANTENIMIENTO,
    ];
}

public static function technicianEquivalentRoles(): array
{
    return [
        self::ROLE_TECNICO,
        self::ROLE_INGENIERO_MANTENIMIENTO,
    ];
}

public static function analysisDateEditorRoles(): array
{
    return [
        self::ROLE_ADMIN,
        ...self::technicianEquivalentRoles(),
        ...self::supervisorEquivalentRoles(),
    ];
}

public function canEditAnalysisDate(): bool
{
    return $this->hasAnyRole(self::analysisDateEditorRoles());
}

public function canDeleteAnalysis(): bool
{
    if ($this->hasRole(self::ROLE_ADMIN)) {
        return true;
    }

    try {
        return $this->hasPermissionTo(self::PERMISSION_DELETE_ANALYSIS);
    } catch (PermissionDoesNotExist) {
        return false;
    }
}

public function hasDirectAnalysisDeletionPermission(): bool
{
    try {
        return $this->hasDirectPermission(self::PERMISSION_DELETE_ANALYSIS);
    } catch (PermissionDoesNotExist) {
        return false;
    }
}

public function usesTechnicianAccessProfile(): bool
{
    return $this->hasAnyRole(self::technicianEquivalentRoles())
        && !$this->hasAnyRole(self::elevatedMaintenanceRoles());
}

public function getPrimaryRoleAttribute(): ?string
{
    return $this->getRoleNames()->first();
}

public function getRoleAttribute(): ?string
{
    return $this->primary_role;
}

public function getRoleLabelAttribute(): string
{
    $role = $this->primary_role;

    return self::roleLabels()[$role] ?? ($role ? str($role)->replace('_', ' ')->title()->toString() : 'Sin rol asignado');
}

public function canAccessModule(string $module): bool
{
    $module = strtolower($module);

    if ($this->hasRole(self::ROLE_ADMIN)) {
        return true;
    }

    if ($this->hasAnyRole(self::supervisorEquivalentRoles())) {
        return $module !== self::MODULE_PASTEURIZADORA;
    }

    if ($this->usesTechnicianAccessProfile()) {
        return $module === self::MODULE_LAVADORA;
    }

    $permission = self::modulePermissionMap()[$module] ?? null;

    if ($permission) {
        try {
            if ($this->hasPermissionTo($permission)) {
                return true;
            }
        } catch (PermissionDoesNotExist) {
            //
        }
    }

    if ($module === self::MODULE_PASTEURIZADORA) {
        return !$this->hasAnyRole([
            self::ROLE_GERENTE_MANTENIMIENTO,
            ...self::supervisorEquivalentRoles(),
        ]);
    }

    return true;
}

public function canAccessPasteurizadoraArea(string $area): bool
{
    if (!$this->canAccessModule(self::MODULE_PASTEURIZADORA)) {
        return false;
    }

    if ($this->hasRole(self::ROLE_ADMIN)) {
        return true;
    }

    $area = \App\Models\AnalisisPasteurizadora::normalizarArea($area);
    $permission = self::pasteurizadoraAreaPermissionMap()[$area] ?? null;

    if (!$permission) {
        return false;
    }

    try {
        return $this->hasPermissionTo($permission);
    } catch (PermissionDoesNotExist) {
        return true;
    }
}

public function canViewPlanActionType(string $type): bool
{
    $type = strtolower($type);

    if ($type === self::MODULE_LAVADORA) {
        return $this->canAccessModule(self::MODULE_LAVADORA);
    }

    if ($type === self::MODULE_PASTEURIZADORA) {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_GERENTE_MANTENIMIENTO,
            ...self::technicianEquivalentRoles(),
            ...self::supervisorEquivalentRoles(),
        ]) || $this->canAccessModule(self::MODULE_PASTEURIZADORA);
    }

    return false;
}

public function shouldShowPasteurizadoraComingSoon(): bool
{
    $isTechnicianOnly = $this->usesTechnicianAccessProfile();

    $isRestrictedMaintenanceRole = $this->hasAnyRole([
        self::ROLE_GERENTE_MANTENIMIENTO,
        ...self::supervisorEquivalentRoles(),
    ]) && !$this->canAccessModule(self::MODULE_PASTEURIZADORA);

    return $isTechnicianOnly || $isRestrictedMaintenanceRole;
}

public function shouldSeePasteurizadoraShortcut(): bool
{
    return $this->canAccessModule(self::MODULE_PASTEURIZADORA)
        || $this->shouldShowPasteurizadoraComingSoon();
}

}
