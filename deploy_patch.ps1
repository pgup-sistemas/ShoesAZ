<#
.SYNOPSIS
  Cria um pacote (.zip) com os arquivos modificados prontos para enviar ao servidor.

.DESCRIPTION
  - Tenta detectar alterações via `git` (staged/unstaged/untracked). Se não houver git ou mudanças,
    usa a lista fixa de arquivos alterados nesta correção.
  - Gera um ZIP em `./deploy/patch_YYYYMMDD_HHMMSS.zip` pronto para upload.
  - Opcional: envia automaticamente via `scp` se informar -UploadHost/-UploadUser/-UploadPath.

.EXAMPLES
  # Só criar o ZIP
  .\deploy_patch.ps1

  # Criar ZIP e enviar via SCP
  .\deploy_patch.ps1 -UploadHost 1.2.3.4 -UploadUser deploy -UploadPath '/var/www/ShoesAZ'
#>

param(
    [string] $UploadHost = '',
    [string] $UploadUser = '',
    [string] $UploadPath = ''
)

Set-StrictMode -Version Latest
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Definition
Push-Location $scriptDir

# Lista padrão (arquivos alterados por esta tarefa)
$manifest = @(
    'app/Controllers/AuthController.php',
    'app/Views/dashboard/index.php',
    'public/index.php'
)

# Tentar detectar alterações via git (se disponível)
$filesToInclude = @()
$git = Get-Command git -ErrorAction SilentlyContinue
if ($git) {
    $status = git status --porcelain 2>$null
    if ($status) {
        $status -split "`n" | ForEach-Object {
            $line = $_.Trim()
            if ($line -ne '') {
                # formatação: XY <path>
                $p = $line.Substring(3).Trim()
                if ($p) { $filesToInclude += $p }
            }
        }
    }
}

if ($filesToInclude.Count -eq 0) {
    Write-Host "Nenhuma alteração detectada via git — usando lista padrão (manifest)."
    $filesToInclude = $manifest
}

# Filtrar apenas arquivos que existem
$existing = $filesToInclude | Where-Object { Test-Path $_ }
$missing = $filesToInclude | Where-Object { -not (Test-Path $_) }
if ($missing.Count -gt 0) {
    Write-Warning "Arquivos não encontrados (serão ignorados):`n$($missing -join "`n")"
}

if ($existing.Count -eq 0) {
    Write-Error "Nenhum arquivo válido para empacotar. Abortando."
    Pop-Location
    exit 1
}

$deployDir = Join-Path $scriptDir 'deploy'
if (-not (Test-Path $deployDir)) { New-Item -ItemType Directory -Path $deployDir | Out-Null }
$timestamp = Get-Date -Format 'yyyyMMdd_HHmmss'
$zipPath = Join-Path $deployDir "patch_${timestamp}.zip"

Compress-Archive -Path $existing -DestinationPath $zipPath -Force

Write-Host "Pacote criado: $zipPath"
Write-Host "Arquivos incluídos:`n  $($existing -join "`n  ")"

# Upload opcional via scp
if ($UploadHost -and $UploadUser -and $UploadPath) {
    $scp = Get-Command scp -ErrorAction SilentlyContinue
    if (-not $scp) {
        Write-Warning "scp não encontrado no PATH. Não foi possível enviar automaticamente."
        Pop-Location
        exit 0
    }
    $remote = "$UploadUser@$UploadHost:$UploadPath/$(Split-Path $zipPath -Leaf)"
    Write-Host "Enviando $zipPath -> $remote"
    scp $zipPath $remote
    if ($LASTEXITCODE -eq 0) { Write-Host "Upload concluído com sucesso." } else { Write-Warning "Erro ao enviar (scp retornou código $LASTEXITCODE)." }
}

Pop-Location
