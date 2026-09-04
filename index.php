<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmaCerta - Login</title>
    
    <!-- SUA LOGO NO FAVICON -->
    <link rel="icon" href="assets/LOGO_2.png">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .login-card {
            background: linear-gradient(135deg, #d62828, #e63946);
            border-radius: 24px;
            padding: 48px 40px;
            width: 420px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .logo h1 {
            color: #fff;
            font-size: 42px;
            font-weight: 800;
            text-align: center;
            letter-spacing: -1px;
        }

        /* Container da imagem de logo */
        .logo-image-container {
            text-align: center;
            margin: 24px 0 36px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Estilo da sua logo PNG */
        .logo-image {
            max-width: 120px;
            height: auto;
            object-fit: contain;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
        }

        label {
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            display: block;
            margin-bottom: 8px;
        }

        /* ========== INPUTS ESTILO GOOGLE ========== */
        .google-input {
            width: 100%;
            padding: 16px 20px;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            color: #333;
            background: #fff;
            outline: none;
            box-shadow: 0 1px 6px rgba(32, 33, 36, 0.18);
            transition: box-shadow 0.2s ease;
            margin-bottom: 20px;
        }

        .google-input:focus {
            box-shadow: 0 2px 12px rgba(32, 33, 36, 0.28);
        }

        /* ========== DROPDOWN CUSTOMIZADO ========== */
        .custom-dropdown {
            position: relative;
            margin-bottom: 28px;
            z-index: 10; 
        }

        /* O "gatilho" (botão fechado) */
        .dropdown-trigger {
            width: 100%;
            padding: 16px 60px 16px 20px;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            color: #333;
            background: #fff;
            outline: none;
            box-shadow: 0 1px 6px rgba(32, 33, 36, 0.18);
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
            position: relative;
        }

        .dropdown-trigger:hover {
            box-shadow: 0 2px 12px rgba(32, 33, 36, 0.28);
        }

        /* Seta vermelha posicionada no final */
        .select-arrow {
            width: 20px;
            height: 20px;
            fill: #d62828;
            flex-shrink: 0;
            transition: transform 0.25s ease;
            
            position: absolute;
            right: 24px;
            top: 50%;
            transform: translateY(-50%);
        }

        .dropdown-trigger.active .select-arrow {
            transform: translateY(-50%) rotate(180deg);
        }

        /* A Lista de Opções */
        .dropdown-list {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            background: #fff;
            
            border-radius: 12px; 
            
            box-shadow: 0 4px 20px rgba(32, 33, 36, 0.25);
            overflow: hidden;
            
            opacity: 0;
            visibility: hidden;
            transform: scale(0.98);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 20;
        }

        .dropdown-list.open {
            opacity: 1;
            visibility: visible;
            transform: scale(1);
        }

        .dropdown-item {
            padding: 16px 20px;
            font-size: 16px;
            color: #333;
            cursor: pointer;
            transition: background 0.2s ease;
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
            border-radius: 0;
        }

        .dropdown-item:hover {
            background: #f5f5f5;
        }

        /* Item Selecionado - VERMELHO PASTEL CLARINHO */
        .dropdown-item.selected {
            background: #ffcdd2; 
            color: #b71c1c; 
            font-weight: 600;
        }

        /* Ícone de Check */
        .check-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 12px;
            flex-shrink: 0;
        }

        .dropdown-item.selected .check-icon {
            background: rgba(183, 28, 28, 0.15);
            color: #b71c1c;
        }
        
        .dropdown-item:not(.selected) .check-icon {
            opacity: 0;
        }

        /* ========== BOTÃO ENTRAR ========== */
        .divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.2);
            margin: 24px 0;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(4px);
            cursor: pointer;
            transition: all 0.2s ease;
            letter-spacing: 0.5px;
        }

        .btn-login:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo"><h1>FarmaCerta</h1></div>

        <!-- SUA LOGO PNG AQUI -->
        <div class="logo-image-container">
            <img src="assets/LOGO_2.png" alt="Logo FarmaCerta" class="logo-image">
        </div>

        <!-- FORMULÁRIO INTEGRADO COM PHP -->
        <form action="login.php" method="POST">
            <label>Login</label>
            <input type="text" name="login" class="google-input" required placeholder="Digite seu login">

            <label>Senha</label>
            <input type="password" name="senha" class="google-input" required placeholder="Digite sua senha">

            <label>Entrar como</label>
            
            <!-- Campo oculto que envia o valor real para o PHP -->
            <input type="hidden" name="cargo" id="cargoInput" value="">

            <!-- Dropdown Visual Customizado -->
            <div class="custom-dropdown" id="roleDropdown">
                <button type="button" class="dropdown-trigger" id="triggerBtn" onclick="toggleDropdown()">
                    <span id="selectedText">Selecione uma função</span>
                    <svg class="select-arrow" viewBox="0 0 24 24">
                        <path d="M7 10l5 5 5-5z"/>
                    </svg>
                </button>

                <div class="dropdown-list" id="dropdownList">
                    <div class="dropdown-item" onclick="selectOption(this, 'administrador', 'Administrador')">
                        <span class="check-icon">✓</span> Administrador
                    </div>
                    <div class="dropdown-item" onclick="selectOption(this, 'gerente', 'Gerente')">
                        <span class="check-icon">✓</span> Gerente
                    </div>
                    <div class="dropdown-item" onclick="selectOption(this, 'balconista', 'Balconista')">
                        <span class="check-icon">✓</span> Balconista
                    </div>
                </div>
            </div>

            <div class="divider"></div>
            <button type="submit" class="btn-login">ENTRAR</button>
        </form>
    </div>

    <script>
        const triggerBtn = document.getElementById('triggerBtn');
        const dropdownList = document.getElementById('dropdownList');
        const selectedText = document.getElementById('selectedText');
        const cargoInput = document.getElementById('cargoInput'); // Input hidden para o PHP

        function toggleDropdown() {
            const isOpen = dropdownList.classList.contains('open');
            
            if (isOpen) {
                closeDropdown();
            } else {
                triggerBtn.style.opacity = '0';
                triggerBtn.style.pointerEvents = 'none';
                dropdownList.classList.add('open');
            }
        }

        function closeDropdown() {
            dropdownList.classList.remove('open');
            setTimeout(() => {
                triggerBtn.style.opacity = '1';
                triggerBtn.style.pointerEvents = 'auto';
            }, 200);
        }

        function selectOption(element, value, label) {
            // Remove seleção anterior
            document.querySelectorAll('.dropdown-item').forEach(item => {
                item.classList.remove('selected');
            });

            // Aplica nova seleção visual
            element.classList.add('selected');
            
            // Atualiza texto do botão
            selectedText.textContent = label;
            
            // ATUALIZA O INPUT HIDDEN PARA O FORMULÁRIO PHP
            cargoInput.value = value;
            
            closeDropdown();
        }

        // Fecha ao clicar fora
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('roleDropdown');
            if (!dropdown.contains(e.target)) {
                closeDropdown();
            }
        });
    </script>
</body>
</html>