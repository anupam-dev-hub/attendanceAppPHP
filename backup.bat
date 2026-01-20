@echo off
REM Backup Script for Windows - Attendance Management System

SET BACKUP_DIR=backups
SET DATE=%date:~-4%%date:~4,2%%date:~7,2%_%time:~0,2%%time:~3,2%%time:~6,2%
SET DB_NAME=attendance_php
SET DB_USER=root
SET DB_PASS=

REM Create backup directory
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

REM Backup Database
echo Backing up database...
mysqldump -u %DB_USER% -p%DB_PASS% %DB_NAME% > "%BACKUP_DIR%\database_%DATE%.sql"

REM Backup Files
echo Backing up files...
tar -czf "%BACKUP_DIR%\files_%DATE%.tar.gz" uploads\ .env

echo Backup completed: %DATE%
dir "%BACKUP_DIR%" | findstr %DATE%

pause
