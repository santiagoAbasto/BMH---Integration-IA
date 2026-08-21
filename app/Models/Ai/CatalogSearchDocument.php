<?php

declare(strict_types=1);

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Model;

final class CatalogSearchDocument extends Model
{
    protected $connection = 'mysql_ai';
    protected $table = 'catalog_search_documents';

    protected $fillable = [
        'product_id', 'code', 'normalized_code', 'name', 'category_id', 'category_name',
        'brand', 'model', 'condition', 'list_price', 'has_image', 'duplicate_code',
        'attributes', 'equivalences', 'searchable_text', 'content_hash',
    ];

    protected $casts = [
        'attributes'     => 'array',
        'equivalences'   => 'array',
        'has_image'      => 'boolean',
        'duplicate_code' => 'boolean',
        'list_price'     => 'float',
    ];
}
