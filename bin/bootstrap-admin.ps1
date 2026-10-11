param([Parameter(Mandatory=$true)][string]$Username,[Parameter(Mandatory=$true)][string]$FullName,[string]$Php='')
$ErrorActionPreference='Stop'
$projectRoot=(Resolve-Path (Join-Path $PSScriptRoot '..')).Path
if (-not $Php) { $Php=Join-Path $projectRoot '.tools\php\php.exe' }
$first=Read-Host 'Initial Admin password (12-72 UTF-8 bytes)' -AsSecureString
$second=Read-Host 'Confirm password' -AsSecureString
$firstPointer=[Runtime.InteropServices.Marshal]::SecureStringToBSTR($first)
$secondPointer=[Runtime.InteropServices.Marshal]::SecureStringToBSTR($second)
try {
    $password=[Runtime.InteropServices.Marshal]::PtrToStringBSTR($firstPointer)
    $confirmation=[Runtime.InteropServices.Marshal]::PtrToStringBSTR($secondPointer)
    if ($password -cne $confirmation) { throw 'Passwords do not match.' }
    $OutputEncoding=New-Object System.Text.UTF8Encoding($false)
    $password | & $Php (Join-Path $PSScriptRoot 'bootstrap-admin.php') $Username $FullName --password-stdin
    if ($LASTEXITCODE -ne 0) { throw 'Admin bootstrap failed.' }
} finally {
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($firstPointer)
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($secondPointer)
    $password=$null; $confirmation=$null; $first.Dispose(); $second.Dispose()
}
