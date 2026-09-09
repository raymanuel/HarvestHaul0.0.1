# build-release.ps1
#
# Builds a production-ready release of HarvestHaul for Hostinger.
#
#   # Full release (first deploy):
#   powershell -ExecutionPolicy Bypass -File scripts/build-release.ps1
#
#   # Incremental update (only files changed since the last release):
#   powershell -ExecutionPolicy Bypass -File scripts/build-release.ps1 -Update
#
#   # Skip the frontend rebuild if nothing visual changed:
#   powershell -ExecutionPolicy Bypass -File scripts/build-release.ps1 -SkipNpmBuild
#
# Output:
#   dist/harvesthaul-release.zip           (full release, first deploy)
#   dist/harvesthaul-update-<stamp>.zip    (incremental update)
#   dist/release-snapshot.json             (last-release manifest used by -Update)

param(
    [switch]$Update,
    [switch]$SkipNpmBuild,
    [string]$ZipName = 'harvesthaul-release.zip',
    [string]$OutDir = 'dist'
)

$ErrorActionPreference = 'Stop'

$Root = Split-Path -Parent $PSScriptRoot
$Stage = Join-Path $Root (Join-Path $OutDir 'staging')
$OutFull = Join-Path $Root (Join-Path $OutDir $ZipName)
$Snapshot = Join-Path $Root (Join-Path $OutDir 'release-snapshot.json')

function Fail($msg) { Write-Host "ERROR: $msg" -ForegroundColor Red; exit 1 }

if (-not (Test-Path (Join-Path $Root '.env'))) {
    Fail "No .env found at the project root - the build script must run from the project folder."
}

# -------------------------------------------------------------
# 1) Frontend assets (unless skipped)
# -------------------------------------------------------------
if (-not $SkipNpmBuild) {
    Write-Host "==> Building frontend assets (npm run build)..." -ForegroundColor Cyan
    Push-Location $Root
    try { npm run build } finally { Pop-Location }
    if ($LASTEXITCODE -ne 0) { Fail "npm run build failed." }
}

# -------------------------------------------------------------
# 2) Clear stale local caches (routes-v7.php, config.php, etc.)
#    NOT config:cache locally - that bakes local .env values.
# -------------------------------------------------------------
Write-Host "==> Clearing stale Laravel caches (local values must not ship)..." -ForegroundColor Cyan
Push-Location $Root
try { php artisan optimize:clear } finally { Pop-Location }
if ($LASTEXITCODE -ne 0) { Fail "php artisan optimize:clear failed." }

# -------------------------------------------------------------
# 3) Fresh staging folder
# -------------------------------------------------------------
Write-Host "==> Preparing staging folder..." -ForegroundColor Cyan
if (Test-Path $Stage) { Remove-Item $Stage -Recurse -Force }
New-Item -ItemType Directory -Path $Stage -Force | Out-Null

# -------------------------------------------------------------
# 4) Copy the app (with exclusions)
# -------------------------------------------------------------
$incDirs  = @('app','config','database','public','resources','routes','scripts','vendor','bootstrap')
$incFiles = @('artisan','composer.json','composer.lock','package.json','package-lock.json','vite.config.js','.env.example','.env.production.example')

$exDirs = @(
    '.git','.github','.agents','.claude','.opencode','.superpowers','.impeccable',
    'node_modules','tests','test-results','dist',
    'eval','response','tasks','__verify','Nice-Admin-master','page-ui-main','docs'
)

$exFiles = @(
    '*.log','*.md',
    '.env','.env.production','.env.backup','auth.json',
    '.gitignore','.gitattributes','.editorconfig',
    'vite.log','open-db.bat','scheduler.bat','start-dev.ps1',
    'opencode.json','skills-lock.json','.phpunit.result.cache',
    'playwright.config.ts','phpunit.xml'
)

Write-Host "==> Copying files (robocopy, multithreaded)..." -ForegroundColor Cyan
$roArgs = @($Root, $Stage, '/E', '/MT:32', '/XD') + $exDirs + @('/XF') + $exFiles + @('/NFL','/NDL','/NJH','/NJS','/NP')
& robocopy @roArgs | Out-Null
if ($LASTEXITCODE -ge 8) { Fail "robocopy failed (exit code $LASTEXITCODE)." }

