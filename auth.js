// auth.js — controle de acesso do site (agora usando o servidor PHP + banco de dados)
// O bloqueio das páginas é feito no servidor (api/sessao.php). Este arquivo só cuida
// dos botões do menu e das chamadas de login/cadastro/logout.

async function sessaoAtual() {
  try {
    const resp = await fetch('api/me.php', { cache: 'no-store' });
    return await resp.json();
  } catch (e) {
    return { logado: false };
  }
}

// Mantida para não quebrar páginas antigas: quem protege agora é o PHP.
function exigirLogin() {}

// Usar só no cadastro.html: se já está logado, vai para o início
async function redirecionarSeLogado() {
  const s = await sessaoAtual();
  if (s.logado) window.location.replace('index.html');
}

async function sair() {
  try { await fetch('api/logout.php', { method: 'POST' }); } catch (e) {}
  window.location.replace('cadastro.html');
}

async function enviarJSON(url, corpo) {
  const resp = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(corpo)
  });
  let dados = {};
  try { dados = await resp.json(); } catch (e) {}
  return { ok: resp.ok, dados: dados };
}

// Retorna { ok, dados }. Em caso de erro, dados.erro traz a mensagem.
function cadastrar(nome, email, senha, telefone) {
  return enviarJSON('api/cadastro.php', { nome, email, senha, telefone });
}

function entrar(email, senha) {
  return enviarJSON('api/login.php', { email, senha });
}

// Troca "Entrar / Cadastre-se" por "Sair" quando a pessoa está logada
document.addEventListener('DOMContentLoaded', async function () {
  const acoes = document.querySelector('.nav-actions');
  if (!acoes) return;
  const s = await sessaoAtual();
  if (s.logado) {
    acoes.innerHTML = '<button class="btn btn-ghost" onclick="sair()">Sair</button>';
  }
});
