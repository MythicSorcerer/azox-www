<?php
/**
 * Simple Markdown Parser for FAQ content
 * Converts basic markdown to HTML with FAQ-specific formatting
 */

function parseMarkdown($markdown) {
    $html = '';
    $lines = explode("\n", $markdown);
    $currentSection = '';
    $inQuestion = false;
    $questionCount = 0;
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        if (empty($line)) {
            continue;
        }
        
        // Main title (# )
        if (preg_match('/^# (.+)$/', $line, $matches)) {
            $html .= '<h1>' . htmlspecialchars($matches[1]) . '</h1>' . "\n";
            continue;
        }
        
        // Category headers (## )
        if (preg_match('/^## (.+)$/', $line, $matches)) {
            if ($inQuestion) {
                $html .= '</div></div>' . "\n"; // Close previous question
                $inQuestion = false;
            }
            if ($currentSection) {
                $html .= '</div>' . "\n"; // Close previous category
            }
            $currentSection = $matches[1];
            $html .= '<div class="faq-category">' . "\n";
            $html .= '<h2>' . htmlspecialchars($matches[1]) . '</h2>' . "\n";
            continue;
        }
        
        // Questions (### )
        if (preg_match('/^### (.+)$/', $line, $matches)) {
            if ($inQuestion) {
                $html .= '</div></div>' . "\n"; // Close previous question
            }
            $questionCount++;
            $question = htmlspecialchars($matches[1]);
            $html .= '<div class="faq-item">' . "\n";
            $html .= '<button class="faq-question" onclick="toggleFAQ(this)">' . "\n";
            $html .= '<span>' . $question . '</span>' . "\n";
            $html .= '<span class="faq-icon">+</span>' . "\n";
            $html .= '</button>' . "\n";
            $html .= '<div class="faq-answer">' . "\n";
            $inQuestion = true;
            continue;
        }
        
        // Regular content
        if ($inQuestion) {
            // Convert markdown formatting
            $line = convertMarkdownFormatting($line);
            
            // Handle lists
            if (preg_match('/^- (.+)$/', $line, $matches)) {
                static $inList = false;
                if (!$inList) {
                    $html .= '<ul>' . "\n";
                    $inList = true;
                }
                $html .= '<li>' . $matches[1] . '</li>' . "\n";
            } else {
                if (isset($inList) && $inList) {
                    $html .= '</ul>' . "\n";
                    $inList = false;
                }
                
                // Handle numbered lists
                if (preg_match('/^\d+\. (.+)$/', $line, $matches)) {
                    static $inOrderedList = false;
                    if (!$inOrderedList) {
                        $html .= '<ol>' . "\n";
                        $inOrderedList = true;
                    }
                    $html .= '<li>' . $matches[1] . '</li>' . "\n";
                } else {
                    if (isset($inOrderedList) && $inOrderedList) {
                        $html .= '</ol>' . "\n";
                        $inOrderedList = false;
                    }
                    
                    // Regular paragraph
                    if (!empty($line)) {
                        $html .= '<p>' . $line . '</p>' . "\n";
                    }
                }
            }
        }
    }
    
    // Close any open elements
    if ($inQuestion) {
        $html .= '</div></div>' . "\n";
    }
    if ($currentSection) {
        $html .= '</div>' . "\n";
    }
    
    return $html;
}

function convertMarkdownFormatting($text) {
    // Convert **bold** to <strong>
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    
    // Convert `code` to <code>
    $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);
    
    // Convert [link text](url) to <a href="url">link text</a>
    $text = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $text);
    
    return $text;
}

?>