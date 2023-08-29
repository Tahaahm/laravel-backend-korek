<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class indexName extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'content'];

    public function searchableAs()
    {
        return 'index_name';
    }
}
