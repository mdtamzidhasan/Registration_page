<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens as HashApiTokens;


class User extends Authenticatable 
{
    use Notifiable, HashApiTokens;

    protected $fillable = ['employee_id', 'user_name', 'email', 'password', 'status'];

    protected $hidden = ['password', 'remember_token'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
