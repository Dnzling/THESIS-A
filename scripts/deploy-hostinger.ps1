param(
    [string] $HostName = '31.220.110.168',
    [int] $Port = 65002,
    [string] $User = 'u520906640',
    [string] $Branch = 'crm-warehouse-updates'
)

$ErrorActionPreference = 'Stop'
$appRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$publicBuild = Join-Path $appRoot 'public\build'
$stagingRoot = Join-Path ([System.IO.Path]::GetTempPath()) ([guid]::NewGuid().ToString())
$zipPath = Join-Path $stagingRoot 'build.zip'
$remote = "$User@$HostName"
$remoteZip = 'domains/furnisync.shop/public_html/public/build.zip'

foreach ($command in @('git', 'pnpm', 'ssh', 'scp')) {
    if (-not (Get-Command $command -ErrorAction SilentlyContinue)) {
        throw "Required command not found: $command"
    }
}

if (-not (Test-Path (Join-Path $appRoot 'artisan'))) {
    throw "Laravel artisan file not found in $appRoot"
}
if ($Branch -notmatch '^[A-Za-z0-9][A-Za-z0-9._/-]*$') {
    throw 'Branch must contain only letters, numbers, dots, underscores, slashes, and hyphens.'
}

Push-Location $appRoot
try {
    $currentBranch = (& git branch --show-current).Trim()
    if ($LASTEXITCODE -ne 0) { throw 'Could not determine the current Git branch.' }
    if ($currentBranch -ne $Branch) {
        throw "Current branch is '$currentBranch'; switch to '$Branch' before deploying."
    }

    Write-Host 'Building frontend assets before staging or pushing changes...'
    pnpm run build
    if ($LASTEXITCODE -ne 0) { throw 'Frontend build failed. Fix the displayed build error, then rerun the deployment script.' }

    if (-not (Test-Path (Join-Path $publicBuild 'manifest.json'))) {
        throw "Build manifest was not produced at $(Join-Path $publicBuild 'manifest.json')"
    }

    $pendingChanges = @(& git status --short)
    if ($LASTEXITCODE -ne 0) { throw 'Could not read Git status.' }
    if ($pendingChanges.Count -gt 0) {
        Write-Host 'These changes will be included in the commit:'
        $pendingChanges | ForEach-Object { Write-Host "  $_" }

        $commitMessage = Read-Host 'Commit message'
        if ([string]::IsNullOrWhiteSpace($commitMessage)) {
            throw 'A commit message is required when there are local changes.'
        }
        $confirmation = Read-Host 'Stage and commit all listed changes, then push? (y/N)'
        if ($confirmation -notmatch '^(y|yes)$') {
            throw 'Commit and deployment cancelled.'
        }

        & git add --all
        if ($LASTEXITCODE -ne 0) { throw 'Git staging failed.' }
        & git commit -m $commitMessage
        if ($LASTEXITCODE -ne 0) { throw 'Git commit failed.' }
    } else {
        Write-Host 'Working tree is clean; no commit is needed.'
    }

    Write-Host "Pushing $Branch to origin..."
    & git push origin $Branch
    if ($LASTEXITCODE -ne 0) { throw 'Git push failed. Deployment stopped.' }
} finally {
    Pop-Location
}

try {
    New-Item -ItemType Directory -Path $stagingRoot | Out-Null
    Copy-Item -Path $publicBuild -Destination $stagingRoot -Recurse
    Add-Type -AssemblyName System.IO.Compression
    $sourceBuild = Join-Path $stagingRoot 'build'
    $zipStream = [System.IO.File]::Open($zipPath, [System.IO.FileMode]::Create)
    $zipArchive = [System.IO.Compression.ZipArchive]::new(
        $zipStream,
        [System.IO.Compression.ZipArchiveMode]::Create,
        $false
    )
    try {
        $sourcePrefix = $sourceBuild.TrimEnd('\') + '\'
        Get-ChildItem -LiteralPath $sourceBuild -File -Recurse | ForEach-Object {
            $relativePath = $_.FullName.Substring($sourcePrefix.Length).Replace('\', '/')
            $entry = $zipArchive.CreateEntry("build/$relativePath", [System.IO.Compression.CompressionLevel]::Optimal)
            $inputStream = [System.IO.File]::OpenRead($_.FullName)
            $entryStream = $entry.Open()
            try {
                $inputStream.CopyTo($entryStream)
            } finally {
                $entryStream.Dispose()
                $inputStream.Dispose()
            }
        }
    } finally {
        $zipArchive.Dispose()
    }

    Write-Host "Uploading frontend build to $remote`:$remoteZip ..."
    & scp -P $Port $zipPath "${remote}:$remoteZip"
    if ($LASTEXITCODE -ne 0) { throw 'Upload failed.' }

    $remoteScript = @"
set -e
APP_DIR="`$HOME/domains/furnisync.shop/public_html"
cd "`$APP_DIR"
test -f artisan
test -f public/build.zip
unzip -oq public/build.zip -d public
rm -f public/build.zip
git pull --ff-only origin $Branch
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
php artisan route:cache
php artisan queue:restart
php artisan up
rm -f public/hot
"@

    Write-Host "Running deployment commands in ~/domains/furnisync.shop/public_html ..."
    $remoteScript | & ssh -p $Port $remote 'bash -s'
    if ($LASTEXITCODE -ne 0) { throw 'Remote deployment failed. Review the SSH output before retrying.' }

    Write-Host 'Hostinger deployment completed.'
} finally {
    if (Test-Path $stagingRoot) {
        Remove-Item -LiteralPath $stagingRoot -Recurse -Force
    }
}
