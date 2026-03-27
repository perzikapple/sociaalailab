<?php
if (!function_exists('formatEventDateDisplay')) {
    function formatEventDateDisplay($dateValue) {
        if (empty($dateValue)) return '';
        $ts = strtotime((string)$dateValue);
        if ($ts === false) return (string)$dateValue;
        $monthsNl = [
            1 => 'januari', 2 => 'februari', 3 => 'maart', 4 => 'april', 5 => 'mei', 6 => 'juni',
            7 => 'juli', 8 => 'augustus', 9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'december'
        ];
        $day = (int)date('j', $ts);
        $month = $monthsNl[(int)date('n', $ts)] ?? date('F', $ts);
        $year = date('Y', $ts);
        return $day . ' ' . $month . ' ' . $year;
    }
}

if (!function_exists('formatEventTimeDisplay')) {
    function formatEventTimeDisplay($timeValue) {
        if (empty($timeValue)) return '';
        $ts = strtotime((string)$timeValue);
        if ($ts === false) return (string)$timeValue;
        return date('H:i', $ts);
    }
}

if (!function_exists('googleMapsDirectionsUrl')) {
    function googleMapsDirectionsUrl($destination) {
        if (empty($destination)) return '#';
        return 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode((string)$destination);
    }
}

if (!function_exists('editorContainsHtmlTag')) {
    function editorContainsHtmlTag($value) {
        return preg_match('~<\s*/?\s*[a-z][^>]*>~i', (string)$value) === 1;
    }
}

if (!function_exists('sanitizeEditorHtml')) {
    function sanitizeEditorHtml($value, $mode = 'block') {
        $value = html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (trim($value) === '') {
            return '';
        }

        $value = preg_replace('/\r\n?/', "\n", $value);
        $value = preg_replace('~<!--.*?-->~s', '', $value);
        $value = preg_replace('~<\s*(script|style|iframe|object|embed|form|input|button|textarea|select|option|meta|link)[^>]*>.*?<\s*/\s*\1\s*>~is', '', $value);
        $value = preg_replace('~<\s*(script|style|iframe|object|embed|form|input|button|textarea|select|option|meta|link)[^>]*/?\s*>~is', '', $value);

        $allowedTags = $mode === 'inline'
            ? '<strong><b><em><i><u><a><br>'
            : '<br><strong><b><em><i><u><ul><ol><li><a>';

        $value = strip_tags($value, $allowedTags);

        // Keep only safe href values on links and drop all other attributes.
        $value = preg_replace_callback('~<a\b([^>]*)>~i', function ($matches) {
            $attrs = $matches[1] ?? '';
            $href = '';

            if (preg_match('/href\s*=\s*(["\'])(.*?)\1/i', $attrs, $hrefMatch)) {
                $href = trim($hrefMatch[2]);
            } elseif (preg_match('/href\s*=\s*([^\s>]+)/i', $attrs, $hrefMatch)) {
                $href = trim($hrefMatch[1]);
            }

            if ($href !== '' && preg_match('~^(https?:|mailto:|tel:|/|#)~i', $href)) {
                return '<a href="' . htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
            }

            return '<a>';
        }, $value);

        // Remove attributes from all non-link tags.
        $value = preg_replace('/<(?!\/?a\b)([a-z0-9]+)\b[^>]*>/i', '<$1>', $value);
        $value = preg_replace('~<p>\s*</p>~i', '', $value);
        // Remove all <p> and </p> tags while preserving content
        $value = preg_replace('~</?p\b[^>]*>~i', '', $value);

        return trim($value);
    }
}

if (!function_exists('sanitizeEditorPlainText')) {
    function sanitizeEditorPlainText($value) {
        $value = html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = strip_tags($value);
        $value = preg_replace('/\r\n?/', "\n", $value);
        $value = preg_replace('/[ \t]+\n/', "\n", $value);
        $value = preg_replace('/\n{3,}/', "\n\n", $value);
        return trim($value);
    }
}

