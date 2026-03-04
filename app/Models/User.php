<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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
    ];

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

}
