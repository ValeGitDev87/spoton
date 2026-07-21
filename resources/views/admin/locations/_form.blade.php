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

<div class="grid3">
    <div class="field">
        <label for="city">Citta</label>
        <input id="city" name="city" value="{{ old('city', $location->city) }}" required>
    </div>

    <div class="field">
        <label for="type">Tipo</label>
        <select id="type" name="type" required>
            @foreach (['metro', 'bar', 'ristorante', 'piazza', 'lungomare', 'parco', 'altro'] as $type)
                <option value="{{ $type }}" @selected(old('type', $location->type) === $type)>{{ ucfirst($type) }}</option>
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
    </script>
@endpush

<div class="grid3">
    <div class="field">
        <label for="latitude">Latitudine</label>
        <input id="latitude" name="latitude" type="number" step="0.0000001" min="-90" max="90" value="{{ old('latitude', $location->latitude) }}" required>
    </div>

    <div class="field">
        <label for="longitude">Longitudine</label>
        <input id="longitude" name="longitude" type="number" step="0.0000001" min="-180" max="180" value="{{ old('longitude', $location->longitude) }}" required>
    </div>

    <div class="field">
        <label for="geo_radius_meters">Raggio metri</label>
        <input id="geo_radius_meters" name="geo_radius_meters" type="number" min="1" max="200000" value="{{ old('geo_radius_meters', $location->geo_radius_meters ?? 100) }}" required>
    </div>
</div>

<label class="check-row">
    <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $location->is_active ?? true))>
    <span>Luogo attivo</span>
</label>

<div class="actions" style="justify-content:flex-start;">
    <button class="btn" type="submit">{{ $submitLabel }}</button>
    <a class="btn secondary" href="{{ route('admin.locations.index') }}">Annulla</a>
</div>
