<div class="mb-3">
    <label for="{{ $id }}" class="form-label">Persona vinculada</label>
    <select name="person_id" id="{{ $id }}" class="form-select">
        <option value="">— Sin persona —</option>
        @foreach ($people as $person)
            <option value="{{ $person->id }}" @selected((string) ($selected ?? '') === (string) $person->id)>
                {{ $person->full_name }}{{ $person->rut ? ' (' . $person->rut . ')' : '' }}
            </option>
        @endforeach
    </select>
    <div class="form-text">Un usuario sin rol de jefe o tesorero solo ve las rendiciones y la cuenta de esta persona.</div>
    <div class="invalid-feedback"></div>
</div>
