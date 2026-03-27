@props([
    'href',
    'active' => false,
    'label',
    'icon' => '',
])

<a href="{{ $href }}"
   {{ $attributes->merge([
      'class' => 'flex items-center gap-3 px-2 py-2 rounded-lg text-sm transition-colors ' .
        ($active
          ? 'bg-blue-600/20 text-slate-100 font-medium'
          : 'text-slate-400 hover:bg-white/7 hover:text-slate-300')
   ]) }}>
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
       stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
       class="flex-shrink-0 opacity-75">
    {!! $icon !!}
  </svg>
  <span>{{ $label }}</span>
</a>
