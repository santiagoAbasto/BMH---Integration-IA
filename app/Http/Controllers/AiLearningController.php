<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Catalog\Contracts\CatalogRepositoryInterface;
use App\Models\Ai\AiCorrectionRule;
use App\Models\Ai\AiDemoAccount;
use App\Models\Ai\AiFeedback;
use App\Services\Ai\ApprovedCorrectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Dirigir al asesor: qué foto o captura corresponde a qué código.
 *
 * Tres entradas alimentan lo aprendido (ver ApprovedCorrectionService):
 * lo que enseña un administrador acá, lo que marcan las cuentas demo con 👍/👎
 * y lo que un administrador aprueba de los clientes. Todo es reversible: una
 * regla se desactiva o se borra y deja de aplicarse al instante.
 */
final class AiLearningController extends Controller
{
    public function __construct(
        private readonly CatalogRepositoryInterface $catalog,
        private readonly ApprovedCorrectionService $corrections,
    ) {
    }

    public function index(): View
    {
        $this->adminId();

        $since = now()->subDays(30);
        $customerFeedback = static fn () => AiFeedback::query()
            ->where('created_at', '>=', $since)
            ->whereHas('conversation', static fn ($q) => $q->customerFacing());

        $good = (clone $customerFeedback())->where('was_correct', true)->count();
        $bad = (clone $customerFeedback())->where('was_correct', false)->count();

        $demoRows = AiDemoAccount::query()->orderByDesc('active')->orderBy('id')->get();
        $users = DB::connection('mysql_legacy')->table('users')
            ->whereIn('id', $demoRows->pluck('customer_id')->all())
            ->get(['id', 'name', 'email'])
            ->keyBy('id');
        $perAccount = $customerFeedback()
            ->whereIn('customer_id', $demoRows->pluck('customer_id')->all())
            ->selectRaw('customer_id, SUM(was_correct) AS good, SUM(NOT was_correct) AS bad')
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $rules = AiCorrectionRule::query()->latest('id')->limit(60)->get();
        $recent = $customerFeedback()
            ->whereIn('customer_id', $demoRows->pluck('customer_id')->all())
            ->with('message:id,content')
            ->latest('id')
            ->limit(25)
            ->get();

        $productIds = $rules->pluck('expected_product_id')
            ->merge($recent->pluck('product_id'))
            ->merge($recent->pluck('expected_product_id'))
            ->filter()->unique()->map(static fn ($id): int => (int) $id)->values()->all();
        $products = collect($this->catalog->findMany($productIds))->keyBy('id');

        return view('backend.ai-learning', [
            'stats' => [
                'total' => $good + $bad,
                'good' => $good,
                'bad' => $bad,
                'rate' => $good + $bad > 0 ? round($good / ($good + $bad) * 100, 1) : null,
                'rules' => AiCorrectionRule::query()->where('active', true)->count(),
                'pending' => AiFeedback::query()
                    ->where('was_correct', false)
                    ->where('review_status', 'pending')
                    ->whereHas('conversation', static fn ($q) => $q->customerFacing())
                    ->count(),
            ],
            'demoAccounts' => $demoRows->map(static fn (AiDemoAccount $a): array => [
                'model' => $a,
                'user' => $users->get($a->customer_id),
                'good' => (int) ($perAccount->get($a->customer_id)->good ?? 0),
                'bad' => (int) ($perAccount->get($a->customer_id)->bad ?? 0),
            ]),
            'rules' => $rules,
            'recent' => $recent,
            'products' => $products,
        ]);
    }

