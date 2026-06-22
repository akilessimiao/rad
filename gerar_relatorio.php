<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/funcoes.php';
require_once 'includes/fpdf.php';

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensagem'] = 'Método não permitido!';
    $_SESSION['tipo'] = 'error';
    header('Location: index.php');
    exit;
}

// Sanitização dos dados
$cliente = sanitizar($_POST['cliente']);
$email = sanitizar($_POST['email']);
$endereco = sanitizar($_POST['endereco']);
$titulo = sanitizar($_POST['titulo']);
$descricao = sanitizar($_POST['descricao']);
$tecnologias = sanitizar($_POST['tecnologias']);
$prazo = sanitizar($_POST['prazo']);

// Validar campos obrigatórios
$erros = validarCampos([
    'Cliente' => $cliente,
    'E-mail' => $email,
    'Título' => $titulo,
    'Descrição' => $descricao
]);

if (!empty($erros)) {
    $_SESSION['mensagem'] = implode('<br>', $erros);
    $_SESSION['tipo'] = 'error';
    header('Location: index.php');
    exit;
}

// Validar email
if (!validarEmail($email)) {
    $_SESSION['mensagem'] = 'E-mail inválido!';
    $_SESSION['tipo'] = 'error';
    header('Location: index.php');
    exit;
}

// Validar e salvar assinatura
if (!isset($_FILES['assinatura']) || $_FILES['assinatura']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['mensagem'] = 'É necessário enviar a assinatura digital!';
    $_SESSION['tipo'] = 'error';
    header('Location: index.php');
    exit;
}

$assinatura = salvarAssinatura($_FILES['assinatura']);
if (!$assinatura) {
    $_SESSION['mensagem'] = 'Erro ao fazer upload da assinatura! Verifique o formato (PNG/JPEG) e tamanho (máx 5MB).';
    $_SESSION['tipo'] = 'error';
    header('Location: index.php');
    exit;
}

// Gerar hash de integridade
$hash = gerarHash([
    'cliente' => $cliente,
    'email' => $email,
    'titulo' => $titulo,
    'data' => date('d/m/Y H:i:s'),
    'desenvolvido_por' => 'Deep AI'
]);

// Criar PDF
class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(102, 126, 234);
        $this->Cell(0, 10, 'RELATÓRIO TÉCNICO', 0, 1, 'C');
        $this->SetFont('Arial', 'I', 10);
        $this->SetTextColor(102, 102, 102);
        $this->Cell(0, 5, 'Documento com assinatura digital - Validade legal', 0, 1, 'C');
        $this->Ln(5);
        $this->SetDrawColor(102, 126, 234);
        $this->Line(10, 35, 200, 35);
        $this->Ln(12);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 10, 'Página ' . $this->PageNo() . ' | Gerado em ' . date('d/m/Y H:i:s'), 0, 0, 'C');
        
        // Crédito
        $this->SetY(-22);
        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(180, 180, 180);
        $this->Cell(0, 10, '🚀 Desenvolvido por Akilessimiao com assistência de Deep AI', 0, 0, 'C');
    }
}

$pdf = new PDF('P', 'mm', 'A4');
$pdf->AddPage();

// Título
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 10, utf8_decode($titulo), 0, 1, 'L');
$pdf->Ln(5);

// Dados do cliente
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(51, 51, 51);

$pdf->Cell(40, 8, 'Cliente:', 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 8, utf8_decode($cliente), 0, 1);

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(40, 8, 'E-mail:', 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 8, utf8_decode($email), 0, 1);

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(40, 8, 'Endereço:', 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 8, utf8_decode($endereco), 0, 1);

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(40, 8, 'Prazo:', 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 8, utf8_decode($prazo), 0, 1);

$pdf->Ln(8);

// Descrição
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 10, 'Descrição do Serviço:', 0, 1);
$pdf->SetFont('Arial', '', 11);
$pdf->MultiCell(0, 8, utf8_decode($descricao));
$pdf->Ln(5);

// Tecnologias
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Tecnologias Utilizadas:', 0, 1);
$pdf->SetFont('Arial', '', 11);
$pdf->MultiCell(0, 8, utf8_decode($tecnologias));
$pdf->Ln(10);

// Assinatura
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 10, 'Assinatura Digital do Cliente:', 0, 1);
$pdf->Ln(2);

// Inserir imagem da assinatura
$pdf->Image($assinatura, 30, $pdf->GetY(), 80, 30);
$pdf->Ln(35);

$pdf->SetFont('Arial', 'I', 10);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 8, '_________________________________________', 0, 1, 'L');
$pdf->Cell(0, 8, utf8_decode($cliente) . ' - ' . date('d/m/Y'), 0, 1, 'L');

// Hash de integridade
$pdf->Ln(5);
$pdf->SetFont('Arial', 'I', 8);
$pdf->SetTextColor(150, 150, 150);
$pdf->Cell(0, 5, 'Hash de Integridade: ' . $hash, 0, 1, 'C');
$pdf->Cell(0, 5, 'Este documento possui validade legal conforme assinatura digital anexada.', 0, 1, 'C');

// Salvar PDF
$pasta_pdf = UPLOAD_PDF_DIR;
criarPasta($pasta_pdf);
$nome_pdf = 'relatorio_' . date('Ymd_His') . '.pdf';
$caminho_pdf = $pasta_pdf . $nome_pdf;

$pdf->Output('F', $caminho_pdf);

// Log da atividade
logAtividade("Relatório gerado para $cliente - $nome_pdf");

// Mensagem de sucesso
$_SESSION['mensagem'] = "✅ <strong>Relatório gerado com sucesso!</strong><br>
                         📄 <a href='$caminho_pdf' target='_blank' style='color:#667eea;font-weight:bold;text-decoration:underline;'>Clique aqui para visualizar o PDF</a><br>
                         🔒 Hash de integridade: <code style='background:#f4f4f4;padding:2px 6px;border-radius:4px;font-size:12px;'>$hash</code>";
$_SESSION['tipo'] = 'success';
header('Location: index.php');
exit;
?>
