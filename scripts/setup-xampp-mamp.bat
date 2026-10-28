@echo off
REM AICore WP Setup Script for XAMPP/MAMP (Windows)
REM This script automates WordPress installation with AICore WP plugin

setlocal enabledelayedexpansion

REM Color codes
REM Note: Windows cmd doesn't have native color codes in output, but we use labels

echo.
echo ========================================================
echo     AICore WP - XAMPP/MAMP Setup (Windows)
echo ========================================================
echo.

REM Configuration
set PROJECT_NAME=aicore-wp
set DB_NAME=wordpress_aicore
set DB_USER=wordpress
set DB_PASSWORD=wordpress_password
set DB_HOST=localhost
set WP_ADMIN_USER=admin
set WP_ADMIN_PASSWORD=AdminPassword123!
set WP_ADMIN_EMAIL=admin@example.local

REM Step 1: Detect XAMPP location
echo Step 1: Detecting XAMPP installation...

set XAMPP_PATH=
set HTDOCS_PATH=

if exist "C:\xampp" (
    set XAMPP_PATH=C:\xampp
    set HTDOCS_PATH=C:\xampp\htdocs
    echo Found: XAMPP at C:\xampp
) else if exist "C:\Program Files\XAMPP" (
    set XAMPP_PATH=C:\Program Files\XAMPP
    set HTDOCS_PATH=C:\Program Files\XAMPP\htdocs
    echo Found: XAMPP at C:\Program Files\XAMPP
) else if exist "C:\MAMP" (
    set XAMPP_PATH=C:\MAMP
    set HTDOCS_PATH=C:\MAMP\htdocs
    echo Found: MAMP at C:\MAMP
) else (
    echo Error: XAMPP/MAMP not found!
    echo.
    echo Please install XAMPP from: https://www.apachefriends.org
    echo Or MAMP from: https://www.mamp.info
    echo.
    pause
    exit /b 1
)

echo.

REM Step 2: Check PHP
echo Step 2: Checking PHP...

if exist "%XAMPP_PATH%\php\php.exe" (
    echo Found: PHP
) else (
    echo Error: PHP not found at %XAMPP_PATH%\php\
    pause
    exit /b 1
)

echo.

REM Step 3: Create WordPress directory
echo Step 3: Setting up WordPress directory...

set WP_DIR=%HTDOCS_PATH%\%PROJECT_NAME%

if exist "%WP_DIR%" (
    echo Warning: Directory already exists
    set /p OVERWRITE="Overwrite? (y/N): "
    if not "!OVERWRITE!"=="y" (
        echo Cancelled
        pause
        exit /b 1
    )
    rmdir /s /q "%WP_DIR%"
)

mkdir "%WP_DIR%"
echo Created: %WP_DIR%
echo.

REM Step 4: Download WordPress
echo Step 4: Downloading WordPress...

cd /d "%WP_DIR%"

REM Using PowerShell to download (more reliable on Windows)
powershell -Command "Invoke-WebRequest -Uri 'https://wordpress.org/latest.zip' -OutFile 'wordpress.zip'" 2>nul

if exist wordpress.zip (
    powershell -Command "Expand-Archive -Path 'wordpress.zip' -DestinationPath '%WP_DIR%'" 2>nul

    for /f %%F in ('dir /b "%WP_DIR%\wordpress\*"') do (
        move "%WP_DIR%\wordpress\%%F" "%WP_DIR%"
    )
    rmdir "%WP_DIR%\wordpress"
    del wordpress.zip

    echo WordPress extracted
) else (
    echo Error: Failed to download WordPress
    pause
    exit /b 1
)

echo.

REM Step 5: Create database
echo Step 5: Creating database...

REM Create MySQL commands file
(
    echo CREATE DATABASE IF NOT EXISTS `%DB_NAME%`;
    echo CREATE USER IF NOT EXISTS '%DB_USER%'^@'%DB_HOST%' IDENTIFIED BY '%DB_PASSWORD%';
    echo GRANT ALL PRIVILEGES ON `%DB_NAME%`.* TO '%DB_USER%'^@'%DB_HOST%';
    echo FLUSH PRIVILEGES;
) > "%TEMP%\mysql_setup.sql"

REM Execute MySQL commands
"%XAMPP_PATH%\mysql\bin\mysql.exe" -u root < "%TEMP%\mysql_setup.sql"

if %ERRORLEVEL% EQU 0 (
    echo Database created: %DB_NAME%
    echo User created: %DB_USER%
) else (
    echo Error creating database
    echo Please ensure MySQL is running in XAMPP Control Panel
    pause
    exit /b 1
)

del "%TEMP%\mysql_setup.sql"
echo.

