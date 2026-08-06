@echo off

:: Muda o diretório atual para a pasta onde o script está localizado.
cd /d "%~dp0"

title Servidor Dubom Refrigeracao
color 0A
echo ==========================================
echo      INICIANDO SISTEMA DUBOM...
echo ==========================================
echo.

:: Tenta localizar o executável do PHP
set "PHP_CMD=php"

where php >nul 2>&1
if %errorlevel% neq 0 (
    if exist "C:\xampp\php\php.exe" (
        set "PHP_CMD=C:\xampp\php\php.exe"
    ) else (
        echo [ERRO] PHP nao foi encontrado no sistema nem no XAMPP!
        echo.
        pause
        exit /b 1
    )
)

echo O servidor esta rodando!
echo NAO FECHE ESTA JANELA ENQUANTO USAR O SISTEMA.
echo.
echo Acessando: http://localhost:8000
echo.

:: Abre o navegador automaticamente
start http://localhost:8000

:: Inicia o servidor do PHP usando o roteador de desenvolvimento
"%PHP_CMD%" -S localhost:8000 -t public public/router_dev.php

pause