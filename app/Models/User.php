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
    public const MODULE_ETIQUETADORA = 'etiquetadora';
    public const MODULE_PASTEURIZADORA = 'pasteurizadora';

    public const PERMISSION_ACCESS_LAVADORA = 'acceder modulo lavadora';
    public const PERMISSION_ACCESS_ETIQUETADORA = 'acceder modulo etiquetadora';
    public const PERMISSION_ACCESS_PASTEURIZADORA = 'acceder modulo pasteurizadora';
    public const PERMISSION_ACCESS_PASTEURIZADORA_MECANICA = 'acceder pasteurizadora mecanica';
    public const PERMISSION_ACCESS_PASTEURIZADORA_CENTRAL_HIDRAULICA = 'acceder pasteurizadora central hidraulica';
    public const PERMISSION_EDIT_ANALYSIS_DATE = 'editar fecha analisis';
    public const PERMISSION_DELETE_ANALYSIS = 'eliminar analisis';
    public const PERMISSION_CLOSE_LAVADORA_DAMAGE = 'cerrar danos lavadora';
    public const PERMISSION_VIEW_LAVADORA_COST_MODULE = 'ver modulo costos lavadora';
    public const PERMISSION_ACCESS_LAVADORA_COSTS = 'acceder vista costos lavadora';
    public const PERMISSION_CREATE_LAVADORA_COSTS = 'crear costos lavadora';
    public const PERMISSION_EDIT_LAVADORA_COSTS = 'editar costos lavadora';
    public const PERMISSION_DELETE_LAVADORA_COSTS = 'eliminar costos lavadora';
    public const PERMISSION_MANAGE_LAVADORA_COSTS = 'gestionar costos lavadora';

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
        self::MODULE_ETIQUETADORA => self::PERMISSION_ACCESS_ETIQUETADORA,
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

public static function lavadoraCostPermissionNames(): array
{
    return [
        self::PERMISSION_VIEW_LAVADORA_COST_MODULE,
        self::PERMISSION_ACCESS_LAVADORA_COSTS,
        self::PERMISSION_CREATE_LAVADORA_COSTS,
        self::PERMISSION_EDIT_LAVADORA_COSTS,
        self::PERMISSION_DELETE_LAVADORA_COSTS,
        self::PERMISSION_MANAGE_LAVADORA_COSTS,
    ];
}

public static function configurablePermissionGroups(): array
{
    return \App\Support\AccessPermissionCatalog::groups();
}

public static function configurablePermissionNames(): array
{
    return \App\Support\AccessPermissionCatalog::visibleNames();
}

