@echo off
setlocal

REM ===== SETTINGS =====
set LOCAL_PROJECT=C:\work\pldl
set SSH_KEY=C:\Users\ace36\.ssh\id_ecdsa.pem

set SSH_USER=pldl
set SSH_HOST=www543b.sakura.ne.jp

set REMOTE_APP_DIR=/home/pldl/www/lab
set REMOTE_TMP_DIR=/home/pldl/www/tmp/pldl_deploy
set REMOTE_SCRIPT=/home/pldl/www/tmp/pldl_deploy/remote_deploy_pldl.sh
REM ====================

for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmmss"') do set TS=%%i

set STAGING_DIR=%TEMP%\pldl_staging_%TS%
set ZIP_FILE=%TEMP%\pldl_%TS%.zip

echo ========================================
echo PLDL deploy start
echo Timestamp: %TS%
echo ========================================

if not exist "%LOCAL_PROJECT%\artisan" (
  echo ERROR: artisan not found
  pause
  exit /b 1
)

if not exist "%SSH_KEY%" (
  echo ERROR: ssh key not found
  echo %SSH_KEY%
  pause
  exit /b 1
)

where ssh >nul 2>nul
if errorlevel 1 (
  echo ERROR: ssh command not found
  pause
  exit /b 1
)

where scp >nul 2>nul
if errorlevel 1 (
  echo ERROR: scp command not found
  pause
  exit /b 1
)

if exist "%STAGING_DIR%" rmdir /s /q "%STAGING_DIR%"
mkdir "%STAGING_DIR%"

echo Copy project to staging...
robocopy "%LOCAL_PROJECT%" "%STAGING_DIR%" /MIR /R:1 /W:1 /XD ".git" "node_modules" "vendor" "storage" ".idea" ".vscode" /XF ".env"
set RC=%ERRORLEVEL%
if %RC% GEQ 8 (
  echo ERROR: robocopy failed
  pause
  exit /b 1
)

if exist "%STAGING_DIR%\bootstrap\cache" del /q "%STAGING_DIR%\bootstrap\cache\*" >nul 2>nul
if exist "%STAGING_DIR%\storage" rmdir /s /q "%STAGING_DIR%\storage"
if exist "%STAGING_DIR%\vendor" rmdir /s /q "%STAGING_DIR%\vendor"
if exist "%STAGING_DIR%\node_modules" rmdir /s /q "%STAGING_DIR%\node_modules"

if exist "%ZIP_FILE%" del /q "%ZIP_FILE%"

echo Create zip...
powershell -NoProfile -Command "Compress-Archive -Path '%STAGING_DIR%\*' -DestinationPath '%ZIP_FILE%' -Force"
if not exist "%ZIP_FILE%" (
  echo ERROR: zip file not created
  pause
  exit /b 1
)

echo Create remote tmp dir...
ssh -i "%SSH_KEY%" %SSH_USER%@%SSH_HOST% "mkdir -p %REMOTE_TMP_DIR%"
if errorlevel 1 (
  echo ERROR: failed to create remote tmp dir
  pause
  exit /b 1
)

echo Upload zip...
scp -i "%SSH_KEY%" "%ZIP_FILE%" %SSH_USER%@%SSH_HOST%:%REMOTE_TMP_DIR%/pldl_%TS%.zip
if errorlevel 1 (
  echo ERROR: zip upload failed
  pause
  exit /b 1
)

echo Run remote deploy...
ssh -i "%SSH_KEY%" %SSH_USER%@%SSH_HOST% "bash %REMOTE_SCRIPT% %REMOTE_APP_DIR% %REMOTE_TMP_DIR%/pldl_%TS%.zip %TS%"
if errorlevel 1 (
  echo ERROR: remote deploy failed
  pause
  exit /b 1
)

echo.
echo ========================================
echo Deploy completed
echo ========================================
echo Next manual steps on server:
echo cd %REMOTE_APP_DIR%
echo php artisan migrate --force
echo php artisan optimize:clear
echo.

if exist "%STAGING_DIR%" rmdir /s /q "%STAGING_DIR%"
if exist "%ZIP_FILE%" del /q "%ZIP_FILE%"

pause
endlocal
