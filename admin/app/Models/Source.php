<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    protected $fillable = ['title', 'url', 'type', 'is_active', 'channel_id'];
    protected $casts = ['is_active' => 'boolean'];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }
}
