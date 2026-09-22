$ErrorActionPreference = "Stop"

$Root = Split-Path $PSScriptRoot -Parent
$FtpHost = "p668753.ispmgr.mchost.ru"
$FtpUser = "p668753"
$FtpPass = $env:INTELAV_FTP_PASS
$RemoteBase = if ($env:INTELAV_FTP_REMOTE) { $env:INTELAV_FTP_REMOTE } else { "www/uchet.info" }

if ([string]::IsNullOrWhiteSpace($FtpPass)) {
    Write-Host "Set INTELAV_FTP_PASS environment variable before deploy."
    exit 1
}

function Upload-FtpFile {
    param(
        [string]$LocalPath,
        [string]$RemotePath
    )

    $remoteUrl = "ftp://${FtpHost}/${RemoteBase}/${RemotePath}"
    & curl.exe -s --ftp-pasv --ftp-create-dirs -u "${FtpUser}:${FtpPass}" -T $LocalPath $remoteUrl | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw "Upload failed: $RemotePath"
    }
    Write-Host "Uploaded $RemotePath"
}

$excludePatterns = @(
    "\.git\\",
    "\\data\\.*\.db$",
    "\\config\.local\.php$",
    "\\config\.hosting\.example\.php$",
    "\\deploy-ftp\.ps1$",
    "\\_github_index\.html$"
)

$files = Get-ChildItem $Root -Recurse -File | Where-Object {
    $path = $_.FullName
    foreach ($pattern in $excludePatterns) {
        if ($path -match $pattern) { return $false }
    }
    return $true
}

foreach ($file in $files) {
    $relative = $file.FullName.Substring($Root.Length + 1).Replace("\", "/")
    Upload-FtpFile -LocalPath $file.FullName -RemotePath $relative
}

$localConfigPath = Join-Path $Root "config.local.php"
if (-not (Test-Path $localConfigPath)) {
    throw "config.local.php not found"
}

$configText = Get-Content $localConfigPath -Raw -Encoding UTF8
$configText = $configText -replace "'db_host'\s*=>\s*'[^']*'", "'db_host' => 'localhost'"
$serverConfigPath = Join-Path $env:TEMP "intelav-config.local.php"
[System.IO.File]::WriteAllText($serverConfigPath, $configText, (New-Object System.Text.UTF8Encoding $false))
Upload-FtpFile -LocalPath $serverConfigPath -RemotePath "config.local.php"

$githubIndex = Join-Path $Root "_github_index.html"
if (Test-Path $githubIndex) {
    Upload-FtpFile -LocalPath $githubIndex -RemotePath "_github_index.html"
}

Write-Host "Deploy complete."
