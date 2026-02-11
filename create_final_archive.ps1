# PowerShell script to create final exam archive
# Usage: .\create_final_archive.ps1

$facNo = "0MI0800206"
$version = "final"  # Change to "draft1", "draft2", etc. if not final
$archiveName = "${facNo}_exam_${version}.zip"

Write-Host "Creating archive: $archiveName" -ForegroundColor Green

# Remove old archive if exists
if (Test-Path $archiveName) {
    Remove-Item $archiveName
    Write-Host "Removed old archive." -ForegroundColor Yellow
}

# Create archive
Compress-Archive -Path "seb-exam", "w25prj_KN_REQ_final.docx" -DestinationPath $archiveName -Force

Write-Host "Archive created successfully: $archiveName" -ForegroundColor Green
Write-Host ""
Write-Host "Archive contains:" -ForegroundColor Cyan
Write-Host "  - seb-exam/ (entire project folder)" -ForegroundColor White
Write-Host "  - w25prj_KN_REQ_final.docx (documentation)" -ForegroundColor White
Write-Host ""
Write-Host "Ready for submission!" -ForegroundColor Green



