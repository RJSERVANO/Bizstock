param([Parameter(Mandatory=$true)][int]$UserId,[Parameter(Mandatory=$true)][string]$Reason,[string]$Php='')
$ErrorActionPreference='Stop'
$projectRoot=(Resolve-Path (Join-Path $PSScriptRoot '..')).Path
if (-not $Php) { $Php=Join-Path $projectRoot '.tools\php\php.exe' }
$first=Read-Host 'Recovery password (12-72 UTF-8 bytes)' -AsSecureString
$second=Read-Host 'Confirm password' -AsSecureString
$firstPointer=[Runtime.InteropServices.Marshal]::SecureStringToBSTR($first)
$secondPointer=[Runtime.InteropServices.Marshal]::SecureStringToBSTR($second)
try {
    $password=[Runtime.InteropServices.Marshal]::PtrToStringBSTR($firstPointer)
    $confirmation=[Runtime.InteropServices.Marshal]::PtrToStringBSTR($secondPointer)
    if ($password -cne $confirmation) { throw 'Passwords do not match.' }
    $OutputEncoding=New-Object System.Text.UTF8Encoding($false)
    $password | & $Php (Join-Path $PSScriptRoot 'recover-admin.php') $UserId $Reason --password-stdin
    if ($LASTEXITCODE -ne 0) { throw 'Admin recovery failed.' }
} finally {
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($firstPointer)
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($secondPointer)
    $password=$null; $confirmation=$null; $first.Dispose(); $second.Dispose()
}
