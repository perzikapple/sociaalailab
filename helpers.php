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

if (!function_exists('renderAanmelderEmbed')) {
    function renderAanmelderEmbed($value) {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        $href = null;
        
        // Check if it's a plain URL
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $href = $value;
        } else {
            // Extract URL from HTML anchor tag
            if (preg_match('~<a\b[^>]*href\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)</a>~is', $value, $matches)) {
                $href = trim((string)$matches[2]);
            }
        }
        
        if ($href !== null && $href !== '') {
            // Return as simple link button
            return '<a href="' . htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" target="_blank" rel="noopener noreferrer" class="inline-flex items-center bg-[#00811F] text-white font-semibold px-6 py-3 rounded-md shadow hover:bg-[#006f19] transition">Aanmelden voor dit evenement</a>';
        }
        
        return '';
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
            : '<br><strong><b><em><i><u><ul><ol><li><a><p>';

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
        // Only remove empty paragraphs
        $value = preg_replace('~<p>\s*</p>~i', '', $value);

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

        $titleText = sanitizeEditorPlainText($title);
        $textLower = function_exists('mb_strtolower')
            ? mb_strtolower($descriptionText, 'UTF-8')
            : strtolower($descriptionText);

        $partnerName = '';
        if (preg_match('/(?:samen met|in samenwerking met|partner(?:s)?(?:chap)? met)\s+([^\.,;\n]+)/iu', $descriptionText, $match)) {
            $partnerName = trim((string)$match[1]);
        }

        $summaryLead = 'Tijdens dit evenement ';
        if ($partnerName !== '') {
            $summaryLead .= 'werkte het SociaalAI Lab samen met ' . $partnerName;
        } elseif ($titleText !== '') {
            $summaryLead .= 'stond ' . $titleText . ' centraal';
        } else {
            $summaryLead .= 'brachten we mensen samen rondom AI in de praktijk';
        }
        $summaryLead .= '. ';

        $happened = eventShortSummary($descriptionText, 520);
        if ($happened !== '') {
            $happened = rtrim($happened);
            if (!preg_match('/[\.\!\?]$/u', $happened)) {
                $happened .= '.';
            }
            $happened .= ' ';
        }

        $learningParts = [];
        if (preg_match('/workshop|sessie|oefening/i', $textLower)) {
            $learningParts[] = 'praktische vaardigheden om AI bewuster te gebruiken';
        }
        if (preg_match('/discussie|gesprek|dialoog|vragen/i', $textLower)) {
            $learningParts[] = 'ruimte om vragen te stellen en ervaringen uit te wisselen';
        }
        if (preg_match('/inzicht|conclusie|resultaat|opgehaald|geleerd/i', $textLower)) {
            $learningParts[] = 'concrete inzichten die direct toepasbaar zijn in het dagelijks leven';
        }

        if (!empty($learningParts)) {
            $learning = 'Wat we hebben geleerd voor mensen: ' . implode(', ', array_unique($learningParts)) . '.';
        } else {
            $learning = 'Wat we hebben geleerd voor mensen: duidelijke uitleg, meer grip op AI en praktische handvatten voor dagelijkse situaties.';
        }

        $summary = $summaryLead . $happened . $learning;
        $maxChars = max(260, (int)$maxChars);

        return editorPreviewText($summary, $maxChars);
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
