<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model
{
    protected $fillable = ['content', 'source_type', 'status', 'error', 'scheduled_at', 'sent_at', 'channel_id'];
    protected $casts = ['scheduled_at' => 'datetime', 'sent_at' => 'datetime'];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function channels(): BelongsToMany
    {
        return $this->belongsToMany(Channel::class, 'post_channel')
            ->withPivot(['status', 'error', 'message_id', 'message_ids', 'sent_at'])
            ->withTimestamps();
    }
}
