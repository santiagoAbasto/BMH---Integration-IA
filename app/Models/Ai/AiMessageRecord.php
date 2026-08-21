<?php

declare(strict_types=1);

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AiMessageRecord extends Model
{
    protected $connection = 'mysql_ai';
    protected $table = 'ai_messages';

    protected $fillable = [
        'conversation_id', 'role', 'content', 'metadata', 'provider', 'model', 'latency_ms',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'latency_ms' => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }

    public function toolCalls(): HasMany
    {
        return $this->hasMany(AiMessageToolCall::class, 'message_id');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(AiProductCandidate::class, 'message_id')->orderBy('position');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(AiAttachment::class, 'message_id');
    }
}
