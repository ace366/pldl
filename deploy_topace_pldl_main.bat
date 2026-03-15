@echo off
setlocal EnableDelayedExpansion

set PROJECT_DIR=C:\work\pldl
set TARGET_BRANCH=main

echo ============================
echo 26yorii deploy start
echo ============================

cd /d %PROJECT_DIR%
if errorlevel 1 (
 echo ERROR: project dir not found
 pause
 exit /b 1
)

for /f "delims=" %%i in ('git branch --show-current') do set CURRENT_BRANCH=%%i

echo Current branch: !CURRENT_BRANCH!

if /I NOT "!CURRENT_BRANCH!"=="%TARGET_BRANCH%" (
 echo ERROR: not on main branch
 echo run: git checkout main
 echo run: git pull origin main
 pause
 exit /b 1
)

git diff --quiet
if errorlevel 1 (
 echo ERROR: uncommitted changes exist
 git status --short
 pause
 exit /b 1
)

git diff --cached --quiet
if errorlevel 1 (
 echo ERROR: staged but uncommitted changes exist
 git status --short
 pause
 exit /b 1
)

echo.
echo STEP1: git pull
git pull origin main
if errorlevel 1 (
 echo ERROR: git pull failed
 pause
 exit /b 1
)

echo.
echo STEP2: local build check
echo This script does not run npm build.
echo Please make sure public/build was already created locally.
if not exist public\build (
 echo ERROR: public\build not found
 echo Run your local build first, then retry.
 pause
 exit /b 1
)

echo.
echo STEP3: upload to sakura

rclone copy C:\work\pldl :sftp:/home/top-ace-picard/www/pldl ^
 --sftp-host www2916.sakura.ne.jp ^
 --sftp-user top-ace-picard ^
 --sftp-key-file C:\Users\ace36\.ssh\sakura_ed25519 ^
 --update ^
 --modify-window 2s ^
 --create-empty-src-dirs ^
 --progress ^
 --transfers 1 ^
 --checkers 1 ^
 --retries 10 ^
 --low-level-retries 20 ^
 --timeout 2m ^
 --contimeout 30s ^
 --sftp-set-modtime=false ^
 --exclude .env ^
 --exclude .env.* ^
 --exclude storage/** ^
 --exclude bootstrap/cache/** ^
 --exclude vendor/** ^
 --exclude node_modules/** ^
 --exclude **/*.log ^
 --exclude .git/** ^
 --exclude .github/** ^
 --exclude tests/** ^
 --exclude database/*.sqlite ^
 --exclude public/sounds/**
if errorlevel 1 (
 echo.
 echo ERROR: rclone copy failed
 pause
 exit /b 1
)

echo.
echo ============================
echo Upload complete
echo ============================

echo Run on sakura server:
echo cd /home/top-ace-picard/www/pldl
echo php artisan migrate --force
echo php artisan optimize:clear
echo.
echo Notes:
echo - public/build is uploaded from local machine.
echo - This script does not run npm build.
echo - If build assets were changed, run local build before deploy.
echo - Check .env and storage settings on sakura if needed.
echo Run on sakura server:
echo cd /home/top-ace-picard/www/pldl
echo php artisan migrate --force
echo php artisan optimize:clear

pause
