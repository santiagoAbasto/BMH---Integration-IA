<?php

/*
|--------------------------------------------------------------------------
| BMH AI Technical Sales Advisor — configuración central
|--------------------------------------------------------------------------
|
| Todo umbral, peso y feature flag del asesor vive acá. Nada de números
| mágicos desperdigados por los Services.
|
*/

return [

    /*
    |----------------------------------------------------------------------
    | Feature flags
    |----------------------------------------------------------------------
    | La Zona Clientes tiene que seguir funcionando aunque todo esto esté
    | apagado. Ver docs/ai-architecture.md §"Fallback sin AI".
    */
    'features' => [
        'ai'               => env('AI_ENABLED', true),
        'vision'           => env('AI_VISION_ENABLED', true),
        'semantic_search'  => env('SEMANTIC_SEARCH_ENABLED', false),
        'customer_history' => env('CUSTOMER_HISTORY_ENABLED', true),
        'debug'            => env('AI_DEBUG', false),
        'fallback'         => env('AI_FALLBACK_ENABLED', false),
    ],

    /*
    |----------------------------------------------------------------------
    | Proveedores de IA
    |----------------------------------------------------------------------
    | Sin AI_API_KEY el manager cae solo a `mock`. La demo funciona igual.
    */
    'ai' => [
        'provider'          => env('AI_PROVIDER', 'mock'),
        'fallback_provider' => env('AI_FALLBACK_PROVIDER', 'mock'),
        'api_key'           => env('AI_API_KEY'),
        'timeout'           => (int) env('AI_TIMEOUT', 30),
        'prompt_version'    => 'bmh-sales-advisor-v1',

        'providers' => [
            'gemini' => [
                'base_url'        => env('AI_GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
                'api_key'         => env('AI_API_KEY'),
                'chat_model'      => env('AI_CHAT_MODEL', 'gemini-2.5-flash'),
                'vision_model'    => env('AI_VISION_MODEL', 'gemini-2.5-flash'),
                'fast_model'      => env('AI_FAST_MODEL', 'gemini-2.5-flash-lite'),
                'embedding_model' => env('AI_EMBEDDING_MODEL', 'text-embedding-004'),
            ],
            'openai' => [
                'base_url'        => env('AI_OPENAI_BASE_URL', 'https://api.openai.com/v1'),
                'api_key'         => env('AI_API_KEY'),
                'chat_model'      => env('AI_CHAT_MODEL', 'gpt-4.1'),
                'vision_model'    => env('AI_VISION_MODEL', 'gpt-4.1'),
                'fast_model'      => env('AI_FAST_MODEL', 'gpt-4.1-mini'),
                'embedding_model' => env('AI_EMBEDDING_MODEL', 'text-embedding-3-small'),
            ],
        ],

        /*
        | AiModelRouter: qué modelo usar según la tarea. No mandamos todo al
        | modelo caro. Ver docs/ai-architecture.md §"AI Router".
        */
        'routing' => [
            'intent'      => 'fast',
            'extraction'  => 'fast',
            'vision'      => 'vision',
            'conversation'=> 'chat',
        ],

        // Techo por conversación. Si se supera, se deriva a humano.
        'limits' => [
            'max_images_per_message' => 3,
            'max_tool_calls_per_turn'=> 8,
            'max_tokens_per_convo'   => (int) env('AI_MAX_TOKENS_PER_CONVO', 200000),
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Umbrales de confianza
    |----------------------------------------------------------------------
    | Un solo lugar. La UI traduce el número a texto; nunca muestra el score
    | crudo al cliente.
    */
    'confidence' => [
        'very_high' => 0.90, // "Encontré esta pieza."
        'high'      => 0.75, // "Encontré una coincidencia muy probable."
        'ambiguous' => 0.50, // Pedir otro dato.
        // < ambiguous  → "No tengo información suficiente para asegurarlo."

        // Etiqueta completa: la que usa el asistente al hablar y el tooltip.
        'labels' => [
            'very_high' => 'Coincidencia muy alta',
            'high'      => 'Coincidencia alta',
            'ambiguous' => 'Coincidencia parcial',
            'low'       => 'Coincidencia baja',
        ],

        // Versión corta para el badge de la card. "Coincidencia" es redundante
        // dentro de una tarjeta de resultado y se come el ancho del rubro.
        'short_labels' => [
            'very_high' => 'Muy alta',
            'high'      => 'Alta',
            'ambiguous' => 'Parcial',
            'low'       => 'Baja',
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Ranking multifactor
    |----------------------------------------------------------------------
    | Regla dura: la similitud visual NUNCA le gana a un código exacto.
    | Por eso `exact_code` está un orden de magnitud por encima del resto.
    */
    'ranking' => [
        'weights' => [
            'exact_code'          => 100.0,
            'normalized_code'     => 80.0,
            'equivalence'         => 60.0,
            'partial_code'        => 35.0,
            'attribute_match'     => 12.0,
            'brand_match'         => 10.0,
            'model_match'         => 8.0,
            'category_match'      => 6.0,
            'text_similarity'     => 5.0,
            'semantic_similarity' => 4.0,
            'vision_similarity'   => 3.0,   // deliberadamente bajo
            'customer_history'    => 7.0,
            'popularity'          => 2.0,
        ],

        // Cuántos candidatos se devuelven / se muestran.
        'max_candidates'   => 24,
        'max_presented'    => 3,
        'comparable_limit' => 4,
    ],

    /*
    |----------------------------------------------------------------------
    | Desambiguación
    |----------------------------------------------------------------------
    */
    'disambiguation' => [
        // Si un atributo no parte el conjunto de candidatos, no se pregunta.
        'min_information_gain' => 0.15,
        // Con esta cantidad o menos de candidatos, mostramos en vez de preguntar.
        'present_threshold'    => 3,
        // Atributos que nunca conviene preguntar (el cliente no los sabe).
        'never_ask'            => ['PESO', 'MEDIDAS', 'TERMINACION'],
    ],

    /*
    |----------------------------------------------------------------------
    | Inventario
    |----------------------------------------------------------------------
    | productos.stock vale 1 en las 5.054 filas. No sabemos qué significa.
    | Hasta que BMH lo confirme, el asistente NO afirma disponibilidad.
    | Ver docs/database-discovery.md §5.
    */
    'inventory' => [
        'semantics_verified' => env('BMH_STOCK_SEMANTICS_VERIFIED', false),
        'unverified_source'  => 'stock_semantics_unverified',
    ],

    /*
    |----------------------------------------------------------------------
    | Pricing
    |----------------------------------------------------------------------
    | Fórmula reconstruida del código de producción. Ver docs/pricing-rules.md.
    */
    'pricing' => [
        'currency'  => 'ARS',
        'iva_id'    => 1,
        // La categoría tiene aumento/descuento en la base pero el código de
        // producción los tiene COMENTADOS. Replicamos producción.
        'apply_category_modifiers' => false,
        // productos.aumento está cargado (1.024 filas con 10) pero tampoco se
        // aplica en producción.
        'apply_product_aumento'    => false,
        // La bonificación por volumen se muestra como escala pero no se
        // descuenta del total en producción. La informamos, no la aplicamos.
        'apply_bonificacion'       => false,
        // Precio de lista sin IVA; el IVA se suma al cerrar el carrito.
        'prices_include_tax'       => false,
    ],

    /*
    |----------------------------------------------------------------------
    | Adjuntos
    |----------------------------------------------------------------------
    */
    'attachments' => [
        'disk'           => 'ai_private',
        'max_size_kb'    => 12288, // 12 MB de entrada…
        'allowed_mimes'  => ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif'],
        // …pero al proveedor va una versión optimizada.
        'analysis_max_px'=> 1280,
        'analysis_quality'=> 82,
        'thumbnail_px'   => 320,
        'strip_exif'     => true,
    ],

    /*
    |----------------------------------------------------------------------
    | Handoff
    |----------------------------------------------------------------------
    */
    'handoff' => [
        'auto_reasons' => [
            'low_confidence',
            'too_many_candidates',
            'pricing_requires_validation',
            'data_conflict',
            'product_not_found',
            'ai_error',
        ],
        'too_many_candidates_threshold' => 40,
        'message' => 'No quiero darte una referencia incorrecta. Dejé la consulta preparada para que la continúe un asesor de BMH.',
    ],

    /*
    |----------------------------------------------------------------------
    | Búsqueda
    |----------------------------------------------------------------------
    */
    'search' => [
        'index_table'      => 'catalog_search_documents',
        'sync_chunk'       => 500,
        'fulltext_min_len' => 3,
        'cache_ttl'        => 600,
    ],
];
