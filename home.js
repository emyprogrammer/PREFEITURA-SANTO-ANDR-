/* ===========================================
   home.js — Lógica da tela principal
   Meu Pedaço – Prefeitura de Santo André
   =========================================== */

// ============================================
// INICIALIZAÇÃO DA PÁGINA
// ============================================

/**
 * Quando a página carrega, mostra o nome do usuário
 * salvo no localStorage durante o login/cadastro.
 */
window.addEventListener('load', function () {
  const dadosUsuario = localStorage.getItem('usuario');

  if (dadosUsuario) {
    const usuario = JSON.parse(dadosUsuario);
    document.getElementById('nome-usuario').textContent = 'Olá, ' + usuario.nome + '!';

    // Preenche os campos de configurações com os dados do usuário
    const campoNome  = document.getElementById('config-nome');
    const campoEmail = document.getElementById('config-email');
    if (campoNome)  campoNome.value  = usuario.nome  || '';
    if (campoEmail) campoEmail.value = usuario.email || '';
  } else {
    // Se não estiver logado, manda de volta para o login
    window.location.href = 'index.html';
  }

  // Inicializa o mapa assim que a página carrega
  inicializarMapa();
});


// ============================================
// NAVEGAÇÃO ENTRE ABAS
// ============================================

/**
 * Troca a aba visível (Feed, Mapa, Configurações).
 * @param {string} nomeAba - 'feed', 'mapa' ou 'config'
 * @param {HTMLElement} botao - O botão clicado na navbar
 */
function mudarAba(nomeAba, botao) {
  // Esconde todas as abas
  document.querySelectorAll('.aba').forEach(function (aba) {
    aba.classList.remove('ativa');
  });

  // Remove o estado 'ativo' de todos os botões da navbar
  document.querySelectorAll('.nav-item').forEach(function (item) {
    item.classList.remove('ativo');
  });

  // Mostra a aba selecionada e ativa o botão
  document.getElementById('aba-' + nomeAba).classList.add('ativa');
  botao.classList.add('ativo');
}


// ============================================
// ABA FEED — CURTIDAS E COMENTÁRIOS
// ============================================

/**
 * Curte ou descurte um post.
 * @param {Event} evento - O clique do botão
 * @param {HTMLElement} botao - O botão de curtir
 */
function curtir(evento, botao) {
  // Impede que o clique no botão abra o post
  evento.stopPropagation();

  const contagem = botao.querySelector('span');
  const numero   = parseInt(contagem.textContent);

  // Alterna: se já curtiu, descurte
  if (botao.classList.contains('curtido')) {
    botao.classList.remove('curtido');
    contagem.textContent = numero - 1;
    botao.style.color = '';
  } else {
    botao.classList.add('curtido');
    contagem.textContent = numero + 1;
    botao.style.color = '#e0051a'; // Vermelho ao curtir
  }
}

/**
 * Mostra ou esconde a seção de comentários de um post.
 * @param {Event} evento
 * @param {number} idPost - Número identificador do post
 */
function comentar(evento, idPost) {
  evento.stopPropagation();

  const secao = document.getElementById('comentarios-' + idPost);
  secao.classList.toggle('hidden');
}

/**
 * Adiciona um novo comentário ao post.
 * @param {number} idPost
 */
function adicionarComentario(idPost) {
  const input = document.getElementById('input-com-' + idPost);
  const texto = input.value.trim();

  if (!texto) return; // Não envia comentário vazio

  // Pega os dados do usuário logado
  const dadosUsuario = JSON.parse(localStorage.getItem('usuario') || '{}');
  const nome = dadosUsuario.nome || 'Você';

  // Cria o elemento de comentário
  const novoComentario = document.createElement('div');
  novoComentario.classList.add('comentario');
  novoComentario.innerHTML = '<strong>' + nome + ':</strong> ' + texto;

  // Insere antes do campo de adicionar comentário
  const secaoComentarios = document.getElementById('comentarios-' + idPost);
  const campoAdd = secaoComentarios.querySelector('.add-comentario');
  secaoComentarios.insertBefore(novoComentario, campoAdd);

  // Limpa o campo de texto
  input.value = '';

  // Incrementa a contagem de comentários no botão
  const post = secaoComentarios.closest('.post');
  const btnComentar = post.querySelector('.btn-comentar span');
  btnComentar.textContent = parseInt(btnComentar.textContent) + 1;
}

/**
 * Compartilhar (simulado — mostra apenas um aviso).
 * @param {Event} evento
 */
function compartilhar(evento) {
  evento.stopPropagation();
  alert('Link copiado! Compartilhe com seus amigos 🔗');
}

/**
 * Ao clicar no post, poderia abrir o detalhe completo.
 * Por enquanto, apenas um aviso.
 * @param {number} idPost
 */
function verPost(idPost) {
  // Futuramente: redirecionar para uma página de detalhe
  console.log('Ver detalhe do post ' + idPost);
}


// ============================================
// MODAL DE NOVA PUBLICAÇÃO
// ============================================

/** Abre o modal de nova publicação. */
function abrirModal() {
  document.getElementById('modal-pub').classList.remove('hidden');
}

/** Fecha o modal de nova publicação. */
function fecharModal() {
  document.getElementById('modal-pub').classList.add('hidden');
}

/**
 * Publica um novo post no feed.
 * Na versão com banco de dados, enviaria para o PHP.
 */
