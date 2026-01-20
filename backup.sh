#!/bin/bash
# Backup Script for Attendance Management System

# Configuration
BACKUP_DIR="./backups"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="attendance_php"
DB_USER="root"
DB_PASS=""

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Backup Database
echo "Backing up database..."
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > "$BACKUP_DIR/database_$DATE.sql"

# Backup Files (uploads and .env)
echo "Backing up files..."
tar -czf "$BACKUP_DIR/files_$DATE.tar.gz" uploads/ .env 2>/dev/null

# Remove backups older than 30 days
echo "Cleaning old backups..."
find "$BACKUP_DIR" -name "*.sql" -mtime +30 -delete
find "$BACKUP_DIR" -name "*.tar.gz" -mtime +30 -delete

echo "Backup completed: $DATE"
ls -lh "$BACKUP_DIR" | grep $DATE
