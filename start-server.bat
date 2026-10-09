@echo off
title Laboratory Management System - Local Server
echo Starting Laboratory Management System...
set "PHP_EXE=%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"
set "PHP_EXT=%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\ext"
if not exist "%PHP_EXE%" (
    echo PHP was not found.
    pause
    exit /b 1
)
echo Open your browser at: http://localhost:8000
echo.
"%PHP_EXE%" -d "extension_dir=%PHP_EXT%" -d extension=php_pdo_pgsql.dll -d extension=php_pgsql.dll -S 127.0.0.1:8000 -t public
pause