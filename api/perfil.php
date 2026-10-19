<?php require __DIR__ . '/api/sessao.php'; exigir_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Meu perfil — Ansiedade Virtual</title>
<link rel="stylesheet" href="style.css">
<style>
  /* Página "Meu perfil" — usa as variáveis do style.css */
  .pf-sec { padding-top: 56px; padding-bottom: 72px; }
  .pf-sec h1 { font-size: clamp(30px, 4vw, 44px); line-height: 1.1; max-width: 20ch; }
  .pf-sub { font-size: 17px; max-width: 56ch; }

  .pf-box { background: var(--white); border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 30px 32px; margin-top: 26px; }
  .pf-box h2 { font-size: 20px; margin-bottom: 6px; }
  .pf-box .pf-dica { font-size: 14px; color: var(--text-soft); margin-bottom: 18px; }

  .pf-grade { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
  .pf-campo { display: flex; flex-direction: column; gap: 7px; }
  .pf-campo.larga { grid-column: 1 / -1; }
  .pf-campo label { font-size: 13px; font-weight: 600; }
  .pf-campo small { font-weight: 400; color: var(--text-soft); }
  .pf-campo input { width: 100%; box-sizing: border-box; font-family: var(--font-body); font-size: 15px; padding: 12px 14px; border: 1px solid var(--line); border-radius: var(--radius-md); background: var(--white); color: var(--text); }
  .pf-campo input:focus { outline: 2px solid var(--blue-gray); outline-offset: 1px; }

  .pf-acoes { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; margin-top: 20px; }
  .pf-msg { font-size: 14.5px; color: var(--text-soft); }
  .pf-msg.erro { color: #9b3b34; }
  .pf-msg.ok { color: #2f6b4a; }

  .pf-selo { display: inline-block; margin-left: 10px; padding: 3px 12px; font-size: 12px; font-weight: 600; border-radius: 100px; background: var(--sage-light); vertical-align: middle; }
  .pf-selo[hidden] { display: none; }

  .pf-resumo { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
  .pf-resumo div { background: var(--sage-light); border-radius: var(--radius-md); padding: 20px 22px; }
  .pf-resumo strong { display: block; font-family: var(--font-display); font-weight: 500; font-size: 28px; line-height: 1.15; }
  .pf-resumo span { color: var(--text-soft); font-size: 14px; }
  .pf-link { display: inline-block; margin-top: 18px; font-weight: 600; text-decoration: underline; }

  @media (max-width: 640px) {
    .pf-box { padding: 24px 20px; }
    .pf-grade { grid-template-columns: 1fr; }
    .pf-resumo { grid-template-columns: 1fr; }
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
      <a href="bem-estar.html">Bem-estar</a>
      <a href="contato.html">Contato</a>
    </div>
    <div class="nav-actions">
      <a href="cadastro.html" class="btn btn-ghost">Entrar</a>
      <a href="cadastro.html" class="btn btn-primary">Cadastre-se</a>
    </div>
  </nav>
</header>

<main>
  <section class="container pf-sec">
    <span class="eyebrow">Sua conta</span>
    <h1>Meu perfil <span class="pf-selo" id="pf-admin" hidden>Administrador</span></h1>
    <p class="pf-sub">Veja e atualize seus dados e troque sua senha quando quiser.</p>

    <div class="pf-box">
      <h2>Meus dados</h2>
      <p class="pf-dica">Estes dados são usados só para organizar sua participação no grupo.</p>
      <form id="form-dados" novalidate>
        <div class="pf-grade">
          <div class="pf-campo larga">
            <label for="pf-nome">Nome completo</label>
            <input type="text" id="pf-nome" maxlength="100" autocomplete="name">
          </div>
          <div class="pf-campo">
            <label for="pf-email">E-mail</label>
            <input type="email" id="pf-email" autocomplete="email">
          </div>
          <div class="pf-campo">
            <label for="pf-tel">Telefone <small>(opcional)</small></label>
            <input type="tel" id="pf-tel" maxlength="20" autocomplete="tel">
          </div>
        </div>
        <div class="pf-acoes">
          <button type="submit" class="btn btn-primary" id="btn-dados">Salvar alterações</button>
          <span class="pf-msg" id="msg-dados" role="status" aria-live="polite"></span>
        </div>
      </form>
    </div>

    <div class="pf-box">
      <h2>Alterar senha</h2>
      <p class="pf-dica">Por segurança, informe sua senha atual. A nova senha precisa ter 6 ou mais caracteres.</p>
      <form id="form-senha" novalidate>
        <div class="pf-grade">
          <div class="pf-campo larga">
            <label for="pf-atual">Senha atual</label>
            <input type="password" id="pf-atual" autocomplete="current-password">
          </div>
          <div class="pf-campo">
            <label for="pf-nova">Nova senha</label>
            <input type="password" id="pf-nova" autocomplete="new-password">
          </div>
          <div class="pf-campo">
            <label for="pf-conf">Confirmar nova senha</label>
            <input type="password" id="pf-conf" autocomplete="new-password">
          </div>
        </div>
        <div class="pf-acoes">
          <button type="submit" class="btn btn-primary" id="btn-senha">Alterar senha</button>
          <span class="pf-msg" id="msg-senha" role="status" aria-live="polite"></span>
        </div>
      </form>
    </div>

    <div class="pf-box">
      <h2>Meu humor nos últimos 30 dias</h2>
      <p class="pf-dica">Um resumo dos seus registros. Só você vê estas informações.</p>
      <div class="pf-resumo" aria-label="Resumo do humor">
        <div><strong id="h-total">0</strong><span>registros</span></div>
        <div><strong id="h-media">—</strong><span>média do período</span></div>
        <div><strong id="h-ultimo">—</strong><span id="h-ultimo-data">último registro</span></div>
      </div>
      <a class="pf-link" href="humor.php">Ver gráfico e registrar meu humor</a>
    </div>
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

  const $ = function (id) { return document.getElementById(id); };
  function dizer(el, texto, tipo) { el.textContent = texto; el.className = 'pf-msg' + (tipo ? ' ' + tipo : ''); }
  function dm(iso) { return iso.slice(8, 10) + '/' + iso.slice(5, 7) + '/' + iso.slice(0, 4); }

  async function chamar(corpo) {
    const r = await fetch('api/perfil.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(corpo)
    });
    if (r.status === 401) { location.href = 'cadastro.html'; return null; }
    const d = await r.json().catch(function () { return {}; });
    return { ok: r.ok, dados: d };
  }

  function preencher(u) {
    $('pf-nome').value = u.nome || '';
    $('pf-email').value = u.email || '';
    $('pf-tel').value = u.telefone || '';
  }

  async function carregar() {
    try {
      const r = await fetch('api/perfil.php');
      if (r.status === 401) { location.href = 'cadastro.html'; return; }
      if (!r.ok) throw new Error();
      const d = await r.json();
      preencher(d.usuario);
      $('pf-admin').hidden = !d.is_admin;

      const h = d.humor;
      $('h-total').textContent = h.total;
      $('h-media').textContent = h.media30 === null ? '—' : EMOJI[Math.round(h.media30)] + ' ' + String(h.media30.toFixed(1)).replace('.', ',');
      if (h.ultimo) {
        $('h-ultimo').textContent = EMOJI[h.ultimo.nivel];
        $('h-ultimo-data').textContent = 'último registro, em ' + dm(h.ultimo.data_hora);
      }
    } catch (e) {
      dizer($('msg-dados'), 'Não foi possível carregar seus dados agora.', 'erro');
    }
  }

  $('form-dados').addEventListener('submit', async function (ev) {
    ev.preventDefault();
    const msg = $('msg-dados'), btn = $('btn-dados');
    btn.disabled = true;
    dizer(msg, 'Salvando...');
    try {
      const res = await chamar({
        acao: 'dados',
        nome: $('pf-nome').value,
        email: $('pf-email').value,
        telefone: $('pf-tel').value
      });
      if (!res) return;
      if (!res.ok) { dizer(msg, res.dados.erro || 'Não foi possível salvar agora.', 'erro'); return; }
      preencher(res.dados.usuario);
      dizer(msg, 'Dados atualizados. 💚', 'ok');
    } catch (e) {
      dizer(msg, 'Sem conexão com o servidor. Tente novamente.', 'erro');
    } finally {
      btn.disabled = false;
    }
  });

  $('form-senha').addEventListener('submit', async function (ev) {
    ev.preventDefault();
    const msg = $('msg-senha'), btn = $('btn-senha');
    const atual = $('pf-atual').value, nova = $('pf-nova').value, conf = $('pf-conf').value;

    if (!atual) { dizer(msg, 'Informe sua senha atual.', 'erro'); return; }
    if (nova.length < 6) { dizer(msg, 'A nova senha precisa ter 6 ou mais caracteres.', 'erro'); return; }
    if (nova !== conf) { dizer(msg, 'A confirmação não é igual à nova senha.', 'erro'); return; }

    btn.disabled = true;
    dizer(msg, 'Alterando...');
    try {
      const res = await chamar({ acao: 'senha', senha_atual: atual, nova_senha: nova });
      if (!res) return;
      if (!res.ok) { dizer(msg, res.dados.erro || 'Não foi possível alterar a senha agora.', 'erro'); return; }
      $('form-senha').reset();
      dizer(msg, 'Senha alterada com sucesso. 💚', 'ok');
    } catch (e) {
      dizer(msg, 'Sem conexão com o servidor. Tente novamente.', 'erro');
    } finally {
      btn.disabled = false;
    }
  });

  carregar();
})();
</script>
</body>
</html>
