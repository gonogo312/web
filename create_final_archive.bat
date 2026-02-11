@echo off
REM Batch script to create final exam archive
REM Usage: create_final_archive.bat

set FACNO=0MI0800206
set VERSION=final
set ARCHIVE=%FACNO%_exam_%VERSION%.zip

echo Creating archive: %ARCHIVE%

REM Remove old archive if exists
if exist %ARCHIVE% del %ARCHIVE%

REM Create archive using PowerShell (works on Windows 10+)
powershell -Command "Compress-Archive -Path 'seb-exam', 'w25prj_KN_REQ_final.docx' -DestinationPath '%ARCHIVE%' -Force"

echo.
echo Archive created successfully: %ARCHIVE%
echo.
echo Archive contains:
echo   - seb-exam/ (entire project folder)
echo   - w25prj_KN_REQ_final.docx (documentation)
echo.
echo Ready for submission!
pause



