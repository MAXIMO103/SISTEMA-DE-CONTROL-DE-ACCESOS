<?php
$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__) . '/src'));
foreach ($it as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $files[] = $file->getPathname();
    }
}

$found = false;
foreach ($files as $filePath) {
    $content = file_get_contents($filePath);
    // Buscar strings dentro de prepare(...)
    if (preg_match_all('/->prepare\s*\(\s*(["\'])(.*?)\1\s*\)/s', $content, $matches)) {
        foreach ($matches[2] as $sql) {
            if (preg_match_all('/:([a-zA-Z0-9_]+)/', $sql, $pMatches)) {
                $params = $pMatches[1];
                $counts = array_count_values($params);
                foreach ($counts as $name => $cnt) {
                    if ($cnt > 1) {
                        $found = true;
                        echo "DUPLICATE :{$name} in {$filePath}:\n{$sql}\n\n";
                    }
                }
            }
        }
    }
}

if (!$found) {
    echo "No duplicate placeholders found.\n";
}