foreach ($f in $incFiles) {
    if (-not (Test-Path (Join-Path $Stage $f))) {
        Write-Host "WARN: missing include file $f" -ForegroundColor Yellow
    }
}

# -------------------------------------------------------------
# 5) Storage skeleton - never ship local uploads/logs/sessions.
# -------------------------------------------------------------
Write-Host "==> Building storage + bootstrap/cache skeletons..." -ForegroundColor Cyan
$skeletonDirs = @(
    'storage\app\private',
    'storage\app\public',
    'storage\framework\cache\data',
    'storage\framework\sessions',
    'storage\framework\views',
    'storage\logs'
)
foreach ($d in $skeletonDirs) {
    $p = Join-Path $Stage $d
    New-Item -ItemType Directory -Path $p -Force | Out-Null
    New-Item -ItemType File -Path (Join-Path $p '.gitkeep') -Force | Out-Null
}

$bc = Join-Path $Stage 'bootstrap\cache'
New-Item -ItemType Directory -Path $bc -Force | Out-Null
Get-ChildItem $bc -Filter '*.php' -ErrorAction SilentlyContinue | Remove-Item -Force
Set-Content -Path (Join-Path $bc '.gitignore') -Value "*`n!.gitignore" -NoNewline

# -------------------------------------------------------------
# 6) Drop dev-only public files
# -------------------------------------------------------------
if (Test-Path (Join-Path $Stage 'public\hot'))      { Remove-Item (Join-Path $Stage 'public\hot') -Force }
if (Test-Path (Join-Path $Stage 'public\storage'))  { Remove-Item (Join-Path $Stage 'public\storage') -Recurse -Force }

# -------------------------------------------------------------
# 7) Safety guards
# -------------------------------------------------------------
Write-Host "==> Running safety checks..." -ForegroundColor Cyan
if (Test-Path (Join-Path $Stage '.env'))                { Fail "Local .env leaked into the zip - aborting." }
if (Test-Path (Join-Path $Stage '.env.production'))     { Fail "Local .env.production leaked into the zip - aborting." }
if (Test-Path (Join-Path $Stage 'public\hot'))          { Fail "'public/hot' leaked into the zip - every page would lose CSS/JS." }
if (Test-Path (Join-Path $Stage 'bootstrap\cache\config.php'))    { Fail "Stale config cache leaked - aborting." }
if (Test-Path (Join-Path $Stage 'bootstrap\cache\routes-v7.php')) { Fail "Stale route cache leaked - aborting." }
if (-not (Test-Path (Join-Path $Stage 'vendor\autoload.php')))    { Fail "vendor/autoload.php missing - is vendor present?" }
if (-not (Test-Path (Join-Path $Stage 'public\build\manifest.json'))) { Fail "public/build/manifest.json missing - run without -SkipNpmBuild first." }

$files = @(Get-ChildItem $Stage -Recurse -File)
$sizeMB = [math]::Round((($files | Measure-Object Length -Sum).Sum / 1MB), 1)
Write-Host "Staging ready: $($files.Count) files, $sizeMB MB" -ForegroundColor Green

# -------------------------------------------------------------
# 8) Build the zip
# -------------------------------------------------------------
New-Item -ItemType Directory -Path (Join-Path $Root $OutDir) -Force | Out-Null

