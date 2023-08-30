@echo off

if "%PHP_PEAR_PHP_BIN%" neq "" (
    set PHPBIN=%PHP_PEAR_PHP_BIN%
) else set PHPBIN=php

:START
"%PHPBIN%" -e "%~dp0\prism" %*
goto START
pause