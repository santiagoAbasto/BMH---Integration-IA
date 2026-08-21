<?php

declare(strict_types=1);

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Model;

final class AiMessageToolCall extends Model
{
    protected $connection = 'mysql_ai';
    protected $table = 'ai_message_tool_calls';

    protected $fillable = ['message_id', 'tool', 'arguments', 'result_summary', 'latency_ms', 'ok'];

    protected $casts = [
        'arguments'      => 'array',
        'result_summary' => 'array',
        'ok'             => 'boolean',
        'latency_ms'     => 'integer',
    ];
}
