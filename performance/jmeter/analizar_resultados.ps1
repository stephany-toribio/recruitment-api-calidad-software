param([Parameter(Mandatory)][string]$Jtl, [string]$Audit, [string]$Output)
$ErrorActionPreference = 'Stop'
$launcher = Get-Command py -ErrorAction SilentlyContinue
$prefix = @('-3')
if (!$launcher) { $launcher = Get-Command python -ErrorAction Stop; $prefix = @() }
if (!$Output) { $Output = Join-Path (Split-Path $Jtl -Parent) 'metrics.json' }
$arguments = @($prefix) + @("$PSScriptRoot/analizar_resultados.py", '--jtl', $Jtl, '--output', $Output)
if ($Audit) { $arguments += @('--audit', $Audit) }
$messages = & $launcher.Source @arguments
if ($LASTEXITCODE -ne 0) { throw 'Analisis fallido. No se puede declarar CUMPLE.' }
$messages | ForEach-Object { Write-Host $_ }
Get-Content $Output -Raw -Encoding UTF8 | ConvertFrom-Json
