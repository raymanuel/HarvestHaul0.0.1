# start-dev.ps1 - HarvestHaul development runner (dev-only)
#
# Launches the two long-running dev processes each in its own window:
#   1. php artisan serve        - web app
#   2. php artisan schedule:work - scheduler (runs the hourly price scraper + daily alerts)
#
# Includes a scheduler watchdog: if schedule:work dies, it auto-restarts.
#
# WHY THIS EXISTS: `php artisan serve` does NOT run the scheduler. If you only start
# `serve`, market prices stop updating silently. Keep both windows open while developing.
#
# PRODUCTION DOES NOT USE THIS SCRIPT. On the VPS, scheduling runs via cron.

$ProjectRoot = $PSScriptRoot
$php = 'php'
$watchdogLog = Join-Path $ProjectRoot 'storage\logs\scheduler-watchdog.log'

function Start-Scheduler {
    Start-Process -FilePath $php -ArgumentList 'artisan', 'schedule:work' -WorkingDirectory $ProjectRoot
    $ts = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'
    "$ts - Scheduler (re)started." | Out-File -Append -FilePath $watchdogLog
}

function Test-SchedulerAlive {
    $scheduler = Get-CimInstance Win32_Process -Filter "Name = 'php.exe'" -ErrorAction SilentlyContinue |
        Where-Object { $_.CommandLine -match 'schedule:work' }
    return $null -ne $scheduler
}

Start-Process -FilePath $php -ArgumentList 'artisan', 'serve' -WorkingDirectory $ProjectRoot
Start-Scheduler

Write-Host 'Started: serve, schedule:work'
Write-Host 'Watchdog running -- will auto-restart schedule:work if it dies.'
Write-Host 'Press Ctrl+C to stop all processes.'

try {
    while ($true) {
        Start-Sleep -Seconds 60
        if (-not (Test-SchedulerAlive)) {
            $ts = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'
            Write-Host "[$ts] schedule:work died. Restarting..."
            "$ts - schedule:work died. Restarting." | Out-File -Append -FilePath $watchdogLog
            Start-Scheduler
        }
    }
} finally {
    Get-Process -Name php -ErrorAction SilentlyContinue | Stop-Process -Force
    $ts = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'
    "$ts - All PHP processes stopped." | Out-File -Append -FilePath $watchdogLog
}
