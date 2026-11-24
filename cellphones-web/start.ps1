[CmdletBinding()]
param(
    [ValidateRange(1024, 65535)]
    [int]$Port = 8000
)

$requestedPort = $Port
$existingUrl = "http://127.0.0.1:$Port"
try {
    $existing = Invoke-WebRequest -UseBasicParsing -TimeoutSec 2 $existingUrl
    if ($existing.StatusCode -eq 200 -and $existing.Content -match 'CellphoneS') {
        Write-Host "CellphoneS is already running at $existingUrl. Reusing the existing container."
        exit 0
    }
} catch {
    # Không có dịch vụ trên cổng mặc định, tiếp tục khởi động server.
}

while (Get-NetTCPConnection -State Listen -LocalPort $Port -ErrorAction SilentlyContinue) {
    $Port++
    if ($Port -gt 65535) {
        throw 'No free port was found for the website.'
    }
}

$serverUrl = "http://127.0.0.1:$Port"
if ($Port -ne $requestedPort) {
    Write-Host "Port $requestedPort is busy. Starting the website on port $Port."
}
$php = Get-Command php.exe -CommandType Application -ErrorAction SilentlyContinue | Select-Object -First 1
if ($php) {
    Write-Host "CellphoneS: $serverUrl (PHP). Press Ctrl+C to stop."
    & $php.Source -S "127.0.0.1:$Port" -t $PSScriptRoot
    exit $LASTEXITCODE
}

$docker = Get-Command docker.exe -CommandType Application -ErrorAction SilentlyContinue | Select-Object -First 1
if (-not $docker) {
    throw 'PHP is not in PATH and Docker is unavailable. Install PHP 8.1+ or start Docker Desktop.'
}

& $docker.Source info --format '{{.ServerVersion}}' 2>$null | Out-Null
if ($LASTEXITCODE -ne 0) {
    throw 'Docker Engine is offline. Start Docker Desktop and run this script again.'
}

Write-Host "CellphoneS: $serverUrl (Docker PHP 8.3). Press Ctrl+C to stop."
& $docker.Source run --rm --publish "127.0.0.1:${Port}:8000" --volume "${PSScriptRoot}:/app" php:8.3-cli php -S 0.0.0.0:8000 -t /app
exit $LASTEXITCODE