    public function storeDemoAccount(Request $request): RedirectResponse
    {
        $adminId = $this->adminId();
        $data = $request->validate([
            'customer' => ['required', 'string', 'max:120'],
            'label' => ['nullable', 'string', 'max:80'],
        ]);

        $needle = trim($data['customer']);
        $query = DB::connection('mysql_legacy')->table('users');
        $user = ctype_digit($needle)
            ? $query->where('id', (int) $needle)->first()
            : $query->where('email', $needle)->first();

        if ($user === null) {
            return back()->withErrors(['customer' => 'No hay ningún cliente con ese ID o correo.'])->withInput();
        }

        if ((int) $user->habilitado !== 1) {
            return back()->withErrors(['customer' => 'Ese cliente no está habilitado en el sitio: no podría iniciar sesión.'])->withInput();
        }

        AiDemoAccount::query()->updateOrCreate(
            ['customer_id' => (int) $user->id],
            ['label' => $data['label'] ?? null, 'active' => true, 'created_by_admin_id' => $adminId],
        );

        return back()->with('status', "Cuenta demo habilitada: {$user->name}. Ya puede usar el asesor y sus 👍/👎 se aprenden al instante.");
    }

    public function toggleDemoAccount(int $account): RedirectResponse
    {
        $this->adminId();
        $row = AiDemoAccount::query()->findOrFail($account);
        $row->update(['active' => ! $row->active]);

        return back()->with('status', $row->active ? 'Cuenta demo activada.' : 'Cuenta demo desactivada: pierde el acceso al asesor.');
    }

    public function destroyDemoAccount(int $account): RedirectResponse
    {
        $this->adminId();
        AiDemoAccount::query()->findOrFail($account)->delete();

        return back()->with('status', 'Cuenta demo quitada.');
    }

    /** Enseñar: esta foto o captura corresponde a este código. */
    public function teach(Request $request): RedirectResponse
    {
        $adminId = $this->adminId();
        $data = $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'code' => ['required', 'string', 'max:64'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $code = trim($data['code']);
        $products = $this->catalog->findByCode($code);

        if ($products === []) {
            $products = $this->catalog->findByNormalizedCode($code);
        }

        $products = array_values(array_filter($products, static fn ($p): bool => $p->condition->isPublic()));

        if ($products === []) {
            return back()->withErrors(['code' => "El código «{$code}» no existe en el catálogo público."])->withInput();
        }

        if (count($products) > 1) {
            $codes = implode(', ', array_map(static fn ($p): string => (string) $p->code, array_slice($products, 0, 5)));

            return back()->withErrors(['code' => "El código «{$code}» corresponde a varios artículos ({$codes}). Usa el código exacto."])->withInput();
        }

        $file = $request->file('image');
        $input = $this->corrections->fingerprintFile((string) $file->getRealPath());

        if ($input === null) {
            return back()->withErrors(['image' => 'No se pudo leer la imagen.'])->withInput();
        }

        $this->corrections->remember($input, $products[0]->id, 'admin_teach', null, $adminId, $data['note'] ?? null, (string) $file->getRealPath());

        return back()->with('status', "Aprendido: esa imagen corresponde a {$products[0]->code}. Se aplica también cuando llega recomprimida o reenviada.");
    }

    public function toggleRule(int $rule): RedirectResponse
    {
        $this->adminId();
        $row = AiCorrectionRule::query()->findOrFail($rule);
        $row->update(['active' => ! $row->active]);

        return back()->with('status', $row->active ? 'Regla activada.' : 'Regla desactivada: el asesor deja de aplicarla.');
    }

    public function destroyRule(int $rule): RedirectResponse
    {
        $this->adminId();
        $row = AiCorrectionRule::query()->findOrFail($rule);

        if ($row->reference_path !== null) {
            Storage::disk(config('bmh.attachments.disk', 'ai_private'))->delete($row->reference_path);
        }

        $row->delete();

        return back()->with('status', 'Regla eliminada.');
    }

    public function ruleImage(int $rule): BinaryFileResponse
    {
        $this->adminId();
        $row = AiCorrectionRule::query()->findOrFail($rule);
        $disk = Storage::disk(config('bmh.attachments.disk', 'ai_private'));

        abort_if($row->reference_path === null || ! $disk->exists($row->reference_path), 404);

        return response()->file($disk->path($row->reference_path));
    }

    private function adminId(): int
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($admin !== null && $admin->rol === 'administrador', 403);

        return (int) $admin->id;
    }
}
