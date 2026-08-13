<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    protected $fillable = [
        'title', 'chat_id', 'is_active',
        'source_mode', 'source_url', 'ai_provider', 'auto_send', 'schedule_enabled', 'schedule_time', 'ai_prompt',
        'category', 'language',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'auto_send'        => 'boolean',
        'schedule_enabled' => 'boolean',
    ];

    public function sources()
    {
        return $this->hasMany(Source::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
