@echo off
php -e src\Entrypoint.php %1 %2 > deb.log
pause