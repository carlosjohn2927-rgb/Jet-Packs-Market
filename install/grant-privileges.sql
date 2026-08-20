-- Verify / grant full privileges to the application database user.
-- Run this as a MySQL superuser (e.g. root) after creating the DB and user.

GRANT ALL PRIVILEGES ON `halymumm_vivicool`.* TO 'halymumm_vivicool'@'localhost';
GRANT ALL PRIVILEGES ON `halymumm_vivicool`.* TO 'halymumm_vivicool'@'%';
FLUSH PRIVILEGES;
