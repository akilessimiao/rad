<?php
/**
 * Gera um hash SHA-256 para integridade do documento
 */
function gerarHash($dados) {
    return hash('sha256', json_encode($dados) . date('Y-m-d H:i:s') . uniqid());
}

/**
 * Sanitiza dados para evitar XSS e injeção
 */
function sanitizar($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida email
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Cria pasta com permissões
 */
function criarPasta($caminho) {
    if (!is_dir($caminho)) {
        return mkdir($caminho, 0755, true);
    }
    return true;
}

/**
 * Salva a imagem da assinatura
 */
function salvarAssinatura($arquivo) {
    $pasta = UPLOAD_IMG_DIR;
    criarPasta($pasta);
    
    // Validar extensão
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    if (!in_array($extensao, ALLOWED_EXTENSIONS)) {
        return false;
    }
    
    // Validar tamanho
    if ($arquivo['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    // Gerar nome único
    $nome = 'assinatura_' . date('Ymd_His') . '_' . uniqid() . '.' . $extensao;
    $caminho = $pasta . $nome;
    
    if (move_uploaded_file($arquivo['tmp_name'], $caminho)) {
        return $caminho;
    }
    return false;
}

/**
 * Log de atividades
 */
function logAtividade($mensagem) {
    $pasta = 'logs/';
    criarPasta($pasta);
    $log = date('Y-m-d H:i:s') . " - " . $mensagem . PHP_EOL;
    file_put_contents($pasta . 'atividade.log', $log, FILE_APPEND);
}

/**
 * Valida dados obrigatórios
 */
function validarCampos($campos) {
    $erros = [];
    foreach ($campos as $campo => $valor) {
        if (empty($valor)) {
            $erros[] = "Campo '$campo' é obrigatório";
        }
    }
    return $erros;
}
?>
