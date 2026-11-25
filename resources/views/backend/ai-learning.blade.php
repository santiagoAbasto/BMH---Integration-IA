@extends('layouts.plantilla-back')

@section('styles')
<style>
  .ail { color:#17212b; --ail-blue:#254f70; --ail-cyan:#0098da; --ail-green:#5f8a12; --ail-red:#b3261e; }
  .ail h1 { margin:0!important; border:0!important; padding:0!important; text-transform:none!important; }
  .ail-eyebrow { margin:0 0 5px; color:var(--ail-cyan); font-size:12px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; }
  .ail-intro { max-width:780px; margin:8px 0 20px; color:#607080; line-height:1.55; }
  .ail-alert { margin-bottom:16px; border-radius:10px; padding:11px 14px; font-size:14px; }
  .ail-alert.ok { border:1px solid #cfe5a6; background:#f3f9e6; color:#43610c; }
  .ail-alert.err { border:1px solid #f0c4c0; background:#fdf1f0; color:#8a1f18; }
  .ail-metrics { display:grid; grid-template-columns:repeat(6,minmax(110px,1fr)); gap:10px; margin-bottom:20px; }
  .ail-metric { border:1px solid #dce5eb; border-radius:12px; padding:12px 14px; background:#fff; }
  .ail-metric strong { display:block; color:#203444; font-size:22px; line-height:1.1; }
  .ail-metric span { display:block; margin-top:5px; color:#6b7b87; font-size:11px; line-height:1.35; }
  .ail-grid { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:16px; align-items:start; margin-bottom:16px; }
  .ail-card { min-width:0; border:1px solid #dce5eb; border-radius:14px; background:#fff; box-shadow:0 5px 18px rgba(32,57,75,.06); overflow:hidden; margin-bottom:16px; }
  .ail-grid .ail-card { margin-bottom:0; }
  .ail-card-head { padding:14px 16px; border-bottom:1px solid #e6edf1; background:#fbfcfd; }
  .ail-card-head h2 { margin:0!important; font-size:16px!important; }
  .ail-card-head p { margin:4px 0 0; color:#71808d; font-size:12.5px; line-height:1.45; }
  .ail-body { padding:14px 16px; }
  .ail-form { display:grid; gap:10px; }
  .ail-form label { display:block; margin-bottom:4px; font-size:12px; font-weight:700; color:#536270; }
  .ail-form input[type=text],.ail-form input[type=file],.ail-form textarea,.ail-form select { width:100%; border:1px solid #cdd9e1; border-radius:9px; background:#fff; padding:9px 11px; font:inherit; }
  .ail-form textarea { min-height:68px; resize:vertical; }
  .ail-form-row { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
  .ail-pill.warn { background:#fff3dc; color:#8a5a00; }
  .ail-edit { margin:0; }
  .ail-edit summary { cursor:pointer; font-size:12px; color:var(--ail-blue); }
  .ail-edit .ail-form { margin-top:8px; max-width:360px; }
  @media (max-width:640px){ .ail-form-row { grid-template-columns:1fr; } }
  .ail-btn { display:inline-flex; min-height:38px; align-items:center; justify-content:center; gap:7px; border:1px solid #cdd9e1; border-radius:9px; padding:7px 13px; background:#fff; color:#263a49; font-weight:600; cursor:pointer; font-size:13px; }
  .ail-btn:hover { border-color:#8aa3b5; }
  .ail-btn-primary { border-color:var(--ail-blue); background:var(--ail-blue); color:#fff; }
  .ail-btn-primary:hover { background:#173b58; color:#fff; }
  .ail-btn-danger { color:var(--ail-red); }
  .ail-btn-sm { min-height:30px; padding:4px 10px; font-size:12px; }
  .ail-table { width:100%; border-collapse:collapse; font-size:13px; }
  .ail-table th { padding:8px 12px; text-align:left; color:#6b7b87; font-size:11px; letter-spacing:.06em; text-transform:uppercase; border-bottom:1px solid #e6edf1; }
  .ail-table td { padding:9px 12px; border-bottom:1px solid #eef2f5; vertical-align:middle; }
  .ail-table tr:last-child td { border-bottom:0; }
  .ail-thumb { width:54px; height:54px; border-radius:8px; border:1px solid #dce5eb; object-fit:cover; background:#f3f6f8; }
  .ail-pill { display:inline-block; border-radius:999px; padding:3px 8px; font-size:11px; font-weight:700; background:#eef1f3; color:#59666f; }
  .ail-pill.good { background:#eaf5dc; color:#4a6b10; }
  .ail-pill.bad { background:#fdecea; color:#9d251d; }
  .ail-pill.blue { background:#e9f5fb; color:#007cad; }
  .ail-actions { display:flex; gap:6px; flex-wrap:wrap; }
  .ail-actions form { margin:0; }
  .ail-empty { padding:22px 16px; color:#71808d; font-size:13px; text-align:center; }
  .ail-muted { color:#71808d; font-size:12px; }
  .ail-scroll { overflow-x:auto; }
  @media (max-width: 1100px) { .ail-grid { grid-template-columns:1fr; } .ail-metrics { grid-template-columns:repeat(3,1fr); } }
</style>
@endsection

@section('content')
@php
  $origen = ['feedback' => 'Cliente + revisión', 'demo_account' => 'Cuenta demo', 'admin_teach' => 'Enseñado por admin'];
@endphp
<main class="ail">
  <p class="ail-eyebrow">Laboratorio interno</p>
  <h1>Aprendizaje de la IA</h1>
  <p class="ail-intro">
    Dirige al asesor: enséñale qué foto o captura corresponde a cada código, cargá términos, equivalencias y reglas del
    negocio en la <strong>Base de conocimiento</strong> (que el asesor lee y aplica en cada conversación), habilitá cuentas
    demo para que lo prueben y evalúen cada respuesta con 👍 o 👎, y revisá lo que fue aprendiendo. Una regla de imagen solo
    se aplica cuando llega la misma imagen (también reenviada o recomprimida) o el mismo texto; el asesor sigue pidiendo
    confirmar antes de agregar al carrito.
  </p>

  @if (session('status'))
    <div class="ail-alert ok" role="status">{{ session('status') }}</div>
  @endif
  @if ($errors->any())
    <div class="ail-alert err" role="alert">{{ $errors->first() }}</div>
  @endif

  <section class="ail-metrics" aria-label="Resumen de los últimos 30 días">
    <div class="ail-metric"><strong>{{ $stats['total'] }}</strong><span>Respuestas evaluadas (30 días)</span></div>
    <div class="ail-metric"><strong>{{ $stats['good'] }}</strong><span>👍 correctas</span></div>
    <div class="ail-metric"><strong>{{ $stats['bad'] }}</strong><span>👎 incorrectas</span></div>
    <div class="ail-metric"><strong>{{ $stats['rate'] === null ? '—' : $stats['rate'].'%' }}</strong><span>Acierto observado</span></div>
    <div class="ail-metric"><strong>{{ $stats['rules'] }}</strong><span>Reglas activas</span></div>
    <div class="ail-metric">
      <strong>{{ $stats['pending'] }}</strong>
      <span>Correcciones de clientes por revisar
        @if ($stats['pending'] > 0)· <a href="{{ route('admin.ai-tests.index') }}">revisar</a>@endif
      </span>
    </div>
  </section>

  <section class="ail-card">
    <header class="ail-card-head">
      <h2>Base de conocimiento</h2>
      <p>Enséñale al asesor términos y equivalencias («arr.» = inducido, bobina de arranque), reglas del negocio y notas de un rubro o producto. Lo que agregás acá el modelo lo <strong>lee y aplica</strong> en cada conversación. Es distinto de «Pruebas IA», que sólo sirve para probar y evaluar respuestas.</p>
    </header>
    <div class="ail-body">
      <form class="ail-form" method="POST" action="{{ route('admin.ai-learning.knowledge.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="ail-form-row">
          <div>
            <label for="kb-kind">Tipo</label>
            <select id="kb-kind" name="kind" required>
              <option value="term" @selected(old('kind') === 'term')>Término / equivalencia</option>
              <option value="rule" @selected(old('kind') === 'rule')>Regla del negocio</option>
              <option value="note" @selected(old('kind') === 'note')>Nota de rubro o producto</option>
            </select>
          </div>
          <div>
            <label for="kb-title">Título corto</label>
            <input id="kb-title" type="text" name="title" value="{{ old('title') }}" maxlength="120" placeholder="Ej.: arr. (arranque)" required>
          </div>
        </div>
        <div>
          <label for="kb-body">Qué debe entender</label>
          <textarea id="kb-body" name="body" maxlength="2000" placeholder="Ej.: «arr.» es lo mismo que inducido, bobina o bobina de arranque." required>{{ old('body') }}</textarea>
        </div>
        <div class="ail-form-row">
          <div>
            <label for="kb-syn">Sinónimos (opcional, separados por coma)</label>
            <input id="kb-syn" type="text" name="synonyms" value="{{ old('synonyms') }}" maxlength="255" placeholder="inducido, bobina, bobina de arranque">
          </div>
          <div>
            <label for="kb-cat">Rubro (opcional)</label>
            <select id="kb-cat" name="category_id">
              <option value="">Todos los rubros</option>
              @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" @selected((string) old('category_id') === (string) $cat->id)>{{ $cat->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="ail-form-row">
          <div>
            <label for="kb-code">Código de producto (opcional)</label>
            <input id="kb-code" type="text" name="code" value="{{ old('code') }}" maxlength="64" placeholder="Ej.: 1836">
          </div>
          <div>
            <label for="kb-image">Imagen de apoyo (opcional)</label>
            <input id="kb-image" type="file" name="image" accept="image/jpeg,image/png,image/webp">
          </div>
        </div>
        <div><button class="ail-btn ail-btn-primary" type="submit">Agregar al conocimiento</button></div>
      </form>
    </div>
    @if ($knowledge->isEmpty())
      <p class="ail-empty">Todavía no hay conocimiento cargado. Lo que agregues aparecerá acá y el asesor lo usará.</p>
    @else
      @php $kindLabels = ['term' => 'Término', 'rule' => 'Regla', 'note' => 'Nota']; @endphp
      <div class="ail-scroll">
        <table class="ail-table">
          <thead><tr><th>Tipo</th><th>Contenido</th><th>Alcance</th><th>Estado</th><th></th></tr></thead>
          <tbody>
            @foreach ($knowledge as $k)
              <tr>
                <td><span class="ail-pill blue">{{ $kindLabels[$k->kind] ?? $k->kind }}</span></td>
                <td>
                  <strong>{{ $k->title }}</strong>
                  <div class="ail-muted">{{ \Illuminate\Support\Str::limit($k->body, 140) }}</div>
                  @if ($k->synonyms)<div class="ail-muted">Sinónimos: {{ $k->synonyms }}</div>@endif
                  @php $kImgs = $k->imagePaths(); @endphp
                  @if ($kImgs !== [])
                    <div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:4px">
                      @foreach ($kImgs as $ki => $kImg)
                        <img class="ail-thumb" src="{{ route('admin.ai-learning.knowledge.image', ['entry' => $k->id, 'index' => $ki]) }}" alt="Foto {{ $ki + 1 }} de la entrada {{ $k->id }}" loading="lazy">
                      @endforeach
                    </div>
                  @endif
                </td>
                <td>
                  @if ($k->category_id)
                    {{ optional($categories->firstWhere('id', $k->category_id))->name ?? 'Rubro #'.$k->category_id }}
                  @elseif ($k->product_id)
                    {{ $products->get($k->product_id)?->code ?? '#'.$k->product_id }}
                  @else
                    <span class="ail-muted">Global</span>
                  @endif
                </td>
                <td>
                  @if ($k->status === 'pending')<span class="ail-pill warn">Pendiente</span>
                  @elseif ($k->status === 'rejected')<span class="ail-pill bad">Rechazada</span>
                  @else<span class="ail-pill good">Aprobada</span>@endif
                  <div style="margin-top:4px"><span class="ail-pill {{ $k->active ? 'good' : '' }}">{{ $k->active ? 'Activa' : 'Inactiva' }}</span></div>
                </td>
                <td>
                  <div class="ail-actions">
                    @if ($k->status === 'pending')
                      <form method="POST" action="{{ route('admin.ai-learning.knowledge.approve', $k->id) }}">@csrf<button class="ail-btn ail-btn-sm" type="submit">Aprobar</button></form>
                      <form method="POST" action="{{ route('admin.ai-learning.knowledge.reject', $k->id) }}">@csrf<button class="ail-btn ail-btn-sm ail-btn-danger" type="submit">Rechazar</button></form>
                    @endif
                    <form method="POST" action="{{ route('admin.ai-learning.knowledge.toggle', $k->id) }}">@csrf<button class="ail-btn ail-btn-sm" type="submit">{{ $k->active ? 'Desactivar' : 'Activar' }}</button></form>
                    <details class="ail-edit">
                      <summary>Editar</summary>
                      <form class="ail-form" method="POST" action="{{ route('admin.ai-learning.knowledge.update', $k->id) }}">
                        @csrf @method('PUT')
                        <div><label>Título</label><input type="text" name="title" value="{{ $k->title }}" maxlength="120" required></div>
                        <div><label>Qué debe entender</label><textarea name="body" maxlength="2000" required>{{ $k->body }}</textarea></div>
                        <div><label>Sinónimos</label><input type="text" name="synonyms" value="{{ $k->synonyms }}" maxlength="255"></div>
                        <div><button class="ail-btn ail-btn-sm ail-btn-primary" type="submit">Guardar</button></div>
                      </form>
                    </details>
                    <form method="POST" action="{{ route('admin.ai-learning.knowledge.destroy', $k->id) }}" onsubmit="return confirm('¿Eliminar esta entrada? No se puede deshacer.')">@csrf @method('DELETE')<button class="ail-btn ail-btn-sm ail-btn-danger" type="submit">Eliminar</button></form>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </section>

  <div class="ail-grid">
    <section class="ail-card">
      <header class="ail-card-head">
        <h2>Enseñar al asesor</h2>
        <p>Sube una foto o una captura del catálogo y el código correcto. Si luego llega esa imagen, el asesor propone ese artículo primero.</p>
      </header>
      <div class="ail-body">
        <form class="ail-form" method="POST" action="{{ route('admin.ai-learning.teach') }}" enctype="multipart/form-data">
          @csrf
          <div>
            <label for="ail-image">Foto o captura (JPG, PNG o WebP, hasta 8 MB)</label>
            <input id="ail-image" type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
          </div>
          <div>
            <label for="ail-code">Código correcto en el catálogo</label>
            <input id="ail-code" type="text" name="code" value="{{ old('code') }}" maxlength="64" placeholder="Ej.: 1984" required>
          </div>
          <div>
            <label for="ail-note">Nota (opcional)</label>
            <input id="ail-note" type="text" name="note" value="{{ old('note') }}" maxlength="255" placeholder="Ej.: captura de la ficha, vista desde el celular">
          </div>
          <div><button class="ail-btn ail-btn-primary" type="submit">Enseñar</button></div>
        </form>
      </div>
    </section>

    <section class="ail-card">
      <header class="ail-card-head">
        <h2>Cuentas demo</h2>
        <p>Clientes que pueden usar el asesor y evaluar cada respuesta. Lo que marcan se aprende al instante; el feedback de clientes comunes espera tu revisión.</p>
      </header>
      <div class="ail-body">
        <form class="ail-form" method="POST" action="{{ route('admin.ai-learning.demo.store') }}">
          @csrf
          <div>
            <label for="ail-customer">ID o correo de un cliente habilitado</label>
            <input id="ail-customer" type="text" name="customer" value="{{ old('customer') }}" maxlength="120" placeholder="Ej.: demo@bmh.local o 6065" required>
          </div>
          <div>
            <label for="ail-label">Etiqueta (opcional)</label>
            <input id="ail-label" type="text" name="label" value="{{ old('label') }}" maxlength="80" placeholder="Ej.: Taller de prueba, vendedor Juan">
          </div>
          <div><button class="ail-btn ail-btn-primary" type="submit">Habilitar cuenta demo</button></div>
        </form>
      </div>
      @if ($demoAccounts->isEmpty())
        <p class="ail-empty">Todavía no hay cuentas demo. El acceso sigue limitado a la lista del servidor.</p>
      @else
        <div class="ail-scroll">
          <table class="ail-table">
            <thead><tr><th>Cuenta</th><th>👍 / 👎 (30 d)</th><th>Estado</th><th></th></tr></thead>
            <tbody>
              @foreach ($demoAccounts as $row)
                <tr>
                  <td>
                    <strong>{{ $row['user']->name ?? 'Cliente #'.$row['model']->customer_id }}</strong>
                    <div class="ail-muted">{{ $row['user']->email ?? '' }}@if ($row['model']->label) · {{ $row['model']->label }}@endif</div>
                  </td>
                  <td><span class="ail-pill good">{{ $row['good'] }}</span> <span class="ail-pill bad">{{ $row['bad'] }}</span></td>
                  <td><span class="ail-pill {{ $row['model']->active ? 'good' : '' }}">{{ $row['model']->active ? 'Activa' : 'Inactiva' }}</span></td>
                  <td>
                    <div class="ail-actions">
                      <form method="POST" action="{{ route('admin.ai-learning.demo.toggle', $row['model']->id) }}">
                        @csrf
                        <button class="ail-btn ail-btn-sm" type="submit">{{ $row['model']->active ? 'Desactivar' : 'Activar' }}</button>
                      </form>
                      <form method="POST" action="{{ route('admin.ai-learning.demo.destroy', $row['model']->id) }}" onsubmit="return confirm('¿Quitar esta cuenta demo? Perderá el acceso al asesor si no está en la lista del servidor.')">
                        @csrf
                        @method('DELETE')
                        <button class="ail-btn ail-btn-sm ail-btn-danger" type="submit">Quitar</button>
                      </form>
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </section>
  </div>

  <section class="ail-card">
    <header class="ail-card-head">
      <h2>Reglas aprendidas</h2>
      <p>Lo que el asesor ya sabe. Desactiva o elimina una regla y deja de aplicarse al instante.</p>
    </header>
    @if ($rules->isEmpty())
      <p class="ail-empty">Aún no hay reglas. Aparecerán al enseñar, al aprobar una corrección o cuando una cuenta demo evalúe una respuesta.</p>
    @else
      <div class="ail-scroll">
        <table class="ail-table">
          <thead><tr><th>Entrada</th><th>Producto esperado</th><th>Origen</th><th>Usos</th><th>Estado</th><th></th></tr></thead>
          <tbody>
            @foreach ($rules as $rule)
              @php $producto = $products->get($rule->expected_product_id); @endphp
              <tr>
                <td>
                  @if ($rule->reference_path)
                    <img class="ail-thumb" src="{{ route('admin.ai-learning.rules.image', $rule->id) }}" alt="Imagen de la regla {{ $rule->id }}" loading="lazy">
                  @else
                    <span class="ail-pill blue">{{ $rule->fingerprint_kind === 'text' ? 'Texto' : 'Imagen' }}</span>
                  @endif
                </td>
                <td>
                  <strong>{{ $producto?->code ?? '#'.$rule->expected_product_id }}</strong>
                  <div class="ail-muted">{{ $producto?->name ?? 'Producto ya no disponible' }}</div>
                  @if ($rule->note)<div class="ail-muted">{{ $rule->note }}</div>@endif
                </td>
                <td><span class="ail-pill">{{ $origen[$rule->source] ?? $rule->source }}</span><div class="ail-muted">{{ $rule->created_at?->format('d/m/Y') }}</div></td>
                <td>{{ $rule->hits }}@if ($rule->last_hit_at)<div class="ail-muted">{{ $rule->last_hit_at->diffForHumans() }}</div>@endif</td>
                <td><span class="ail-pill {{ $rule->active ? 'good' : '' }}">{{ $rule->active ? 'Activa' : 'Inactiva' }}</span></td>
                <td>
                  <div class="ail-actions">
                    <form method="POST" action="{{ route('admin.ai-learning.rules.toggle', $rule->id) }}">
                      @csrf
                      <button class="ail-btn ail-btn-sm" type="submit">{{ $rule->active ? 'Desactivar' : 'Activar' }}</button>
                    </form>
                    <form method="POST" action="{{ route('admin.ai-learning.rules.destroy', $rule->id) }}" onsubmit="return confirm('¿Eliminar esta regla? No se puede deshacer.')">
                      @csrf
                      @method('DELETE')
                      <button class="ail-btn ail-btn-sm ail-btn-danger" type="submit">Eliminar</button>
                    </form>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </section>

  <section class="ail-card">
    <header class="ail-card-head">
      <h2>Últimas evaluaciones de cuentas demo</h2>
      <p>Cada 👍/👎 queda guardado con el motivo y el código esperado.</p>
    </header>
    @if ($recent->isEmpty())
      <p class="ail-empty">Cuando una cuenta demo evalúe una respuesta del asesor aparecerá acá.</p>
    @else
      <div class="ail-scroll">
        <table class="ail-table">
          <thead><tr><th>Fecha</th><th>Valoración</th><th>Respuesta</th><th>Propuesto</th><th>Esperado</th><th>Motivo</th></tr></thead>
          <tbody>
            @foreach ($recent as $fb)
              <tr>
                <td>{{ $fb->created_at?->format('d/m H:i') }}</td>
                <td><span class="ail-pill {{ $fb->was_correct ? 'good' : 'bad' }}">{{ $fb->was_correct ? '👍 Correcta' : '👎 Incorrecta' }}</span></td>
                <td class="ail-muted">{{ \Illuminate\Support\Str::limit((string) $fb->message?->content, 90) }}</td>
                <td>{{ $fb->product_id ? ($products->get($fb->product_id)?->code ?? '#'.$fb->product_id) : '—' }}</td>
                <td>{{ $fb->expected_code ?? '—' }}</td>
                <td class="ail-muted">{{ $fb->reason ?? '—' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </section>
</main>
@endsection
