<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Services\Ai\DTO\AiMessage;
use App\Services\Ai\Providers\OpenAiProvider;
use Tests\TestCase;

/**
 * El formato que exige Chat Completions para los resultados de tool.
 *
 * OpenAI rechaza con 400 cualquier mensaje `tool` que no responda a un
 * `assistant` previo con `tool_calls`. Nuestro orquestador resuelve las
 * herramientas por su cuenta y pasa los resultados ya listos, así que ese
 * `assistant` lo sintetiza el adaptador.
 *
 * Sin esto la llamada falla entera y la respuesta sale del texto de
 * contingencia: el cliente lee una plantilla creyendo que le contestó la IA.
 */
final class OpenAiMessageFormatTest extends TestCase
{
    /** @param list<AiMessage> $messages */
    private function serializar(array $messages): array
    {
        $provider = new OpenAiProvider([
            'api_key' => 'sk-test',
            'models'  => ['conversation' => 'gpt-4o-mini'],
        ]);

        $metodo = new \ReflectionMethod($provider, 'toMessages');
        $metodo->setAccessible(true);

        return $metodo->invoke($provider, $messages);
    }

    public function test_todo_resultado_de_tool_va_precedido_por_su_declaracion(): void
    {
        $serializados = $this->serializar([
            AiMessage::system('Sos el asesor.'),
            AiMessage::user('¿tenés este rotor?'),
            AiMessage::toolResult('search_products', 'call_search', ['total' => 3]),
            AiMessage::toolResult('get_price', 'call_price', ['unit' => 1000]),
        ]);

        $roles = array_column($serializados, 'role');

        $this->assertSame(['system', 'user', 'assistant', 'tool', 'tool'], $roles);

        // El assistant declara las dos llamadas, en una sola ronda.
        $declaracion = $serializados[2];

        $this->assertNull($declaracion['content']);
        $this->assertCount(2, $declaracion['tool_calls']);
        $this->assertSame(['call_search', 'call_price'], array_column($declaracion['tool_calls'], 'id'));
        $this->assertSame(
            'search_products',
            $declaracion['tool_calls'][0]['function']['name'],
        );

        // Y cada resultado apunta a la llamada que le corresponde.
        $this->assertSame('call_search', $serializados[3]['tool_call_id']);
        $this->assertSame('call_price', $serializados[4]['tool_call_id']);
    }

    public function test_una_conversacion_sin_tools_no_se_toca(): void
    {
        $serializados = $this->serializar([
            AiMessage::system('Sos el asesor.'),
            AiMessage::user('hola'),
            AiMessage::assistant('¿Qué pieza necesitás?'),
            AiMessage::user('un rotor'),
        ]);

        $this->assertSame(
            ['system', 'user', 'assistant', 'user'],
            array_column($serializados, 'role'),
        );

        foreach ($serializados as $mensaje) {
            $this->assertArrayNotHasKey('tool_calls', $mensaje);
        }
    }

    public function test_dos_rondas_separadas_no_se_mezclan(): void
    {
        // Turno anterior con tools, y turno nuevo con los suyos: cada resultado
        // tiene que quedar pegado a su propia declaración.
        $serializados = $this->serializar([
            AiMessage::user('primero'),
            AiMessage::toolResult('search_products', 'call_a', ['total' => 1]),
            AiMessage::user('segundo'),
            AiMessage::toolResult('search_products', 'call_b', ['total' => 2]),
        ]);

        $this->assertSame(
            ['user', 'assistant', 'tool', 'user', 'assistant', 'tool'],
            array_column($serializados, 'role'),
        );

        $this->assertSame('call_a', $serializados[1]['tool_calls'][0]['id']);
        $this->assertSame('call_b', $serializados[4]['tool_calls'][0]['id']);
    }
}