REM Step 6: Create wp-config.php
echo Step 6: Configuring WordPress...

(
    echo ^<?php
    echo // Database settings
    echo define( 'DB_NAME', '%DB_NAME%' ^);
    echo define( 'DB_USER', '%DB_USER%' ^);
    echo define( 'DB_PASSWORD', '%DB_PASSWORD%' ^);
    echo define( 'DB_HOST', '%DB_HOST%' ^);
    echo define( 'DB_CHARSET', 'utf8mb4' ^);
    echo define( 'DB_COLLATE', '' ^);
    echo.
    echo // Debug settings
    echo define( 'WP_DEBUG', true ^);
    echo define( 'WP_DEBUG_LOG', true ^);
    echo define( 'WP_DEBUG_DISPLAY', false ^);
    echo define( 'SCRIPT_DEBUG', true ^);
    echo.
    echo // Table prefix
    echo $table_prefix = 'wp_';
    echo.
    echo // Salts - Generate from https://api.wordpress.org/secret-key/1.1/salt/
    echo define( 'AUTH_KEY', 'put your unique phrase here' ^);
    echo define( 'SECURE_AUTH_KEY', 'put your unique phrase here' ^);
    echo define( 'LOGGED_IN_KEY', 'put your unique phrase here' ^);
    echo define( 'NONCE_KEY', 'put your unique phrase here' ^);
    echo define( 'AUTH_SALT', 'put your unique phrase here' ^);
    echo define( 'SECURE_AUTH_SALT', 'put your unique phrase here' ^);
    echo define( 'LOGGED_IN_SALT', 'put your unique phrase here' ^);
    echo define( 'NONCE_SALT', 'put your unique phrase here' ^);
    echo.
    echo if ( !defined( 'ABSPATH' ^) ^) {
    echo     define( 'ABSPATH', __DIR__ . '/' ^);
    echo }
    echo require_once ABSPATH . 'wp-settings.php';
) > "%WP_DIR%\wp-config.php"

echo wp-config.php created
echo.

REM Step 7: Get plugin path
echo Step 7: Setting up plugin link...

REM Get the directory of the current batch file's parent (project root)
cd /d "%~dp0.."
set PLUGIN_SOURCE=%CD%

if not exist "%PLUGIN_SOURCE%\aicore-wp.php" (
    echo Error: aicore-wp.php not found at %PLUGIN_SOURCE%
    pause
    exit /b 1
)

set PLUGIN_DEST=%WP_DIR%\wp-content\plugins\aicore-wp

if exist "%PLUGIN_DEST%" (
    rmdir "%PLUGIN_DEST%"
)

REM Create symlink or copy (depending on Windows version)
mklink /d "%PLUGIN_DEST%" "%PLUGIN_SOURCE%"

if %ERRORLEVEL% EQU 0 (
    echo Plugin linked successfully
) else (
    echo Warning: Could not create symlink. Trying to copy instead...
    xcopy "%PLUGIN_SOURCE%" "%PLUGIN_DEST%" /E /I /Y
)

echo.

REM Step 8: Install dependencies (if available)
echo Step 8: Checking for Composer and npm...

cd /d "%PLUGIN_SOURCE%"

where composer >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    echo Installing Composer dependencies...
    call composer install
) else (
    echo Note: Composer not found. Install from https://getcomposer.org
)

echo.

where npm >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    echo Installing npm dependencies...
    call npm install
) else (
    echo Note: Node.js not found. Install from https://nodejs.org
)

echo.

REM Summary
echo.
echo ========================================================
echo           Setup Complete!
echo ========================================================
echo.

echo WordPress Installation:
echo   URL:   http://localhost/%PROJECT_NAME%
echo   Path:  %WP_DIR%
echo.

echo Admin Credentials:
echo   Username: %WP_ADMIN_USER%
echo   Password: %WP_ADMIN_PASSWORD%
echo.

echo Database Credentials:
echo   Host:     %DB_HOST%
echo   Database: %DB_NAME%
echo   User:     %DB_USER%
echo   Password: %DB_PASSWORD%
echo.

echo Plugin:
echo   Path: %PLUGIN_DEST%
echo.

echo Next Steps:
echo   1. Open XAMPP Control Panel
echo   2. Start Apache and MySQL services
echo   3. Visit: http://localhost/%PROJECT_NAME%
echo   4. Go through WordPress installation
echo   5. Activate AICore WP plugin in WordPress admin
echo   6. Configure AI providers
echo.

echo Development:
echo   Frontend: npm run dev
echo   View logs: %WP_DIR%\wp-content\debug.log
echo.

echo Important: Edit wp-config.php to add security salts from:
echo   https://api.wordpress.org/secret-key/1.1/salt/
echo.

pause
