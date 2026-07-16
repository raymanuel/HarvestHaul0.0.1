<#
.SYNOPSIS
    Generates the complete HarvestHaul system flowchart in draw.io XML format.
.DESCRIPTION
    Outputs the full drawio XML to STDOUT.
    Run: .\generate-flowchart.ps1 > harvesthaul-system-flowchart.drawio
.NOTES
    Generated: 2026-07-16 | Source: SYSTEM_FLOWCHART.md
#>

# Read and output the complete drawio XML content
$content = Get-Content -Path "$(Join-Path $PSScriptRoot 'harvesthaul-system-flowchart.drawio')" -Raw
Write-Output $content
