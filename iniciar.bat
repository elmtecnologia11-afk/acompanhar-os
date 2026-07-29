@echo off
title Acompanhar OS
cd /d "%~dp0"
echo Iniciando Acompanhar OS em http://127.0.0.1:8081
start "" "http://127.0.0.1:8081"
.\php\php.exe -S 0.0.0.0:8081 -t "%~dp0"
