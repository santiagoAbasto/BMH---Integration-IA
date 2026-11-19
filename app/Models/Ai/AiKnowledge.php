<?php

declare(strict_types=1);

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Una entrada de la base de conocimiento del asesor: un término, una regla o una
 * nota que el equipo de BMH enseñó. Sólo las aprobadas y activas se inyectan al
 * prompt del modelo (ver KnowledgeBaseService).
 */
final class AiKnowledge extends Model
{
    protected $connection = 'mysql_ai';
    protected $table = 'ai_knowledge';

    public const KIND_TERM = 'term';
    public const KIND_RULE = 'rule';
    public const KIND_NOTE = 'note';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'kind', 'title', 'body', 'synonyms',
        'category_id', 'product_id', 'reference_path',
        'status', 'source', 'created_by', 'approved_by_admin_id', 'approved_at', 'active',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'product_id' => 'integer',
        'created_by' => 'integer',
        'approved_by_admin_id' => 'integer',
        'approved_at' => 'datetime',
        'active' => 'boolean',
    ];

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
