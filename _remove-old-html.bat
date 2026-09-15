@echo off
rem ===========================================================================
rem  TBM - remove the static prototype files from the project root
rem ---------------------------------------------------------------------------
rem  The prototype has already been copied to html\. This deletes the leftover
rem  originals sitting at the root of the project.
rem
rem  It touches ONLY prototype files:
rem      *.html, admin\, assets\, .htaccess, CPANEL-DEPLOYMENT.md
rem
rem  It does NOT touch anything Laravel:
rem      app\ bootstrap\ config\ database\ public\ resources\ routes\ storage\
rem      tests\ artisan composer.json phpunit.xml README.md .env.example
rem
rem  Double-click this file, or run it from a command prompt.
rem ===========================================================================

setlocal
cd /d "%~dp0"

echo.
echo   TBM - removing the old prototype files from the project root
echo   ------------------------------------------------------------
echo.

rem --- Safety check: refuse to delete anything unless the archive is intact ---
if not exist "html\index.html" goto :missing
if not exist "html\admin\dashboard\index.html" goto :missing
if not exist "html\assets\js\bags.js" goto :missing
if not exist "html\assets\css\style.css" goto :missing

rem --- Safety check: make sure we really are in the Laravel project ----------
if not exist "artisan" goto :wrongfolder
if not exist "public\index.php" goto :wrongfolder

echo   Archive verified in html\ - removing the root copies.
echo.

del /q "*.html"                 2>nul
del /q ".htaccess"              2>nul
del /q "CPANEL-DEPLOYMENT.md"   2>nul
rmdir /s /q "admin"             2>nul
rmdir /s /q "assets"            2>nul

echo   Done.
echo.
echo   The prototype now lives only in html\.
echo   The Laravel application is untouched.
echo.
echo   This is a git repository, so you can review what changed with:
echo       git status
echo   and record it with:
echo       git add -A
echo       git commit -m "Move static prototype into html/"
echo.
pause

rem Remove this script now that it has done its job.
(goto) 2>nul & del "%~f0"

:missing
echo   ABORTED - the html\ archive looks incomplete.
echo.
echo   Expected to find html\index.html, html\admin\dashboard\index.html,
echo   html\assets\js\bags.js and html\assets\css\style.css.
echo.
echo   NOTHING has been deleted.
echo.
pause
exit /b 1

:wrongfolder
echo   ABORTED - this does not look like the Laravel project folder.
echo.
echo   Expected to find artisan and public\index.php alongside this script.
echo   Move this file into E:\laragon\www\tbm and run it there.
echo.
echo   NOTHING has been deleted.
echo.
pause
exit /b 1
