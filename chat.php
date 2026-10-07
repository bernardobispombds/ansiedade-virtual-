<?php require __DIR__ . '/api/sessao.php'; exigir_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chat — Ansiedade Virtual</title>
<link rel="stylesheet" href="style.css">
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
      <a href="chat.html">Chat</a>
      <a href="contato.html">Contato</a>
    </div>
    <div class="nav-actions">
      <a href="cadastro.html" class="btn btn-ghost">Entrar</a>
      <a href="cadastro.html" class="btn btn-primary">Cadastre-se</a>
    </div>
  </nav>
</header>

<main>
  <section style="padding:64px 0 96px;">
    <div class="container">
      <div class="section-head center" style="margin-bottom:36px;">
        <span class="eyebrow" style="justify-content:center;">Converse com a gente</span>
        <h1 style="font-family:var(--font-display); font-size:clamp(28px,3.6vw,38px);">Um espaço para colocar em palavras o que você sente</h1>
        <p>Converse com o assistente do Ansiedade Virtual, um espaço de escuta e acolhimento disponível quando você precisar.</p>
      </div>

      <div class="chat-shell">
        <div class="chat-top">
          <div class="chat-who">
            <div class="chat-avatar"></div>
            <div>
              <div style="font-weight:600; font-size:14.5px;">Assistente Ansiedade Virtual</div>
              <div class="chat-status">Online</div>
            </div>
          </div>
          <span class="chat-badge">Assistente com IA</span>
        </div>

        <div class="chat-log" id="chat-log">
          <div class="msg system">Conversa iniciada</div>
          <div class="msg bot">Oi, que bom que você chegou até aqui. Sou o assistente do Ansiedade Virtual e estou aqui para te escutar. Pode escrever à vontade sobre o que está sentindo.</div>
        </div>

        <form id="chat-form" class="chat-input-row">
          <input type="text" id="chat-input" placeholder="Escreva sua mensagem..." autocomplete="off" required>
          <button type="submit" class="btn btn-primary">Enviar</button>
        </form>
        <p class="chat-disclaimer">Este chat usa inteligência artificial (suas mensagens são enviadas a um serviço da OpenAI para gerar as respostas) e não substitui atendimento psicológico. Em caso de crise, ligue para o CVV no 188 ou para o SAMU no 192, ou veja a página de <a href="contato.html" style="color:var(--blue-deep); font-weight:600;">Contato</a>.</p>
      </div>
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
        <a href="cadastro.html">Cadastre-se</a>
        <a href="chat.html">Converse com a gente</a>
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
</body>
</html>
