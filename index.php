<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/recursos.php';

// Verifica se o usuário já possui sessão ativa
$usuarioLogado = false;
$linkPainel = '';
$nomePerfil = '';

if (isset($_SESSION['admin_id']) && (($_SESSION['perfil'] ?? '') === 'administrador')) {
    $usuarioLogado = true;
    $linkPainel = 'administrador/inicioadmin.php';
    $nomePerfil = 'Administrador (' . htmlspecialchars($_SESSION['admin_nome'] ?? 'Admin', ENT_QUOTES, 'UTF-8') . ')';
} elseif (isset($_SESSION['usuario_id'])) {
    $usuarioLogado = true;
    $cargo = $_SESSION['cargo'] ?? 'balconista';
    $linkPainel = ($cargo === 'gerente') ? 'gerente/iniciogerente.php' : 'balconista/iniciobalconista.php';
    $nomePerfil = ucfirst((string) $cargo) . ' (' . htmlspecialchars($_SESSION['usuario_nome'] ?? 'Usuário', ENT_QUOTES, 'UTF-8') . ')';
}

$erro = $_GET['erro'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php recursosCabeca('FarmaCerta - Gestão Farmacêutica Inteligente & Rede de Farmácias'); ?>

    <!-- =========================================================
         SEO: META TAGS PRINCIPAIS
         ========================================================= -->
    <meta name="description" content="FarmaCerta: Sistema de gestão completo para redes de farmácias com controle de estoque FEFO, PDV ágil, serviços farmacêuticos e suporte a inteligência artificial.">
    <meta name="keywords" content="farmácia, gestão farmacêutica, controle de estoque FEFO, validade de medicamentos, PDV farmácia, sistema farmacêutico, FarmaCerta, rede de farmácias">
    <meta name="author" content="FarmaCerta">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <link rel="canonical" href="index.php">

    <!-- =========================================================
         SEO: OPEN GRAPH (FACEBOOK, WHATSAPP, LINKEDIN)
         ========================================================= -->
    <meta property="og:locale" content="pt_BR">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="FarmaCerta">
    <meta property="og:title" content="FarmaCerta - Gestão Farmacêutica Inteligente & Rede de Farmácias">
    <meta property="og:description" content="Controle de estoque com validade FEFO, rapidez no PDV, gerenciamento de múltiplas filiais e assistência com FarmaCerta IA.">
    <meta property="og:image" content="assets/farmacerta-icon-512x512.png">
    <meta property="og:image:width" content="512">
    <meta property="og:image:height" content="512">
    <meta property="og:image:alt" content="Logo FarmaCerta">

    <!-- =========================================================
         SEO: TWITTER CARDS
         ========================================================= -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="FarmaCerta - Gestão Farmacêutica Inteligente">
    <meta name="twitter:description" content="Controle de estoque com validade FEFO, rapidez no PDV e serviços farmacêuticos integrados.">
    <meta name="twitter:image" content="assets/farmacerta-icon-512x512.png">

    <!-- =========================================================
         SEO: DADOS ESTRUTURADOS (SCHEMA.ORG JSON-LD)
         ========================================================= -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Pharmacy",
      "name": "FarmaCerta",
      "description": "Rede de farmácias com sistema integrado de gestão farmacêutica, controle rigoroso de validade FEFO e serviços clínicos.",
      "image": "assets/farmacerta-icon-512x512.png",
      "logo": "assets/LOGO_2.png",
      "telephone": "(84) 0000-0000",
      "priceRange": "$$",
      "openingHoursSpecification": [
        {
          "@type": "OpeningHoursSpecification",
          "dayOfWeek": [
            "Monday",
            "Tuesday",
            "Wednesday",
            "Thursday",
            "Friday",
            "Saturday"
          ],
          "opens": "07:00",
          "closes": "22:00"
        },
        {
          "@type": "OpeningHoursSpecification",
          "dayOfWeek": [
            "Sunday"
          ],
          "opens": "08:00",
          "closes": "20:00"
        }
      ],
      "department": [
        {
          "@type": "Pharmacy",
          "name": "FarmaCerta - Farmácia 1 (Matriz)",
          "telephone": "(84) 0000-0000",
          "address": {
            "@type": "PostalAddress",
            "streetAddress": "Endereco da Farmacia 1",
            "addressLocality": "Centro",
            "addressRegion": "RN",
            "addressCountry": "BR"
          }
        },
        {
          "@type": "Pharmacy",
          "name": "FarmaCerta - Farmácia 2 (Filial)",
          "telephone": "(84) 1111-1111",
          "address": {
            "@type": "PostalAddress",
            "streetAddress": "Endereco da Farmacia 2",
            "addressLocality": "Bairro Novo",
            "addressRegion": "RN",
            "addressCountry": "BR"
          }
        }
      ]
    }
    </script>


    <style>
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 85px;
        }

        section[id],
        header[id],
        div[id] {
            scroll-margin-top: 85px;
        }

        body {
            background: var(--fc-background) !important;
            background-image: none !important;
            color: var(--fc-text) !important;
        }

        /* Botão Vermelho FarmaCerta */
        .btn-vermelho {
            background-color: #e63946 !important;
            border-color: #e63946 !important;
            color: #ffffff !important;
            font-weight: 700;
            transition: all 0.2s ease;
        }

        .btn-vermelho:hover,
        .btn-vermelho:focus,
        .btn-vermelho:active {
            background-color: #c92f3b !important;
            border-color: #c92f3b !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(230, 57, 70, 0.35) !important;
        }

        .btn-outline-vermelho {
            color: #e63946 !important;
            border-color: #e63946 !important;
            background-color: transparent !important;
            font-weight: 700;
            transition: all 0.2s ease;
        }

        .btn-outline-vermelho:hover,
        .btn-outline-vermelho:focus,
        .btn-outline-vermelho:active {
            background-color: #e63946 !important;
            color: #ffffff !important;
        }

        .hero-banner {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
        }

        .card-recurso {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card-recurso:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08) !important;
        }

        .indicador-box {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 1.25rem 1rem;
            text-align: center;
        }

        .indicador-numero {
            font-size: 1.8rem;
            font-weight: 800;
            color: #e63946;
            line-height: 1.1;
        }
    </style>
