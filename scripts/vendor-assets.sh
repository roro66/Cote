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
bootstrap/css/bootstrap.min.css|https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css|9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM
bootstrap/js/bootstrap.bundle.min.js|https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js|geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz
jquery/jquery.min.js|https://code.jquery.com/jquery-3.7.1.min.js|1H217gwSVyLSIfaLxHbE7dRb3v4mYCKbpQvzx0cegeju1MVsGrX5xXxAvs/HgeFs
toastr/toastr.min.css|https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.css|YzEqZ2pBV0i9OmlTyoz75PqwTR8If8GsXBv7HLQclEVqIC3VxIt98/U94ES6CJTR
toastr/toastr.min.js|https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.js|VDls8ImYGI8SwVxpmjX2Bn27U2TcNodzTNROTusVEWO55+lmL+H9NczoQJk6mwZR
fontawesome/css/all.min.css|https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css|iw3OoTErCYJJB9mCa8LNS2hbsQ7M3C0EpIsO/H5+EGAkPGc6rk+V8i04oW/K5xq0
fontawesome/webfonts/fa-brands-400.woff2|https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-brands-400.woff2|
fontawesome/webfonts/fa-brands-400.ttf|https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-brands-400.ttf|
fontawesome/webfonts/fa-regular-400.woff2|https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-regular-400.woff2|
fontawesome/webfonts/fa-regular-400.ttf|https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-regular-400.ttf|
fontawesome/webfonts/fa-solid-900.woff2|https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.woff2|
fontawesome/webfonts/fa-solid-900.ttf|https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.ttf|
fontawesome/webfonts/fa-v4compatibility.woff2|https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-v4compatibility.woff2|
fontawesome/webfonts/fa-v4compatibility.ttf|https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-v4compatibility.ttf|
datatables/css/dataTables.bootstrap5.min.css|https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css|ok3J6xA9oQqai5C9ytYveFsBeKgoGk4T+NExsr6hoIKjZdv9SJcmx2mafwUWRNf9
datatables/css/buttons.bootstrap5.min.css|https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css|ElnGX1+feu5KEucSXJLJE9peLXQ3kZDXmq9a2q6nS26zB8zJ7j6ZlljCktlrgsUx
datatables/css/responsive.bootstrap5.min.css|https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css|jj44VXG857zuSsNQ7iqJihzOcCFRhs3qS4oLwyc4Hg+P9WjpwyR6T1ulnFKzhhaQ
datatables/css/colReorder.bootstrap5.min.css|https://cdn.datatables.net/colreorder/1.7.0/css/colReorder.bootstrap5.min.css|4Dbetv92yZvbtJgLxoVr1+212thg1GEstqR/f2wef3W5Wj9VCb1aEdudXltW27fP
datatables/css/select.bootstrap5.min.css|https://cdn.datatables.net/select/1.7.0/css/select.bootstrap5.min.css|BQuA/IRHdZd4G0fkajPKOBOE6lIuKmN2G95L52+ULcI1T/NGKY+gWsB/qDn6xxv7
datatables/js/jquery.dataTables.min.js|https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js|cjmdOgDzOE22dUheI5E6Gzd3upfmReW8N1y/4jwKQE50KYcvFKZJA9JxWgQOzqwQ
datatables/js/dataTables.bootstrap5.min.js|https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js|PgPBH0hy6DTJwu7pTf6bkRqPlf/+pjUBExpr/eIfzszlGYFlF9Wi9VTAJODPhgCO
datatables/js/dataTables.buttons.min.js|https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js|D7Ifmm6VZVyjXndPxbvw6UXiK3Vixw1lgK5y+0pVVaQvzHPCpx8atng0IjL5wdcQ
datatables/js/buttons.bootstrap5.min.js|https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js|8Fm9OFhJ1epvcmDiJTZ2SFHHZoCp/xJ8Ld7wG7/aUwGni32fG7LIhsNTFfEtUaSv
datatables/js/buttons.html5.min.js|https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js|On4D3nN2gGDU45v05twd+TTLH8PL+2gbsGVpnKEpSfv7pDd1pgEJxxc4k7iWeg9d
datatables/js/buttons.print.min.js|https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js|f5wX6gS38xMVGrwiXWKR7VijcBONISoTGfHF3N7yTwGrmvHaMHB4lq+VKxeZID10
datatables/js/buttons.colVis.min.js|https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js|dyI79OT6UE9qWK4x3HAQSilUPj6TfmkE8pX6KeBTyrhOaNrk7cbvUZUDMuC8S7/k
datatables/js/dataTables.responsive.min.js|https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js|VUnyCeQcqiiTlSM4AISHjJWKgLSM5VSyOeipcD9S/ybCKR3OhChZrPPjjrLfVV0y
datatables/js/responsive.bootstrap5.min.js|https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js|T6YQaHyTPTbybQQV23jtlugHCneQYjePXdcEU+KMWGQY8EUQygBW9pRx0zpSU0/i
datatables/i18n/es-ES.json|https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json|
jszip/jszip.min.js|https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js|+mbV2IY1Zk/X1p/nWllGySJSUN8uMs+gUAN10Or95UBH0fpj6GfKgPmgC5EXieXG
pdfmake/pdfmake.min.js|https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js|VFQrHzqBh5qiJIU0uGU5CIW3+OWpdGGJM9LBnGbuIH2mkICcFZ7lPd/AAtI7SNf7
pdfmake/vfs_fonts.js|https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js|/RlQG9uf0M2vcTw3CX7fbqgbj/h8wKxw7C3zu9/GxcBPRKOEcESxaxufwRXqzq6n
chartjs/chart.umd.min.js|https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js|e6nUZLBkQ86NJ6TVVKAeSaK8jWa3NhkYWZFomE39AvDbQWeie9PlQqM3pmYW5d1g
EOF
)

while IFS='|' read -r path url sha; do
    target="$DEST/$path"
    mkdir -p "$(dirname "$target")"
    curl -fsSL --retry 3 -o "$target.tmp" "$url"
    if [ -n "$sha" ]; then
        actual=$(openssl dgst -sha384 -binary "$target.tmp" | openssl base64 -A)
        [ "$actual" = "$sha" ] || { echo "Hash distinto para $url" >&2; rm -f "$target.tmp"; exit 1; }
    fi
    # Los source maps apuntan a archivos que no se publican; sin la línea el navegador no los pide
    case "$target" in *.js|*.css) sed -i '/^\/[*\/]# sourceMappingURL=/d' "$target.tmp" ;; esac
    mv "$target.tmp" "$target"
    echo "ok  $path"
done <<< "$FILES"
