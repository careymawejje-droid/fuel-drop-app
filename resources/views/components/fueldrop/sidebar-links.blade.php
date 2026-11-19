@foreach ($items as $item)
    @php
        $activePatterns = (array) ($item['active'] ?? []);
        $excludedPatterns = (array) ($item['exclude'] ?? []);
        $isActive = request()->routeIs(...$activePatterns)
            && ($excludedPatterns === [] || ! request()->routeIs(...$excludedPatterns));
    @endphp
    <a href="{{ route($item['route'], $item['parameters'] ?? []) }}"
        @if ($isActive) aria-current="page" @endif
        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition {{ $isActive ? 'bg-slate-800 font-medium text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
        <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4 {{ $isActive ? 'text-amber-400' : '' }}"></i>
        {{ $item['title'] }}
    </a>
@endforeach
