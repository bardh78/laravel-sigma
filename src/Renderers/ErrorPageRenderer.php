<?php

namespace Bardh78\LaravelSigma\Renderers;

use Illuminate\Foundation\Exceptions\Renderer\Renderer;
use Throwable;

class ErrorPageRenderer
{
    protected Renderer $laravelRenderer;

    public function __construct(Renderer $laravelRenderer)
    {
        $this->laravelRenderer = $laravelRenderer;
    }

    public function render(Throwable $throwable): void
    {
        // Use Laravel's native error renderer
        $html = $this->laravelRenderer->render(request(), $throwable);
        
        // Enhance with Sigma features
        $html = $this->enhanceWithSigma($html, $throwable);
        
        echo $html;
    }

    protected function enhanceWithSigma(string $html, Throwable $throwable): string
    {
        // Add Sigma enhancements
        $sigmaScript = $this->getSigmaScript($throwable);
        $sigmaStyles = $this->getSigmaStyles();
        $sigmaCopyButton = $this->getSigmaCopyButton();
        
        // Inject Sigma styles before closing head tag
        $html = $this->insertBeforeClosingTag($html, 'head', $sigmaStyles, false);
        
        // Inject Sigma script before closing body tag
        $html = $this->insertBeforeClosingTag($html, 'body', $sigmaScript);

        // Inject floating Copy Error button before closing body tag
        $html = $this->insertBeforeClosingTag($html, 'body', $sigmaCopyButton);
        
        return $html;
    }

    /**
     * Insert a fragment before the requested closing tag, defaulting to the last occurrence.
     */
    protected function insertBeforeClosingTag(string $html, string $tag, string $insertion, bool $useLastMatch = true): string
    {
        $closingTag = sprintf('</%s>', $tag);
        $position = $useLastMatch ? strripos($html, $closingTag) : stripos($html, $closingTag);

        if ($position === false) {
            if ($tag === 'head') {
                return $insertion . $html;
            }

            return $html . $insertion;
        }

        return substr_replace($html, $insertion, $position, 0);
    }

