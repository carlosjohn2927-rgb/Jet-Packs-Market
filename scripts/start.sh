#!/bin/bash
# Halyk Petroleum - start the CodeIgniter 3 app under PHP's built-in server.
# For development only. Production uses Apache/Nginx on cPanel.
set -e
cd "$(dirname "$0")/.."
PORT="${PORT:-8080}"
HOST="${HOST:-0.0.0.0}"
echo "Starting Halyk Petroleum (CodeIgniter 3) at http://${HOST}:${PORT}"
echo "Document root: $(pwd)"
echo ""
echo "Prerequisites:"
echo "  1. Set DB + secret env vars in .env (see .env.example)"
echo "  2. Create a database and import database/production.sql via phpMyAdmin"
echo "  3. Update .env with your database credentials"
echo ""
php -S "${HOST}:${PORT}" -t . tests/router.php