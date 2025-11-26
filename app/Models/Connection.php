<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Connection extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'host',
        'port',
        'database_user',
        'database_password',
    ];

    protected $hidden = [
        'database_password_encrypted',
    ];

    protected function databasePassword(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (empty($attributes['database_password_encrypted'])) {
                    return null;
                }
                try {
                    return Crypt::decryptString($attributes['database_password_encrypted']);
                } catch (\Exception $e) {
                    return null;
                }
            },

            set: function ($value) {
                if (empty($value)) {
                    return ['database_password_encrypted' => null];
                }
                return [
                    'database_password_encrypted' => Crypt::encryptString($value)
                ];
            }
        );
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'connection_user');
    }

    public function databasePermissions() {
        return $this->hasMany(DatabasePermission::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($connection) {
            $adminUserIds = User::where('role', 'Administrator')->pluck('id');

            if ($adminUserIds->isNotEmpty()) {
                $connection->users()->attach($adminUserIds);
            }
        });
    }
}
