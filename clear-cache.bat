@echo off
echo Clearing Laravel Cache...
echo.

echo [1/6] Clearing route cache...
php artisan route:clear

echo [2/6] Clearing config cache...
php artisan config:clear

echo [3/6] Clearing application cache...
php artisan cache:clear

echo [4/6] Clearing view cache...
php artisan view:clear

echo [5/6] Dumping autoload...
composer dump-autoload

echo [6/6] Optimizing...
php artisan optimize:clear

echo.
echo ✅ All cache cleared successfully!
echo.
pause