    protected function getSigmaScript(Throwable $throwable): string
    {
        $errorData = [
            'message' => $throwable->getMessage(),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'class' => get_class($throwable),
        ];
        
        $sigmaConfig = [
            'editor' => config('sigma.editor', 'vscode'),
            'editorOptions' => config('sigma.editor_options', []),
            'remoteSitesPath' => config('sigma.remote_sites_path'),
            'localSitesPath' => config('sigma.local_sites_path'),
        ];

        $jsonData = json_encode($errorData, JSON_UNESCAPED_SLASHES);
        $configJson = json_encode($sigmaConfig, JSON_UNESCAPED_SLASHES);
        
        // Encode as base64 for safe JavaScript embedding
        $jsonDataB64 = base64_encode($jsonData);
        $configJsonB64 = base64_encode($configJson);

        return <<<HTML
        <script>
            window.sigmaErrorData = JSON.parse(atob('{$jsonDataB64}'));
            window.sigmaConfig = JSON.parse(atob('{$configJsonB64}'));

            const sigmaIcons = {
                check: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>'
            };

            const sigmaPreferredEditors = ['phpstorm', 'windsurf', 'vscode', 'zed'];

            window.sigmaSelectedEditor = (window.sigmaConfig && window.sigmaConfig.editor) || 'vscode';

            const sigmaResolveEditorTarget = function(explicitKey) {
                const data = window.sigmaErrorData || {};
                const config = window.sigmaConfig || {};
                const options = config.editorOptions || {};
                let editorKey = explicitKey || window.sigmaSelectedEditor || config.editor || '';

                if (!options[editorKey]) {
                    const fallback = sigmaPreferredEditors.find(function(key) {
                        return options[key];
                    }) || Object.keys(options)[0];

                    if (fallback) {
                        editorKey = fallback;
                    }
                }

                if (!editorKey) {
                    editorKey = '';
                }

                window.sigmaSelectedEditor = editorKey;

                const select = document.querySelector('.sigma-editor-select');
                if (select && editorKey) {
                    const hasOption = Array.from(select.options).some(function(option) {
                        return option.value === editorKey;
                    });

                    if (hasOption) {
                        select.value = editorKey;
                    }
                }

                const selected = options[editorKey];
                const lineValue = data.line ?? 1;
                let filePath = data.file || '';

                const remoteBase = config.remoteSitesPath || '';
                const localBase = config.localSitesPath || '';

                if (remoteBase && localBase && filePath.startsWith(remoteBase)) {
                    filePath = localBase + filePath.slice(remoteBase.length);
                }

                if (!selected) {
                    console.warn('Sigma editor configuration missing for key:', editorKey);
                    return {
                        url: filePath + ':' + lineValue,
                        clipboard: true,
                    };
                }

                const isClipboard = !!selected.clipboard;
                const pathToken = isClipboard ? filePath : encodeURIComponent(filePath);
                const lineToken = isClipboard ? String(lineValue) : encodeURIComponent(String(lineValue));
                const resolvedUrl = (selected.url || '')
                    .replace('%path', pathToken)
                    .replace('%line', lineToken);

                return {
                    url: resolvedUrl,
                    clipboard: isClipboard,
                };
            };
            
            window.sigmaHandleEditorChange = function(editorKey) {
                window.sigmaSelectedEditor = editorKey;
            };

            const sigmaResetEditorButton = function(btn) {
                if (!btn) {
                    return;
                }

                if (btn.dataset.defaultHtml) {
                    btn.innerHTML = btn.dataset.defaultHtml;
                }
                btn.classList.remove('success');
            };

            const sigmaFlashEditorButton = function(btn, html, className) {
                if (!btn) {
                    return;
                }

                if (!btn.dataset.defaultHtml) {
                    btn.dataset.defaultHtml = btn.innerHTML;
                }

                btn.innerHTML = html;

                if (className) {
                    btn.classList.add(className);
                }

                setTimeout(function() {
                    sigmaResetEditorButton(btn);
                }, 2000);
            };

            document.addEventListener('DOMContentLoaded', function() {
                const select = document.querySelector('.sigma-editor-select');
                if (!select) {
                    return;
                }

                const config = window.sigmaConfig || {};
                const options = config.editorOptions || {};

                Array.from(select.options).forEach(function(option) {
                    if (!options[option.value]) {
                        option.disabled = true;
                    }
                });

                const initialKey = window.sigmaSelectedEditor && options[window.sigmaSelectedEditor]
                    ? window.sigmaSelectedEditor
                    : sigmaPreferredEditors.find(function(key) {
                        return options[key];
                    });

                const fallbackOption = initialKey
                    ? initialKey
                    : (Array.from(select.options).find(function(option) {
                        return !option.disabled;
                    }) || { value: select.value }).value;

                if (fallbackOption) {
                    select.value = fallbackOption;
                    window.sigmaSelectedEditor = fallbackOption;
                }
            });
            
            // Copy error functionality
            window.sigmaCopyError = function() {
                const errorText = window.sigmaErrorData.class + ': ' + window.sigmaErrorData.message + '\\n' +
                                 'File: ' + window.sigmaErrorData.file + ':' + window.sigmaErrorData.line;
                
                navigator.clipboard.writeText(errorText).then(function() {
                    const btn = document.querySelector('.sigma-copy-btn');
                    const originalText = btn.innerHTML;
                    btn.classList.add('copied');
                    btn.innerHTML = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                    
                    setTimeout(function() {
                        btn.innerHTML = originalText;
                        btn.classList.remove('copied');
                    }, 2000);
                }).catch(function(err) {
                    console.error('Failed to copy error:', err);
                    alert('Failed to copy to clipboard');
                });
            };
            
            // Open with editor functionality
            window.sigmaOpenWithEditor = function() {
                const select = document.querySelector('.sigma-editor-select');
                const editorKey = select ? select.value : undefined;
                window.sigmaSelectedEditor = editorKey || window.sigmaSelectedEditor;

                const target = sigmaResolveEditorTarget(editorKey);
                const btn = document.querySelector('.sigma-editor-btn');

                if (!target.url) {
                    console.error('Unable to resolve editor target.');
                    alert('Sigma could not determine how to open the editor. Check your Sigma configuration.');
                    return;
                }

                if (target.clipboard) {
                    navigator.clipboard.writeText(target.url).then(function() {
                        sigmaFlashEditorButton(btn, sigmaIcons.check + ' Path copied!', 'success');
                    }).catch(function(err) {
                        console.error('Failed to copy editor path:', err);
                        alert('Failed to copy the editor path to your clipboard.');
                    });
                    return;
                }

                try {
                    window.location.href = target.url;
                    sigmaFlashEditorButton(btn, sigmaIcons.check + ' Opening...', 'success');
                } catch (err) {
                    console.error('Failed to trigger editor protocol:', err);
                    alert('Failed to trigger the editor protocol. Please verify your Sigma editor configuration.');
                }
            };
            
            // Dropdown toggle functionality
            window.sigmaTriggerDropdown = function() {
                const container = document.querySelector('.sigma-dropdown-container');
                const menu = document.getElementById('sigma-dropdown-menu');
                
                if (!container || !menu) {
                    return;
                }
                
                const isOpen = menu.style.display !== 'none';
                
                if (isOpen) {
                    menu.style.display = 'none';
                    container.classList.remove('active');
                } else {
                    menu.style.display = 'block';
                    container.classList.add('active');
                }
            };
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                const container = document.querySelector('.sigma-dropdown-container');
                if (container && !container.contains(event.target)) {
                    const menu = document.getElementById('sigma-dropdown-menu');
                    if (menu) {
                        menu.style.display = 'none';
                        container.classList.remove('active');
                    }
                }
            });
            
            // Initialize Sigma
            (function() {
                console.log('Sigma Error Enhancement Active', window.sigmaErrorData);
            })();
        </script>
        HTML;
    }

    protected function getSigmaStyles(): string
    {
        return <<<HTML
        <style>
            /* Sigma error page enhancements */
            :root {
                /* Light mode: bg-red-50 with red accents */
                --sigma-btn-bg: #fef2f2; /* red-50 */
                --sigma-btn-bg-hover: #fee2e2; /* red-100 */
                --sigma-btn-bg-active: #fee2e2; /* red-100 */
                --sigma-btn-border: #fecaca; /* red-200 */
                --sigma-btn-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
                --sigma-btn-shadow-hover: 0 6px 14px rgba(0, 0, 0, 0.12);
                --sigma-btn-shadow-active: 0 2px 8px rgba(0, 0, 0, 0.08);
                --sigma-btn-text: #991b1b; /* red-800 for contrast on red-50 */
                --sigma-btn-bg-copied: linear-gradient(135deg, #34d399 0%, #10b981 100%); /* green-400 -> green-500 */
                --sigma-btn-shadow-copied: 0 4px 12px rgba(52, 211, 153, 0.25);
            }

            @media (prefers-color-scheme: dark) {
                :root {
                    /* Dark mode: black button with red-100 border */
                    --sigma-btn-bg: #1a1919; /* true black */
                    --sigma-btn-bg-hover: #0a0a0a; /* slightly lighter on hover */
                    --sigma-btn-bg-active: #0a0a0a;
                    --sigma-btn-border: #823333; /* red-100 */
                    --sigma-btn-shadow: 0 6px 18px rgba(0, 0, 0, 0.50);
                    --sigma-btn-shadow-hover: 0 8px 22px rgba(0, 0, 0, 0.55);
                    --sigma-btn-shadow-active: 0 3px 12px rgba(0, 0, 0, 0.45);
                    --sigma-btn-text: #ffffff;
                    --sigma-btn-bg-copied: linear-gradient(135deg, #e76363 0%, #7e3b3b 100%); /* green-400 -> green-500 */
                    --sigma-btn-shadow-copied: 0 6px 18px rgb(119,69,69);
                }
            }

            .sigma-badge {
                display: inline-flex;
                align-items: center;
                gap: 0.375rem;
                background: rgba(239, 68, 68, 0.08);
                color: var(--sigma-btn-text);
                padding: 0.375rem 0.875rem;
                border-radius: 9999px;
                font-size: 0.75rem;
                font-weight: 600;
                margin-left: 0.75rem;
                backdrop-filter: saturate(120%);
            }
            
            .sigma-badge::before {
                content: '\ud83d\udd0d';
                font-size: 0.875rem;
            }
            
            .sigma-actions {
                position: fixed;
                bottom: 30px;
                right: 30px;
                z-index: 9999;
                display: flex;
                gap: 0.75rem;
                align-items: center;
                justify-content: flex-end;
                flex-wrap: wrap;
            }

            .sigma-copy-btn,
            .sigma-editor-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                background: var(--sigma-btn-bg);
                color: var(--sigma-btn-text);
                padding: 0.75rem 1.25rem;
                border-radius: 0.5rem;
                font-size: 0.9375rem;
                font-weight: 600;
                border: 1px solid var(--sigma-btn-border);
                cursor: pointer;
                transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
                box-shadow: var(--sigma-btn-shadow);
            }

            .sigma-copy-btn:hover,
            .sigma-editor-btn:hover {
                transform: translateY(-2px);
                box-shadow: var(--sigma-btn-shadow-hover);
                background: var(--sigma-btn-bg-hover);
            }

            .sigma-copy-btn:active,
            .sigma-editor-btn:active {
                transform: translateY(0);
                box-shadow: var(--sigma-btn-shadow-active);
                background: var(--sigma-btn-bg-active);
            }

            .sigma-copy-btn svg,
            .sigma-editor-btn svg {
                width: 1rem;
                height: 1rem;
            }

            .sigma-copy-btn.copied {
                background: var(--sigma-btn-bg-copied);
                color: #ffffff;
                box-shadow: var(--sigma-btn-shadow-copied);
            }
            
            .sigma-editor-btn.success {
                background: var(--sigma-btn-bg-copied);
                color: #ffffff;
                box-shadow: var(--sigma-btn-shadow-copied);
            }

            .sigma-dropdown-container {
                position: relative;
                display: inline-block;
            }

            .sigma-main-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                background: var(--sigma-btn-bg);
                color: var(--sigma-btn-text);
                padding: 0.75rem 1.25rem;
                border-radius: 0.5rem;
                font-size: 0.9375rem;
                font-weight: 600;
                border: 1px solid var(--sigma-btn-border);
                cursor: pointer;
                transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
                box-shadow: var(--sigma-btn-shadow);
            }

            .sigma-main-btn:hover {
                transform: translateY(-2px);
                box-shadow: var(--sigma-btn-shadow-hover);
                background: var(--sigma-btn-bg-hover);
            }

            .sigma-main-btn:active {
                transform: translateY(0);
                box-shadow: var(--sigma-btn-shadow-active);
                background: var(--sigma-btn-bg-active);
            }

            .sigma-main-btn svg {
                width: 1rem;
                height: 1rem;
            }

            .sigma-chevron {
                width: 1rem;
                height: 1rem;
                transition: transform 0.2s ease;
            }

            .sigma-dropdown-container.active .sigma-chevron {
                transform: rotate(180deg);
            }

            .sigma-dropdown-menu {
                position: absolute;
                bottom: 100%;
                right: 0;
                margin-bottom: 0.5rem;
                background: var(--sigma-btn-bg);
                border: 1px solid var(--sigma-btn-border);
                border-radius: 0.5rem;
                box-shadow: var(--sigma-btn-shadow-hover);
                min-width: 220px;
                overflow: hidden;
                z-index: 10000;
                animation: sigmaPulse 0.15s ease;
            }

            .sigma-dropdown-item {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                width: 100%;
                padding: 0.75rem 1rem;
                background: transparent;
                color: var(--sigma-btn-text);
                border: none;
                cursor: pointer;
                font-size: 0.9375rem;
                font-weight: 500;
                transition: background 0.15s ease;
                text-align: left;
            }

            .sigma-dropdown-item:hover {
                background: var(--sigma-btn-bg-hover);
            }

            .sigma-dropdown-item svg {
                width: 1rem;
                height: 1rem;
                flex-shrink: 0;
            }

            .sigma-dropdown-divider {
                height: 1px;
                background: var(--sigma-btn-border);
                margin: 0.5rem 0;
            }

            .sigma-dropdown-section {
                padding: 0.75rem 1rem;
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }

            .sigma-dropdown-label {
                font-size: 0.75rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                color: var(--sigma-btn-text);
                opacity: 0.7;
            }

            .sigma-editor-select {
                appearance: none;
                background: var(--sigma-btn-bg-hover);
                color: var(--sigma-btn-text);
                border: 1px solid var(--sigma-btn-border);
                border-radius: 0.375rem;
                padding: 0.5rem 1.75rem 0.5rem 0.75rem;
                font-size: 0.875rem;
                cursor: pointer;
                transition: background 0.15s ease;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' stroke='%23991b1b' viewBox='0 0 24 24'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 14l-7 7m0 0l-7-7m7 7V3'%3E%3C/path%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: right 0.5rem center;
                background-size: 1rem;
                padding-right: 2rem;
            }

            @media (prefers-color-scheme: dark) {
                .sigma-editor-select {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' stroke='%23ffffff' viewBox='0 0 24 24'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 14l-7 7m0 0l-7-7m7 7V3'%3E%3C/path%3E%3C/svg%3E");
                }
            }

            .sigma-editor-select:focus {
                outline: none;
                background-color: var(--sigma-btn-bg);
            }

            .sigma-editor-select option:disabled {
                color: #9ca3af;
            }

            .sigma-editor-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                background: var(--sigma-btn-bg-hover);
                color: var(--sigma-btn-text);
                padding: 0.5rem 0.875rem;
                border-radius: 0.375rem;
                font-size: 0.875rem;
                font-weight: 600;
                border: 1px solid var(--sigma-btn-border);
                cursor: pointer;
                transition: background 0.15s ease;
                width: 100%;
                justify-content: center;
            }

            .sigma-editor-btn:hover {
                background: var(--sigma-btn-bg);
            }

            .sigma-editor-btn svg {
                width: 0.875rem;
                height: 0.875rem;
            }

            .sigma-editor-btn.success {
                background: var(--sigma-btn-bg-copied);
                color: #ffffff;
                box-shadow: var(--sigma-btn-shadow-copied);
            }

            .sigma-sr-only {
                position: absolute;
                width: 1px;
                height: 1px;
                padding: 0;
                margin: -1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                border: 0;
            }
            
            @keyframes sigmaPulse { 
                from { 
                    opacity: 0;
                    transform: translateY(0.5rem);
                } 
                to { 
                    opacity: 1;
                    transform: translateY(0);
                } 
            }
        </style>
        HTML;
    }

    protected function getSigmaCopyButton(): string
    {
        return <<<'HTML'
            <div class="sigma-actions">
                <button onclick="sigmaCopyError()" class="sigma-copy-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                  
                </button>
                <div class="sigma-dropdown-container">
                    <button onclick="sigmaTriggerDropdown()" class="sigma-main-btn">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m0 0h6m-6-6H6m0 0H0"></path>
                        </svg>
                        <span>Actions</span>
                        <svg class="sigma-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                    </button>
                    <div id="sigma-dropdown-menu" class="sigma-dropdown-menu" style="display: none;">
                        <div class="sigma-dropdown-section">
                            <label class="sigma-dropdown-label">Open in Editor:</label>
                            <select id="sigma-editor-select" class="sigma-editor-select" onchange="sigmaHandleEditorChange(this.value)">
                                <option value="phpstorm">PHPStorm</option>
                                <option value="windsurf">Windsurf</option>
                                <option value="vscode">VS Code</option>
                                <option value="zed">Zed</option>
                            </select>
                            <button onclick="sigmaOpenWithEditor(); sigmaTriggerDropdown();" class="sigma-editor-btn">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                <span>Open</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        HTML;
    }
}
