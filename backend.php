<?php


define('DB_HOST', 'localhost');
define('DB_NAME', 'meupedaco');
define('DB_USER', 'root');       // ← altere para seu usuário
define('DB_PASS', '');           // ← altere para sua senha
define('DB_CHARSET', 'utf8mb4');

// Chave secreta para geração de tokens JWT simples
define('SECRET_KEY', 'meupedaco_santo_andre_2025');

// Tempo de expiração do token (em segundos) — 7 dias
define('TOKEN_EXPIRA', 60 * 60 * 24 * 7);


// ─── CABEÇALHOS CORS (permite chamadas do GitHub Pages) ──────

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');   
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Responde pré-voo do navegador
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


// ─── CONEXÃO COM O BANCO ─────────────────────────────────────

function conectar(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        responder(500, ['erro' => 'Falha na conexão com o banco de dados.']);
    }

    return $pdo;
}


// ─── HELPERS ─────────────────────────────────────────────────

/** Envia resposta JSON e encerra o script. */
function responder(int $status, array $dados): void {
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Lê e decodifica o corpo JSON da requisição. */
function corpo(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

/** Gera um token simples assinado com HMAC. */
function gerarToken(int $usuario_id): string {
    $payload = base64_encode(json_encode([
        'id'  => $usuario_id,
        'exp' => time() + TOKEN_EXPIRA,
    ]));
    $assinatura = hash_hmac('sha256', $payload, SECRET_KEY);
    return $payload . '.' . $assinatura;
}

/** Valida e decodifica o token. Retorna o ID do usuário ou false. */
function validarToken(): int|false {
    $cabecalho = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!str_starts_with($cabecalho, 'Bearer ')) return false;

    $token = substr($cabecalho, 7);
    [$payload, $assinatura] = explode('.', $token) + [null, null];
    if (!$payload || !$assinatura) return false;

    $esperada = hash_hmac('sha256', $payload, SECRET_KEY);
    if (!hash_equals($esperada, $assinatura)) return false;

    $dados = json_decode(base64_decode($payload), true);
    if (!$dados || $dados['exp'] < time()) return false;

    return (int) $dados['id'];
}

/** Exige autenticação — retorna o ID do usuário logado. */
function autenticar(): int {
    $id = validarToken();
    if ($id === false) {
        responder(401, ['erro' => 'Não autenticado. Faça login novamente.']);
    }
    return $id;
}


// ─── ROTEAMENTO ──────────────────────────────────────────────

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

match (true) {
    $action === 'cadastrar'         && $method === 'POST' => cadastrar(),
    $action === 'login'             && $method === 'POST' => login(),
    $action === 'posts'             && $method === 'GET'  => listarPosts(),
    $action === 'publicar'          && $method === 'POST' => publicar(),
    $action === 'curtir'            && $method === 'POST' => curtir(),
    $action === 'comentar'          && $method === 'POST' => comentar(),
    $action === 'atualizar_usuario' && $method === 'POST' => atualizarUsuario(),
    $action === 'alterar_senha'     && $method === 'POST' => alterarSenha(),
    $action === 'excluir_conta'     && $method === 'POST' => excluirConta(),
    $action === 'pontos_mapa'       && $method === 'GET'  => listarPontosMapa(),
    $action === 'adicionar_ponto'   && $method === 'POST' => adicionarPontoMapa(),
    default => responder(404, ['erro' => "Ação '$action' não encontrada."]),
};



/**
 * POST /backend.php?action=cadastrar
 * Body: { "nome": "...", "email": "...", "senha": "..." }
 */
function cadastrar(): void {
    $dados = corpo();

    $nome  = trim($dados['nome']  ?? '');
    $email = trim($dados['email'] ?? '');
    $senha =      $dados['senha'] ?? '';

    // Validações
    if (!$nome || !$email || !$senha) {
        responder(400, ['erro' => 'Preencha nome, e-mail e senha.']);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        responder(400, ['erro' => 'E-mail inválido.']);
    }
    if (strlen($senha) < 6) {
        responder(400, ['erro' => 'A senha deve ter pelo menos 6 caracteres.']);
    }

    $pdo = conectar();

    // Verifica se o e-mail já está cadastrado
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        responder(409, ['erro' => 'Este e-mail já está cadastrado.']);
    }

    // Cria o usuário com senha criptografada
    $hash = password_hash($senha, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)');
    $stmt->execute([$nome, $email, $hash]);

    $id = (int) $pdo->lastInsertId();
    responder(201, [
        'mensagem' => 'Conta criada com sucesso!',
        'token'    => gerarToken($id),
        'usuario'  => ['id' => $id, 'nome' => $nome, 'email' => $email],
    ]);
}

