<?php
/**
 * SimpleMarkdown - Simplified Markdown Parser
 * Supports basic Markdown syntax
 */

class Parsedown
{
    const version = '1.0-simple';

    public function text($text)
    {
        if (empty($text)) {
            return '';
        }
        
        // Normalize line endings
        $text = str_replace(array("\r\n", "\r"), "\n", $text);
        $text = trim($text);
        
        $lines = explode("\n", $text);
        $html = $this->processLines($lines);
        
        return $html;
    }

    protected function processLines($lines)
    {
        $html = '';
        $inCodeBlock = false;
        $codeContent = '';
        $codeLang = '';
        $inList = false;
        $inBlockquote = false;
        $blockquoteContent = '';
        
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            $trimmed = trim($line);
            
            // Code blocks (```)
            if (preg_match('/^```(\w*)$/', $trimmed, $matches)) {
                if ($inCodeBlock) {
                    // End code block
                    $html .= '<pre><code' . ($codeLang ? ' class="language-' . htmlspecialchars($codeLang, ENT_QUOTES, 'UTF-8') . '"' : '') . '>';
                    $html .= htmlspecialchars($codeContent, ENT_QUOTES, 'UTF-8');
                    $html .= '</code></pre>';
                    $inCodeBlock = false;
                    $codeContent = '';
                    $codeLang = '';
                } else {
                    // Start code block
                    $inCodeBlock = true;
                    $codeLang = isset($matches[1]) ? $matches[1] : '';
                }
                continue;
            }
            
            if ($inCodeBlock) {
                $codeContent .= $line . "\n";
                continue;
            }
            
            // Empty line
            if (empty($trimmed)) {
                if ($inList) {
                    $html .= '</ul>';
                    $inList = false;
                }
                if ($inBlockquote) {
                    $html .= '<blockquote>' . $this->parseInline($blockquoteContent) . '</blockquote>';
                    $inBlockquote = false;
                    $blockquoteContent = '';
                }
                $html .= "\n";
                continue;
            }
            
            // Headers (# ## ###)
            if (preg_match('/^(#{1,6})\s+(.+)$/', $trimmed, $matches)) {
                if ($inList) {
                    $html .= '</ul>';
                    $inList = false;
                }
                $level = strlen($matches[1]);
                $html .= '<h' . $level . '>' . $this->parseInline($matches[2]) . '</h' . $level . '>';
                continue;
            }
            
            // Horizontal rule (---)
            if (preg_match('/^([-*_]){3,}$/', $trimmed)) {
                if ($inList) {
                    $html .= '</ul>';
                    $inList = false;
                }
                $html .= '<hr>';
                continue;
            }
            
            // Blockquote (>)
            if (preg_match('/^>\s*(.*)$/', $trimmed, $matches)) {
                if (!$inBlockquote) {
                    $inBlockquote = true;
                }
                $blockquoteContent .= $matches[1] . ' ';
                continue;
            } else if ($inBlockquote) {
                $html .= '<blockquote>' . $this->parseInline($blockquoteContent) . '</blockquote>';
                $inBlockquote = false;
                $blockquoteContent = '';
            }
            
            // Unordered list (- or *)
            if (preg_match('/^[-*]\s+(.+)$/', $trimmed, $matches)) {
                if (!$inList) {
                    $html .= '<ul>';
                    $inList = true;
                }
                $html .= '<li>' . $this->parseInline($matches[1]) . '</li>';
                continue;
            }
            
            // Ordered list (1. 2. 3.)
            if (preg_match('/^\d+\.\s+(.+)$/', $trimmed, $matches)) {
                if ($inList && strpos($html, '<ul>') !== false && strrpos($html, '<ul>') > strrpos($html, '</ul>')) {
                    $html .= '</ul>';
                }
                if (!$inList || strpos($html, '<ol>') === false || strrpos($html, '</ol>') > strrpos($html, '<ol>')) {
                    $html .= '<ol>';
                    $inList = true;
                }
                $html .= '<li>' . $this->parseInline($matches[1]) . '</li>';
                continue;
            }
            
            // Close list if not a list item
            if ($inList && !preg_match('/^[-*]\s+/', $trimmed) && !preg_match('/^\d+\.\s+/', $trimmed)) {
                if (strpos($html, '<ul>') !== false && strrpos($html, '<ul>') > strrpos($html, '</ul>')) {
                    $html .= '</ul>';
                } else if (strpos($html, '<ol>') !== false && strrpos($html, '<ol>') > strrpos($html, '</ol>')) {
                    $html .= '</ol>';
                }
                $inList = false;
            }
            
            // Regular paragraph
            $html .= '<p>' . $this->parseInline($line) . '</p>';
        }
        
        // Close any open tags
        if ($inCodeBlock) {
            $html .= '<pre><code>' . htmlspecialchars($codeContent, ENT_QUOTES, 'UTF-8') . '</code></pre>';
        }
        if ($inList) {
            if (strpos($html, '<ul>') !== false && strrpos($html, '<ul>') > strrpos($html, '</ul>')) {
                $html .= '</ul>';
            } else {
                $html .= '</ol>';
            }
        }
        if ($inBlockquote) {
            $html .= '<blockquote>' . $this->parseInline($blockquoteContent) . '</blockquote>';
        }
        
        return $html;
    }

    protected function parseInline($text)
    {
        if (empty($text)) {
            return '';
        }
        
        // Escape HTML
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        
        // Images: ![alt](url)
        $text = preg_replace('/!\[([^\]]*)\]\(([^\)]+)\)/', '<img src="$2" alt="$1">', $text);
        
        // Links: [text](url)
        $text = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2">$1</a>', $text);
        
        // Bold: **text** or __text__
        $text = preg_replace('/\*\*([^\*]+)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/__([^_]+)__/', '<strong>$1</strong>', $text);
        
        // Italic: *text* or _text_ (but not in middle of words)
        $text = preg_replace('/(?<!\w)\*([^\*]+)\*(?!\w)/', '<em>$1</em>', $text);
        $text = preg_replace('/(?<!\w)_([^_]+)_(?!\w)/', '<em>$1</em>', $text);
        
        // Strikethrough: ~~text~~
        $text = preg_replace('/~~([^~]+)~~/', '<del>$1</del>', $text);
        
        // Inline code: `code`
        $text = preg_replace_callback('/`([^`]+)`/', function($matches) {
            return '<code>' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '</code>';
        }, $text);
        
        return $text;
    }
}