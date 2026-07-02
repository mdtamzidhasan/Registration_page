<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = ['code', 'name', 'status'];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}