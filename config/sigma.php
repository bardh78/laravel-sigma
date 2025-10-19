<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sigma Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can configure the settings for the Laravel Sigma package.
    |
    */

    'enabled' => env('SIGMA_ENABLED', true),
    'editor' => env('SIGMA_EDITOR', 'phpstorm'),

    /*
    |--------------------------------------------------------------------------
    | Remote Path Mapping
    |--------------------------------------------------------------------------
    |
    | Configure how Sigma translates file paths when your app runs remotely
    | (inside Docker, Homestead, WSL, etc.) but your editor runs locally. When
    | both paths are provided the remote base path is replaced with the local
    | one before opening the file.
    |
    */
    'remote_sites_path' => env('SIGMA_REMOTE_SITES_PATH', base_path()),
    'local_sites_path' => env('SIGMA_LOCAL_SITES_PATH', base_path()),

    /*
    |--------------------------------------------------------------------------
    | Editor Protocols
    |--------------------------------------------------------------------------
    |
    | These options mirror Ignition's defaults. Sigma uses them to resolve the
    | correct URI template or clipboard target for "Open in Editor". Feel free
    | to extend this list or tweak existing entries for your setup.
    |
    */
    'editor_options' => [
        'clipboard' => [
            'label' => 'Clipboard',
            'url' => '%path:%line',
            'clipboard' => true,
        ],
        'sublime' => [
            'label' => 'Sublime',
            'url' => 'subl://open?url=file://%path&line=%line',
        ],
        'textmate' => [
            'label' => 'TextMate',
            'url' => 'txmt://open?url=file://%path&line=%line',
        ],
        'emacs' => [
            'label' => 'Emacs',
            'url' => 'emacs://open?url=file://%path&line=%line',
        ],
        'macvim' => [
            'label' => 'MacVim',
            'url' => 'mvim://open/?url=file://%path&line=%line',
        ],
        'phpstorm' => [
            'label' => 'PhpStorm',
            'url' => 'phpstorm://open?file=%path&line=%line',
        ],
        'phpstorm-remote' => [
            'label' => 'PhpStorm Remote',
            'url' => 'javascript:r = new XMLHttpRequest;r.open("get", "http://localhost:63342/api/file/%path:%line");r.send()',
        ],
        'idea' => [
            'label' => 'IDEA',
            'url' => 'idea://open?file=%path&line=%line',
        ],
        'vscode' => [
            'label' => 'VS Code',
            'url' => 'vscode://file/%path:%line',
        ],
        'vscode-insiders' => [
            'label' => 'VS Code Insiders',
            'url' => 'vscode-insiders://file/%path:%line',
        ],
        'vscode-remote' => [
            'label' => 'VS Code Remote',
            'url' => 'vscode://vscode-remote/%path:%line',
        ],
        'vscode-insiders-remote' => [
            'label' => 'VS Code Insiders Remote',
            'url' => 'vscode-insiders://vscode-remote/%path:%line',
        ],
        'vscodium' => [
            'label' => 'VS Codium',
            'url' => 'vscodium://file/%path:%line',
        ],
        'windsurf' => [
            'label' => 'Windsurf',
            'url' => 'windsurf://file/%path:%line',
        ],
        'cursor' => [
            'label' => 'Cursor',
            'url' => 'cursor://file/%path:%line',
        ],
        'zed' => [
            'label' => 'Zed',
            'url' => 'zed://file/%path:%line',
        ],
        'atom' => [
            'label' => 'Atom',
            'url' => 'atom://core/open/file?filename=%path&line=%line',
        ],
        'nova' => [
            'label' => 'Nova',
            'url' => 'nova://open?path=%path&line=%line',
        ],
        'netbeans' => [
            'label' => 'NetBeans',
            'url' => 'netbeans://open/?f=%path:%line',
        ],
        'xdebug' => [
            'label' => 'Xdebug',
            'url' => 'xdebug://%path@%line',
        ],
    ],
];