</head>

<body>

    <!-- =========================================================
         BARRA SUPERIOR NO PADRÃO FARMACERTA
         ========================================================= -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-farmacia py-3 sticky-top">
        <div class="container-fluid px-3 px-lg-4">
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <img src="assets/LOGO_1.png" alt="Logo FarmaCerta" height="46">
            </a>
            <span class="text-white fw-bold fs-5 d-none d-md-inline">FarmaCerta</span>

            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#menuTopo" aria-controls="menuTopo" aria-expanded="false" aria-label="Menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="menuTopo">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-1 text-center">
                    <li class="nav-item">
                        <a class="nav-link text-white fw-bold px-3" href="#inicio">Início</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white fw-bold px-3" href="#sobre">Sobre Nós</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white fw-bold px-3" href="#recursos">Recursos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white fw-bold px-3" href="#servicos">Serviços</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white fw-bold px-3" href="#perfis">Perfis</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white fw-bold px-3" href="#unidades">Unidades</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center justify-content-center gap-2 mt-2 mt-lg-0">
                    <?php echo renderizarBotaoTema(); ?>
                    <?php if ($usuarioLogado): ?>
                        <a href="<?php echo htmlspecialchars($linkPainel, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light text-danger rounded-pill px-3 py-1 fw-bold text-decoration-none">
                            Meu Painel
                        </a>
                        <a href="logout.php" class="btn btn-outline-light rounded-pill px-3 py-1 fw-bold text-decoration-none" title="Sair da Sessão">
                            Sair
                        </a>
                    <?php else: ?>
                        <button type="button" class="btn btn-light text-danger rounded-pill px-4 py-1 fw-bold text-decoration-none shadow-sm" data-bs-toggle="modal" data-bs-target="#modalLogin">
                            Entrar
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- =========================================================
         LANDING PAGE: HERO / APRESENTAÇÃO INICIAL
         (Primeira coisa vista ao abrir a página)
         ========================================================= -->
    <header id="inicio" class="hero-banner py-5">
        <div class="container py-3">
            <div class="row align-items-center gy-4">
                
                <div class="col-12 col-lg-7 text-center text-lg-start">
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill fw-bold mb-3">
                        Sistema Integrado de Gestão Farmacêutica
                    </span>

                    <h1 class="display-5 fw-bold text-dark mb-3">
                        Bem-vindo à <span class="text-danger">FarmaCerta</span>
                    </h1>

                    <p class="lead text-secondary mb-4">
                        Solução completa para a gestão de farmácias. Controle de estoque com validade <strong>FEFO</strong>, rapidez no ponto de venda (PDV), gerenciamento de múltiplas unidades e assistência com inteligência artificial.
                    </p>

                    <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-lg-start">
                        <?php if ($usuarioLogado): ?>
                            <a href="<?php echo htmlspecialchars($linkPainel, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-vermelho rounded-pill px-4 py-2">
                                Continuar no Painel (<?php echo htmlspecialchars($nomePerfil, ENT_QUOTES, 'UTF-8'); ?>)
                            </a>
                        <?php else: ?>
                            <!-- Botão Acessar o Sistema VERMELHO -->
                            <button type="button" class="btn btn-vermelho rounded-pill px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalLogin">
                                Acessar o Sistema
                            </button>
                        <?php endif; ?>

                        <a href="#recursos" class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-bold">
                            Conhecer Recursos
                        </a>
                    </div>
                </div>

                <div class="col-12 col-lg-5 text-center">
                    <img
                        src="assets/LOGO_2_vermelho.png"
                        alt="Logo FarmaCerta"
                        width="190"
                        height="190"
                        class="img-fluid"
                    >
                </div>

            </div>
        </div>
    </header>

    <!-- =========================================================
         BARRA DE INDICADORES RÁPIDOS
         ========================================================= -->
    <section class="py-4">
        <div class="container">
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="indicador-box shadow-sm">
                        <div class="indicador-numero">2 Filiais</div>
                        <div class="small text-secondary fw-bold mt-1">Rede Integrada</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="indicador-box shadow-sm">
                        <div class="indicador-numero">100% FEFO</div>
                        <div class="small text-secondary fw-bold mt-1">Validade Sob Controle</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="indicador-box shadow-sm">
                        <div class="indicador-numero">&lt; 1 min</div>
                        <div class="small text-secondary fw-bold mt-1">Agilidade no PDV</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="indicador-box shadow-sm">
                        <div class="indicador-numero">IA Conectada</div>
                        <div class="small text-secondary fw-bold mt-1">Apoio em Tempo Real</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- =========================================================
         SEÇÃO SOBRE NÓS / INSTITUCIONAL
         ========================================================= -->
    <section id="sobre" class="py-5 bg-white border-top border-bottom">
        <div class="container">
            <div class="row align-items-center gy-4">
                <div class="col-12 col-lg-6">
                    <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-bold mb-2">
                        Sobre a Rede
                    </span>
                    <h2 class="fw-bold text-dark mb-3">Compromisso com a sua saúde e bem-estar</h2>
                    <p class="text-secondary leading-relaxed">
                        A <strong>FarmaCerta</strong> nasceu para unir a confiança do atendimento farmacêutico tradicional à tecnologia moderna de gestão em saúde. Nosso objetivo é garantir que cada cliente encontre medicamentos com procedência comprovada, validade rigorosamente controlada e atendimento humanizado.
                    </p>
                    <p class="text-secondary leading-relaxed mb-4">
                        Por meio de nossa plataforma integrada, nossas unidades operam de forma sincronizada: desde o recebimento de mercadorias com conferência fiscal até a dispensação no balcão, oferecendo segurança máxima para quem consome e eficiência para quem gerencia.
                    </p>

                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <div class="p-3 bg-light rounded-3 border-start border-danger border-4">
                                <strong class="d-block text-dark mb-1">Qualidade Assegurada</strong>
                                <span class="small text-secondary">Conformidade com todas as normas da ANVISA e boas práticas de dispensação.</span>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="p-3 bg-light rounded-3 border-start border-danger border-4">
                                <strong class="d-block text-dark mb-1">Atendimento Humanizado</strong>
                                <span class="small text-secondary">Profissionais qualificados prontos para orientar sobre o uso correto de medicamentos.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="card card-brand rounded-4 p-4 p-md-5 text-center shadow">
                        <h3 class="fw-bold display-6 mb-3">FarmaCerta</h3>
                        <p class="mb-4">
                            "Mais do que uma farmácia, um ponto de cuidado e assistência para toda a sua família."
                        </p>
                        <div class="d-flex flex-wrap justify-content-center gap-3">
                            <div class="p-2 bg-white bg-opacity-10 rounded-3 px-3">
                                <strong>+1.200</strong>
                                <div class="small opacity-75">Itens em Catálogo</div>
                            </div>
                            <div class="p-2 bg-white bg-opacity-10 rounded-3 px-3">
                                <strong>Plantão</strong>
                                <div class="small opacity-75">Atendimento Diário</div>
                            </div>
                            <div class="p-2 bg-white bg-opacity-10 rounded-3 px-3">
                                <strong>100% Digital</strong>
                                <div class="small opacity-75">Recibos e Histórico</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- =========================================================
         LANDING PAGE: PRINCIPAIS RECURSOS DO SISTEMA
         ========================================================= -->
    <section id="recursos" class="py-5">
        <div class="container">
            
            <div class="text-center mb-5">
                <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-bold mb-2">
                    Funcionalidades
                </span>
                <h2 class="fw-bold text-dark mb-2">Recursos da Plataforma FarmaCerta</h2>
                <p class="text-secondary mb-0">Tecnologia desenvolvida sob medida para a rotina diária da sua farmácia</p>
            </div>

            <div class="row g-4 justify-content-center">
                
                <!-- Recurso 1: PDV -->
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="card card-recurso h-100 rounded-4 shadow-sm p-4 text-center">
                        <div class="kpi-icone rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                            $
                        </div>
                        <h3 class="h5 fw-bold text-dark mb-2">PDV de Balcão Ágil</h3>
                        <p class="small text-secondary mb-0">
                            Vendas rápidas com busca instantânea por produto, suporte a Dinheiro, PIX, Cartão de Débito e Crédito, e emissão de recibos na hora.
                        </p>
                    </div>
                </div>

                <!-- Recurso 2: Lotes e Validade (FEFO) -->
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="card card-recurso h-100 rounded-4 shadow-sm p-4 text-center">
                        <div class="kpi-icone rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                            ✓
                        </div>
                        <h3 class="h5 fw-bold text-dark mb-2">Controle FEFO de Validade</h3>
                        <p class="small text-secondary mb-0">
                            Algoritmo <em>First Expire, First Out</em>: baixa prioritária nos lotes que vencem primeiro, acabando com as perdas por medicamentos vencidos.
                        </p>
                    </div>
                </div>

                <!-- Recurso 3: Multi-Farmácias -->
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="card card-recurso h-100 rounded-4 shadow-sm p-4 text-center">
                        <div class="kpi-icone rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                            +
                        </div>
                        <h3 class="h5 fw-bold text-dark mb-2">Rede Multi-Unidades</h3>
                        <p class="small text-secondary mb-0">
                            Gestão de múltiplas filiais da rede com estoque, movimentações, colaboradores e relatórios separados por loja com total segurança.
                        </p>
                    </div>
                </div>

                <!-- Recurso 4: Assistente IA -->
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="card card-recurso h-100 rounded-4 shadow-sm p-4 text-center">
                        <div class="kpi-icone rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                            ✦
                        </div>
                        <h3 class="h5 fw-bold text-dark mb-2">FarmaCerta IA Integrada</h3>
                        <p class="small text-secondary mb-0">
                            Assistente de Inteligência Artificial para orientar a equipe, consultar produtos críticos com estoque baixo e esclarecer rotinas operacionais.
                        </p>
                    </div>
                </div>

                <!-- Recurso 5: Fiscal e Auditoria -->
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="card card-recurso h-100 rounded-4 shadow-sm p-4 text-center">
                        <div class="kpi-icone rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                            #
                        </div>
                        <h3 class="h5 fw-bold text-dark mb-2">Módulo Fiscal & Auditoria</h3>
                        <p class="small text-secondary mb-0">
                            Registro de dados fiscais (NF-e/NFC-e), trilha detalhada de auditoria para cada ação dos operadores e segurança de dados.
                        </p>
                    </div>
                </div>

                <!-- Recurso 6: Clientes e Fidelização -->
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="card card-recurso h-100 rounded-4 shadow-sm p-4 text-center">
                        <div class="kpi-icone rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                            ♥
                        </div>
                        <h3 class="h5 fw-bold text-dark mb-2">Histórico de Clientes</h3>
                        <p class="small text-secondary mb-0">
                            Cadastro simplificado de clientes no balcão e consulta dos itens mais comprados para garantir atendimento personalizado e contínuo.
                        </p>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- =========================================================
         SEÇÃO DE SERVIÇOS FARMACÊUTICOS (O QUE A FARMÁCIA OFERECE)
         ========================================================= -->
    <section id="servicos" class="py-5 bg-white border-top border-bottom">
        <div class="container">
            
            <div class="text-center mb-5">
                <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-bold mb-2">
                    Cuidado & Saúde
                </span>
                <h2 class="fw-bold text-dark mb-2">Serviços Farmacêuticos ao Cliente</h2>
                <p class="text-secondary mb-0">Conheça os procedimentos e atendimentos disponíveis em nossas unidades</p>
            </div>

            <div class="row g-4">
                
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="p-4 rounded-4 bg-light h-100 border">
                        <h4 class="h5 fw-bold text-dark mb-2">🩺 Aferição de Pressão</h4>
                        <p class="small text-secondary mb-0">
                            Acompanhamento gratuito da pressão arterial realizado por profissionais capacitados, com orientação sobre medidas de prevenção.
                        </p>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="p-4 rounded-4 bg-light h-100 border">
                        <h4 class="h5 fw-bold text-dark mb-2">🩸 Testes de Glicemia</h4>
                        <p class="small text-secondary mb-0">
                            Monitoramento rápido dos níveis de glicose no sangue com registro e histórico para acompanhamento com seu médico.
                        </p>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="p-4 rounded-4 bg-light h-100 border">
                        <h4 class="h5 fw-bold text-dark mb-2">💉 Aplicação de Medicamentos</h4>
                        <p class="small text-secondary mb-0">
                            Serviço de aplicação de injetáveis autorizados mediante apresentação de receita médica válida, com total higiene e segurança.
                        </p>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="p-4 rounded-4 bg-light h-100 border">
                        <h4 class="h5 fw-bold text-dark mb-2">💊 Orientação Farmacêutica</h4>
                        <p class="small text-secondary mb-0">
                            Esclarecimento de dúvidas sobre horários, dosagens, efeitos adversos e interações medicamentosas com nossos farmacêuticos.
                        </p>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="p-4 rounded-4 bg-light h-100 border">
                        <h4 class="h5 fw-bold text-dark mb-2">🛵 Entrega em Domicílio</h4>
                        <p class="small text-secondary mb-0">
                            Receba seus medicamentos, produtos de higiene e cosméticos no conforto de sua casa com rapidez e comodidade.
                        </p>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="p-4 rounded-4 bg-light h-100 border">
                        <h4 class="h5 fw-bold text-dark mb-2">🏷️ Variedade & Melhores Preços</h4>
                        <p class="small text-secondary mb-0">
                            Amplo estoque de medicamentos de referência, genéricos e similares com condições acessíveis e programas de fidelidade.
                        </p>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- =========================================================
         PERFIS DE ACESSO AO SISTEMA
         ========================================================= -->
    <section id="perfis" class="py-5">
        <div class="container">
            
            <div class="text-center mb-5">
                <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-bold mb-2">
                    Acesso Estruturado
                </span>
                <h2 class="fw-bold text-dark mb-2">Perfis de Acesso do Sistema</h2>
                <p class="text-secondary mb-0">Ambientes pensados para a produtividade de cada integrante da equipe</p>
            </div>

            <div class="row g-4 justify-content-center">
                
                <!-- Balconista -->
                <div class="col-12 col-md-4">
                    <div class="card h-100 shadow-sm rounded-4 border-0 p-4 d-flex flex-column">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 fw-bold align-self-start mb-3">
                            Balconista
                        </span>
                        <h3 class="h5 fw-bold text-dark">Agilidade no Balcão</h3>
                        <p class="small text-secondary mb-3">Interface rápida para busca de produtos, adição ao carrinho, cadastro de clientes e finalização de vendas com recibo.</p>
                        
                        <ul class="list-unstyled small text-secondary d-flex flex-column gap-2 mb-4">
                            <li>✓ Consulta rápida de catálogo e estoque</li>
                            <li>✓ Carrinho dinâmico com troco e formas de pagamento</li>
                            <li>✓ Cadastro e histórico de compras do cliente</li>
                            <li>✓ Emissão imediata de comprovante de venda</li>
                        </ul>

                        <button type="button" class="btn btn-outline-vermelho rounded-pill w-100 mt-auto" data-bs-toggle="modal" data-bs-target="#modalLogin">
                            Entrar como Balconista
                        </button>
                    </div>
                </div>

                <!-- Gerente -->
                <div class="col-12 col-md-4">
                    <div class="card h-100 shadow-sm rounded-4 border-0 p-4 d-flex flex-column">
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-2 fw-bold align-self-start mb-3">
                            Gerente
                        </span>
                        <h3 class="h5 fw-bold text-dark">Controle Operacional</h3>
                        <p class="small text-secondary mb-3">Gestão completa da loja: compras de fornecedores, controle de lotes/validade FEFO, equipe, relatórios e fiscal.</p>
                        
                        <ul class="list-unstyled small text-secondary d-flex flex-column gap-2 mb-4">
                            <li>✓ Entrada de mercadorias com dados de nota fiscal</li>
                            <li>✓ Gestão de estoque mínimo e reposição de lotes</li>
                            <li>✓ Gestão de funcionários da unidade</li>
                            <li>✓ Relatórios financeiros e visão geral de faturamento</li>
                        </ul>

                        <button type="button" class="btn btn-outline-vermelho rounded-pill w-100 mt-auto" data-bs-toggle="modal" data-bs-target="#modalLogin">
                            Entrar como Gerente
                        </button>
                    </div>
                </div>

                <!-- Administrador -->
                <div class="col-12 col-md-4">
                    <div class="card h-100 shadow-sm rounded-4 border-0 p-4 d-flex flex-column">
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2 fw-bold align-self-start mb-3">
                            Administrador
                        </span>
                        <h3 class="h5 fw-bold text-dark">Visão Geral da Rede</h3>
                        <p class="small text-secondary mb-3">Administração corporativa: cadastro e status de filiais, trilha de auditoria e segurança com backup do banco de dados.</p>
                        
                        <ul class="list-unstyled small text-secondary d-flex flex-column gap-2 mb-4">
                            <li>✓ Cadastro e monitoramento das farmácias</li>
                            <li>✓ Auditoria detalhada de cada ação do sistema</li>
                            <li>✓ Backup e restauração do banco com 1 clique</li>
                            <li>✓ Gestão de governança e segurança de dados</li>
                        </ul>

                        <button type="button" class="btn btn-outline-vermelho rounded-pill w-100 mt-auto" data-bs-toggle="modal" data-bs-target="#modalLogin">
                            Entrar como Administrador
                        </button>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- =========================================================
         NOSSAS UNIDADES
         ========================================================= -->
    <section id="unidades" class="py-5 bg-white border-top border-bottom">
        <div class="container">
            
            <div class="text-center mb-5">
                <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-bold mb-2">
                    Filiais Ativas
                </span>
                <h2 class="fw-bold text-dark mb-2">Nossas Unidades</h2>
                <p class="text-secondary mb-0">Encontre a Farmácia FarmaCerta mais próxima de você</p>
            </div>

            <div class="row g-4 justify-content-center">
                
                <!-- Farmácia 1 -->
                <div class="col-12 col-md-6 col-lg-5">
                    <div class="card h-100 shadow-sm rounded-4 border p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h3 class="h5 fw-bold text-dark mb-0">Farmácia 1 — Matriz</h3>
                            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1">Aberta</span>
                        </div>

                        <ul class="list-unstyled small text-secondary d-flex flex-column gap-2 mb-4">
                            <li>📍 <strong>Endereço:</strong> Endereco da Farmacia 1 · Centro</li>
                            <li>📞 <strong>Telefone:</strong> (84) 0000-0000 (WhatsApp)</li>
                            <li>🕒 <strong>Horário:</strong> Seg a Sáb: 07h00 às 22h00 | Dom e Feriados: 08h00 às 20h00</li>
                        </ul>

                        <div class="p-3 bg-light rounded-3 small">
                            <strong class="text-dark d-block mb-1">Serviços Disponíveis:</strong>
                            Dispensação orientada, aferição de pressão arterial, testes rápidos e entrega em domicílio.
                        </div>
                    </div>
                </div>

                <!-- Farmácia 2 -->
                <div class="col-12 col-md-6 col-lg-5">
                    <div class="card h-100 shadow-sm rounded-4 border p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h3 class="h5 fw-bold text-dark mb-0">Farmácia 2 — Filial</h3>
                            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1">Aberta</span>
                        </div>

                        <ul class="list-unstyled small text-secondary d-flex flex-column gap-2 mb-4">
                            <li>📍 <strong>Endereço:</strong> Endereco da Farmacia 2 · Bairro Novo</li>
                            <li>📞 <strong>Telefone:</strong> (84) 1111-1111 (WhatsApp)</li>
                            <li>🕒 <strong>Horário:</strong> Seg a Sáb: 07h00 às 22h00 | Plantão Farmacêutico</li>
                        </ul>

                        <div class="p-3 bg-light rounded-3 small">
                            <strong class="text-dark d-block mb-1">Serviços Disponíveis:</strong>
                            Aplicação de injetáveis autorizados, controle glicêmico e farmacovigilância ativa.
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- =========================================================
         CHAMADA PARA AÇÃO (CTA) ANTES DO RODAPÉ
         ========================================================= -->
    <section class="py-5">
        <div class="container">
            <div class="card card-brand rounded-4 p-4 p-md-5 text-center shadow">
                <h3 class="fw-bold display-6 mb-3">Área do Colaborador FarmaCerta</h3>
                <p class="lead mb-4 mx-auto" style="max-width: 600px;">
                    Administradores, gerentes e balconistas: acesse a plataforma com seu login e senha para gerenciar suas operações.
                </p>
                <div>
                    <button type="button" class="btn btn-light text-danger fw-bold rounded-pill px-5 py-3 shadow" data-bs-toggle="modal" data-bs-target="#modalLogin">
                        Acessar o Sistema
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- =========================================================
         RODAPÉ COMPLETO NO PADRÃO DO SISTEMA
         ========================================================= -->
    <footer class="py-5 bg-dark text-white-50 border-top">
        <div class="container">
            <div class="row g-4 mb-4">
                
                <div class="col-12 col-md-5">
                    <img src="assets/LOGO_1.png" alt="Logo FarmaCerta" height="42" class="mb-3">
                    <p class="small pe-md-4">
                        Sistema acadêmico e operacional de gestão para rede de farmácias FarmaCerta. Controle de estoque, conformidade sanitária e agilidade nas vendas.
                    </p>
                </div>

                <div class="col-6 col-md-3">
                    <h4 class="h6 fw-bold text-white mb-3">Links Rápidos</h4>
                    <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                        <li><a href="#inicio" class="text-white-50 text-decoration-none">Início</a></li>
                        <li><a href="#sobre" class="text-white-50 text-decoration-none">Sobre Nós</a></li>
                        <li><a href="#recursos" class="text-white-50 text-decoration-none">Recursos</a></li>
                        <li><a href="#servicos" class="text-white-50 text-decoration-none">Serviços Farmacêuticos</a></li>
                        <li><a href="#perfis" class="text-white-50 text-decoration-none">Perfis do Sistema</a></li>
                        <li><a href="#unidades" class="text-white-50 text-decoration-none">Nossas Unidades</a></li>
                    </ul>
                </div>

                <div class="col-6 col-md-4">
                    <h4 class="h6 fw-bold text-white mb-3">Atendimento</h4>
                    <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                        <li>📞 Central: (84) 0000-0000</li>
                        <li>✉️ contato@farmacerta.local</li>
                        <li>🕒 Seg a Sáb: 07h às 22h</li>
                        <li>📍 Rio Grande do Norte, Brasil</li>
                    </ul>
                </div>

            </div>

            <hr class="border-secondary opacity-25">

            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-2 small">
                <div>
                    &copy; <?php echo date('Y'); ?> FarmaCerta &bull; Todos os direitos reservados.
                </div>
                <div>
                    Gestão Farmacêutica Inteligente
                </div>
            </div>
        </div>
    </footer>

    <!-- =========================================================
         MODAL DE LOGIN ORIGINAL DA FARMACERTA
         (Acessado apenas ao clicar em "Entrar" ou "Acessar Sistema")
         ========================================================= -->
    <div class="modal fade" id="modalLogin" tabindex="-1" aria-labelledby="modalLoginTitulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
            <div class="modal-content border-0 bg-transparent shadow-none">
                
                <section class="card card-brand rounded-4 p-4 p-md-5 position-relative shadow">
                    
                    <!-- Botão Fechar -->
                    <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Fechar"></button>

                    <!-- Título -->
                    <h2 class="text-center fw-bold display-6 mb-3" id="modalLoginTitulo">
                        FarmaCerta
                    </h2>

                    <!-- Logo -->
                    <div class="text-center mb-4">
                        <img
                            src="assets/LOGO_2.png"
                            alt="Logo FarmaCerta"
                            width="110"
                            height="110"
                            class="img-fluid"
                        >
                    </div>

                    <!-- Mensagem de erro -->
                    <?php if (isset($_GET['erro'])) { ?>
                        <div class="alert alert-light text-danger fw-bold py-2 small mb-3" role="alert">
                            <?php
                            echo $_GET['erro'] === 'acesso'
                                ? 'Você não tem permissão para acessar essa área.'
                                : 'Login, senha ou função inválidos.';
                            ?>
                        </div>
                    <?php } ?>

                    <!-- Formulário de login -->
                    <form action="login.php" method="POST">

                        <!-- Login -->
                        <div class="mb-3">
                            <label class="form-label fw-bold" for="loginModalInput">
                                Login
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                id="loginModalInput"
                                name="login"
                                required
                                placeholder="Digite seu login"
                                autocomplete="username"
                            >
                        </div>

                        <!-- Senha -->
                        <div class="mb-3">
                            <label class="form-label fw-bold" for="senhaModalInput">
                                Senha
                            </label>
                            <input
                                class="form-control"
                                type="password"
                                id="senhaModalInput"
                                name="senha"
                                required
                                placeholder="Digite sua senha"
                                autocomplete="current-password"
                            >
                        </div>

                        <!-- Função -->
                        <div class="mb-4">
                            <label class="form-label fw-bold" for="cargoModalInput">
                                Entrar como
                            </label>
                            <select
                                class="form-select"
                                id="cargoModalInput"
                                name="cargo"
                                required
                            >
                                <option value="">
                                    Selecione uma função
                                </option>
                                <option value="administrador">
                                    Administrador
                                </option>
                                <option value="gerente">
                                    Gerente
                                </option>
                                <option value="balconista">
                                    Balconista
                                </option>
                            </select>
                        </div>

                        <!-- Separador -->
                        <hr class="border-light opacity-25">

                        <!-- Botão -->
                        <button
                            class="btn btn-outline-light w-100 rounded-pill fw-bold py-2"
                            type="submit"
                        >
                            ENTRAR
                        </button>

                    </form>

                </section>

            </div>
        </div>
    </div>

    <!-- Scripts do Sistema (PWA, Bootstrap e Agente IA) -->
    <?php recursosRodape(); ?>

    <!-- Abre o modal de login automaticamente se houver parâmetro de erro ou acesso -->
    <?php if (isset($_GET['erro'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modalEl = document.getElementById('modalLogin');
            var bs = window.bootstrap || (typeof bootstrap !== 'undefined' ? bootstrap : null);
            if (modalEl && bs && bs.Modal) {
                var modal = new bs.Modal(modalEl);
                modal.show();
            }
        });
    </script>
    <?php endif; ?>

</body>
</html>
