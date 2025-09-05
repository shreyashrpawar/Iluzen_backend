<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'user_id',
        'subdomain',
    ];

    /**
     * Get the user that owns the server.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
