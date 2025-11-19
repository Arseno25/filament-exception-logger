<?php

namespace Arseno25\ExceptionLogger\Components;

use Arseno25\ExceptionLogger\Models\ExceptionLog;
use Livewire\Component;

class CodeSnippet extends Component
{
    public ExceptionLog $record;

    public function mount(ExceptionLog $record): void
    {
        $this->record = $record;
    }

    public function render()
    {
        $snippet = $this->record->getSnippet(10);
        $extension = $this->record->getFileExtension();

        return view('exception-logger::code-snippet', [
            'snippet' => $snippet,
            'extension' => $extension,
        ]);
    }
}

