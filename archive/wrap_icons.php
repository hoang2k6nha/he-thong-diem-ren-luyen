<?php
$file = 'dashboard.php';
$content = file_get_contents($file);

// Find <i class="fas ... feature-icon"></i> and wrap it with <div class="feature-icon-wrapper">...</div>
$content = preg_replace('/(<i class="fas [^>]+ feature-icon"><\/i>)/', '<div class="feature-icon-wrapper">$1</div>', $content);

file_put_contents($file, $content);
echo "Replaced icons.\n";
