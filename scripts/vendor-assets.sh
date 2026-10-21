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
fontawesome/css/all.min.css|https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css|nRgPTkuX86pH8yjPJUAFuASXQSSl2/bBUiNV47vSYpKFxHJhbcrGnmlYpYJMeD7a
fontawesome/webfonts/fa-brands-400.woff2|https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/webfonts/fa-brands-400.woff2|
fontawesome/webfonts/fa-brands-400.ttf|https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/webfonts/fa-brands-400.ttf|
fontawesome/webfonts/fa-regular-400.woff2|https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/webfonts/fa-regular-400.woff2|
fontawesome/webfonts/fa-regular-400.ttf|https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/webfonts/fa-regular-400.ttf|
fontawesome/webfonts/fa-solid-900.woff2|https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/webfonts/fa-solid-900.woff2|
fontawesome/webfonts/fa-solid-900.ttf|https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/webfonts/fa-solid-900.ttf|
fontawesome/webfonts/fa-v4compatibility.woff2|https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/webfonts/fa-v4compatibility.woff2|
fontawesome/webfonts/fa-v4compatibility.ttf|https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/webfonts/fa-v4compatibility.ttf|
datatables/css/dataTables.bootstrap5.min.css|https://cdn.datatables.net/1.13.11/css/dataTables.bootstrap5.min.css|0gIEQgzci/UNbFAKcuem1owTyJxmDMzkIka2STHeD8hUj1X9Ej3SxWafQclv7NCM
datatables/css/buttons.bootstrap5.min.css|https://cdn.jsdelivr.net/npm/datatables.net-buttons-bs5@2.4.3/css/buttons.bootstrap5.min.css|pZ0WBkZvjXVs+cznlKxzQDazHsf8DuyL4APQE+07UDMZa9yuEGDV7rSQDJ9r1TMh
datatables/css/responsive.bootstrap5.min.css|https://cdn.jsdelivr.net/npm/datatables.net-responsive-bs5@2.5.1/css/responsive.bootstrap5.min.css|INyCLPoe4fwjjIS2iOpnSiP6neAur+iv07/pUu7MZ4XplZioaGpaD6YZCJ/GBMG/
datatables/css/colReorder.bootstrap5.min.css|https://cdn.jsdelivr.net/npm/datatables.net-colreorder-bs5@1.7.2/css/colReorder.bootstrap5.min.css|h0zqwVgUphxmv8EDAhSUgsNgA1nENH+x8JxyJqPvm4anfLZF2zwHCDH2v3etqO75
datatables/css/select.bootstrap5.min.css|https://cdn.jsdelivr.net/npm/datatables.net-select-bs5@1.7.1/css/select.bootstrap5.min.css|BQuA/IRHdZd4G0fkajPKOBOE6lIuKmN2G95L52+ULcI1T/NGKY+gWsB/qDn6xxv7
datatables/js/jquery.dataTables.min.js|https://cdn.datatables.net/1.13.11/js/jquery.dataTables.min.js|xbKh5PcHqYD2znaTJ+mPamIq8ERw8yRfp72NI8CGRg51FffJAXidmePtdkmCjEh9
datatables/js/dataTables.bootstrap5.min.js|https://cdn.datatables.net/1.13.11/js/dataTables.bootstrap5.min.js|PgPBH0hy6DTJwu7pTf6bkRqPlf/+pjUBExpr/eIfzszlGYFlF9Wi9VTAJODPhgCO
datatables/js/dataTables.buttons.min.js|https://cdn.jsdelivr.net/npm/datatables.net-buttons@2.4.3/js/dataTables.buttons.min.js|57StXIwAbLcC8X3+3CfIRofQ1D7rMggjdwLqpqUhBcl7m8JdVqKH6/9F0Sn9B2j5
datatables/js/buttons.bootstrap5.min.js|https://cdn.jsdelivr.net/npm/datatables.net-buttons-bs5@2.4.3/js/buttons.bootstrap5.min.js|8Fm9OFhJ1epvcmDiJTZ2SFHHZoCp/xJ8Ld7wG7/aUwGni32fG7LIhsNTFfEtUaSv
datatables/js/buttons.html5.min.js|https://cdn.jsdelivr.net/npm/datatables.net-buttons@2.4.3/js/buttons.html5.min.js|CZe/qsxzVISKa8QreSVVnU6pxOMTg4t5AZ2iC74eHDK5ZysL2QrZS3ldIM7D4TkX
datatables/js/buttons.print.min.js|https://cdn.jsdelivr.net/npm/datatables.net-buttons@2.4.3/js/buttons.print.min.js|f5wX6gS38xMVGrwiXWKR7VijcBONISoTGfHF3N7yTwGrmvHaMHB4lq+VKxeZID10
datatables/js/buttons.colVis.min.js|https://cdn.jsdelivr.net/npm/datatables.net-buttons@2.4.3/js/buttons.colVis.min.js|dyI79OT6UE9qWK4x3HAQSilUPj6TfmkE8pX6KeBTyrhOaNrk7cbvUZUDMuC8S7/k
datatables/js/dataTables.responsive.min.js|https://cdn.jsdelivr.net/npm/datatables.net-responsive@2.5.1/js/dataTables.responsive.min.js|/R8VhIGH4431bJSsUOi46WH9iqjFrcRvxiDSlK4fPuNxK/Y+O9745+OlE5sBf7wR
datatables/js/responsive.bootstrap5.min.js|https://cdn.jsdelivr.net/npm/datatables.net-responsive-bs5@2.5.1/js/responsive.bootstrap5.min.js|T6YQaHyTPTbybQQV23jtlugHCneQYjePXdcEU+KMWGQY8EUQygBW9pRx0zpSU0/i
datatables/i18n/es-ES.json|https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json|+36vexJyu1KljF7WKGAaP3xRhzt8Wh+zdtQszdDhbz03PUNdMtwiU4XVG0NeteNE
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