public static function customAccessControlPermissionName(): string
{
    return \App\Support\AccessPermissionCatalog::CUSTOM_ACCESS_CONTROL;
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

public function canReviewWasherAiPlans(): bool
{
    return $this->hasAnyRole(self::elevatedMaintenanceRoles())
        && $this->canAccessModule(self::MODULE_LAVADORA);
}

public function canManageWasherKnowledgeDocuments(): bool
{
    return $this->hasAnyRole(self::elevatedMaintenanceRoles())
        && $this->canAccessModule(self::MODULE_LAVADORA);
}

public function canDeleteAnalysis(): bool
{
    return $this->canDeleteAnalysisWithPermission(self::PERMISSION_DELETE_ANALYSIS);
}

public function canDeleteLavadoraAnalysis(): bool
{
    return $this->canDeleteAnalysisWithPermission(self::PERMISSION_DELETE_ANALYSIS);
}

public function canDeleteEtiquetadoraAnalysis(): bool
{
    return $this->canDeleteAnalysisWithPermission('eliminar analisis etiquetadora');
}

public function canDeletePasteurizadoraAnalysis(): bool
{
    return $this->canDeleteAnalysisWithPermission('eliminar analisis pasteurizadora');
}

public function canDeleteLegacyAnalysis(): bool
{
    return $this->canDeleteAnalysisWithPermission('eliminar analisis legado');
}

private function canDeleteAnalysisWithPermission(string $permission): bool
{
    if ($this->hasRole(self::ROLE_ADMIN)) {
        return true;
    }

    if ($this->usesCustomPermissionAccess()) {
        return $this->hasDirectConfigurablePermission($permission);
    }

    try {
        return $this->hasPermissionTo(self::PERMISSION_DELETE_ANALYSIS);
    } catch (PermissionDoesNotExist) {
        return false;
    }
}

public function canCloseLavadoraDamage(): bool
{
    if ($this->hasRole(self::ROLE_ADMIN)) {
        return true;
    }

    return $this->hasDirectLavadoraDamageClosurePermission();
}

public function canViewLavadoraCostModule(): bool
{
    if ($this->canManageLavadoraCosts()) {
        return true;
    }

    return $this->hasDirectPermissionSafely(self::PERMISSION_VIEW_LAVADORA_COST_MODULE);
}

public function canAccessLavadoraCosts(): bool
{
    if ($this->canManageLavadoraCosts()) {
        return true;
    }

    return $this->hasDirectPermissionSafely(self::PERMISSION_ACCESS_LAVADORA_COSTS);
}

public function canCreateLavadoraCosts(): bool
{
    if ($this->canManageLavadoraCosts()) {
        return true;
    }

    return $this->hasDirectPermissionSafely(self::PERMISSION_CREATE_LAVADORA_COSTS);
}

public function canEditLavadoraCosts(): bool
{
    if ($this->canManageLavadoraCosts()) {
        return true;
    }

    return $this->hasDirectPermissionSafely(self::PERMISSION_EDIT_LAVADORA_COSTS);
}

public function canDeleteLavadoraCosts(): bool
{
    if ($this->canManageLavadoraCosts()) {
        return true;
    }

    return $this->hasDirectPermissionSafely(self::PERMISSION_DELETE_LAVADORA_COSTS);
}

public function canManageLavadoraCosts(): bool
{
    if ($this->hasRole(self::ROLE_ADMIN)) {
        return true;
    }

    return $this->hasDirectPermissionSafely(self::PERMISSION_MANAGE_LAVADORA_COSTS);
}

public function hasDirectAnalysisDeletionPermission(): bool
{
    try {
        return $this->hasDirectPermission(self::PERMISSION_DELETE_ANALYSIS);
    } catch (PermissionDoesNotExist) {
        return false;
    }
}

public function hasDirectLavadoraDamageClosurePermission(): bool
{
    return $this->hasDirectPermissionSafely(self::PERMISSION_CLOSE_LAVADORA_DAMAGE);
}

public function hasDirectConfigurablePermission(string $permission): bool
{
    return in_array($permission, self::configurablePermissionNames(), true)
        && $this->hasDirectPermissionSafely($permission);
}

public function usesCustomPermissionAccess(): bool
{
    return $this->hasDirectPermissionSafely(self::customAccessControlPermissionName());
}

public function canUseCustomPermission(string $permission): bool
{
    if ($this->hasRole(self::ROLE_ADMIN)) {
        return true;
    }

    if (!$this->usesCustomPermissionAccess()) {
        return \App\Support\AccessPermissionCatalog::defaultAllows($this, $permission);
    }

    $allowedByDefault = \App\Support\AccessPermissionCatalog::roleDefaultAllows($this, $permission);

    if ($this->hasDirectConfigurablePermission($permission)) {
        return !$allowedByDefault;
    }

    return $allowedByDefault;
}

private function hasDirectPermissionSafely(string $permission): bool
{
    try {
        return $this->hasDirectPermission($permission);
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
    $permission = self::modulePermissionMap()[$module] ?? null;

    if ($this->hasRole(self::ROLE_ADMIN)) {
        return true;
    }

    $allowedByDefault = $this->canAccessModuleByDefault($module);

    if (!$this->usesCustomPermissionAccess()) {
        if ($allowedByDefault) {
            return true;
        }

        return $permission ? $this->hasDirectPermissionSafely($permission) : false;
    }

    if ($permission && $this->hasDirectConfigurablePermission($permission)) {
        return !$allowedByDefault;
    }

    return $allowedByDefault;
}

public function canAccessModuleByDefault(string $module): bool
{
    $module = strtolower($module);
    $permission = self::modulePermissionMap()[$module] ?? null;

    if ($this->hasRole(self::ROLE_ADMIN)) {
        return true;
    }

    if ($this->hasAnyRole(self::supervisorEquivalentRoles())) {
        return $module !== self::MODULE_PASTEURIZADORA;
    }

    if ($this->usesTechnicianAccessProfile()) {
        return in_array($module, [self::MODULE_LAVADORA, self::MODULE_ETIQUETADORA], true);
    }

    if ($permission) {
        if ($this->hasPermissionThroughRoleSafely($permission)) {
            return true;
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

    if ($this->usesCustomPermissionAccess()) {
        $allowedByDefault = $this->canAccessPasteurizadoraAreaByDefault($area);

        if ($this->hasDirectConfigurablePermission($permission)) {
            return !$allowedByDefault;
        }

        return $allowedByDefault;
    }

    try {
        return $this->hasPermissionTo($permission);
    } catch (PermissionDoesNotExist) {
        return true;
    }
}

public function canAccessPasteurizadoraAreaByDefault(string $area): bool
{
    if (!$this->canAccessModuleByDefault(self::MODULE_PASTEURIZADORA)) {
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
        return $this->hasPermissionThroughRoleSafely($permission);
    } catch (PermissionDoesNotExist) {
        return true;
    }
}

public function canViewPlanActionType(string $type): bool
{
    $type = strtolower($type);

    if ($type === self::MODULE_LAVADORA) {
        return $this->canUseCustomPermission('ver planes accion')
            && $this->canAccessModule(self::MODULE_LAVADORA);
    }

    if ($type === self::MODULE_ETIQUETADORA) {
        return $this->canUseCustomPermission('ver planes accion')
            && $this->canAccessModule(self::MODULE_ETIQUETADORA);
    }

    if ($type === self::MODULE_PASTEURIZADORA) {
        if (!$this->canUseCustomPermission('ver planes accion')) {
            return false;
        }

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
    if ($this->canAccessModule(self::MODULE_PASTEURIZADORA)) {
        return false;
    }

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

private function hasPermissionThroughRoleSafely(string $permission): bool
{
    try {
        return $this->getPermissionsViaRoles()->contains('name', $permission);
    } catch (PermissionDoesNotExist) {
        return false;
    }
}

}
