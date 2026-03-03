@echo off

:: Muda o diretório atual para a pasta onde o script está localizado.
:: Isso garante que todos os caminhos relativos (como 'public/router_dev.php') funcionem.
cd /d "%~dp0"

title Servidor Dubom Refrigeracao
color 0A
echo ==========================================
echo      INICIANDO SISTEMA DUBOM...
echo ==========================================
echo.
echo O servidor esta rodando!
echo NAO FECHE ESTA JANELA ENQUANTO USAR O SISTEMA.
echo.
echo Acessando: http://localhost:8000
echo.

:: Abre o navegador automaticamente
start http://localhost:8000

:: Inicia o servidor do PHP usando o roteador de desenvolvimento
php -S localhost:8000 -t public public/router_dev.php

pause