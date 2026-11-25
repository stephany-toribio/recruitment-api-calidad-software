param(
    [ValidateRange(3,50)][int]$Users=25,
    [ValidateRange(1,300)][int]$RampUp=30,
    [ValidateRange(10,3600)][int]$Duration=300,
    [ValidatePattern('^[a-zA-Z0-9_-]+$')][string]$Name='baseline',
    [ValidateSet('continue','stopthread','stoptest')][string]$OnError='stopthread',
    [string]$JMeter='jmeter',
    [switch]$NegativeCases,
    [switch]$ConfirmoQAAislado,
    [switch]$SinAnalisis
)
$ErrorActionPreference='Stop'
if (!$ConfirmoQAAislado) { throw 'Agrega -ConfirmoQAAislado para confirmar el entorno local de QA.' }
if ($Duration -le $RampUp) { throw 'Duration debe ser mayor que RampUp.' }
. "$PSScriptRoot/config.ps1"
if ($Cfg.Host -notin @('127.0.0.1','localhost') -or $Cfg.Protocol -ne 'http' -or [int]$Cfg.Port -ne 8002) {
    throw 'Este runner esta limitado al QA local HTTP en el puerto 8002.'
}
$read=[int][math]::Round($Users*0.6)
$login=[math]::Max(1,[int][math]::Round($Users*0.16))
$apply=$Users-$read-$login
if ($apply -lt 1) { $read--; $apply=1 }
$counts=@{ lectura=$read; login=$login; postulacion=$apply }
$emails=[System.Collections.Generic.HashSet[string]]::new([System.StringComparer]::OrdinalIgnoreCase)
$csvHashes=@{}
foreach ($loadProfile in @('lectura','login','postulacion')) {
    $file=Join-Path $PSScriptRoot "data/usuarios_$loadProfile.csv"
    if (!(Test-Path $file)) { throw "Falta CSV del perfil $loadProfile." }
    $rows=@(Import-Csv $file)
    if ($rows.Count -lt $counts[$loadProfile]) { throw "CSV insuficiente para $loadProfile." }
    foreach ($row in $rows) {
        if ($row.correo -notmatch "^${loadProfile}[0-9]{3}@carga\.qa\.invalid$" -or !$row.password -or $row.password -match '["\\\r\n]') {
            throw "Fila invalida en CSV $loadProfile. Se requieren cuentas ficticias y valores compatibles con JSON."
        }
        if (!$emails.Add($row.correo)) { throw 'Hay cuentas repetidas entre CSV o filas.' }
    }
    $csvHashes[$loadProfile]=(Get-FileHash $file -Algorithm SHA256).Hash
}
$command=Get-Command $JMeter -ErrorAction SilentlyContinue
if ($command) { $JMeter=$command.Source }
elseif (!(Test-Path $JMeter)) {
    $candidates=@(
        Get-Item "$env:USERPROFILE/Downloads/apache-jmeter*/bin/jmeter.bat" -ErrorAction SilentlyContinue
        Get-Item "$env:USERPROFILE/OneDrive/Documentos/Herramientas/apache-jmeter*/bin/jmeter.bat" -ErrorAction SilentlyContinue
    )
    if ($candidates.Count -ne 1) { throw 'Indica la ruta: -JMeter C:\ruta\apache-jmeter\bin\jmeter.bat' }
    $JMeter=$candidates[0].FullName
}
$JMeter=(Get-Item $JMeter).FullName
$containerId=docker compose ps -q app
if ($LASTEXITCODE -ne 0 -or !$containerId) { throw 'No se encontro el servicio app. Ejecutar desde la raiz del backend.' }
$inspection=@(docker inspect $containerId | ConvertFrom-Json)[0]
if ($LASTEXITCODE -ne 0) { throw 'No se pudo inspeccionar el servicio app.' }
$port=@($inspection.NetworkSettings.Ports.'8000/tcp' | Where-Object { [int]$_.HostPort -eq 8002 })
if (!$port.Count) { throw 'El servicio app no publica su puerto 8000 en 8002.' }
$guard=@'
<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db=Illuminate\Support\Facades\DB::connection();
if ($app->configurationIsCached() || !$app->environment('local') || $db->getDriverName()!=='mysql' || $db->getConfig('host')!=='mysql' || $db->getDatabaseName()!=='portal_empleos_calidad_qa') { fwrite(STDERR,"Entorno QA no autorizado\n"); exit(1); }
echo "QA local aislado confirmado\n";
'@
$guard | docker compose exec -T app php
if ($LASTEXITCODE -ne 0) { throw 'Fallo la verificacion del entorno QA.' }
docker compose exec -T app php -l performance/jmeter/verificar_integridad.php
if ($LASTEXITCODE -ne 0) { throw 'El auditor PHP no pasa la comprobacion de sintaxis.' }
$runId=[guid]::NewGuid().ToString()
$folder=Join-Path $PSScriptRoot ("results/{0}-{1}-{2}" -f $Name,(Get-Date -Format 'yyyyMMdd-HHmmss'),$runId.Substring(0,8))
New-Item -ItemType Directory -Path $folder -Force | Out-Null
$jtl=Join-Path $folder 'samples.jtl'
$manifest=[ordered]@{
    run_id=$runId; started=(Get-Date -Format o); name=$Name; users=$Users
    profiles=$counts; ramp_up_s=$RampUp; duration_s=$Duration; think_time_ms=@(1000,3000)
    negative_cases=[bool]$NegativeCases; target="http://$($Cfg.Host):$($Cfg.Port)"
    on_error=$OnError; commit=(git rev-parse HEAD); branch=(git branch --show-current)
    jmx_sha256=(Get-FileHash "$PSScriptRoot/plan_carga.jmx" -Algorithm SHA256).Hash
    csv_sha256=$csvHashes; mounts=@($inspection.Mounts | Select-Object Source,Destination,Type)
    app_limits=($inspection.HostConfig | Select-Object NanoCpus,Memory,MemorySwap,CpuQuota,CpuPeriod)
    resources=@(docker info --format '{{.NCPU}} CPUs; {{.MemTotal}} bytes RAM Docker')
    note='Duracion incluye ramp-up; percentiles incluyen arranque. CLI, sin listeners graficos. SinAnalisis se conserva solo por compatibilidad; la auditoria siempre se ejecuta.'
}
$manifest | ConvertTo-Json -Depth 8 | Set-Content (Join-Path $folder 'manifest.json') -Encoding UTF8
& $JMeter -v 2>&1 | Out-File (Join-Path $folder 'jmeter-version.txt') -Encoding utf8
if ($LASTEXITCODE -ne 0) { throw 'JMeter no pudo iniciar. Revisar Java y jmeter-version.txt.' }
docker stats --no-stream $containerId calidad_software-mysql-1 2>&1 | Out-File (Join-Path $folder 'resources-before.txt') -Encoding utf8
Write-Host "Ejecutando ${Name}: $Users VU ($read/$login/$apply), ${RampUp}s ramp-up, ${Duration}s total."
$arguments=@(
    '-n','-t',"$PSScriptRoot/plan_carga.jmx",'-l',$jtl,'-j',(Join-Path $folder 'jmeter.log'),
    "-Jhost=$($Cfg.Host)","-Jport=$($Cfg.Port)","-Jprotocol=$($Cfg.Protocol)",
    "-Jusers_read=$read","-Jusers_login=$login","-Jusers_apply=$apply",
    "-Jrampup=$RampUp","-Jduration=$Duration","-Jonerror=$OnError",
    "-Jdatadir=$PSScriptRoot/data","-Jrun_id=$runId",
    "-Jnegative_cases=$($NegativeCases.IsPresent.ToString().ToLowerInvariant())",
    '-Jsample_variables=qa_run_id,qa_profile,qa_vu,qa_user_id,qa_job_id,qa_app_id',
    '-Jhttpclient4.retrycount=0','-Jjmeter.save.saveservice.output_format=csv','-Jjmeter.save.saveservice.print_field_names=true',
    '-Jjmeter.save.saveservice.timestamp_format=ms','-Jjmeter.save.saveservice.thread_counts=true',
    '-Jjmeter.save.saveservice.response_data=false','-Jjmeter.save.saveservice.response_data.on_error=false',
    '-Jjmeter.save.saveservice.samplerData=false','-Jjmeter.save.saveservice.requestHeaders=false',
    '-Jjmeter.save.saveservice.responseHeaders=false'
)
& $JMeter @arguments 2>&1 | Tee-Object -FilePath (Join-Path $folder 'console.txt') | Out-Host
$jmeterExit=$LASTEXITCODE
$manifest['jmeter_exit_code']=$jmeterExit
$manifest['finished']=(Get-Date -Format o)
$manifest | ConvertTo-Json -Depth 8 | Set-Content (Join-Path $folder 'manifest.json') -Encoding UTF8
docker stats --no-stream $containerId calidad_software-mysql-1 2>&1 | Out-File (Join-Path $folder 'resources-after.txt') -Encoding utf8
if (!(Test-Path $jtl)) { throw "JMeter no genero JTL. Revisar $folder" }
$metrics=& "$PSScriptRoot/analizar_resultados.ps1" -Jtl $jtl
$audit=Join-Path $folder 'backend-integrity.json'
if ($metrics.successful_creates -gt 0 -and $metrics.client_ids_available) {
    $raw=Get-Content (Join-Path $folder 'expected-integrity.json') -Raw -Encoding UTF8 |
        docker compose exec -T app php performance/jmeter/verificar_integridad.php
    $auditExit=$LASTEXITCODE
    $raw | Set-Content $audit -Encoding UTF8
    if ($auditExit -ne 0) { Write-Warning 'Auditoria de BD fallida. Consultar backend-integrity.json.' }
} else {
    @{success=$false;checked=0;error='No hay escrituras con IDs completos para auditar.'} |
        ConvertTo-Json | Set-Content $audit -Encoding UTF8
}
$result=& "$PSScriptRoot/analizar_resultados.ps1" -Jtl $jtl -Audit $audit
if ($jmeterExit -ne 0) { Write-Warning "JMeter termino con codigo $jmeterExit; corrida incompleta." }
& $JMeter -g $jtl -o (Join-Path $folder 'html') -j (Join-Path $folder 'dashboard.log') 2>&1 |
    Out-File (Join-Path $folder 'dashboard-console.txt') -Encoding utf8
if ($LASTEXITCODE -ne 0) { Write-Warning 'No se pudo generar el dashboard. Los resultados JTL y metrics.json se conservaron.' }
Write-Host "RESULTADOS: $folder"
$result | Add-Member -NotePropertyName RunDir -NotePropertyValue $folder -PassThru
