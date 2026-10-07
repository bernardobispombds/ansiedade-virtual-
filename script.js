// ============ Navegação mobile ============
document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('.nav-toggle');
  const links = document.querySelector('.nav-links');
  if (toggle && links) {
    toggle.addEventListener('click', () => {
      const isOpen = links.classList.toggle('open');
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
    links.querySelectorAll('a').forEach(a => a.addEventListener('click', () => links.classList.remove('open')));
  }

  // marca o link ativo pela página atual
  const current = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav-links a').forEach(a => {
    if (a.getAttribute('href') === current) a.classList.add('active');
  });

  // ============ FAQ (acordeão) ============
  document.querySelectorAll('.faq-item').forEach(item => {
    const btn = item.querySelector('.faq-q');
    const answer = item.querySelector('.faq-a');
    if (!btn || !answer) return;
    btn.addEventListener('click', () => {
      const isOpen = item.classList.contains('open');
      document.querySelectorAll('.faq-item.open').forEach(other => {
        if (other !== item) {
          other.classList.remove('open');
          other.querySelector('.faq-a').style.maxHeight = null;
          other.querySelector('.faq-q').setAttribute('aria-expanded', 'false');
        }
      });
      if (isOpen) {
        item.classList.remove('open');
        answer.style.maxHeight = null;
        btn.setAttribute('aria-expanded', 'false');
      } else {
        item.classList.add('open');
        answer.style.maxHeight = answer.scrollHeight + 'px';
        btn.setAttribute('aria-expanded', 'true');
      }
    });
  });

  // ============ Chat (salvo no banco via PHP) ============
  const chatForm = document.getElementById('chat-form');
  const chatInput = document.getElementById('chat-input');
  const chatLog = document.getElementById('chat-log');

  function addMessage(text, who) {
    if (!chatLog) return;
    const div = document.createElement('div');
    div.className = 'msg ' + who;
    div.textContent = text;
    chatLog.appendChild(div);
    chatLog.scrollTop = chatLog.scrollHeight;
  }

  // carrega as últimas mensagens da pessoa
  async function carregarHistorico() {
    try {
      const resp = await fetch('api/chat.php', { cache: 'no-store' });
      if (!resp.ok) return;
      const dados = await resp.json();
      (dados.mensagens || []).forEach(m => {
        addMessage(m.pergunta, 'user');
        if (m.resposta) addMessage(m.resposta, 'bot');
      });
    } catch (e) {}
  }
  if (chatLog) carregarHistorico();

  if (chatForm) {
    chatForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const text = chatInput.value.trim();
      if (!text) return;
      addMessage(text, 'user');
      chatInput.value = '';
      chatInput.disabled = true;
      const digitando = document.createElement('div');
      digitando.className = 'msg bot';
      digitando.textContent = 'Digitando...';
      chatLog.appendChild(digitando);
      chatLog.scrollTop = chatLog.scrollHeight;
      try {
        const resp = await fetch('api/chat.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ mensagem: text })
        });
        if (resp.status === 401) { window.location.replace('cadastro.html'); return; }
        const dados = await resp.json();
        if (resp.ok) addMessage(dados.resposta, 'bot');
        else addMessage(dados.erro || 'Não foi possível enviar a mensagem.', 'system');
      } catch (err) {
        addMessage('Sem conexão com o servidor. Tente novamente.', 'system');
      } finally {
        digitando.remove();
        chatInput.disabled = false;
        chatInput.focus();
      }
    });
  }
});
