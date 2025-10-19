<?php

namespace Bardh78\LaravelSigma\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class EditorController
{
    public function openEditor(Request $request): JsonResponse
    {
        $file = $request->input('file');
        $line = $request->input('line', 1);
        $editor = $request->input('editor', config('sigma.editor', 'vscode'));

        if (!$file || !file_exists($file)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        try {
            $this->openFileInEditor($file, $line, $editor);
            return response()->json(['success' => true, 'message' => 'File opened in editor']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    protected function openFileInEditor(string $file, int $line, string $editor): void
    {
        $editor = strtolower($editor);

        $commands = [
            'vscode' => "code --goto {$file}:{$line}",
            'phpstorm' => "phpstorm --line {$line} {$file}",
            'sublime' => "subl {$file}:{$line}",
            'atom' => "atom {$file}:{$line}",
            'vim' => "vim +{$line} {$file}",
            'nvim' => "nvim +{$line} {$file}",
            'nano' => "nano +{$line} {$file}",
        ];

        $command = $commands[$editor] ?? $commands['vscode'];

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(5);
        $process->run();
    }
}
