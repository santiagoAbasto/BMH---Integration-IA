<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Support\Motion;
use Tests\TestCase;

/**
 * Animaciones del sitio público (App\Support\Motion, layouts/partials/motion-head, resources/js/components/motion.js).
 * Hermético: no usa la base.
 */
final class MotionTest extends TestCase
{
    public function test_el_head_activa_las_animaciones_solo_sin_reducir_movimiento_y_con_salida_si_falla_el_js(): void
    {
        $head = view('layouts.partials.motion-head')->render();

        $this->assertStringContainsString("matchMedia('(prefers-reduced-motion: reduce)')", $head);
        $this->assertStringContainsString("root.classList.add('motion')", $head);
        // Si el JS no llega a correr, a los 2,5 s se ve todo igual.
        $this->assertStringContainsString("root.classList.remove('motion'); }, 2500)", $head);
        $this->assertStringContainsString('html.motion :is(', $head);
        $this->assertStringContainsString('<script type="application/json" id="motion-targets">', $head);

        $json = str($head)->after('id="motion-targets">')->before('</script>')->toString();
        $this->assertSame(Motion::targets(), json_decode($json, true));
    }

    public function test_el_estado_oculto_es_solo_de_pantalla_y_de_lo_que_todavia_no_aparecio(): void
    {
        $css = Motion::hiddenCss();

        $this->assertStringStartsWith('@media screen', $css);
        $this->assertSame(3, substr_count($css, ':not(.is-revealed)'));
        $this->assertStringContainsString('.bmh-grilla-rubros > *', $css);
        $this->assertStringContainsString('.catalogo__lista > *', $css);
        $this->assertStringContainsString('.osole-info-split-v1__media', $css);
        // Solo opacidad, desplazamiento, escala y recorte: nada que cambie el tamaño o empuje lo de alrededor.
        $this->assertDoesNotMatchRegularExpression('/\b(height|width|margin|padding|top|left|display)\s*:/', $css);
        $this->assertSame(['blocks', 'media', 'grids', 'rows', 'swaps'], array_keys(Motion::targets()));
    }

    public function test_el_header_y_las_migas_no_se_animan(): void
    {
        $selectors = implode(' ', array_merge(...array_values(Motion::targets())));

        $this->assertStringNotContainsString('header', $selectors);
        $this->assertStringNotContainsString('migas', $selectors);
        $this->assertStringNotContainsString('breadcrumb', $selectors);
    }

    public function test_el_layout_publico_lo_carga_antes_que_las_hojas_de_estilo(): void
    {
        $layout = (string) file_get_contents(resource_path('views/layouts/plantilla-front.blade.php'));

        $motion = strpos($layout, "@include('layouts.partials.motion-head')");
        $this->assertNotFalse($motion);
        $this->assertLessThan(strpos($layout, '@vite('), $motion);
        $this->assertLessThan(strpos($layout, '<style>'), $motion);
    }
}
