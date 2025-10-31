<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;

    protected $table = 'requests';

    protected $fillable = [
        'name',
        'server_id',
        'url',
        'type',
        'response',
    ];

    protected $casts = [
        'response' => 'array', // because it's JSON
    ];

    /**
     * Each request belongs to a server
     */
    public function server()
    {
        return $this->belongsTo(Server::class);
    }
}
