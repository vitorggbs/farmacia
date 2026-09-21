/**
 * FarmaCerta - Componente de Seleção Customizado Global
 * Transforma todos os <select> da aplicação no componente visual da marca:
 * - Botão em formato de pílula com chevron, com cores idênticas aos demais campos nos temas claro e escuro
 * - Card flutuante branco com cantos bem arredondados (18px)
 * - Posicionado exatamente no centro/local da seleção (imagem 4 - lado direito)
 * - Exibe apenas as opções reais (oculta o placeholder "Selecione..." quando há opções válidas)
 * - Sem barra de rolagem lateral
 * - Navegação completa por teclado com setas (mantém aberto enquanto navega) e Enter para confirmar
 * - 100% de compatibilidade com submissão de formulários e eventos onchange
 */
(function () {
    'use strict';

    var CHEVRON_SVG = '<svg class="fc-custom-select-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>';

    function fecharTodosMenus(exceto) {
        var abertos = document.querySelectorAll('.fc-select-wrapper.is-open');
        abertos.forEach(function (wrapper) {
            if (wrapper !== exceto) {
                wrapper.classList.remove('is-open');
                var trigger = wrapper.querySelector('.fc-custom-select-trigger');
                if (trigger) {
                    trigger.setAttribute('aria-expanded', 'false');
                }
            }
        });
    }

    function ehPlaceholder(opt) {
        if (!opt) return true;
        var val = (opt.value !== undefined && opt.value !== null) ? opt.value.trim() : '';
        var txt = (opt.text || '').trim().toLowerCase();

        // É placeholder se o valor for vazio ou texto for instrução de seleção
        if (val === '') return true;
        if (txt.indexOf('selecione') === 0 || txt.indexOf('escolha') === 0) {
            if (val === '0' || val === '' || val === '-1') return true;
        }
        return false;
    }

    function inicializarSelect(selectEl) {
        if (!selectEl || selectEl.dataset.fcCustomInitialized === 'true') {
            return;
        }

        // Ignora múltiplos e selects desabilitados propositalmente
        if (selectEl.multiple || (selectEl.size && selectEl.size > 1) || selectEl.getAttribute('data-no-custom') === 'true') {
            return;
        }

        selectEl.dataset.fcCustomInitialized = 'true';

        // Cria o container do componente
        var wrapper = document.createElement('div');
        wrapper.className = 'fc-select-wrapper';
        if (selectEl.id) {
            wrapper.setAttribute('data-target-id', selectEl.id);
        }

        // Insere o wrapper logo antes do select e move o select para dentro do wrapper
        selectEl.parentNode.insertBefore(wrapper, selectEl);
        wrapper.appendChild(selectEl);

        // Oculta o select nativo preservando validação e envio
        selectEl.classList.add('fc-native-select-hidden');
        selectEl.tabIndex = -1;

        // Cria o botão gatilho (Pílula)
        var trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'fc-custom-select-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');

        var textSpan = document.createElement('span');
        textSpan.className = 'fc-custom-select-text';

        // Obtém o texto inicial
        var optInicial = selectEl.options[selectEl.selectedIndex] || selectEl.options[0];
        textSpan.textContent = optInicial ? optInicial.text : 'Selecione...';

        trigger.appendChild(textSpan);
        trigger.insertAdjacentHTML('beforeend', CHEVRON_SVG);
        wrapper.appendChild(trigger);

        // Cria o card de opções
        var menu = document.createElement('div');
        menu.className = 'fc-custom-select-menu';
        menu.setAttribute('role', 'listbox');
        wrapper.appendChild(menu);

        var highlightIndex = 0;

        function obterOpcoesParaExibicao() {
            var todas = selectEl.options;
            var validas = [];
            for (var i = 0; i < todas.length; i++) {
                if (!ehPlaceholder(todas[i])) {
                    validas.push({ index: i, option: todas[i] });
                }
            }
            // Se houver opções reais, exibe apenas elas.
            // Se NÃO houver nenhuma opção real, exibe o que estiver disponível (placeholder ou aviso)
            if (validas.length > 0) {
                return validas;
            }

            var fallback = [];
            for (var j = 0; j < todas.length; j++) {
                fallback.push({ index: j, option: todas[j] });
            }
            return fallback;
        }

        function atualizarDestaqueVisual(itens) {
            if (!itens || itens.length === 0) return;
            if (highlightIndex < 0) highlightIndex = 0;
            if (highlightIndex >= itens.length) highlightIndex = itens.length - 1;

            itens.forEach(function (it, idx) {
                if (idx === highlightIndex) {
                    it.classList.add('is-highlighted');
                    it.classList.add('is-selected');
                    it.setAttribute('aria-selected', 'true');
                    it.scrollIntoView({ block: 'nearest' });
                } else {
                    it.classList.remove('is-highlighted');
                    it.classList.remove('is-selected');
                    it.setAttribute('aria-selected', 'false');
                }
            });
        }

        function reconstruirOpcoes() {
            menu.innerHTML = '';
            var opcoesExibir = obterOpcoesParaExibicao();

            if (opcoesExibir.length === 0) {
                var vazioEl = document.createElement('div');
                vazioEl.className = 'fc-custom-select-option';
                vazioEl.style.opacity = '0.6';
                vazioEl.style.pointerEvents = 'none';
                vazioEl.textContent = 'Nenhuma opção disponível';
                menu.appendChild(vazioEl);
                return;
            }

            highlightIndex = 0;
            var encontrouSelecionado = false;

            opcoesExibir.forEach(function (itemObj, viewIdx) {
                var realIdx = itemObj.index;
                var opt = itemObj.option;

                var optBtn = document.createElement('div');
                optBtn.className = 'fc-custom-select-option';
                optBtn.setAttribute('role', 'option');
                optBtn.setAttribute('data-value', opt.value);
                optBtn.setAttribute('data-real-index', realIdx.toString());
                optBtn.setAttribute('data-view-index', viewIdx.toString());
                optBtn.textContent = opt.text;

                if (realIdx === selectEl.selectedIndex) {
                    optBtn.classList.add('is-selected');
                    optBtn.classList.add('is-highlighted');
                    optBtn.setAttribute('aria-selected', 'true');
                    highlightIndex = viewIdx;
                    encontrouSelecionado = true;
                } else {
                    optBtn.setAttribute('aria-selected', 'false');
                }

                if (opt.disabled) {
                    optBtn.style.opacity = '0.5';
                    optBtn.style.pointerEvents = 'none';
                }

                // Hover do mouse atualiza o item em foco
                optBtn.addEventListener('mouseenter', function () {
                    highlightIndex = parseInt(this.getAttribute('data-view-index'), 10);
                    var todosItens = menu.querySelectorAll('.fc-custom-select-option');
                    atualizarDestaqueVisual(todosItens);
                });

                // Clique do mouse seleciona e fecha
                optBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var rIdx = parseInt(this.getAttribute('data-real-index'), 10);
                    selecionarOpcaoPorIndiceReal(rIdx);
                });

                menu.appendChild(optBtn);
            });

            // Se a opção selecionada for o placeholder, destaca a primeira opção real ao abrir
            if (!encontrouSelecionado && opcoesExibir.length > 0) {
                highlightIndex = 0;
                var todosItens = menu.querySelectorAll('.fc-custom-select-option');
                if (todosItens.length > 0) {
                    todosItens[0].classList.add('is-selected');
                    todosItens[0].classList.add('is-highlighted');
                }
            }
        }

        function selecionarOpcaoPorIndiceReal(realIndex) {
            if (realIndex < 0 || realIndex >= selectEl.options.length) return;

            selectEl.selectedIndex = realIndex;
            var novaOpt = selectEl.options[realIndex];
            textSpan.textContent = novaOpt.text;

            // Fecha menu
            wrapper.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
            trigger.focus();

            // Dispara evento nativo 'change' e 'input' no <select>
            var changeEvent = new Event('change', { bubbles: true });
            selectEl.dispatchEvent(changeEvent);

            var inputEvent = new Event('input', { bubbles: true });
            selectEl.dispatchEvent(inputEvent);

            // Dispara onchange inline caso exista
            if (typeof selectEl.onchange === 'function') {
                selectEl.onchange(changeEvent);
            }
        }

        function abrirMenu() {
            fecharTodosMenus(wrapper);
            reconstruirOpcoes();
            wrapper.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');

            // Posiciona foco visual
            var itens = menu.querySelectorAll('.fc-custom-select-option');
            atualizarDestaqueVisual(itens);
        }

        function fecharMenu() {
            wrapper.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
        }

        // Alterna abrir/fechar ao clicar no gatilho
        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (wrapper.classList.contains('is-open')) {
                fecharMenu();
            } else {
                abrirMenu();
            }
        });

        // Suporte completo a navegação por teclado (Setas, Enter, Espaço, Escape)
        trigger.addEventListener('keydown', function (e) {
            var estaAberto = wrapper.classList.contains('is-open');
            var itens = menu.querySelectorAll('.fc-custom-select-option:not([style*="pointer-events: none"])');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!estaAberto) {
                    abrirMenu();
                } else if (itens.length > 0) {
                    highlightIndex = Math.min(itens.length - 1, highlightIndex + 1);
                    atualizarDestaqueVisual(itens);
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (!estaAberto) {
                    abrirMenu();
                } else if (itens.length > 0) {
                    highlightIndex = Math.max(0, highlightIndex - 1);
                    atualizarDestaqueVisual(itens);
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (!estaAberto) {
                    abrirMenu();
                } else if (itens.length > 0 && itens[highlightIndex]) {
                    var rIdx = parseInt(itens[highlightIndex].getAttribute('data-real-index'), 10);
                    selecionarOpcaoPorIndiceReal(rIdx);
                }
            } else if (e.key === ' ') {
                e.preventDefault();
                if (!estaAberto) {
                    abrirMenu();
                } else if (itens.length > 0 && itens[highlightIndex]) {
                    var rIdxSpace = parseInt(itens[highlightIndex].getAttribute('data-real-index'), 10);
                    selecionarOpcaoPorIndiceReal(rIdxSpace);
                }
            } else if (e.key === 'Escape') {
                if (estaAberto) {
                    e.preventDefault();
                    fecharMenu();
                }
            } else if (e.key === 'Tab') {
                if (estaAberto) {
                    fecharMenu();
                }
            }
        });

        // Se o select nativo for modificado via JS externo
        selectEl.addEventListener('change', function () {
            var curIdx = selectEl.selectedIndex;
            var opt = selectEl.options[curIdx];
            if (opt) {
                textSpan.textContent = opt.text;
            }
        });

        // Se o formulário pai for resetado
        if (selectEl.form) {
            selectEl.form.addEventListener('reset', function () {
                setTimeout(function () {
                    var curIdx = selectEl.selectedIndex;
                    var opt = selectEl.options[curIdx] || selectEl.options[0];
                    if (opt) {
                        textSpan.textContent = opt.text;
                    }
                    reconstruirOpcoes();
                }, 50);
            });

            // Se o campo for inválido na validação nativa HTML5
            selectEl.addEventListener('invalid', function () {
                trigger.focus();
                trigger.style.borderColor = '#ef4444';
            });
        }
    }

    function inicializarTodosSelects() {
        var selects = document.querySelectorAll('select.form-select, select:not([class*="custom-"]):not(.fc-native-select-hidden)');
        selects.forEach(function (sel) {
            inicializarSelect(sel);
        });
    }

    // Fecha os menus ao clicar fora
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.fc-select-wrapper')) {
            fecharTodosMenus();
        }
    });

    // Fecha ao pressionar Escape globalmente
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            fecharTodosMenus();
        }
    });

    // Inicializa no carregamento do documento
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializarTodosSelects);
    } else {
        inicializarTodosSelects();
    }

    // Observa elementos adicionados dinamicamente (ex: modais, AJAX)
    if (window.MutationObserver) {
        var observer = new MutationObserver(function (mutations) {
            var precisaInicializar = false;
            mutations.forEach(function (mutation) {
                if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                    for (var i = 0; i < mutation.addedNodes.length; i++) {
                        var node = mutation.addedNodes[i];
                        if (node.nodeType === 1) {
                            if (node.matches && (node.matches('select.form-select') || node.matches('select'))) {
                                precisaInicializar = true;
                                break;
                            }
                            if (node.querySelector && node.querySelector('select')) {
                                precisaInicializar = true;
                                break;
                            }
                        }
                    }
                }
            });
            if (precisaInicializar) {
                inicializarTodosSelects();
            }
        });

        observer.observe(document.body || document.documentElement, {
            childList: true,
            subtree: true
        });
    }

    // Expõe no objeto global FarmaCertaSelect para uso programático
    window.FarmaCertaSelect = {
        inicializar: inicializarTodosSelects,
        inicializarElemento: inicializarSelect,
        fecharTodos: fecharTodosMenus
    };
})();
