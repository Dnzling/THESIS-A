@echo off
set "PROJECT_DIR=%~dp0"

start "THESIS-A Laravel" cmd /k "cd /d ""%PROJECT_DIR%"" && php artisan serve --host=0.0.0.0 --port=8000"
start "THESIS-A Vite" cmd /k "cd /d ""%PROJECT_DIR%"" && npm run dev -- --host=127.0.0.1 --port=5173"

echo Starting Laravel and Vite...
echo Keep both command windows open while using the system.
echo.
echo Local URL: http://127.0.0.1:8000
timeout /t 5 /nobreak >nul
