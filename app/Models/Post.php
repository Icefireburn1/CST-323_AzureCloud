<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    // // Table Name
    // protected $table = 'posts';

    // // Primary Key
    // public $primaryKey = 'item_id';

    // // Timestamps
    // public $timestamps = false;
    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
