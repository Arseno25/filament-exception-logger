@props(['content', 'error' => null])

@if ($error)
    <div class="exception-logger-ai-solution__alert">
        <x-heroicon-m-x-circle class="exception-logger-ai-solution__icon h-5 w-5" />
        <div class="text-sm">
            <h3>Analysis Failed</h3>
            <p class="text-xs">{{ $error }}</p>
        </div>
    </div>
@else
    <div class="exception-logger-ai-solution__content custom-scrollbar">
        <div class="break-words text-sm leading-relaxed">
            {!! \Illuminate\Support\Str::markdown($content) !!}
        </div>
    </div>
@endif