if (!function_exists('renderEditorInline')) {
    function renderEditorInline($value) {
        $value = (string)$value;
        if ($value === '') {
            return '';
        }

        if (!editorContainsHtmlTag($value)) {
            return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        return sanitizeEditorHtml($value, 'inline');
    }
}

if (!function_exists('renderEditorBlock')) {
    function renderEditorBlock($value) {
        $value = (string)$value;
        if ($value === '') {
            return '';
        }

        if (!editorContainsHtmlTag($value)) {
            return nl2br(htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        }

        return sanitizeEditorHtml($value, 'block');
    }
}

if (!function_exists('editorPreviewText')) {
    function editorPreviewText($value, $maxWidth = 150) {
        $plain = sanitizeEditorPlainText($value);

        if (function_exists('mb_strimwidth')) {
            return mb_strimwidth($plain, 0, (int)$maxWidth, '...');
        }

        if (strlen($plain) <= $maxWidth) {
            return $plain;
        }

        return substr($plain, 0, max(0, (int)$maxWidth - 3)) . '...';
    }
}

if (!function_exists('eventShortSummary')) {
    function eventShortSummary($value, $maxChars = 180) {
        $text = sanitizeEditorPlainText($value);
        if ($text === '') {
            return '';
        }

        $maxChars = max(80, (int)$maxChars);
        $sentences = preg_split('/(?<=[.!?])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($sentences)) {
            return editorPreviewText($text, $maxChars);
        }

        $summary = '';
        foreach ($sentences as $sentence) {
            $candidate = trim($summary === '' ? $sentence : $summary . ' ' . $sentence);

            if (function_exists('mb_strlen')) {
                if (mb_strlen($candidate) <= $maxChars) {
                    $summary = $candidate;
                    continue;
                }
            } else {
                if (strlen($candidate) <= $maxChars) {
                    $summary = $candidate;
                    continue;
                }
            }

            break;
        }

        if ($summary === '') {
            return editorPreviewText($text, $maxChars);
        }

        return $summary;
    }
}

if (!function_exists('eventNarrativeSummary')) {
    function eventNarrativeSummary($title, $description, $date = null, $location = null, $maxChars = 280) {
        $descriptionText = sanitizeEditorPlainText($description);
        if ($descriptionText === '') {
            return '';
        }

        $textLower = function_exists('mb_strtolower')
            ? mb_strtolower($descriptionText, 'UTF-8')
            : strtolower($descriptionText);

        $signals = [];
        if (preg_match('/spreker|gastspreker|presentatie/i', $textLower)) {
            $signals[] = 'er was een spreker';
        }
        if (preg_match('/workshop|sessie|oefening/i', $textLower)) {
            $signals[] = 'er was een workshop of sessie';
        }
        if (preg_match('/discussie|gesprek|dialoog|vragen/i', $textLower)) {
            $signals[] = 'er was ruimte voor gesprek en vragen';
        }
        if (preg_match('/inzicht|conclusie|resultaat|opgehaald/i', $textLower)) {
            $signals[] = 'er zijn inzichten opgehaald';
        }

        $summaryLead = 'Korte samenvatting: tijdens dit event ';
        if (!empty($signals)) {
            $summaryLead .= implode(', ', $signals) . '. ';
        } else {
            $summaryLead .= 'zijn deelnemers samengekomen rondom het onderwerp. ';
        }

        $core = eventShortSummary($descriptionText, 170);
        if ($core === '') {
            return rtrim($summaryLead);
        }

        $maxChars = max(140, (int)$maxChars);
        if (function_exists('mb_strlen')) {
            $available = $maxChars - mb_strlen($summaryLead);
        } else {
            $available = $maxChars - strlen($summaryLead);
        }

        if ($available < 50) {
            $available = 50;
        }

        return $summaryLead . editorPreviewText($core, $available);
    }
}

if (!function_exists('normalizeDisplayText')) {
    function normalizeDisplayText($value) {
        $value = (string)$value;
        if ($value === '') {
            return '';
        }

        if (preg_match('//u', $value) !== 1 && function_exists('iconv')) {
            $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $value);
            if ($converted !== false && $converted !== '') {
                $value = $converted;
            }
        }

        $value = strtr($value, [
            'Ã¶' => 'ö',
            'Ã«' => 'ë',
            'Ã¼' => 'ü',
            'Ã¯' => 'ï',
            'Ã©' => 'é',
            'Ã¨' => 'è',
            'Ãª' => 'ê',
            'Ã¡' => 'á',
            'Ã³' => 'ó',
            'Ã–' => 'Ö',
            'Ã‹' => 'Ë',
            'Ã‰' => 'É',
            'â€™' => '’',
            'â€œ' => '“',
            'â€\x9d' => '”',
            'â€“' => '–',
            'â€”' => '—',
            'Â ' => ' ',
        ]);

        return $value;
    }
}

?>