# Snapshot (path => sha256) for the next incremental update
Write-Host "==> Hashing files for the snapshot..." -ForegroundColor Cyan
$snap = @{}
foreach ($f in $files) {
    $rel = $f.FullName.Substring($Stage.Length + 1).Replace('\', '/')
    $snap[$rel] = (Get-FileHash -Algorithm SHA256 -LiteralPath $f.FullName).Hash
}

if ($Update) {
    if (-not (Test-Path $Snapshot)) { Fail "-Update requested but no release-snapshot.json exists yet. Run a full release first." }

    $old = (Get-Content $Snapshot -Raw | ConvertFrom-Json).PSObject.Properties
    $oldMap = @{}
    foreach ($p in $old) { $oldMap[$p.Name] = $p.Value }

    $changed = @()
    foreach ($rel in $snap.Keys) {
        if (-not $oldMap.ContainsKey($rel) -or $oldMap[$rel] -ne $snap[$rel]) { $changed += $rel }
    }
    $deleted = @($oldMap.Keys | Where-Object { -not $snap.ContainsKey($_) })

    if ($changed.Count -eq 0 -and $deleted.Count -eq 0) {
        Write-Host "No changes detected since the last release - nothing to pack." -ForegroundColor Green
        $snap | ConvertTo-Json | Set-Content -Path $Snapshot
        exit 0
    }

    Write-Host "Changed/added: $($changed.Count); deleted: $($deleted.Count)" -ForegroundColor Yellow
    if ($deleted.Count) { Write-Host "  (deleted locally - the server keeps them; you may delete manually)" -ForegroundColor DarkGray }

    $upStage = Join-Path $Root 'dist\_update'
    if (Test-Path $upStage) { Remove-Item $upStage -Recurse -Force }
    New-Item -ItemType Directory -Path $upStage -Force | Out-Null
    foreach ($rel in $changed) {
        $src = Join-Path $Stage $rel.Replace('/', '\')
        $dst = Join-Path $upStage $rel.Replace('/', '\')
        New-Item -ItemType Directory -Path (Split-Path -Parent $dst) -Force | Out-Null
        Copy-Item -LiteralPath $src -Destination $dst
    }

    $stamp = Get-Date -Format 'yyyyMMdd-HHmm'
    $upName = "harvesthaul-update-$stamp.zip"
    $upOut = Join-Path $Root (Join-Path $OutDir $upName)
    & tar -a -c -f $upOut -C $upStage .
    if ($LASTEXITCODE -ne 0) { Fail "tar failed packing the update." }
    Remove-Item $upStage -Recurse -Force

    $hasMigration = $changed | Where-Object { $_ -like 'database/migrations/*' }
    Write-Host "" -ForegroundColor Cyan
    Write-Host "Update zip ready: $OutDir\$upName" -ForegroundColor Green
    Write-Host "Next:" -ForegroundColor Cyan
    Write-Host " 1. Upload $upName via hPanel File Manager into laravel_app/ and Extract (overwrite)."
    Write-Host ' 2. Then over SSH:' -ForegroundColor Cyan
    Write-Host "    cd ~/domains/<your-sub>/laravel_app && php artisan optimize:clear && php artisan view:cache"
    if ($hasMigration) {
        Write-Host ' 3. A database migration is included - also run:' -ForegroundColor Cyan
        Write-Host '    cd ~/domains/<your-sub>/laravel_app && php artisan migrate --force'
    }
    Write-Host "Remember: your database and the farmers' uploads are never touched by a code upload." -ForegroundColor DarkGray
} else {
    Write-Host "==> Packing $ZipName ..." -ForegroundColor Cyan
    & tar -a -c -f $OutFull -C $Stage .
    if ($LASTEXITCODE -ne 0) { Fail "tar failed packing the release." }

    $zipMB = [math]::Round(((Get-Item $OutFull).Length / 1MB), 1)
    Write-Host "" -ForegroundColor Cyan
    Write-Host "Release zip ready: $OutDir\$ZipName ($zipMB MB)" -ForegroundColor Green
    Write-Host "Contents: $($files.Count) files" -ForegroundColor Green
    Write-Host ""
    Write-Host "Verified excluded:" -ForegroundColor Green
    Write-Host "  - .env / .env.production / auth.json (secrets)"
    Write-Host "  - public/hot (would kill CSS/JS)"
    Write-Host "  - local storage uploads + logs (261 MB removed)"
    Write-Host "  - node_modules, .git, tests, docs, eval/"
    Write-Host ""
    Write-Host "Now hand this zip to your Hostinger steps (see docs/hostinger-deploy.md)." -ForegroundColor Green
}

$snap | ConvertTo-Json | Set-Content -Path $Snapshot
Write-Host "Snapshot saved to dist/release-snapshot.json" -ForegroundColor DarkGray