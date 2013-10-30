@echo off
:START
php -e bootstrap.php %1 %2
goto START
pause
