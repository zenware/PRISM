@echo off
:START
php -e src\Entrypoint.php %1 %2
goto START
pause