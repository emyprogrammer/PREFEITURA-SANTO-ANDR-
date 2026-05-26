/* ===========================================
   login.js — Lógica da tela de login/cadastro
   Meu Pedaço – Prefeitura de Santo André
   =========================================== */

/**
 * Alterna entre os formulários de Login e Cadastro.
 * @param {string} qual - 'login' ou 'cadastro'
 */
function mostrarAba(qual) {
  // Oculta os dois formulários
  document.getElementById('form-login').classList.add('hidden');
  document.getElementById('form-cadastro').classList.add('hidden');

  // Remove a classe 'active' de todas as abas
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));

  // Mostra o formulário correto e ativa a aba
  if (qual === 'login') {
    document.getElementById('form-login').classList.remove('hidden');
    document.querySelectorAll('.tab')[0].classList.add('active');
  } else {
    document.getElementById('form-cadastro').classList.remove('hidden');
    document.querySelectorAll('.tab')[1].classList.add('active');
  }
}

/**
 * Realiza o login do usuário.
 * Por enquanto apenas valida os campos e redireciona.
 * Futuramente vai se conectar ao PHP.
 */
function fazerLogin() {
  const email = document.getElementById('login-email').value.trim();
  const senha = document.getElementById('login-senha').value;

  // Validação simples
  if (!email || !senha) {
    alert('Por favor, preencha todos os campos.');
    return;
  }

  if (!email.includes('@')) {
    alert('Digite um e-mail válido.');
    return;
  }

  /*
   * TODO: Aqui você vai fazer a chamada ao PHP com fetch():
   *
   * fetch('../backend/login.php', {
   *   method: 'POST',
   *   headers: { 'Content-Type': 'application/json' },
   *   body: JSON.stringify({ email, senha })
   * })
   * .then(res => res.json())
   * .then(data => {
   *   if (data.sucesso) {
   *     localStorage.setItem('usuario', JSON.stringify(data.usuario));
   *     window.location.href = 'pages/home.html';
   *   } else {
   *     alert(data.mensagem);
   *   }
   * });
   */

  // SIMULAÇÃO: salva o nome no localStorage e redireciona
  localStorage.setItem('usuario', JSON.stringify({ nome: 'Usuário', email }));
  window.location.href = 'pages/home.html';
}

/**
 * Realiza o cadastro do usuário.
 */
function fazerCadastro() {
  const nome    = document.getElementById('cad-nome').value.trim();
  const email   = document.getElementById('cad-email').value.trim();
  const senha   = document.getElementById('cad-senha').value;
  const confirma = document.getElementById('cad-confirma').value;

  // Validações
  if (!nome || !email || !senha || !confirma) {
    alert('Preencha todos os campos.');
    return;
  }

  if (!email.includes('@')) {
    alert('Digite um e-mail válido.');
    return;
  }

  if (senha.length < 6) {
    alert('A senha deve ter pelo menos 6 caracteres.');
    return;
  }

  if (senha !== confirma) {
    alert('As senhas não coincidem.');
    return;
  }

  /*
   * TODO: Chamada ao PHP para cadastrar:
   *
   * fetch('../backend/cadastro.php', {
   *   method: 'POST',
   *   headers: { 'Content-Type': 'application/json' },
   *   body: JSON.stringify({ nome, email, senha })
   * })
   * .then(res => res.json())
   * .then(data => {
   *   if (data.sucesso) {
   *     localStorage.setItem('usuario', JSON.stringify(data.usuario));
   *     window.location.href = 'pages/home.html';
   *   } else {
   *     alert(data.mensagem);
   *   }
   * });
   */

  // SIMULAÇÃO
  localStorage.setItem('usuario', JSON.stringify({ nome, email }));
  window.location.href = 'pages/home.html';
}
