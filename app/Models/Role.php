<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public const ADMIN = 'ADMIN';
    public const COORDINATOR = 'COORDINATOR';
    public const TECHNICIAN = 'TECHNICIAN';
    public const SELLER = 'SELLER';

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}