function publicar() {
  const texto   = document.getElementById('nova-pub-texto').value.trim();
  const bairro  = document.getElementById('nova-pub-bairro').value.trim() || 'Santo André';

  if (!texto) {
    alert('Escreva algo antes de publicar!');
    return;
  }

  const dadosUsuario = JSON.parse(localStorage.getItem('usuario') || '{}');
  const nome         = dadosUsuario.nome || 'Usuário';
  const iniciais     = nome.split(' ').map(p => p[0]).join('').toUpperCase().slice(0, 2);

  // Cria o HTML do novo post
  const novoPost = document.createElement('article');
  novoPost.classList.add('post');
  novoPost.innerHTML = `
    <div class="post-header">
      <div class="avatar">${iniciais}</div>
      <div>
        <strong>${nome}</strong>
        <span class="post-bairro">📍 ${bairro}</span>
      </div>
      <span class="post-data">agora</span>
    </div>
    <p class="post-texto">${texto}</p>
    <div class="post-acoes">
      <button class="btn-curtir" onclick="curtir(event, this)">❤️ <span>0</span></button>
      <button class="btn-compartilhar" onclick="compartilhar(event)">🔗 Compartilhar</button>
    </div>
  `;

  // Adiciona o post no topo da lista
  const lista = document.getElementById('lista-posts');
  lista.insertBefore(novoPost, lista.firstChild);

  // Fecha e limpa o modal
  fecharModal();
  document.getElementById('nova-pub-texto').value = '';
  document.getElementById('nova-pub-bairro').value = '';
}


// ============================================
// ABA MAPA — Leaflet (OpenStreetMap)
// ============================================

/** Variável global do mapa para evitar inicializar duas vezes */
let mapaInicializado = false;

/**
 * Inicializa o mapa interativo com Leaflet.js + OpenStreetMap.
 * É chamada uma única vez ao carregar a página.
 */
function inicializarMapa() {
  if (mapaInicializado) return;
  mapaInicializado = true;

  // Coordenadas do centro de Santo André - SP
  const lat = -23.6638;
  const lng = -46.5322;

  // Cria o mapa centralizado em Santo André
  const mapa = L.map('mapa').setView([lat, lng], 14);

  // Adiciona o mapa base do OpenStreetMap (gratuito)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
  }).addTo(mapa);

  // ---- Pontos culturais fictícios de exemplo ----
  const pontos = [
    {
      lat:  -23.6638,
      lng:  -46.5322,
      nome: 'Praça IV Centenário',
      desc: 'Coração do centro de Santo André. Ponto de encontro histórico dos moradores.'
    },
    {
      lat:  -23.6700,
      lng:  -46.5280,
      nome: 'Mercado Municipal',
      desc: 'Famoso mercadão com produtos frescos e muita história. Funciona desde 1960.'
    },
    {
      lat:  -23.6590,
      lng:  -46.5400,
      nome: 'Parque Celso Daniel',
      desc: 'Grande área verde e de lazer da cidade. Ideal para caminhadas e piqueniques.'
    },
    {
      lat:  -23.6750,
      lng:  -46.5350,
      nome: 'Vila Luzita',
      desc: 'Bairro tradicional com muita história e cultura local.'
    },
    {
      lat:  -23.6500,
      lng:  -46.5200,
      nome: 'Jardim Cristiane',
      desc: 'Bairro residencial com forte senso de comunidade.'
    }
  ];

  // Adiciona cada ponto como marcador no mapa
  pontos.forEach(function (ponto) {
    L.marker([ponto.lat, ponto.lng])
      .addTo(mapa)
      .bindPopup(
        '<strong>' + ponto.nome + '</strong><br>' + ponto.desc
      );
  });
}


// ============================================
// ABA CONFIGURAÇÕES
// ============================================

/** Salva as alterações de nome e e-mail do usuário. */
function salvarDados() {
  const nome  = document.getElementById('config-nome').value.trim();
  const email = document.getElementById('config-email').value.trim();

  if (!nome || !email) {
    alert('Preencha nome e e-mail.');
    return;
  }

  // Atualiza no localStorage
  localStorage.setItem('usuario', JSON.stringify({ nome, email }));
  document.getElementById('nome-usuario').textContent = 'Olá, ' + nome + '!';

  /*
   * TODO: Enviar para o PHP:
   * fetch('../backend/atualizar_usuario.php', {
   *   method: 'POST',
   *   headers: { 'Content-Type': 'application/json' },
   *   body: JSON.stringify({ nome, email })
   * });
   */

  alert('Dados atualizados com sucesso! ✅');
}

/** Simula a alteração de senha. */
function alterarSenha() {
  const atual  = document.getElementById('config-senha-atual').value;
  const nova   = document.getElementById('config-senha-nova').value;
  const conf   = document.getElementById('config-senha-conf').value;

  if (!atual || !nova || !conf) {
    alert('Preencha todos os campos de senha.');
    return;
  }

  if (nova.length < 6) {
    alert('A nova senha deve ter pelo menos 6 caracteres.');
    return;
  }

  if (nova !== conf) {
    alert('As novas senhas não coincidem.');
    return;
  }

  /*
   * TODO: Enviar para o PHP para verificar e alterar a senha no banco.
   */

  alert('Senha atualizada com sucesso! 🔒');
  document.getElementById('config-senha-atual').value = '';
  document.getElementById('config-senha-nova').value  = '';
  document.getElementById('config-senha-conf').value  = '';
}

/** Exclui a conta do usuário (com confirmação). */
function excluirConta() {
  const confirma = confirm(
    '⚠️ Tem certeza? Esta ação é irreversível e todos os seus dados serão apagados.'
  );

  if (confirma) {
    localStorage.removeItem('usuario');

    /*
     * TODO: Chamar PHP para excluir do banco de dados.
     */

    alert('Conta excluída. Até logo!');
    window.location.href = 'index.html';
  }
}

/** Redireciona para o login e limpa os dados da sessão. */
function sair() {
  localStorage.removeItem('usuario');
  window.location.href = 'index.html';
}
