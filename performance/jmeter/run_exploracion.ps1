param([switch]$ConfirmoQAAislado,[int[]]$Niveles=@(10,25,50),[int]$Duration=300,[string]$JMeter='jmeter')
$ErrorActionPreference='Stop'
if (!$ConfirmoQAAislado) { throw 'Agrega -ConfirmoQAAislado. No se ejecuta contra produccion.' }
foreach ($level in $Niveles) { if ($level -notin @(10,25,50)) { throw 'Niveles permitidos: 10,25,50.' } }
foreach ($level in $Niveles) {
    $res=& "$PSScriptRoot/run_baseline.ps1" -Users $level -Duration $Duration -Name "explor_$level" -OnError stoptest -JMeter $JMeter -ConfirmoQAAislado
    if ($res.unexpected_errors -gt 0 -or $res.setup_errors -gt 0 -or
        $null -eq $res.error_pct -or $res.error_pct -gt 5 -or
        $null -eq $res.latency.read.p95_ms -or $res.latency.read.p95_ms -gt 4000 -or
        $null -eq $res.latency.login.p95_ms -or $res.latency.login.p95_ms -gt 6000 -or
        $null -eq $res.latency.write.p95_ms -or $res.latency.write.p95_ms -gt 6000 -or
        $res.backend_integrity -ne 'VERIFICADA' -or $res.duplicate_successful_pairs -gt 0) {
        Write-Warning "PARADA en $level VU: error inesperado, integridad o limite de exploracion. No se escala."
        break
    }
}
