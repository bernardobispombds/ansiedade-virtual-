<?php require __DIR__ . '/api/sessao.php'; exigir_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Meu humor — Ansiedade Virtual</title>
<link rel="stylesheet" href="style.css">
<style>
  /* Página "Meu humor" — usa as variáveis do style.css */
  .hm-sec { padding-top: 56px; padding-bottom: 72px; } /* só vertical: mantém o padding lateral do .container */
  .hm-sec h1 { font-size: clamp(30px, 4vw, 44px); line-height: 1.1; max-width: 20ch; }
  .hm-sub { font-size: 17px; max-width: 56ch; }

  .hm-box { background: var(--white); border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 30px 32px; margin-top: 26px; }
  .hm-box h2 { font-size: 20px; margin-bottom: 16px; }

  /* escolha do humor */
  .hm-niveis { border: 0; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; }
  .hm-niveis legend { font-weight: 600; margin-bottom: 14px; padding: 0; }
  .hm-niveis input { position: absolute; opacity: 0; pointer-events: none; }
  .hm-niveis label { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 16px 6px; border: 2px solid var(--line); border-radius: var(--radius-md); cursor: pointer; font-size: 14px; text-align: center; background: var(--white); transition: transform .18s ease, border-color .18s ease, background .18s ease; }
  .hm-niveis label span { font-size: 36px; line-height: 1; }
  .hm-niveis label:hover { transform: translateY(-2px); border-color: var(--sage); }
  .hm-niveis input:checked + label { border-color: var(--blue-gray); background: var(--sage-light); font-weight: 600; }
  .hm-niveis input:focus-visible + label { outline: 2px solid var(--blue-gray); outline-offset: 3px; }

  .hm-nota { margin-top: 22px; }
  .hm-nota label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 7px; }
  .hm-nota small { font-weight: 400; color: var(--text-soft); }
  .hm-acoes { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; margin-top: 18px; }
  #hm-msg { font-size: 14.5px; color: var(--text-soft); }
  #hm-msg.erro { color: #9b3b34; }
  #hm-msg.ok { color: #2f6b4a; }

  #hm-apoio { margin-top: 18px; padding: 16px 18px; border-radius: var(--radius-md); background: #f6ead2; color: #5c3d0d; font-size: 14.5px; }
  #hm-apoio[hidden] { display: none; }
  #hm-apoio a { font-weight: 600; text-decoration: underline; }

  /* resumo */
  .hm-resumo { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 26px; }
  .hm-resumo div { background: var(--sage-light); border-radius: var(--radius-md); padding: 20px 22px; }
  .hm-resumo strong { display: block; font-family: var(--font-display); font-weight: 500; font-size: 32px; line-height: 1.15; }
  .hm-resumo span { color: var(--text-soft); font-size: 14px; }

  /* gráfico */
  .hm-grafico { overflow-x: auto; }
  .hm-grafico svg { width: 100%; min-width: 520px; height: auto; display: block; font-family: var(--font-body); }
  .hm-vazio { color: var(--text-soft); font-size: 15px; }

  /* histórico */
  #hm-lista li { display: flex; gap: 14px; align-items: flex-start; padding: 14px 0; border-top: 1px solid var(--line); }
  #hm-lista li:first-child { border-top: 0; padding-top: 0; }
  #hm-lista .emo { font-size: 28px; line-height: 1.1; }
  #hm-lista .info { flex: 1; min-width: 0; }
  #hm-lista .info small { display: block; color: var(--text-soft); font-size: 13px; }
  #hm-lista .nota { margin-top: 4px; font-size: 15px; word-break: break-word; white-space: pre-wrap; }
  .hm-apagar { font-family: var(--font-body); font-size: 13px; font-weight: 600; background: transparent; border: 1px solid var(--line); border-radius: 100px; padding: 6px 14px; cursor: pointer; color: var(--text); transition: border-color .18s ease, color .18s ease; }
  .hm-apagar:hover { border-color: var(--blue-gray); color: var(--blue-deep); }

  .hm-aviso { margin-top: 26px; font-size: 13px; }

  @media (max-width: 640px) {
    .hm-box { padding: 24px 20px; }
    .hm-niveis { gap: 6px; }
    .hm-niveis label { font-size: 11.5px; padding: 12px 2px; gap: 6px; }
    .hm-niveis label span { font-size: 26px; }
    .hm-resumo { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>

<header class="site-header">
  <nav class="nav">
    <a href="index.html" class="brand"><span class="brand-mark"></span>Ansiedade Virtual</a>
    <button class="nav-toggle" aria-label="Abrir menu" aria-expanded="false"><span></span></button>
    <div class="nav-links">
      <a href="index.html">Início</a>
      <a href="sobre.html">Sobre</a>
      <a href="perguntas.html">Perguntas</a>
      <a href="chat.php">Chat</a>
      <a href="bem-estar.html" class="active">Bem-estar</a>
      <a href="contato.html">Contato</a>
    </div>
    <div class="nav-actions">
      <a href="cadastro.html" class="btn btn-ghost">Entrar</a>
      <a href="cadastro.html" class="btn btn-primary">Cadastre-se</a>
    </div>
  </nav>
</header>

<main>
  <section class="container hm-sec">
    <span class="eyebrow">Acompanhe você</span>
    <h1>Como você está se sentindo hoje?</h1>
    <p class="hm-sub">Registrar o humor aos poucos ajuda a perceber padrões: o que piora, o que melhora. Só você vê estes registros.</p>

    <div class="hm-box">
      <fieldset class="hm-niveis">
        <legend>Escolha o que mais combina com agora</legend>
        <input type="radio" name="nivel" id="n1" value="1"><label for="n1"><span>😞</span>Muito mal</label>
        <input type="radio" name="nivel" id="n2" value="2"><label for="n2"><span>😕</span>Mal</label>
        <input type="radio" name="nivel" id="n3" value="3"><label for="n3"><span>😐</span>Mais ou menos</label>
        <input type="radio" name="nivel" id="n4" value="4"><label for="n4"><span>🙂</span>Bem</label>
        <input type="radio" name="nivel" id="n5" value="5"><label for="n5"><span>😄</span>Muito bem</label>
      </fieldset>

      <div class="hm-nota">
        <label for="nota">Quer anotar algo? <small>(opcional, até 300 caracteres)</small></label>
        <textarea id="nota" maxlength="300" placeholder="Ex.: dormi mal, tive uma prova, conversei com um amigo..."></textarea>
      </div>

      <div class="hm-acoes">
        <button type="button" class="btn btn-primary" id="hm-salvar">Registrar meu humor</button>
        <span id="hm-msg" role="status" aria-live="polite"></span>
      </div>

      <div id="hm-apoio" hidden>
        Sinto muito que hoje esteja tão difícil. Você não precisa passar por isso sozinho(a). Se quiser conversar,
        <a href="chat.php">abra o chat</a>. Se estiver em sofrimento intenso, ligue para o <strong>CVV: 188</strong> (24 horas, gratuito)
        ou, em emergência, para o <strong>SAMU: 192</strong>.
      </div>
    </div>

    <div class="hm-resumo" aria-label="Resumo">
      <div><strong id="r-semana">—</strong><span>média dos últimos 7 dias</span></div>
      <div><strong id="r-mes">—</strong><span>média dos últimos 30 dias</span></div>
      <div><strong id="r-total">0</strong><span>registros nos últimos 30 dias</span></div>
    </div>

    <div class="hm-box">
      <h2>Últimos 14 dias</h2>
      <div class="hm-grafico" id="hm-grafico"></div>
    </div>

    <div class="hm-box">
      <h2>Seus registros recentes</h2>
      <ul id="hm-lista"></ul>
    </div>

    <p class="hm-aviso">Este registro é uma ferramenta de autoconhecimento e não substitui atendimento psicológico ou psiquiátrico profissional.</p>
  </section>
</main>

<footer>
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="brand" style="margin-bottom:14px;"><span class="brand-mark"></span>Ansiedade Virtual</div>
        <p style="max-width:32ch;">Um espaço de apoio psicológico em grupo, pensado para acolher sem julgar.</p>
      </div>
      <div>
        <h4>Navegue</h4>
        <a href="index.html">Início</a>
        <a href="sobre.html">Sobre o grupo</a>
        <a href="perguntas.html">Perguntas frequentes</a>
      </div>
      <div>
        <h4>Participe</h4>
        <a href="chat.php">Converse com a gente</a>
        <a href="bem-estar.html">Bem-estar</a>
        <a href="contato.html">Contato</a>
      </div>
      <div>
        <h4>Precisa de ajuda agora?</h4>
        <a href="contato.html">Central de Valorização da Vida — 188</a>
        <a href="contato.html">Emergência — 192</a>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© 2026 Ansiedade Virtual. Site criado com carinho para o grupo.</span>
      <span>Este site não substitui atendimento psicológico ou psiquiátrico profissional.</span>
    </div>
  </div>
</footer>

<script src="auth.js"></script>
<script src="script.js"></script>
<script>
(function () {
  const EMOJI = { 1: '😞', 2: '😕', 3: '😐', 4: '🙂', 5: '😄' };
  const NOME  = { 1: 'Muito mal', 2: 'Mal', 3: 'Mais ou menos', 4: 'Bem', 5: 'Muito bem' };

  const msg = document.getElementById('hm-msg');
  const apoio = document.getElementById('hm-apoio');
  const lista = document.getElementById('hm-lista');
  const grafico = document.getElementById('hm-grafico');

  function dizer(texto, tipo) { msg.textContent = texto; msg.className = tipo || ''; }
  function media(arr) { return arr.length ? arr.reduce(function (a, b) { return a + b; }, 0) / arr.length : null; }
  function fmt(m) { return m === null ? '—' : EMOJI[Math.round(m)] + ' ' + m.toFixed(1).replace('.', ','); }
  function pad(n) { return String(n).padStart(2, '0'); }
  function dataLocal(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
  function dm(iso) { return iso.slice(8, 10) + '/' + iso.slice(5, 7); }

  async function carregar() {
    try {
      const r = await fetch('api/humor.php');
      if (r.status === 401) { location.href = 'cadastro.html'; return; }
      if (!r.ok) throw new Error();
      const d = await r.json();
      desenhar(d.hoje, d.registros);
    } catch (e) {
      grafico.innerHTML = '<p class="hm-vazio">Não foi possível carregar seus registros agora.</p>';
    }
  }

  function desenhar(hoje, regs) {
    // 14 dias terminando em "hoje" (data de Brasília, vinda do servidor)
    const base = new Date(hoje + 'T12:00:00');
    const dias = [];
    for (let i = 13; i >= 0; i--) {
      const d = new Date(base);
      d.setDate(d.getDate() - i);
      dias.push(dataLocal(d));
    }

    // resumo
    const d7 = dias[7]; // hoje - 6
    document.getElementById('r-semana').textContent = fmt(media(regs.filter(function (r) { return r.data_hora.slice(0, 10) >= d7; }).map(function (r) { return +r.nivel; })));
    document.getElementById('r-mes').textContent = fmt(media(regs.map(function (r) { return +r.nivel; })));
    document.getElementById('r-total').textContent = regs.length;

    desenharGrafico(dias, regs);
    desenharLista(regs);
  }

  function svg(tag, attrs, texto) {
    const e = document.createElementNS('http://www.w3.org/2000/svg', tag);
    for (const k in attrs) e.setAttribute(k, attrs[k]);
    if (texto !== undefined) e.textContent = texto;
    return e;
  }

  function desenharGrafico(dias, regs) {
    grafico.innerHTML = '';
    if (!regs.length) {
      grafico.innerHTML = '<p class="hm-vazio">Ainda não há registros. Faça o primeiro acima e o gráfico aparece aqui.</p>';
      return;
    }
    const W = 640, H = 270, L = 52, R = 16, T = 16, B = 38;
    const x = function (i) { return L + i * (W - L - R) / 13; };
    const y = function (v) { return T + (5 - v) * (H - T - B) / 4; };

    const porDia = {};
    regs.forEach(function (r) { (porDia[r.data_hora.slice(0, 10)] = porDia[r.data_hora.slice(0, 10)] || []).push(+r.nivel); });

    const s = svg('svg', { viewBox: '0 0 ' + W + ' ' + H, role: 'img', 'aria-label': 'Gráfico da média diária de humor nos últimos 14 dias' });

    for (let v = 1; v <= 5; v++) {
      s.appendChild(svg('line', { x1: L, x2: W - R, y1: y(v), y2: y(v), stroke: '#dfe6e0', 'stroke-width': 1 }));
      s.appendChild(svg('text', { x: L - 10, y: y(v) + 7, 'text-anchor': 'end', 'font-size': 20 }, EMOJI[v]));
    }
    dias.forEach(function (d, i) {
      if (i % 2 === 1 || i === 13) s.appendChild(svg('text', { x: x(i), y: H - 12, 'text-anchor': 'middle', 'font-size': 12, fill: '#5f6f66' }, dm(d)));
    });

    const pontos = [];
    dias.forEach(function (d, i) {
      if (porDia[d]) pontos.push({ i: i, v: media(porDia[d]), n: porDia[d].length, d: d });
    });

    if (pontos.length > 1) {
      s.appendChild(svg('polyline', {
        points: pontos.map(function (p) { return x(p.i) + ',' + y(p.v); }).join(' '),
        fill: 'none', stroke: '#7D9BA6', 'stroke-width': 3, 'stroke-linejoin': 'round', 'stroke-linecap': 'round'
      }));
    }
    pontos.forEach(function (p) {
      const c = svg('circle', { cx: x(p.i), cy: y(p.v), r: 6, fill: '#34413B', stroke: '#fff', 'stroke-width': 2 });
      c.appendChild(svg('title', {}, dm(p.d) + ': média ' + p.v.toFixed(1).replace('.', ',') + ' (' + p.n + ' registro' + (p.n > 1 ? 's' : '') + ')'));
      s.appendChild(c);
    });
    grafico.appendChild(s);
  }

  function desenharLista(regs) {
    lista.innerHTML = '';
    if (!regs.length) {
      const li = document.createElement('li');
      li.className = 'hm-vazio';
      li.textContent = 'Nenhum registro ainda.';
      lista.appendChild(li);
      return;
    }
    regs.slice().reverse().slice(0, 10).forEach(function (r) {
      const li = document.createElement('li');

      const emo = document.createElement('span');
      emo.className = 'emo'; emo.textContent = EMOJI[r.nivel]; emo.setAttribute('aria-hidden', 'true');

      const info = document.createElement('div');
      info.className = 'info';
      const t = document.createElement('strong'); t.textContent = NOME[r.nivel];
      const dt = document.createElement('small'); dt.textContent = dm(r.data_hora) + ' às ' + r.data_hora.slice(11, 16);
      info.append(t, dt);
      if (r.nota) { const n = document.createElement('div'); n.className = 'nota'; n.textContent = r.nota; info.appendChild(n); }

      const b = document.createElement('button');
      b.type = 'button'; b.className = 'hm-apagar'; b.textContent = 'Apagar';
      b.addEventListener('click', function () { apagar(r.id_humor); });

      li.append(emo, info, b);
      lista.appendChild(li);
    });
  }

  async function apagar(id) {
    if (!confirm('Apagar este registro?')) return;
    try {
      const r = await fetch('api/humor.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ acao: 'apagar', id: id }) });
      if (!r.ok) throw new Error();
      carregar();
    } catch (e) { dizer('Não foi possível apagar agora.', 'erro'); }
  }

  document.getElementById('hm-salvar').addEventListener('click', async function () {
    const sel = document.querySelector('input[name=nivel]:checked');
    if (!sel) { dizer('Escolha como você está se sentindo.', 'erro'); return; }
    const nivel = +sel.value;
    const nota = document.getElementById('nota').value;
    this.disabled = true;
    try {
      const r = await fetch('api/humor.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ nivel: nivel, nota: nota }) });
      const d = await r.json().catch(function () { return {}; });
      if (!r.ok) { dizer(d.erro || 'Não foi possível registrar agora.', 'erro'); return; }
      dizer('Registrado. Obrigado por se cuidar. 💚', 'ok');
      apoio.hidden = nivel !== 1;
      sel.checked = false;
      document.getElementById('nota').value = '';
      carregar();
    } catch (e) {
      dizer('Sem conexão com o servidor. Tente novamente.', 'erro');
    } finally {
      this.disabled = false;
    }
  });

  carregar();
})();
</script>
</body>
</html>
