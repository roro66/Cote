#!/usr/bin/env bash
# Despliega Cote al servidor de producción: build de assets, rsync, migraciones y limpieza de cachés.
# Uso: ./deploy.sh            despliega
#      ./deploy.sh --dry-run  muestra qué archivos cambiarían, sin tocar el servidor
#
# Requiere sshpass y la contraseña SSH en SSHPASS o en ~/.config/cote/secrets.env (permisos 600).
# En el servidor, .env, docker-compose.override.yml y storage/ son propios y nunca se sobrescriben.
set -euo pipefail

SERVER="${COTE_SERVER_USER:-rodrigo}@${COTE_SERVER_HOST:-10.244.249.148}"
REMOTE_DIR=/home/rodrigo/projects/laravel/coteso
SECRETS="$HOME/.config/cote/secrets.env"

cd "$(dirname "$0")"

if [ -z "${SSHPASS:-}" ] && [ -f "$SECRETS" ]; then
    # shellcheck disable=SC1090
    source "$SECRETS"
fi
: "${SSHPASS:?Define SSHPASS o crea $SECRETS con SSHPASS='...'}"
export SSHPASS

remote() { sshpass -e ssh -o ConnectTimeout=10 "$SERVER" "cd $REMOTE_DIR && $1"; }

if [ -n "$(git status --porcelain)" ]; then
    echo "Hay cambios sin commit; haz commit antes de desplegar." >&2
    exit 1
fi

RSYNC=(sshpass -e rsync -az --no-perms --no-times --checksum --delete --exclude-from=deploy/rsync-excludes.txt)

if [ "${1:-}" = "--dry-run" ]; then
    "${RSYNC[@]}" --dry-run --itemize-changes ./ "$SERVER:$REMOTE_DIR/" | grep -v '^\.' || echo "Sin cambios."
    exit 0
fi

echo "==> Compilando assets"
./vendor/bin/sail npm run build >/dev/null

echo "==> Copiando archivos ($(git rev-parse --short HEAD))"
LOCK_BEFORE=$(remote "md5sum composer.lock" | cut -d' ' -f1)
"${RSYNC[@]}" ./ "$SERVER:$REMOTE_DIR/"

for f in routes/web.php composer.lock public/build/manifest.json; do
    [ "$(md5sum "$f" | cut -d' ' -f1)" = "$(remote "md5sum $f" | cut -d' ' -f1)" ] || { echo "md5 distinto en $f" >&2; exit 1; }
done

if [ "$LOCK_BEFORE" != "$(md5sum composer.lock | cut -d' ' -f1)" ]; then
    echo "==> composer.lock cambió: instalando dependencias"
    remote "./vendor/bin/sail composer install --no-interaction --prefer-dist"
fi

echo "==> Migraciones y cachés"
remote "./vendor/bin/sail artisan migrate --force && \
    ./vendor/bin/sail artisan config:clear && \
    ./vendor/bin/sail artisan route:clear && \
    ./vendor/bin/sail artisan view:clear && \
    ./vendor/bin/sail artisan cache:clear"

echo "==> Verificación"
STATUS=$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 "http://${COTE_SERVER_HOST:-10.244.249.148}:8090/up")
echo "Salud (/up): $STATUS"
[ "$STATUS" = "200" ]
