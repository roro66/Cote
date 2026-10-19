// RUT chileno: formateo y verificación del dígito verificador mientras se escribe.
// Se aplica a todo <input data-rut>. El servidor valida igual (App\Rules\ValidChileanRut).

const INVALID_MESSAGE = 'RUT inválido: el dígito verificador no corresponde.';
const INCOMPLETE_MESSAGE = 'RUT incompleto.';

function clean(value) {
    return String(value || '').replace(/[^0-9kK]/g, '').toUpperCase();
}

function checkDigit(number) {
    let sum = 0;
    let multiplier = 2;
    for (let i = number.length - 1; i >= 0; i--) {
        sum += Number(number[i]) * multiplier;
        multiplier = multiplier === 7 ? 2 : multiplier + 1;
    }
    const dv = 11 - (sum % 11);
    return dv === 11 ? '0' : dv === 10 ? 'K' : String(dv);
}

export function formatRut(value) {
    const raw = clean(value).replace(/K(?=.)/g, ''); // la K solo puede ser el último carácter
    if (raw.length < 2) return raw;
    const number = raw.slice(0, -1).replace(/^0+/, '');
    return number.replace(/\B(?=(\d{3})+(?!\d))/g, '.') + '-' + raw.slice(-1);
}

export function isValidRut(value) {
    const raw = clean(value);
    if (!/^\d{1,8}[0-9K]$/.test(raw)) return false;
    return checkDigit(raw.slice(0, -1)) === raw.slice(-1);
}

function feedbackFor(input) {
    return input.parentElement.querySelector('.invalid-feedback');
}

/**
 * Marca el campo como válido/inválido. Con final=false (mientras escribe) solo opina cuando el RUT
 * parece completo (7+ dígitos y verificador), para no marcar en rojo a mitad de la escritura.
 */
function checkInput(input, final) {
    const raw = clean(input.value);
    const feedback = feedbackFor(input);

    if (raw === '') {
        input.classList.remove('is-valid', 'is-invalid');
        input.setCustomValidity('');
        return !input.required;
    }

    const valid = isValidRut(raw);
    const complete = raw.length >= 8;
    const message = valid ? '' : (complete ? INVALID_MESSAGE : INCOMPLETE_MESSAGE);
    input.setCustomValidity(message);

    if (!final && !complete) {
        input.classList.remove('is-valid', 'is-invalid');
        return valid;
    }

    input.classList.toggle('is-valid', valid);
    input.classList.toggle('is-invalid', !valid);
    if (feedback) feedback.textContent = message;

    return valid;
}

function handle(event) {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || !input.matches('[data-rut]')) return;

    // Pequeño retraso para convivir con autocompletadores que escriben por partes
    setTimeout(() => {
        const formatted = formatRut(input.value);
        if (formatted !== input.value) input.value = formatted;
        checkInput(input, event.type === 'blur' || event.type === 'change');
    }, 10);
}

['input', 'change', 'blur', 'paste'].forEach(type => document.addEventListener(type, handle, true));

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[data-rut]').forEach(input => {
        if (input.value) {
            input.value = formatRut(input.value);
            checkInput(input, true);
        }
    });
});

// Para formularios enviados por JavaScript: valida todos los RUT del formulario antes de enviarlo
window.Rut = {
    format: formatRut,
    isValid: isValidRut,
    checkForm(form) {
        let ok = true;
        form.querySelectorAll('input[data-rut]').forEach(input => {
            input.value = formatRut(input.value);
            if (!checkInput(input, true)) ok = false;
        });
        return ok;
    },
};

// Compatibilidad con código existente que llamaba formatRut(input)
window.formatRut = input => {
    input.value = formatRut(input.value);
    checkInput(input, false);
};
