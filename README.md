# AttendTrack setup
1. Import `schema.sql` into MySQL (phpMyAdmin or `mysql < schema.sql`).
2. Edit DB credentials and the SUBJECTS list at the top of `lib.php`.
3. Run `composer install` (installs PhpSpreadsheet for Excel reading). Requires PHP 8.1+ with zip, gd, xml extensions.
4. Serve the folder: `php -S localhost:8000` then open http://localhost:8000
5. Make sure `uploads/` is writable.
Attendance Excel format (row 1 header): Roll No | Subject | Date | Status(P/A)
