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
        'database_password_encrypted',
    ];

    /**
     * Criptografa a senha ao definir o atributo.
     * Acessaremos como $connection->database_password = 'senha_pura'
     */
    protected function databasePassword(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => Crypt::decryptString($value),
            set: fn (string $value) => Crypt::encryptString($value),
        );
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (isset($model->attributes['database_password'])) {
                $model->attributes['database_password_encrypted'] = $model->database_password;
                unset($model->attributes['database_password']);
            }
        });

        static::updating(function ($model) {
            if (isset($model->attributes['database_password'])) {
                $model->attributes['database_password_encrypted'] = $model->database_password;
                unset($model->attributes['database_password']);
            }
        });
    }
}
