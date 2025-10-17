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
        $html = str_replace('</head>', $sigmaStyles . '</head>', $html);
        
        // Inject Sigma script before closing body tag
        $html = str_replace('</body>', $sigmaScript . '</body>', $html);

        // Inject floating Copy Error button before closing body tag
        $html = str_replace('</body>', $sigmaCopyButton . '</body>', $html);
        
        return $html;
    }

    protected function getSigmaScript(Throwable $throwable): string
    {
        $errorData = [
            'message' => $throwable->getMessage(),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'class' => get_class($throwable),
        ];

        $jsonData = json_encode($errorData, JSON_HEX_TAG | JSON_HEX_AMP);

        return <<<HTML
        <script>
            window.sigmaErrorData = {$jsonData};
            
            // Sigma error enhancement logic
            (function() {
                console.log('🔍 Sigma Error Enhancement Active', window.sigmaErrorData);
                
                // Copy error functionality
                window.sigmaCopyError = function() {
                    const errorText = window.sigmaErrorData.class + ': ' + window.sigmaErrorData.message + '\\n' +
                                     'File: ' + window.sigmaErrorData.file + ':' + window.sigmaErrorData.line;
                    
                    navigator.clipboard.writeText(errorText).then(function() {
                        const btn = document.querySelector('.sigma-copy-btn');
                        const originalText = btn.innerHTML;
                        btn.classList.add('copied');
                        btn.innerHTML = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Copied!';
                        
                        setTimeout(function() {
                            btn.innerHTML = originalText;
                            btn.classList.remove('copied');
                        }, 2000);
                    }).catch(function(err) {
                        console.error('Failed to copy error:', err);
                        alert('Failed to copy to clipboard');
                    });
                };
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
            
            .sigma-copy-btn {
                position: fixed;
                bottom: 30px;
                right: 30px;
                z-index: 9999;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                background: var(--sigma-btn-bg);
                color: var(--sigma-btn-text);
                padding: 0.75rem 1.25rem;
                border-radius: 0.5rem;
                font-size: 0.9375rem;
                font-weight: 600;
                margin-left: 0.5rem;
                border: 1px solid var(--sigma-btn-border);
                cursor: pointer;
                transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
                box-shadow: var(--sigma-btn-shadow);
            }
            
            .sigma-copy-btn:hover {
                transform: translateY(-2px);
                box-shadow: var(--sigma-btn-shadow-hover);
                background: var(--sigma-btn-bg-hover);
            }
            
            .sigma-copy-btn:active {
                transform: translateY(0);
                box-shadow: var(--sigma-btn-shadow-active);
                background: var(--sigma-btn-bg-active);
            }
            
            .sigma-copy-btn svg {
                width: 1rem;
                height: 1rem;
            }
            
            .sigma-copy-btn.copied {
                background: var(--sigma-btn-bg-copied);
                color: #ffffff;
                box-shadow: var(--sigma-btn-shadow-copied);
            }
            
            @keyframes sigmaPulse { from { opacity: 1; } to { opacity: 1; } }
        </style>
        HTML;
    }

    protected function getSigmaCopyButton(): string
    {
        return <<<'HTML'
            <button onclick="sigmaCopyError()" class="sigma-copy-btn">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                Copy Error
            </button>
        HTML;
    }
}
