@props(['disabled' => false])

<button
    {{ $disabled ? 'disabled' : '' }}
    {{ $attributes->merge([
        'type'  => 'submit',
        'class' =>
            'inline-flex items-center justify-center gap-2 ' .
            'rounded-full px-5 py-2.5 text-sm font-semibold ' .
            'text-white ' .
            'bg-gradient-to-r from-indigo-500 via-sky-500 to-cyan-500 ' .
            'shadow-sm shadow-indigo-200 ' .
            'ring-1 ring-inset ring-white/25 ' .
            'transition-all duration-200 ' .
            'hover:-translate-y-0.5 hover:shadow-md hover:shadow-indigo-200 ' .
            'active:translate-y-0 active:shadow-sm ' .
            'focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 ' .
            'disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:hover:shadow-sm'
    ]) }}
>
    {{ $slot }}
</button>