/**
 * POST /backend.php?action=login
 * Body: { "email": "...", "senha": "..." }
 */
function login(): void {
    $dados = corpo();
    $email = trim($dados['email'] ?? '');
    $senha =      $dados['senha'] ?? '';

    if (!$email || !$senha) {
        responder(400, ['erro' => 'Informe e-mail e senha.']);
    }

    $pdo  = conectar();
    $stmt = $pdo->prepare('SELECT id, nome, email, senha FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($senha, $usuario['senha'])) {
        responder(401, ['erro' => 'E-mail ou senha incorretos.']);
    }

    responder(200, [
        'mensagem' => 'Login realizado com sucesso!',
        'token'    => gerarToken((int) $usuario['id']),
        'usuario'  => [
            'id'    => $usuario['id'],
            'nome'  => $usuario['nome'],
            'email' => $usuario['email'],
        ],
    ]);
}

/**
 * POST /backend.php?action=atualizar_usuario
 * Body: { "nome": "...", "email": "..." }
 * Header: Authorization: Bearer <token>
 */
function atualizarUsuario(): void {
    $usuario_id = autenticar();
    $dados      = corpo();

    $nome  = trim($dados['nome']  ?? '');
    $email = trim($dados['email'] ?? '');

    if (!$nome || !$email) {
        responder(400, ['erro' => 'Preencha nome e e-mail.']);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        responder(400, ['erro' => 'E-mail inválido.']);
    }

    $pdo  = conectar();

    // Verifica se o e-mail já pertence a outro usuário
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? AND id != ?');
    $stmt->execute([$email, $usuario_id]);
    if ($stmt->fetch()) {
        responder(409, ['erro' => 'Este e-mail já está em uso por outra conta.']);
    }

    $stmt = $pdo->prepare('UPDATE usuarios SET nome = ?, email = ? WHERE id = ?');
    $stmt->execute([$nome, $email, $usuario_id]);

    responder(200, ['mensagem' => 'Dados atualizados com sucesso!']);
}

/**
 * POST /backend.php?action=alterar_senha
 * Body: { "senha_atual": "...", "nova_senha": "..." }
 * Header: Authorization: Bearer <token>
 */
function alterarSenha(): void {
    $usuario_id = autenticar();
    $dados      = corpo();

    $atual = $dados['senha_atual'] ?? '';
    $nova  = $dados['nova_senha']  ?? '';

    if (!$atual || !$nova) {
        responder(400, ['erro' => 'Informe a senha atual e a nova senha.']);
    }
    if (strlen($nova) < 6) {
        responder(400, ['erro' => 'A nova senha deve ter pelo menos 6 caracteres.']);
    }

    $pdo  = conectar();
    $stmt = $pdo->prepare('SELECT senha FROM usuarios WHERE id = ?');
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($atual, $usuario['senha'])) {
        responder(401, ['erro' => 'Senha atual incorreta.']);
    }

    $hash = password_hash($nova, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('UPDATE usuarios SET senha = ? WHERE id = ?');
    $stmt->execute([$hash, $usuario_id]);

    responder(200, ['mensagem' => 'Senha alterada com sucesso!']);
}

/**
 * POST /backend.php?action=excluir_conta
 * Header: Authorization: Bearer <token>
 */
