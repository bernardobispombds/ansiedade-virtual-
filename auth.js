// auth.js — controle de acesso do site (apenas HTML + JS)

function usuarioAtual() {
  return localStorage.getItem('usuarioLogado');
}

// Usar nas páginas protegidas (index, sobre, perguntas, chat)
function exigirLogin() {
  if (!usuarioAtual()) {
    window.location.replace('cadastro.html');
  }
}

// Usar só no cadastro.html: se já está logado, vai para o início
function redirecionarSeLogado() {
  if (usuarioAtual()) {
    window.location.replace('index.html');
  }
}

function sair() {
  localStorage.removeItem('usuarioLogado');
  window.location.replace('cadastro.html');
}

// Troca "Entrar / Cadastre-se" por "Sair" quando a pessoa está logada
document.addEventListener('DOMContentLoaded', function () {
  const acoes = document.querySelector('.nav-actions');
  if (acoes && usuarioAtual()) {
    acoes.innerHTML = '<button class="btn btn-ghost" onclick="sair()">Sair</button>';
  }
});
