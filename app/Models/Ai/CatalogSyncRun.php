<?php

declare(strict_types=1);

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Model;

final class CatalogSyncRun extends Model
{
    protected $connection = 'mysql_ai';
    protected $table = 'catalog_sync_runs';

    protected $fillable = [
        'status', 'products_read', 'documents_created', 'documents_updated',
        'documents_deleted', 'anomalies', 'report', 'duration_ms',
    ];

    protected $casts = ['report' => 'array'];
}
