<?php

declare(strict_types=1);

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Model;

final class AiAuditLog extends Model
{
    protected $connection = 'mysql_ai';
    protected $table = 'ai_audit_logs';

    protected $fillable = [
        'conversation_id', 'customer_id', 'event', 'provider', 'model',
        'prompt_version', 'payload', 'latency_ms',
    ];

    protected $casts = ['payload' => 'array'];
}
