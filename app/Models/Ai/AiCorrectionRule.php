<?php

declare(strict_types=1);

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Regla global aprobada para repetir una corrección ante la misma entrada. */
final class AiCorrectionRule extends Model
{
    protected $connection = 'mysql_ai';
    protected $table = 'ai_correction_rules';

    protected $fillable = [
        'feedback_id', 'input_fingerprint', 'fingerprint_kind', 'expected_product_id',
        'approved_by_admin_id', 'approved_at', 'active',
    ];

    protected $casts = [
        'feedback_id' => 'integer',
        'expected_product_id' => 'integer',
        'approved_by_admin_id' => 'integer',
        'approved_at' => 'datetime',
        'active' => 'boolean',
    ];

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(AiFeedback::class, 'feedback_id');
    }
}
