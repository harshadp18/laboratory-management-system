@echo off
title Laboratory Management System - Local Server
echo Starting Laboratory Management System...
echo Open your browser at: http://localhost:8000
echo.
"%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe" -S 127.0.0.1:8000 -t public
pause
