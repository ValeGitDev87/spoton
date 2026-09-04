@csrf

@if ($errors->any())
    <div class="errors">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="grid">
    <div class="field">
        <label for="name">Nome luogo</label>
        <input id="name" name="name" value="{{ old('name', $location->name) }}" required>
    </div>

    <div class="field">
        <label for="short">Nome breve</label>
        <input id="short" name="short" value="{{ old('short', $location->short) }}">
    </div>
</div>

<div class="grid">
    <div class="field">
        <label for="tier">Gestione luogo</label>
        <select id="tier" name="tier" required>
            <option value="partner" @selected(old('tier', $location->tier ?? 'partner') === 'partner')>Partner</option>
            <option value="community" @selected(old('tier', $location->tier) === 'community')>Community</option>
        </select>
        <small class="field-help">I luoghi creati da questo pannello sono Partner per impostazione predefinita.</small>
    </div>

    <div class="field">
        <label for="moderation_status">Moderazione</label>
        <select id="moderation_status" name="moderation_status" required>
            <option value="approved" @selected(old('moderation_status', $location->moderation_status ?? 'approved') === 'approved')>Approvato</option>
            <option value="pending" @selected(old('moderation_status', $location->moderation_status) === 'pending')>Da controllare</option>
            <option value="rejected" @selected(old('moderation_status', $location->moderation_status) === 'rejected')>Respinto</option>
            <option value="suspended" @selected(old('moderation_status', $location->moderation_status) === 'suspended')>Sospeso</option>
        </select>
    </div>
</div>

@if ($location->exists && $location->creator)
    <div class="alert">
        Creato dalla community: {{ $location->creator->display_name }} ({{ $location->creator->email }}).
    </div>
@endif

<div class="grid3">
    <div class="field">
        <label for="city">Citta</label>
        <input id="city" name="city" value="{{ old('city', $location->city) }}" required>
    </div>

    <div class="field">
        <label for="type">Tipo</label>
        <select id="type" name="type" required>
            @foreach ([
                'metro' => 'Metropolitana',
                'bus' => 'Bus',
                'ristorante' => 'Ristorante',
                'bar' => 'Bar',
                'universita' => 'Universita',
                'locale' => 'Locale',
                'discoteca' => 'Discoteca',
                'piazza' => 'Piazza',
                'lungomare' => 'Lungomare',
                'parco' => 'Parco',
                'altro' => 'Altro',
            ] as $type => $label)
                <option value="{{ $type }}" @selected(old('type', $location->type) === $type)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label for="icon">Icona</label>
        <div class="icon-picker">
            <div class="icon-preview" aria-hidden="true">
                <ion-icon id="location-icon-preview" name="{{ old('icon', $location->icon) }}"></ion-icon>
            </div>
            <select id="icon" name="icon" required>
                @foreach ($iconOptions as $iconCode => $iconLabel)
                    <option value="{{ $iconCode }}" @selected(old('icon', $location->icon) === $iconCode)>
                        {{ $iconLabel }} ({{ $iconCode }})
                    </option>
                @endforeach
            </select>
        </div>
        <small class="field-help">L'anteprima sara usata anche nelle storie e accanto al luogo nell'app.</small>
    </div>
</div>

@push('scripts')
    <script>
        document.getElementById('icon').addEventListener('change', function () {
            document.getElementById('location-icon-preview').setAttribute('name', this.value);
        });

        const locationLock = document.getElementById('is_locked');
        const locationTier = document.getElementById('tier');
        const accessPasswordField = document.getElementById('access-password-field');
        const accessPassword = document.getElementById('access_password');

        function syncLocationLock() {
            const isCommunity = locationTier.value === 'community';
            if (isCommunity) {
                locationLock.checked = false;
            }
            locationLock.disabled = isCommunity;
            accessPasswordField.hidden = !locationLock.checked;
            accessPassword.required = locationLock.checked
                && accessPassword.dataset.hasPassword !== '1';
        }

        locationLock.addEventListener('change', syncLocationLock);
        locationTier.addEventListener('change', syncLocationLock);
        syncLocationLock();
    </script>
@endpush

<div class="grid3">
    <div class="field">
        <label for="latitude">Latitudine</label>
        <input id="latitude" name="latitude" type="text" inputmode="decimal" maxlength="20" value="{{ old('latitude', $location->latitude) }}" required>
    </div>

    <div class="field">
        <label for="longitude">Longitudine</label>
        <input id="longitude" name="longitude" type="text" inputmode="decimal" maxlength="20" value="{{ old('longitude', $location->longitude) }}" required>
    </div>

    <div class="field">
        <label for="geo_radius_meters">Raggio metri</label>
        <input id="geo_radius_meters" name="geo_radius_meters" type="number" min="1" max="200000" value="{{ old('geo_radius_meters', $location->geo_radius_meters ?? 100) }}" required>
    </div>
</div>

@if ($location->exists)
    <div class="field">
        <label for="moderation_note">Nota moderazione</label>
        <textarea id="moderation_note" maxlength="1000" name="moderation_note" rows="3">{{ old('moderation_note', $location->moderation_note) }}</textarea>
        <small class="field-help">La nota e visibile all'utente che ha proposto il luogo.</small>
    </div>
@endif

<div class="grid">
    <div>
        <label class="check-row">
            <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $location->is_active ?? true))>
            <span>Luogo attivo</span>
        </label>

        <label class="check-row">
            <input id="is_locked" name="is_locked" type="checkbox" value="1" @checked(old('is_locked', $location->is_locked ?? false))>
            <span>Luogo riservato con password</span>
        </label>
    </div>

    <div class="field" id="access-password-field">
        <label for="access_password">Password del luogo</label>
        <input
            autocomplete="new-password"
            data-has-password="{{ $location->access_password_hash ? '1' : '0' }}"
            id="access_password"
            maxlength="255"
            minlength="4"
            name="access_password"
            placeholder="{{ $location->access_password_hash ? 'Lascia vuoto per mantenere quella attuale' : 'Minimo 4 caratteri' }}"
            type="password"
        >
        <small class="field-help">
            La password viene salvata in forma protetta e non sara mai mostrata nell'app.
        </small>
    </div>
</div>

<div class="actions" style="justify-content:flex-start;">
    <button class="btn" type="submit">{{ $submitLabel }}</button>
    <a class="btn secondary" href="{{ route('admin.locations.index') }}">Annulla</a>
</div>