function excluirConta(): void {
    $usuario_id = autenticar();
    $pdo        = conectar();

    // Exclui em cascata: comentários → curtidas → posts → usuário
    $pdo->prepare('DELETE FROM comentarios WHERE usuario_id = ?')->execute([$usuario_id]);
    $pdo->prepare('DELETE FROM curtidas    WHERE usuario_id = ?')->execute([$usuario_id]);
    $pdo->prepare('DELETE FROM posts       WHERE usuario_id = ?')->execute([$usuario_id]);
    $pdo->prepare('DELETE FROM usuarios    WHERE id = ?')->execute([$usuario_id]);

    responder(200, ['mensagem' => 'Conta excluída com sucesso.']);
}


// ═══════════════════════════════════════════════════════════════
// FUNÇÕES — POSTS / FEED
// ═══════════════════════════════════════════════════════════════

/**
 * GET /backend.php?action=posts[&pagina=1]
 * Retorna os posts mais recentes com contagem de curtidas e comentários.
 */
function listarPosts(): void {
    $pagina   = max(1, (int) ($_GET['pagina'] ?? 1));
    $por_pag  = 10;
    $offset   = ($pagina - 1) * $por_pag;

    $pdo = conectar();

    $sql = "
        SELECT
            p.id,
            p.texto,
            p.bairro,
            p.imagem_url,
            p.criado_em,
            u.nome           AS autor_nome,
            COUNT(DISTINCT c.id)  AS total_curtidas,
            COUNT(DISTINCT co.id) AS total_comentarios
        FROM posts p
        JOIN usuarios u  ON u.id  = p.usuario_id
        LEFT JOIN curtidas   c  ON c.post_id = p.id
        LEFT JOIN comentarios co ON co.post_id = p.id
        GROUP BY p.id
        ORDER BY p.criado_em DESC
        LIMIT ? OFFSET ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$por_pag, $offset]);
    $posts = $stmt->fetchAll();

    // Para cada post, busca os últimos 3 comentários
    foreach ($posts as &$post) {
        $stmtCom = $pdo->prepare("
            SELECT co.texto, u.nome AS autor
            FROM comentarios co
            JOIN usuarios u ON u.id = co.usuario_id
            WHERE co.post_id = ?
            ORDER BY co.criado_em DESC
            LIMIT 3
        ");
        $stmtCom->execute([$post['id']]);
        $post['comentarios'] = array_reverse($stmtCom->fetchAll());
    }

    responder(200, ['posts' => $posts, 'pagina' => $pagina]);
}

/**
 * POST /backend.php?action=publicar
 * Body: { "texto": "...", "bairro": "...", "imagem_url": "..." }
 * Header: Authorization: Bearer <token>
 */
