<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Http\Middleware\EnsureHumanFormSubmission;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\CreatesApplication;

/**
 * /datos-kc: la subida pública sólo acepta planillas y la extensión la decide el servidor (antes se podía subir
 * un .php a public/archivos). Base local; la fila de `descargas` y su archivo se restauran al terminar.
 */
final class DatosKcSubidaTest extends BaseTestCase
{
    use CreatesApplication;

    private string $origDefault = '';

    private array $origMysql = [];

    private ?object $fila = null;

    private ?string $respaldo = null;

    private string $cwd = '';

    /** @var list<string> */
    private array $antes = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->origDefault = config('database.default');
        $this->origMysql = config('database.connections.mysql');
        config([
            'database.connections.mysql.database' => 'bmh_legacy',
            'database.connections.mysql.username' => 'bmh_app',
            'database.connections.mysql.password' => 'bmh_app_local',
            'database.default' => 'mysql',
        ]);
        DB::purge('mysql');
        // Como en un pedido web: la carpeta de trabajo es la raíz pública (la subida usa la ruta relativa «archivos»).
        $this->cwd = (string) getcwd();
        chdir(public_path());

        $this->fila = DB::table('descargas')->where('sector', 'datos-actual')->first();
        $actual = public_path('archivos/'.$this->fila->path);
        if (is_file($actual)) {
            $this->respaldo = tempnam(sys_get_temp_dir(), 'datoskc');
            copy($actual, $this->respaldo);
        }
        $this->antes = File::glob(public_path('archivos/datoskc_*')) ?: [];
    }

    protected function tearDown(): void
    {
        foreach (array_diff(File::glob(public_path('archivos/datoskc_*')) ?: [], $this->antes) as $nuevo) {
            File::delete($nuevo);
        }
        DB::table('descargas')->where('id', $this->fila->id)->update(['archivo' => $this->fila->archivo, 'path' => $this->fila->path]);
        if ($this->respaldo !== null) {
            copy($this->respaldo, public_path('archivos/'.$this->fila->path));
            unlink($this->respaldo);
        }
        chdir($this->cwd);
        config(['database.default' => $this->origDefault, 'database.connections.mysql' => $this->origMysql]);
        DB::purge('mysql');
        parent::tearDown();
    }

    private function antiBots(): array
    {
        $token = EnsureHumanFormSubmission::token();
        $this->travel(3)->seconds();

        return [EnsureHumanFormSubmission::HONEYPOT => '', EnsureHumanFormSubmission::TOKEN => $token];
    }

    public function test_un_php_disfrazado_de_excel_no_se_guarda(): void
    {
        $php = UploadedFile::fake()->createWithContent('datos.php', '<?php echo "hola";');
        $disfrazado = UploadedFile::fake()->createWithContent('datos.xlsx', '<?php system($_GET["c"]);');

        foreach ([$php, $disfrazado] as $archivo) {
            $this->from(route('datos.kc'))->post(route('datos.update'), ['file' => $archivo] + $this->antiBots())
                ->assertRedirect(route('datos.kc'))
                ->assertSessionHasErrors('file');
        }

        $this->assertSame($this->antes, File::glob(public_path('archivos/datoskc_*')) ?: []);
        $this->assertSame($this->fila->path, DB::table('descargas')->where('id', $this->fila->id)->value('path'));
    }

    public function test_una_planilla_se_guarda_con_la_extension_que_decide_el_servidor(): void
    {
        $ruta = tempnam(sys_get_temp_dir(), 'kc').'.xlsx';
        $libro = new Spreadsheet();
        $libro->getActiveSheet()->setCellValue('A1', 'CODIGO');
        (new Xlsx($libro))->save($ruta);
        // El nombre trae otra extensión: la que vale es la del contenido.
        $archivo = new UploadedFile($ruta, 'datos.php.xlsx', null, null, true);

        $this->from(route('datos.kc'))->post(route('datos.update'), ['file' => $archivo] + $this->antiBots())
            ->assertRedirect(route('datos.kc'))
            ->assertSessionHasNoErrors();

        $path = DB::table('descargas')->where('id', $this->fila->id)->value('path');
        $this->assertMatchesRegularExpression('/^datoskc_[a-f0-9]+\.xlsx$/', $path);
        $this->assertFileExists(public_path('archivos/'.$path));
    }

    public function test_sin_los_campos_anti_bots_se_rechaza(): void
    {
        $this->post(route('datos.update'), ['file' => UploadedFile::fake()->create('datos.xlsx', 10)])
            ->assertRedirect();
        $this->assertSame($this->fila->path, DB::table('descargas')->where('id', $this->fila->id)->value('path'));
    }
}
