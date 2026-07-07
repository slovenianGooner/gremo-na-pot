<div class="place-row-group">
    <form action="{{ $place ? route('admin.places.update', $place) : '__UPDATE_URL__' }}" method="POST" class="place-row">
        @csrf
        @method('PUT')
        <input type="number" name="order" value="{{ $place?->order ?? '__ORDER__' }}" min="0">
        <input type="text" name="name" value="{{ $place?->name ?? '__NAME__' }}">
        <button type="submit" class="link-btn">{{ __('Save') }}</button>
    </form>
    <form action="{{ $place ? route('admin.places.destroy', $place) : '__DESTROY_URL__' }}" method="POST" class="place-row" onsubmit="return confirm('{{ __('Remove this place?') }}');" style="padding-top:0;">
        @csrf
        @method('DELETE')
        <button type="submit" class="link-btn" style="color:#ffb3b3;">{{ __('Delete') }} "{{ $place?->name ?? '__NAME__' }}"</button>
    </form>
</div>
