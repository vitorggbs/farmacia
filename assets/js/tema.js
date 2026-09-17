/**
 * FarmaCerta - Gerenciamento de Tema Escuro / Claro
 * Persistência automática via localStorage e sincronização em todo o site.
 */
(function () {
    'use strict';

    var CHAVE_STORAGE = 'farmacerta_tema';

    function obterTemaAtual() {
        var temaSalvo = localStorage.getItem(CHAVE_STORAGE);
        if (temaSalvo === 'dark' || temaSalvo === 'light') {
            return temaSalvo;
        }
        var docTema = document.documentElement.getAttribute('data-bs-theme');
        if (docTema === 'dark' || docTema === 'light') {
            return docTema;
        }
        return (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
    }

    function atualizarBotoes(tema) {
        var botoes = document.querySelectorAll('.btn-alternar-tema');
        var ehEscuro = tema === 'dark';

        botoes.forEach(function (btn) {
            var texto = btn.querySelector('.texto-tema');
            if (texto) {
                texto.textContent = ehEscuro ? 'Modo Claro' : 'Modo Escuro';
            }

            var label = ehEscuro
                ? 'Tema escuro ativo. Clique para modo claro'
                : 'Tema claro ativo. Clique para modo escuro';
            btn.setAttribute('aria-label', label);
            btn.setAttribute('title', label);
            btn.setAttribute('data-tema-ativo', tema);
        });
    }

    function aplicarTema(tema, salvar) {
        if (salvar !== false) {
            try {
                localStorage.setItem(CHAVE_STORAGE, tema);
            } catch (e) {
                // Silencia falha se storage desabilitado
            }
        }

        document.documentElement.setAttribute('data-bs-theme', tema);
        atualizarBotoes(tema);

        try {
            window.dispatchEvent(new CustomEvent('farmacerta:tema-alterado', { detail: { tema: tema } }));
        } catch (e) {}
    }

    function alternarTema() {
        var temaAtual = obterTemaAtual();
        var novoTema = temaAtual === 'dark' ? 'light' : 'dark';

        var botoes = document.querySelectorAll('.btn-alternar-tema');
        botoes.forEach(function (btn) {
            btn.classList.add('animando-clique');
        });

        aplicarTema(novoTema, true);

        setTimeout(function () {
            botoes.forEach(function (btn) {
                btn.classList.remove('animando-clique');
            });
        }, 450);
    }

    // Delegação de clique para todos os botões de alternância de tema
    document.addEventListener('click', function (evento) {
        var btn = evento.target.closest('.btn-alternar-tema');
        if (btn) {
            evento.preventDefault();
            alternarTema();
        }
    });

    // Sincroniza botões assim que o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            atualizarBotoes(obterTemaAtual());
        });
    } else {
        atualizarBotoes(obterTemaAtual());
    }

    // Expõe no objeto global para caso scripts externos queiram alternar programaticamente
    window.FarmaCertaTema = {
        obterTema: obterTemaAtual,
        aplicarTema: aplicarTema,
        alternarTema: alternarTema
    };
})();
