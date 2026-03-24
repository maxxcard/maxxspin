<?php

declare(strict_types=1);

namespace App\View;

final class SegmentEditorPage
{
    /**
     * @param array<int, array{name:string, color:string, chance:string}> $formRows
     * @param array<int, string> $errors
     */
    public function render(array $formRows, array $errors, ?string $successMessage): string
    {
        ob_start();
        require dirname(__DIR__, 2) . '/views/segment-editor.php';

        return (string) ob_get_clean();
    }
}
