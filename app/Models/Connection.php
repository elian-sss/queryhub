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
            get: function (?string $value) {
                if ($value === null) {
                    return null;
                }
                try {
                    return Crypt::decryptString($value);
                } catch (\Exception $e) {
                    return null;
                }
            },
            set: function (?string $value) {
                if ($value === null || $value === '') {
                    return null;
                }
                return Crypt::encryptString($value);
            },
        );
    }

    protected static function boot()
    {
        parent::boot();

        $attribute_handler = function ($model) {
            if (array_key_exists('database_password', $model->attributes)) {
                $model->attributes['database_password_encrypted'] = $model->database_password;
                unset($model->attributes['database_password']);
            }
        };

        static::creating($attribute_handler);
        static::updating($attribute_handler);
    }
}
