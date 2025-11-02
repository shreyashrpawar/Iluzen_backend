<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDatabase extends Model
{
    use HasFactory;

    protected $table = 'user_databases';

    protected $fillable = [
        'user_id',
        'database_name',
    ];

    /**
     * Relation: A user can have many databases.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
