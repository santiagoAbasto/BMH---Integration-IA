<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Services\Anio;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** El año del footer sale del reloj, en hora de Argentina. */
final class AnioTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_es_el_anio_en_curso(): void
    {
        Carbon::setTestNow('2026-11-12 12:00:00');
        $this->assertSame(2026, (new Anio())->actual());
    }

    public function test_usa_la_hora_de_argentina_y_no_la_del_servidor(): void
    {
        // 1 de enero 01:00 UTC = 31 de diciembre 22:00 en Buenos Aires: todavía es 2026.
        Carbon::setTestNow(Carbon::parse('2027-01-01 01:00:00', 'UTC'));
        $this->assertSame(2026, (new Anio())->actual());

        Carbon::setTestNow(Carbon::parse('2027-01-01 03:00:00', 'UTC'));
        $this->assertSame(2027, (new Anio())->actual());
    }
}
