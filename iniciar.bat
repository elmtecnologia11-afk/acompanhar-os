@echo off
title Acompanhar OS
cd /d "%~dp0"

start /B "" php\php.exe -S 0.0.0.0:8081 -t "%~dp0" > nul 2>&1
timeout /t 2 > nul
start "" "http://127.0.0.1:8081"
exit
