@php
    $record = $getRecord();
@endphp

@if($record && $record->file && $record->line && file_exists($record->file))
    <div>
        @livewire(\Arseno25\ExceptionLogger\Components\CodeSnippet::class, ['record' => $record], key('code-snippet-'.$record->id))
    </div>
@else
    <div class="exception-logger-code-snippet__empty">
        <p class="text-sm text-gray-500">Source code preview is not available. File may not exist or is not accessible.</p>
    </div>
@endif

