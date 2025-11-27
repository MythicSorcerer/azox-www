<?php
/**
 * Content Management System for News and Events
 * Reads markdown files and parses them for display
 */

function getContentFiles($directory, $type = 'news') {
    $files = [];
    $path = __DIR__ . '/../' . $directory;
    
    if (!is_dir($path)) {
        return $files;
    }
    
    $mdFiles = glob($path . '/*.md');
    
    foreach ($mdFiles as $file) {
        $filename = basename($file);
        $content = file_get_contents($file);
        
        // Parse frontmatter and content
        $parsed = parseContentFile($content, $filename, $type);
        if ($parsed) {
            $files[] = $parsed;
        }
    }
    
    // Sort by date (newest first)
    usort($files, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    return $files;
}

function parseContentFile($content, $filename, $type) {
    // Extract date from filename
    $date = '';
    if (preg_match('/(\d{4}-\d{2}-\d{2})/', $filename, $matches)) {
        $date = date('F j, Y', strtotime($matches[1]));
    }
    
    // Set category based on type (always News or Events)
    $category = ucfirst($type);
    
    // Content is everything from the file - no title extraction
    $body = $content;
    
    return [
        'filename' => $filename,
        'date' => $date,
        'category' => $category,
        'content' => $body
    ];
}

function parseContentMarkdown($text) {
    // Split into lines for better processing
    $lines = explode("\n", $text);
    $result = [];
    $inList = false;
    
    foreach ($lines as $line) {
        // Skip empty lines
        if (trim($line) === '') {
            if ($inList) {
                $result[] = '</ul>';
                $inList = false;
            }
            $result[] = '';
            continue;
        }
        
        // Headers
        if (preg_match('/^### (.+)$/', $line, $matches)) {
            if ($inList) { $result[] = '</ul>'; $inList = false; }
            $result[] = '<h3>' . htmlspecialchars($matches[1]) . '</h3>';
        } elseif (preg_match('/^## (.+)$/', $line, $matches)) {
            if ($inList) { $result[] = '</ul>'; $inList = false; }
            $result[] = '<h2>' . htmlspecialchars($matches[1]) . '</h2>';
        } elseif (preg_match('/^# (.+)$/', $line, $matches)) {
            if ($inList) { $result[] = '</ul>'; $inList = false; }
            $result[] = '<h1>' . htmlspecialchars($matches[1]) . '</h1>';
        }
        // List items
        elseif (preg_match('/^\* (.+)$/', $line, $matches)) {
            if (!$inList) {
                $result[] = '<ul>';
                $inList = true;
            }
            $result[] = '<li>' . htmlspecialchars($matches[1]) . '</li>';
        }
        // Regular paragraphs
        else {
            if ($inList) { $result[] = '</ul>'; $inList = false; }
            $result[] = '<p>' . htmlspecialchars($line) . '</p>';
        }
    }
    
    // Close any open list
    if ($inList) {
        $result[] = '</ul>';
    }
    
    $html = implode("\n", $result);
    
    // Apply inline formatting
    $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
    $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);
    $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);
    $html = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $html);
    
    // Clean up empty paragraphs
    $html = preg_replace('/<p><\/p>/', '', $html);
    $html = preg_replace('/<p>\s*<\/p>/', '', $html);
    
    return $html;
}

?>