function publicar(): void {
    $usuario_id = autenticar();
    $dados      = corpo();

    $texto      = trim($dados['texto']      ?? '');
    $bairro     = trim($dados['bairro']     ?? 'Santo André');
    $imagem_url = trim($dados['imagem_url'] ?? '');

    if (!$texto) {
        responder(400, ['erro' => 'O texto da publicação não pode estar vazio.']);
    }
    if (strlen($texto) > 1000) {
        responder(400, ['erro' => 'Texto muito longo (máximo 1000 caracteres).']);
    }

    $pdo  = conectar();
    $stmt = $pdo->prepare(
        'INSERT INTO posts (usuario_id, texto, bairro, imagem_url) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$usuario_id, $texto, $bairro, $imagem_url ?: null]);

    responder(201, [
        'mensagem' => 'Publicado com sucesso!',
        'post_id'  => (int) $pdo->lastInsertId(),
    ]);
}

/**
 * POST /backend.php?action=curtir
 * Body: { "post_id": 1 }
 * Header: Authorization: Bearer <token>
 * Funciona como toggle: curte se ainda não curtiu, descurte se já curtiu.
 */
function curtir(): void {
    $usuario_id = autenticar();
    $dados      = corpo();
    $post_id    = (int) ($dados['post_id'] ?? 0);

    if (!$post_id) {
        responder(400, ['erro' => 'Informe o post_id.']);
    }

    $pdo  = conectar();

    // Verifica se já curtiu
    $stmt = $pdo->prepare('SELECT id FROM curtidas WHERE usuario_id = ? AND post_id = ?');
    $stmt->execute([$usuario_id, $post_id]);

    if ($stmt->fetch()) {
        // Já curtiu — remove curtida
        $pdo->prepare('DELETE FROM curtidas WHERE usuario_id = ? AND post_id = ?')
            ->execute([$usuario_id, $post_id]);
        $acao = 'descurtido';
    } else {
        // Não curtiu — adiciona curtida
        $pdo->prepare('INSERT INTO curtidas (usuario_id, post_id) VALUES (?, ?)')
            ->execute([$usuario_id, $post_id]);
        $acao = 'curtido';
    }

    // Retorna o total atualizado
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM curtidas WHERE post_id = ?');
    $stmt->execute([$post_id]);
    $total = $stmt->fetchColumn();

    responder(200, ['acao' => $acao, 'total_curtidas' => (int) $total]);
}

/**
 * POST /backend.php?action=comentar
 * Body: { "post_id": 1, "texto": "..." }
 * Header: Authorization: Bearer <token>
 */
function comentar(): void {
    $usuario_id = autenticar();
    $dados      = corpo();

    $post_id = (int) ($dados['post_id'] ?? 0);
    $texto   = trim($dados['texto'] ?? '');

    if (!$post_id || !$texto) {
        responder(400, ['erro' => 'Informe post_id e o texto do comentário.']);
    }
    if (strlen($texto) > 500) {
        responder(400, ['erro' => 'Comentário muito longo (máximo 500 caracteres).']);
    }

    $pdo  = conectar();
    $stmt = $pdo->prepare(
        'INSERT INTO comentarios (post_id, usuario_id, texto) VALUES (?, ?, ?)'
    );
    $stmt->execute([$post_id, $usuario_id, $texto]);

    // Retorna o novo comentário já formatado
    $stmtUser = $pdo->prepare('SELECT nome FROM usuarios WHERE id = ?');
    $stmtUser->execute([$usuario_id]);
    $nome = $stmtUser->fetchColumn();

    responder(201, [
        'mensagem'    => 'Comentário adicionado!',
        'comentario'  => ['autor' => $nome, 'texto' => $texto],
    ]);
}


// ═══════════════════════════════════════════════════════════════
// FUNÇÕES — MAPA CULTURAL
// ═══════════════════════════════════════════════════════════════

/**
 * GET /backend.php?action=pontos_mapa
 * Retorna todos os pontos culturais cadastrados.
 */
function listarPontosMapa(): void {
    $pdo  = conectar();
    $stmt = $pdo->query("
        SELECT pm.id, pm.nome, pm.descricao, pm.latitude, pm.longitude,
               pm.categoria, u.nome AS cadastrado_por, pm.criado_em
        FROM pontos_mapa pm
        JOIN usuarios u ON u.id = pm.usuario_id
        ORDER BY pm.nome ASC
    ");

    responder(200, ['pontos' => $stmt->fetchAll()]);
}

/**
 * POST /backend.php?action=adicionar_ponto
 * Body: { "nome": "...", "descricao": "...", "latitude": -23.66, "longitude": -46.53, "categoria": "..." }
 * Header: Authorization: Bearer <token>
 */
function adicionarPontoMapa(): void {
    $usuario_id = autenticar();
    $dados      = corpo();

    $nome      = trim($dados['nome']      ?? '');
    $descricao = trim($dados['descricao'] ?? '');
    $lat       = (float) ($dados['latitude']  ?? 0);
    $lng       = (float) ($dados['longitude'] ?? 0);
    $categoria = trim($dados['categoria'] ?? 'Cultura');

    if (!$nome || !$lat || !$lng) {
        responder(400, ['erro' => 'Informe nome, latitude e longitude.']);
    }

    $pdo  = conectar();
    $stmt = $pdo->prepare(
        'INSERT INTO pontos_mapa (usuario_id, nome, descricao, latitude, longitude, categoria)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$usuario_id, $nome, $descricao, $lat, $lng, $categoria]);

    responder(201, [
        'mensagem'  => 'Ponto adicionado ao mapa!',
        'ponto_id'  => (int) $pdo->lastInsertId(),
    ]);
}
