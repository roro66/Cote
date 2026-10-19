// Columnas que exportan los botones de DataTables: las que el usuario no ocultó con «Columnas»,
// aunque Responsive las esconda por el ancho de pantalla (con ':visible' se perdían), menos Acciones.
// Al ocultarla con «Columnas», DataTables saca su encabezado del DOM; Responsive solo le pone display:none.
window.dtExportColumns = function (idx, data, header) {
    return header.isConnected && !header.matches(':last-child');
};

// Ajusta el ancho de las columnas de los Excel que genera DataTables al texto más largo de cada una.
// DataTables limita el ancho a ~54 caracteres; aquí se usan los mismos límites que en el servidor
// (App\Support\XlsxColumnWidths).
const MIN_WIDTH = 8;
const MAX_WIDTH = 100;

function columnNumber(cellRef) {
    const letters = cellRef.replace(/[0-9]/g, '');
    let n = 0;
    for (const ch of letters) {
        n = n * 26 + (ch.charCodeAt(0) - 64);
    }
    return n;
}

function cellText(cell) {
    const texts = cell.getElementsByTagName('t');
    if (texts.length) {
        return Array.from(texts).map((t) => t.textContent).join('');
    }
    const value = cell.getElementsByTagName('v')[0];
    if (!value) return '';
    const number = Number(value.textContent);
    // Excel muestra los números con separador de miles
    return Number.isFinite(number) ? number.toLocaleString('es-CL') : value.textContent;
}

function mergedTitleCells(sheet) {
    const refs = new Set();
    for (const merge of Array.from(sheet.getElementsByTagName('mergeCell'))) {
        const [start, end] = (merge.getAttribute('ref') || '').split(':');
        if (end && columnNumber(start) !== columnNumber(end)) {
            refs.add(start);
        }
    }
    return refs;
}

export function autoFitColumns(xlsx) {
    const sheet = xlsx.xl.worksheets['sheet1.xml'];
    const skip = mergedTitleCells(sheet);
    const lengths = {};

    for (const cell of Array.from(sheet.getElementsByTagName('c'))) {
        const ref = cell.getAttribute('r');
        if (!ref || skip.has(ref)) continue;
        const longestLine = Math.max(...cellText(cell).split(/\r?\n/).map((line) => line.length));
        const column = columnNumber(ref);
        lengths[column] = Math.max(lengths[column] || 0, longestLine);
    }

    for (const col of Array.from(sheet.getElementsByTagName('col'))) {
        const column = Number(col.getAttribute('min'));
        if (!(column in lengths)) continue;
        const width = Math.min(MAX_WIDTH, Math.max(MIN_WIDTH, Math.ceil(lengths[column] * 1.1) + 2));
        col.setAttribute('width', width);
        col.setAttribute('customWidth', '1');
    }
}

function install() {
    const excel = window.jQuery?.fn?.dataTable?.ext?.buttons?.excelHtml5;
    if (!excel || excel.__autoFit) return;

    const originalAction = excel.action;
    excel.action = function (e, dt, button, config, cb) {
        const customize = config.customize;
        const fitted = Object.assign({}, config, {
            customize(xlsx, buttonConfig, api) {
                if (typeof customize === 'function') {
                    customize.call(this, xlsx, buttonConfig, api);
                }
                autoFitColumns(xlsx);
            },
        });
        return originalAction.call(this, e, dt, button, fitted, cb);
    };
    excel.__autoFit = true;
}

install();
