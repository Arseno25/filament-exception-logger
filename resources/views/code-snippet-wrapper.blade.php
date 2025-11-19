@php
    $record = $getRecord();
    $snippet = null;
    $extension = null;

    if ($record && $record->file && $record->line && file_exists($record->file)) {
        $snippet = $record->getSnippet();
        $extension = $record->getFileExtension();
    }
@endphp

@include('exception-logger::code-snippet', [
    'snippet' => $snippet,
    'extension' => $extension,
])

