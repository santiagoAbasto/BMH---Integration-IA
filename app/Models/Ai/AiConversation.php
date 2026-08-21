<?php

declare(strict_types=1);

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Una conversación del asesor.
 *
 * Vive en `mysql_ai`. `customer_id` apunta a `users` de la base legacy sin
 * foreign key: son bases distintas a propósito.
 */
final class AiConversation extends Model
{
    use HasFactory;

    protected $connection = 'mysql_ai';
    protected $table = 'ai_conversations';

    protected $fillable = [
        'customer_id', 'title', 'status', 'detected_intent', 'resolved_product_id',
        'prompt_version', 'total_input_tokens', 'total_output_tokens', 'total_images',
    ];

    protected $casts = [
        'customer_id'         => 'integer',
        'resolved_product_id' => 'integer',
        'total_input_tokens'  => 'integer',
        'total_output_tokens' => 'integer',
        'total_images'        => 'integer',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessageRecord::class, 'conversation_id');
    }

    public function facts(): HasMany
    {
        return $this->hasMany(AiCustomerContext::class, 'conversation_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(AiAttachment::class, 'conversation_id');
    }

    /** Aislamiento de clientes a nivel de query. */
    public function scopeOwnedBy($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }
}
