$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot

if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    Write-Host "PHP не найден. Установите: winget install PHP.PHP.8.3"
    exit 1
}

if (-not (Test-Path "data/feedback.db")) {
    php scripts/seed-articles.php
}

Write-Host "Сайт: http://localhost:8080/"
Write-Host "Статьи: http://localhost:8080/articles.php"
Write-Host "Для остановки нажмите Ctrl+C"
php -S localhost:8080
