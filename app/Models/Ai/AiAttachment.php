<?php

declare(strict_types=1);

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Model;

final class AiAttachment extends Model
{
    protected $connection = 'mysql_ai';
    protected $table = 'ai_attachments';

    protected $fillable = [
        'conversation_id', 'message_id', 'disk', 'path', 'analysis_path', 'thumbnail_path',
        'mime', 'bytes', 'width', 'height', 'analysis',
    ];

    protected $casts = [
        'analysis' => 'array',
        'bytes'    => 'integer',
        'width'    => 'integer',
        'height'   => 'integer',
    ];
}
