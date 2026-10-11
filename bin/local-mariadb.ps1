param([ValidateSet('start','stop')][string]$Action = 'start')
$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$instanceRoot = Join-Path $projectRoot '.tools\mariadb'
$settings = Join-Path $instanceRoot 'server.ini'
if (-not (Test-Path -LiteralPath $settings)) { throw 'Run bin/init-local-mariadb.php first.' }
if ($Action -eq 'stop') {
    & 'C:\xampp\mysql\bin\mysqladmin.exe' "--defaults-file=$instanceRoot\admin.ini" shutdown
    if ($LASTEXITCODE -ne 0) { throw 'Local instance shutdown failed.' }
    Write-Output 'Workspace-local MariaDB stopped.'
} else {
    $listener = Get-NetTCPConnection -LocalPort 13307 -State Listen -ErrorAction SilentlyContinue
    if ($listener) { throw 'Port 13307 is already in use; no additional instance started.' }
    $process = Start-Process -FilePath 'C:\xampp\mysql\bin\mysqld.exe' -ArgumentList "--defaults-file=`"$settings`"" -WorkingDirectory $instanceRoot -WindowStyle Hidden -PassThru
    Write-Output "Workspace-local MariaDB process started (PID $($process.Id)), 127.0.0.1:13307."
}
