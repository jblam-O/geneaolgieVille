#!/bin/sh
set -eu

chown -R www-data:www-data /var/www/html/var /var/www/html/public/uploads 2>/dev/null || true
exec apache2-foreground
