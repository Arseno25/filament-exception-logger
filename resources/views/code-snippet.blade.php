@if ($snippet)
    <div class="exception-logger-code-snippet">
        <div class="exception-logger-code-snippet__header">
            <div class="exception-logger-code-snippet__header-left">
                <span class="exception-logger-code-snippet__file-name">{{ basename($snippet['file']) }}</span>
                <span class="exception-logger-code-snippet__file-path">{{ $snippet['file'] }}</span>
            </div>
        </div>
        <div class="exception-logger-code-snippet__meta">
            @if (!empty($snippet['errorLine']))
                <span class="exception-logger-code-snippet__line-badge">Line {{ $snippet['errorLine'] }}</span>
            @endif
            <span class="exception-logger-code-snippet__language">{{ strtoupper($extension ?? 'text') }}</span>
        </div>
        <div class="exception-logger-code-snippet__container">
            <div class="exception-logger-code-snippet__rows">
                @foreach ($snippet['lines'] as $lineNumber => $line)
                    <div class="exception-logger-code-snippet__row {{ $lineNumber === $snippet['errorLine'] ? 'exception-logger-code-snippet__row--error' : '' }}">
                        <div class="exception-logger-code-snippet__line-number">
                            {{ $lineNumber }}
                        </div>
                        <pre class="exception-logger-code-snippet__line-code"><code>{{ htmlspecialchars($line) }}</code></pre>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@else
    <div class="exception-logger-code-snippet__empty">
        <p class="text-sm text-gray-500">Source code preview is not available. File may not exist or is not accessible.</p>
    </div>
@endif

