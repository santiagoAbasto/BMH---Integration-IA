@extends('layouts.plantilla-back')

@section('styles')
<style>
  .ait { --ait-blue:#254f70; --ait-cyan:#0098da; --ait-green:#7aa51a; color:#17212b; }
  .ait-header { display:flex; justify-content:space-between; gap:24px; align-items:flex-start; margin-bottom:22px; }
  .ait-eyebrow { margin:0 0 5px; color:var(--ait-cyan); font-size:12px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; }
  .ait h1 { margin:0!important; border:0!important; padding:0!important; text-transform:none!important; }
  .ait-intro { max-width:760px; margin:8px 0 0; color:#607080; line-height:1.55; }
  .ait-badge { display:inline-flex; align-items:center; gap:7px; border:1px solid #dce5eb; border-radius:999px; padding:7px 11px; background:#fff; color:#51616f; white-space:nowrap; }
  .ait-dot { width:8px; height:8px; border-radius:50%; background:#87b524; }
  .ait-metrics { display:grid; grid-template-columns:repeat(5,minmax(110px,1fr)); gap:10px; margin-bottom:18px; }
  .ait-metric { min-width:0; border:1px solid #dce5eb; border-radius:12px; padding:12px 14px; background:#fff; }
  .ait-metric strong { display:block; color:#203444; font-size:21px; line-height:1.1; }
  .ait-metric span { display:block; margin-top:5px; color:#6b7b87; font-size:11px; line-height:1.35; }
  .ait-metric-note { grid-column:1/-1; margin:0; color:#687985; font-size:11px; }
  .ait-setup { display:grid; grid-template-columns:minmax(190px,1.5fr) minmax(150px,.7fr) auto; gap:12px; align-items:end; padding:16px; margin-bottom:18px; border:1px solid #dce5eb; border-radius:14px; background:#f8fafb; }
  .ait-field label { display:block; margin-bottom:5px; font-size:12px; font-weight:700; color:#536270; }
  .ait-field select,.ait-field input,.ait-composer textarea,.ait-review textarea,.ait-review select { width:100%; border:1px solid #cdd9e1; border-radius:9px; background:#fff; padding:9px 11px; font:inherit; }
  .ait-btn { display:inline-flex; min-height:40px; align-items:center; justify-content:center; gap:7px; border:1px solid #cdd9e1; border-radius:9px; padding:8px 13px; background:#fff; color:#263a49; font-weight:600; cursor:pointer; }
  .ait-btn:hover { border-color:#8aa3b5; }
  .ait-btn-primary { border-color:var(--ait-blue); background:var(--ait-blue); color:#fff; }
  .ait-btn-primary:hover { background:#173b58; color:#fff; }
  .ait-btn:disabled { cursor:not-allowed; opacity:.55; }
  .ait-grid { display:grid; grid-template-columns:minmax(220px,.72fr) minmax(380px,1.35fr) minmax(250px,.9fr); gap:16px; align-items:start; }
  .ait-card { min-width:0; border:1px solid #dce5eb; border-radius:14px; background:#fff; box-shadow:0 5px 18px rgba(32,57,75,.06); overflow:hidden; }
  .ait-card-head { padding:14px 16px; border-bottom:1px solid #e6edf1; background:#fbfcfd; }
  .ait-card-head h2 { margin:0!important; font-size:16px!important; }
  .ait-card-head p { margin:4px 0 0; color:#71808d; font-size:12px; line-height:1.45; }
  .ait-bank { max-height:660px; overflow:auto; padding:10px; }
  .ait-question { display:grid; grid-template-columns:26px 1fr; gap:9px; padding:10px 8px; border-bottom:1px solid #edf1f4; }
  .ait-question.is-reference { background:#fbfcfd; }
  .ait-priority { display:flex; width:24px; height:24px; align-items:center; justify-content:center; border-radius:7px; background:#e9f5fb; color:#007cad; font-size:11px; font-weight:700; }
  .ait-question p { margin:1px 0 0; font-size:13px; line-height:1.45; }
  .ait-question-state { display:inline-block; margin-top:5px; border-radius:999px; padding:3px 7px; background:#eaf5dc; color:#567416; font-size:10px; font-weight:700; }
  .ait-question-state.is-reference { background:#eef1f3; color:#6b7780; }
  .ait-chat { display:flex; min-height:660px; max-height:660px; flex-direction:column; }
  .ait-messages { flex:1; overflow:auto; padding:16px; background:linear-gradient(180deg,#fff,#f7fafb); }
  .ait-empty { display:grid; height:100%; place-content:center; padding:28px; text-align:center; color:#71808d; }
  .ait-empty i { margin-bottom:12px; color:#aebdc7; font-size:35px; }
  .ait-message { max-width:88%; margin-bottom:13px; padding:10px 12px; border-radius:12px; white-space:pre-wrap; line-height:1.5; }
  .ait-message-user { margin-left:auto; border-bottom-right-radius:3px; background:var(--ait-blue); color:#fff; }
  .ait-message-ai { border:1px solid #dae5eb; border-bottom-left-radius:3px; background:#fff; color:#263744; }
  .ait-message-meta { margin-top:7px; color:#7c8a95; font-size:11px; }
  .ait-candidates { display:grid; gap:7px; margin-top:9px; }
  .ait-candidate { padding:8px 10px; border:1px solid #dbe5ea; border-radius:9px; background:#f8fafb; font-size:12px; }
  .ait-next { display:inline-flex; margin-top:8px; border-radius:999px; padding:5px 8px; background:#edf7de; color:#526f12; font-size:11px; font-weight:700; }
  .ait-composer { padding:12px; border-top:1px solid #dce5eb; background:#fff; }
  .ait-composer textarea { min-height:74px; resize:vertical; }
  .ait-composer-row { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-top:8px; }
  .ait-file-label { max-width:65%; overflow:hidden; color:#647581; font-size:12px; text-overflow:ellipsis; white-space:nowrap; }
  .ait-review { padding:14px; }
  .ait-review fieldset { margin:0 0 15px; padding:0; border:0; }
  .ait-review legend,.ait-review label.ait-label { display:block; margin-bottom:7px; font-size:12px; font-weight:700; color:#536270; }
  .ait-ratings { display:grid; grid-template-columns:repeat(3,1fr); gap:6px; }
  .ait-rating input { position:absolute; opacity:0; }
  .ait-rating span { display:block; border:1px solid #d4dfe5; border-radius:8px; padding:8px 4px; text-align:center; font-size:11px; cursor:pointer; }
  .ait-rating input:checked + span { border-color:var(--ait-blue); background:#eaf2f7; color:#173f5e; font-weight:700; }
  .ait-tags { display:flex; flex-wrap:wrap; gap:6px; }
  .ait-tag input { position:absolute; opacity:0; }
  .ait-tag span { display:block; border:1px solid #d5dfe5; border-radius:999px; padding:5px 8px; color:#5f6f7b; font-size:11px; cursor:pointer; }
  .ait-tag input:checked + span { border-color:#e39b38; background:#fff4df; color:#8a570d; }
  .ait-review textarea { min-height:90px; resize:vertical; }
  .ait-review-fields-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:15px; }
  .ait-help { margin-top:8px; padding:10px; border-radius:9px; background:#eef5f8; color:#566b79; font-size:11px; line-height:1.45; }
  .ait-alert { margin-bottom:12px; border-radius:9px; padding:9px 11px; background:#fdeaea; color:#9d2828; font-size:12px; }
  .ait-success { background:#eaf6e8; color:#406f36; }
  .ait-recent { margin-top:18px; }
  .ait-recent-buttons { display:flex; flex-wrap:wrap; gap:7px; }
  .ait-recent-btn { min-height:34px; padding:6px 9px; font-size:11px; }
  @media (max-width:1250px) { .ait-grid { grid-template-columns:minmax(220px,.8fr) minmax(400px,1.4fr); } .ait-review-card { grid-column:1/-1; } }
  @media (max-width:850px) { .ait-header { flex-direction:column; } .ait-setup,.ait-grid { grid-template-columns:1fr; } .ait-metrics { grid-template-columns:repeat(2,1fr); } .ait-chat { min-height:590px; } }
  @media (max-width:520px) { .ait-review-fields-grid,.ait-metrics { grid-template-columns:1fr; } }
</style>
@endsection

@section('content')
<main class="ait" id="ai-test-lab"
      data-bank="{{ json_encode($questionBank, JSON_UNESCAPED_UNICODE) }}"
      data-routes="{{ json_encode([
          "store" => route("admin.ai-tests.store"),
          "show" => route("admin.ai-tests.show", ["conversation" => "__ID__"]),
          "message" => route("admin.ai-tests.messages.store", ["conversation" => "__ID__"]),
          "attachments" => route("admin.ai-tests.attachments.store", ["conversation" => "__ID__"]),
          "review" => route("admin.ai-tests.reviews.store", ["conversation" => "__ID__"]),
      ], JSON_UNESCAPED_UNICODE) }}"
      data-csrf="{{ csrf_token() }}">
  <header class="ait-header">
    <div>
      <p class="ait-eyebrow">Laboratorio interno</p>
      <h1>Pruebas del asesor IA</h1>
      <p class="ait-intro">Probá casos reales, compará la respuesta con el banco de preguntas técnicas y dejá una evaluación. Cada prueba queda separada de las conversaciones de clientes.</p>
    </div>
    <span class="ait-badge"><span class="ait-dot"></span>{{ $provider['provider'] ?? 'mock' }} · {{ $provider['mode'] ?? 'MOCK' }} · banco {{ $questionBankVersion }}</span>
  </header>

  <section aria-label="Resumen de evaluaciones">
    <p class="ait-eyebrow">Resumen de evaluaciones</p>
    <div class="ait-metrics">
      <div class="ait-metric"><strong>{{ $reviewMetrics['total'] }}</strong><span>Total evaluadas</span></div>
      <div class="ait-metric"><strong>{{ $reviewMetrics['correct'] }}</strong><span>Correctas</span></div>
      <div class="ait-metric"><strong>{{ $reviewMetrics['needs_adjustment'] }}</strong><span>Mejorables</span></div>
      <div class="ait-metric"><strong>{{ $reviewMetrics['incorrect'] }}</strong><span>Incorrectas</span></div>
      <div class="ait-metric">
        @if ($reviewMetrics['observed_rate'] !== null)
          <strong>{{ number_format($reviewMetrics['observed_rate'], 1, ',', '.') }} %</strong>
          <span>Tasa observada en estas evaluaciones</span>
        @else
          <strong>—</strong>
          <span>Sin evaluaciones todavía</span>
        @endif
      </div>
      <p class="ait-metric-note">La tasa observada es correctas sobre el total evaluado por este administrador. Este porcentaje describe únicamente esa muestra revisada.</p>
    </div>
  </section>

  <section class="ait-setup" aria-label="Configurar prueba">
    <div class="ait-field">
      <label for="ait-family">Familia a probar</label>
      <select id="ait-family"></select>
    </div>
    <div class="ait-field">
      <label for="ait-customer">Cliente de contexto</label>
      <input id="ait-customer" type="number" min="1" value="{{ $defaultCustomerId }}" placeholder="ID de cliente QA">
    </div>
    <button class="ait-btn ait-btn-primary" id="ait-start" type="button"><i class="fa-solid fa-play"></i> Nueva prueba</button>
  </section>

  <div id="ait-alert" class="ait-alert" role="alert" hidden></div>

  <div class="ait-grid">
    <section class="ait-card">
      <header class="ait-card-head">
        <h2>Preguntas esperadas</h2>
        <p>“Automática” puede salir del catálogo actual. “Referencia” sirve para proponer datos o soporte que todavía faltan.</p>
      </header>
      <div class="ait-bank" id="ait-bank"></div>
    </section>

    <section class="ait-card ait-chat">
      <header class="ait-card-head">
        <h2 id="ait-chat-title">Conversación de prueba</h2>
        <p id="ait-chat-subtitle">Iniciá una prueba para usar el mismo flujo de búsqueda, visión y precios del asesor.</p>
      </header>
      <div class="ait-messages" id="ait-messages" aria-live="polite">
        <div class="ait-empty"><i class="fa-regular fa-comments"></i><p>Elegí una familia y empezá una prueba.</p></div>
      </div>
      <form class="ait-composer" id="ait-composer">
        <textarea id="ait-message" maxlength="2000" placeholder="Ej.: Necesito identificar un rotor; no tengo el código."></textarea>
        <div class="ait-composer-row">
          <div>
            <label class="ait-btn" for="ait-images"><i class="fa-solid fa-camera"></i> Fotos</label>
            <input id="ait-images" type="file" accept="image/jpeg,image/png,image/webp,image/heic,image/heif" multiple hidden>
            <span class="ait-file-label" id="ait-file-label">Sin fotos</span>
          </div>
          <button class="ait-btn ait-btn-primary" id="ait-send" type="submit" disabled>Enviar</button>
        </div>
      </form>
    </section>

    <section class="ait-card ait-review-card">
      <header class="ait-card-head">
        <h2>Evaluar respuesta</h2>
        <p>La evaluación queda ligada al turno, proveedor, modelo y versión del prompt.</p>
      </header>
      <form class="ait-review" id="ait-review">
        <div id="ait-review-empty" class="ait-help">Enviá un mensaje para habilitar la evaluación del último turno.</div>
        <div id="ait-review-fields" hidden>
          <fieldset>
            <legend>Resultado</legend>
            <div class="ait-ratings">
              <label class="ait-rating"><input type="radio" name="rating" value="correct"><span>Correcta</span></label>
              <label class="ait-rating"><input type="radio" name="rating" value="needs_adjustment"><span>Mejorable</span></label>
              <label class="ait-rating"><input type="radio" name="rating" value="incorrect"><span>Incorrecta</span></label>
            </div>
          </fieldset>
          <div class="ait-review-fields-grid">
            <div class="ait-field">
              <label for="ait-expected-action">Acción que debía realizar</label>
              <select id="ait-expected-action" required>
                <option value="">Elegí una acción</option>
                <option value="ask_question">Pedir un dato</option>
                <option value="present_product">Presentar producto</option>
                <option value="abstain">No asegurar / abstenerse</option>
                <option value="handoff">Derivar a una persona</option>
                <option value="quote">Cotizar</option>
              </select>
            </div>
            <div class="ait-field">
              <label for="ait-surface">Dónde aplica el caso</label>
              <select id="ait-surface" required>
                <option value="private" selected>Zona privada</option>
                <option value="public">Zona pública</option>
                <option value="both">Ambas zonas</option>
              </select>
            </div>
          </div>
          <div class="ait-field" style="margin-bottom:15px">
            <label for="ait-expected">¿Qué pregunta esperabas?</label>
            <select id="ait-expected"><option value="">No corresponde / ninguna</option></select>
          </div>
          <div class="ait-review-fields-grid">
            <div class="ait-field">
              <label for="ait-expected-product">Producto esperado (ID)</label>
              <input id="ait-expected-product" type="number" min="1" list="ait-candidate-options" placeholder="Opcional">
              <datalist id="ait-candidate-options"></datalist>
            </div>
            <div class="ait-field">
              <label for="ait-expected-code">Código esperado</label>
              <input id="ait-expected-code" type="text" maxlength="64" placeholder="Opcional">
            </div>
          </div>
          <div class="ait-field" style="margin-bottom:15px">
            <label for="ait-severity">Impacto del problema</label>
            <select id="ait-severity" required>
              <option value="low">Bajo</option>
              <option value="medium" selected>Medio</option>
              <option value="high">Alto</option>
              <option value="critical">Crítico</option>
            </select>
          </div>
          <fieldset>
            <legend>Qué habría que ajustar</legend>
            <div class="ait-tags">
              @foreach ([
                'wrong_question' => 'Pregunta', 'wrong_result' => 'Resultado', 'ignored_data' => 'Dato ignorado',
                'tone' => 'Tono', 'price' => 'Precio', 'image' => 'Imagen', 'handoff' => 'Derivación', 'other' => 'Otro'
              ] as $value => $label)
                <label class="ait-tag"><input type="checkbox" name="issue_tags[]" value="{{ $value }}"><span>{{ $label }}</span></label>
              @endforeach
            </div>
          </fieldset>
          <label class="ait-label" for="ait-comment">Observación</label>
          <textarea id="ait-comment" maxlength="2000" placeholder="Qué respondió, qué dato ignoró o cómo debería haber preguntado."></textarea>
          <button class="ait-btn ait-btn-primary" style="width:100%;margin-top:10px" type="submit">Guardar evaluación</button>
          <div id="ait-review-status" class="ait-help" hidden></div>
        </div>
        <p class="ait-help">Las pruebas no entrenan solas al modelo. Este registro permite comparar versiones y decidir cambios concretos en reglas, catálogo o prompt.</p>
      </form>
    </section>
  </div>

  @if ($recentTests->isNotEmpty())
  <section class="ait-recent">
    <p class="ait-eyebrow">Pruebas recientes</p>
    <div class="ait-recent-buttons">
      @foreach ($recentTests as $test)
        <button class="ait-btn ait-recent-btn" type="button" data-load-test="{{ $test->id }}" data-family="{{ $test->test_family }}">
          #{{ $test->id }} · {{ $test->test_family }} · {{ $test->updated_at?->format('d/m H:i') }}
        </button>
      @endforeach
    </div>
  </section>
  @endif
</main>
@endsection

@section('script')
<script>
(() => {
  const root = document.getElementById('ai-test-lab');
  if (!root) return;

  const bank = JSON.parse(root.dataset.bank || '[]');
  const routes = JSON.parse(root.dataset.routes || '{}');
  const csrf = root.dataset.csrf;
  const familySelect = document.getElementById('ait-family');
  const customerInput = document.getElementById('ait-customer');
  const bankPanel = document.getElementById('ait-bank');
  const messages = document.getElementById('ait-messages');
  const composer = document.getElementById('ait-composer');
  const messageInput = document.getElementById('ait-message');
  const imagesInput = document.getElementById('ait-images');
  const fileLabel = document.getElementById('ait-file-label');
  const sendButton = document.getElementById('ait-send');
  const alertBox = document.getElementById('ait-alert');
  const reviewForm = document.getElementById('ait-review');
  const reviewFields = document.getElementById('ait-review-fields');
  const reviewEmpty = document.getElementById('ait-review-empty');
  const expectedSelect = document.getElementById('ait-expected');
  const expectedActionSelect = document.getElementById('ait-expected-action');
  const expectedProductInput = document.getElementById('ait-expected-product');
  const expectedCodeInput = document.getElementById('ait-expected-code');
  const candidateOptions = document.getElementById('ait-candidate-options');
  const surfaceSelect = document.getElementById('ait-surface');
  const severitySelect = document.getElementById('ait-severity');
  const reviewStatus = document.getElementById('ait-review-status');
  let conversationId = null;
  let lastAssistantMessageId = null;
  let lastCandidates = [];
  let busy = false;

  const url = (template, id) => template.replace('__ID__', String(id));
  const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));

  async function request(endpoint, options = {}) {
    const response = await fetch(endpoint, {
      credentials: 'same-origin',
      ...options,
      headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', ...(options.headers || {})},
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      const validation = payload.errors ? Object.values(payload.errors).flat()[0] : null;
      throw new Error(validation || payload.message || 'No se pudo completar la operación.');
    }
    return payload;
  }

  function selectedFamily() {
    return bank.find(item => item.key === familySelect.value) || bank[0];
  }

  function renderBank() {
    const family = selectedFamily();
    bankPanel.innerHTML = '';
    expectedSelect.innerHTML = '<option value="">No corresponde / ninguna</option>';
    (family?.questions || []).forEach(question => {
      const automatic = question.enabled === true && question.catalog_filterable === true;
      const item = document.createElement('article');
      item.className = `ait-question${automatic ? '' : ' is-reference'}`;
      item.innerHTML = `<span class="ait-priority">${escapeHtml(question.priority)}</span><div><p>${escapeHtml(question.text)}</p><span class="ait-question-state${automatic ? '' : ' is-reference'}">${automatic ? 'Automática' : 'Referencia para ajuste'}</span></div>`;
      bankPanel.appendChild(item);
      const option = document.createElement('option');
      option.value = question.id;
      option.textContent = `${automatic ? '' : '[Referencia] '}${question.priority}. ${question.text}`;
      expectedSelect.appendChild(option);
    });
  }

  function showAlert(message, success = false) {
    alertBox.textContent = message;
    alertBox.classList.toggle('ait-success', success);
    alertBox.hidden = false;
    if (success) setTimeout(() => { alertBox.hidden = true; }, 3500);
  }

  function clearAlert() { alertBox.hidden = true; alertBox.classList.remove('ait-success'); }

  function addMessage(role, content, result = null) {
    const empty = messages.querySelector('.ait-empty');
    if (empty) empty.remove();
    const block = document.createElement('article');
    block.className = `ait-message ait-message-${role === 'user' ? 'user' : 'ai'}`;
    let extra = '';
    if (result?.next_question) extra += `<span class="ait-next">Siguiente dato: ${escapeHtml(result.next_question.label || result.next_question.key)}</span>`;
    if (result?.candidates?.length) {
      extra += '<div class="ait-candidates">' + result.candidates.map(candidate =>
        `<div class="ait-candidate"><strong>${escapeHtml(candidate.product?.code || candidate.product_code || 'Sin código')}</strong>${candidate.product?.name ? ` · ${escapeHtml(candidate.product.name)}` : ''}<br><span>${escapeHtml(candidate.confidence_label || candidate.confidence_band || '')}</span></div>`
      ).join('') + '</div>';
    }
    block.innerHTML = `<div>${escapeHtml(content)}</div>${extra}`;
    messages.appendChild(block);
    messages.scrollTop = messages.scrollHeight;
  }

  function setBusy(value) {
    busy = value;
    sendButton.disabled = value || conversationId === null;
    document.getElementById('ait-start').disabled = value;
    sendButton.textContent = value ? 'Procesando…' : 'Enviar';
  }

  function normalizedCandidate(candidate) {
    return {
      id: candidate?.product?.id ?? candidate?.product_id ?? null,
      code: candidate?.product?.code ?? candidate?.product_code ?? null,
      name: candidate?.product?.name ?? '',
    };
  }

  function renderCandidateOptions(candidates) {
    lastCandidates = (candidates || []).map(normalizedCandidate).filter(item => item.id !== null);
    candidateOptions.innerHTML = '';
    lastCandidates.forEach(candidate => {
      const option = document.createElement('option');
      option.value = candidate.id;
      option.label = [candidate.code, candidate.name].filter(Boolean).join(' · ');
      candidateOptions.appendChild(option);
    });
  }

  function enableReview(messageId, review = null, candidates = []) {
    lastAssistantMessageId = messageId;
    reviewEmpty.hidden = true;
    reviewFields.hidden = false;
    reviewStatus.hidden = true;
    reviewForm.reset();
    renderBank();
    renderCandidateOptions(candidates);
    if (!review) return;

    const rating = reviewForm.querySelector(`input[name="rating"][value="${review.rating}"]`);
    if (rating) rating.checked = true;
    expectedActionSelect.value = review.expected_action || '';
    expectedSelect.value = review.expected_question_id || '';
    expectedProductInput.value = review.expected_product_id || '';
    expectedCodeInput.value = review.expected_code || '';
    surfaceSelect.value = review.surface || 'private';
    severitySelect.value = review.severity || 'medium';
    (review.issue_tags || []).forEach(tag => {
      const input = reviewForm.querySelector(`input[name="issue_tags[]"][value="${tag}"]`);
      if (input) input.checked = true;
    });
    document.getElementById('ait-comment').value = review.comment || '';
    reviewStatus.textContent = 'Evaluación guardada. Podés actualizarla y volver a guardar.';
    reviewStatus.classList.add('ait-success');
    reviewStatus.hidden = false;
  }

  async function startTest() {
    clearAlert();
    const family = selectedFamily();
    if (!family) return;
    setBusy(true);
    try {
      const payload = await request(routes.store, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({family: family.key, customer_id: customerInput.value || null}),
      });
      conversationId = payload.conversation.id;
      lastAssistantMessageId = null;
      messages.innerHTML = '<div class="ait-empty"><i class="fa-solid fa-flask"></i><p>' + escapeHtml(payload.greeting) + '</p></div>';
      document.getElementById('ait-chat-title').textContent = `Prueba #${conversationId} · ${family.label}`;
      document.getElementById('ait-chat-subtitle').textContent = `Contexto: cliente ${payload.customer.id} · ${payload.customer.segment}`;
      reviewFields.hidden = true;
      reviewEmpty.hidden = false;
      messageInput.focus();
    } catch (error) { showAlert(error.message); }
    finally { setBusy(false); }
  }

  async function uploadFiles() {
    if (!imagesInput.files.length) return [];
    const data = new FormData();
    [...imagesInput.files].slice(0, 3).forEach(file => data.append('images[]', file));
    const payload = await request(url(routes.attachments, conversationId), {method: 'POST', body: data});
    return (payload.attachments || []).map(item => item.id);
  }

  composer.addEventListener('submit', async event => {
    event.preventDefault();
    if (busy || conversationId === null) return;
    const text = messageInput.value.trim();
    if (!text && !imagesInput.files.length) { showAlert('Escribí un mensaje o agregá una foto.'); return; }
    clearAlert();
    addMessage('user', text || '[Foto adjunta]');
    messageInput.value = '';
    setBusy(true);
    try {
      const attachmentIds = await uploadFiles();
      const result = await request(url(routes.message, conversationId), {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({message: text, attachment_ids: attachmentIds}),
      });
      addMessage('assistant', result.message.content, result);
      enableReview(result.message.id, null, result.candidates || []);
      imagesInput.value = '';
      fileLabel.textContent = 'Sin fotos';
    } catch (error) { showAlert(error.message); }
    finally { setBusy(false); }
  });

  reviewForm.addEventListener('submit', async event => {
    event.preventDefault();
    if (conversationId === null || lastAssistantMessageId === null) return;
    const rating = reviewForm.querySelector('input[name="rating"]:checked')?.value;
    if (!rating) { showAlert('Elegí si la respuesta fue correcta, mejorable o incorrecta.'); return; }
    const tags = [...reviewForm.querySelectorAll('input[name="issue_tags[]"]:checked')].map(input => input.value);
    try {
      await request(url(routes.review, conversationId), {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          message_id: lastAssistantMessageId,
          rating,
          expected_action: expectedActionSelect.value,
          expected_question_id: expectedSelect.value || null,
          expected_product_id: expectedProductInput.value ? Number(expectedProductInput.value) : null,
          expected_code: expectedCodeInput.value.trim() || null,
          surface: surfaceSelect.value,
          severity: severitySelect.value,
          issue_tags: tags,
          comment: document.getElementById('ait-comment').value.trim() || null,
        }),
      });
      reviewStatus.textContent = 'Evaluación guardada. Podés corregirla y volver a guardar si hace falta.';
      reviewStatus.classList.add('ait-success');
      reviewStatus.hidden = false;
    } catch (error) { showAlert(error.message); }
  });

  async function loadTest(id, familyKey) {
    clearAlert();
    familySelect.value = familyKey;
    renderBank();
    try {
      const payload = await request(url(routes.show, id));
      conversationId = payload.conversation.id;
      messages.innerHTML = '';
      payload.messages.forEach(item => addMessage(item.role, item.content, {candidates: item.candidates, next_question: item.next_question}));
      const last = [...payload.messages].reverse().find(item => item.role === 'assistant');
      if (last) enableReview(last.id, last.review, last.candidates || []);
      document.getElementById('ait-chat-title').textContent = payload.conversation.title || `Prueba #${id}`;
      document.getElementById('ait-chat-subtitle').textContent = `Contexto: cliente ${payload.conversation.customer_id}`;
      setBusy(false);
    } catch (error) { showAlert(error.message); }
  }

  bank.forEach(family => {
    const option = document.createElement('option');
    option.value = family.key;
    const automatic = family.questions.filter(question => question.enabled === true && question.catalog_filterable === true).length;
    option.textContent = `${family.label} (${automatic} automáticas / ${family.questions.length})`;
    familySelect.appendChild(option);
  });
  familySelect.addEventListener('change', renderBank);
  imagesInput.addEventListener('change', () => {
    fileLabel.textContent = imagesInput.files.length ? `${imagesInput.files.length} foto(s)` : 'Sin fotos';
  });
  expectedProductInput.addEventListener('change', () => {
    const candidate = lastCandidates.find(item => String(item.id) === expectedProductInput.value);
    if (candidate?.code) expectedCodeInput.value = candidate.code;
  });
  document.getElementById('ait-start').addEventListener('click', startTest);
  document.querySelectorAll('[data-load-test]').forEach(button => {
    button.addEventListener('click', () => loadTest(button.dataset.loadTest, button.dataset.family));
  });
  renderBank();
})();
</script>
@endsection
