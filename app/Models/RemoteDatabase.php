<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class RemoteDatabase extends Model
{
    use HasFactory;

    protected $table = 'remote_databases';

    protected $fillable = [
        'user_id',
        'database_name',
        'database_host',
        'user_name',
        'user_password'
    ];

    /**
     * Relation: A user can have many databases.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
