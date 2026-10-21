#!/usr/bin/env bash
# Descarga a public/vendor/ las librerías de interfaz que antes se cargaban desde CDNs, para que la app
# funcione sin internet. Los archivos quedan en el repositorio; este script solo se usa para actualizarlos.
# Cada archivo con hash se verifica (sha384) y el script falla si no coincide.
#
# Uso: scripts/vendor-assets.sh
set -euo pipefail
cd "$(dirname "$0")/.."

DEST=public/vendor

# destino | url | sha384 (vacío si el proveedor no publica uno)
FILES=$(cat <<'EOF'
bootstrap/css/bootstrap.min.css|https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css|sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB
bootstrap/js/bootstrap.bundle.min.js|https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js|FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI
jquery/jquery.min.js|https://code.jquery.com/jquery-3.7.1.min.js|1H217gwSVyLSIfaLxHbE7dRb3v4mYCKbpQvzx0cegeju1MVsGrX5xXxAvs/HgeFs
toastr/toastr.min.css|https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.css|YzEqZ2pBV0i9OmlTyoz75PqwTR8If8GsXBv7HLQclEVqIC3VxIt98/U94ES6CJTR
toastr/toastr.min.js|https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.js|VDls8ImYGI8SwVxpmjX2Bn27U2TcNodzTNROTusVEWO55+lmL+H9NczoQJk6mwZR
sweetalert2/sweetalert2.min.css|https://cdn.jsdelivr.net/npm/sweetalert2@11.26.3/dist/sweetalert2.min.css|gw5/Zpf2X1U9enIui8xFc+SNK5YIqEPNQ1dc0Tx3erA+znxx5UnPQpisVSel7VZH
sweetalert2/sweetalert2.min.js|https://cdn.jsdelivr.net/npm/sweetalert2@11.26.3/dist/sweetalert2.min.js|9GynVk5jPocYbuypwpDEOLV5SC9mGDlWn1YMabUKSQlG7DK/zmn+MX0gtHLGjXRa
fontawesome/css/all.min.css|https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.min.css|qrALq7+6jBOZIQsNnT6xGkMDru64qD6uTlDra39xrt2SoXl4pO3FX6Roz/RpR/BS
fontawesome/webfonts/fa-brands-400.woff2|https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/webfonts/fa-brands-400.woff2|QSzlW1NKpLQ4F3Vyt5v6/78tJC+q3tbKZBx6mmL4357XJJMQwKo3s0VxJ0Fp8l78
fontawesome/webfonts/fa-regular-400.woff2|https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/webfonts/fa-regular-400.woff2|78Wu/Ea/cmf/TbrN4bDVNmemhBWOSesv4mzA40dUVsj9Hb5E2CTaukY/7qGGVmBg
fontawesome/webfonts/fa-solid-900.woff2|https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/webfonts/fa-solid-900.woff2|TeBDWCQ2a4tojAZRcJzXsEgFI2EzW27W0GYt9HIpqXdUiPIauuYxz9RpAgJM1x9+
fontawesome/webfonts/fa-v4compatibility.woff2|https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/webfonts/fa-v4compatibility.woff2|8KTmqdoloZhvcK32d80Lp8nxp3p7P6hdL/QoVw5cbuiay6wfLwRGcPwp6tdkAaBu
datatables/css/dataTables.bootstrap5.min.css|https://cdn.datatables.net/3.1.4/css/dataTables.bootstrap5.min.css|OZKa6QSlaaq/LGR1sBFkYhC0c/nacIFh1chsblhDUxggC9Zb0XLEEMs95i1Kydnt
datatables/css/buttons.bootstrap5.min.css|https://cdn.datatables.net/buttons/4.1.2/css/buttons.bootstrap5.min.css|ys2qCyYVNhn8VsCr+WK5mt1Q9u/v8xEwSANgSdv34gDwFq1GjCLL56QiAKzXpzXk
datatables/css/responsive.bootstrap5.min.css|https://cdn.datatables.net/responsive/4.1.1/css/responsive.bootstrap5.min.css|IccFVZxMebKzou2YAT+5kHCpRTtvbJKtU1WS6PmGGeO3LvfGhMNgmeDE5k59j9Qk
datatables/css/colReorder.bootstrap5.min.css|https://cdn.datatables.net/colreorder/3.1.2/css/colReorder.bootstrap5.min.css|GbOIUvtTgiwI6/J1RPbHEigVcEHKQ5rwIoEmihz+slAsiIn6KTkzPQbdMneBryUS
datatables/css/select.bootstrap5.min.css|https://cdn.datatables.net/select/4.1.1/css/select.bootstrap5.min.css|lY2EDxInD5hFVkRZPk3oaLXpAvOE6jGlZ0GZEjw6zkEjtkKw/nIGu3n9HZ0FMnXj
datatables/js/dataTables.min.js|https://cdn.datatables.net/3.1.4/js/dataTables.min.js|Lg8qwUDhp1uuDJinT3SMR9sxQyVjC/2NPPFkbPahEVgqBRrWGI6zfmrgPYTaVgrS
datatables/js/dataTables.bootstrap5.min.js|https://cdn.datatables.net/3.1.4/js/dataTables.bootstrap5.min.js|4d8X9sr6Gnv9AgIQn6bv3lmQxj5fD+9bVAun0/XMmdy7oPRvT0adfiUUiiYpi4Ck
datatables/js/dataTables.buttons.min.js|https://cdn.datatables.net/buttons/4.1.2/js/dataTables.buttons.min.js|6XJm6yvMgGW3Vi8lCMi02aAI35eZ1syl8TQ8UovEVyHxaYv65hcG9XQG3CGMaK52
datatables/js/buttons.bootstrap5.min.js|https://cdn.datatables.net/buttons/4.1.2/js/buttons.bootstrap5.min.js|6++rOQQygcw7GGHgVZ/DnZmPosWY7R4duTZ91M7jBcIWrmlAJHV3wFbUWZVIhi4h
datatables/js/buttons.html5.min.js|https://cdn.datatables.net/buttons/4.1.2/js/buttons.html5.min.js|fo+TDvjtTcPEonjFXot7gjnBnVBliAxjxO7Et2INICR8O9P0KWV3Cs+Et3KYeVdD
datatables/js/buttons.print.min.js|https://cdn.datatables.net/buttons/4.1.2/js/buttons.print.min.js|fo+TDvjtTcPEonjFXot7gjnBnVBliAxjxO7Et2INICR8O9P0KWV3Cs+Et3KYeVdD
datatables/js/buttons.colVis.min.js|https://cdn.datatables.net/buttons/4.1.2/js/buttons.colVis.min.js|fo+TDvjtTcPEonjFXot7gjnBnVBliAxjxO7Et2INICR8O9P0KWV3Cs+Et3KYeVdD
datatables/js/dataTables.responsive.min.js|https://cdn.datatables.net/responsive/4.1.1/js/dataTables.responsive.min.js|PQkuArYpt1S0q5TqF/LnSkmadKwFyLBnPgdKgxfzpZy+h8UOk/jF3895MC6ZbHof
datatables/js/responsive.bootstrap5.min.js|https://cdn.datatables.net/responsive/4.1.1/js/responsive.bootstrap5.min.js|knw38sH7qpV3KrjATT6pxZQbU/X2YxbvSTFx8RSBVmc5KndzK7iStdfQiNUs0nnd
datatables/i18n/es-ES.json|https://cdn.datatables.net/plug-ins/2.3.3/i18n/es-ES.json|akAc1eqlnvI092AEvLGKm0ltQiwv2VJyA1zLVY04oc8+xbGI69UlVSkI/L0u5aoA
jszip/jszip.min.js|https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.2/jszip.min.js|DdGlNq+wVXAn83gXqwyeFHB7iyXy6L0rLrNzgDmhKWPjiJLg9vj6qosYmZmkMfQ+
pdfmake/pdfmake.min.js|https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.23/pdfmake.min.js|8Fgcdr1c4x0qG8ByTWhXwvf1iBa6tQReFeLNBSkvCN9F+/RZuP1ha8RUydfLSGeG
pdfmake/vfs_fonts.js|https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.23/vfs_fonts.js|pkBUW1wxcm6m7ZjKDxADnNHqnz+Sx9sAL1ndsLNv/GZnWZgodPYsju1yxeyQnn0c
chartjs/chart.umd.min.js|https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js|jb8JQMbMoBUzgWatfe6COACi2ljcDdZQ2OxczGA3bGNeWe+6DChMTBJemed7ZnvJ
EOF
)

while IFS='|' read -r path url sha; do
    target="$DEST/$path"
    mkdir -p "$(dirname "$target")"
    curl -fsSL --retry 3 -o "$target.tmp" "$url"
    if [ -n "$sha" ]; then
        actual=$(openssl dgst -sha384 -binary "$target.tmp" | openssl base64 -A)
        [ "$actual" = "$sha" ] || { echo "Hash distinto para $url" >&2; echo "esperado $sha" >&2; echo "obtuvo   $actual" >&2; rm -f "$target.tmp"; exit 1; }
    fi
    # Los source maps apuntan a archivos que no se publican; sin la línea el navegador no los pide
    case "$target" in *.js|*.css) sed -i '/^\/[*\/]# sourceMappingURL=/d' "$target.tmp" ;; esac
    mv "$target.tmp" "$target"
    echo "ok  $path"
done <<< "$FILES"
