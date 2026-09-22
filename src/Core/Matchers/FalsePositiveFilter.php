<?php

namespace Blaspsoft\Blasp\Core\Matchers;

class FalsePositiveFilter
{
    // Unicode word character: letters, digits, underscore and combining marks.
    // PCRE's \w (even with /u) excludes marks, which breaks Devanagari vowel signs.
    private const WORD_CHAR = '[\p{L}\p{N}\p{M}_]';

    private array $falsePositivesMap;

    public function __construct(array $falsePositives)
    {
        $this->falsePositivesMap = array_flip(array_map('strtolower', $falsePositives));
    }

    public function isFalsePositive(string $word): bool
    {
        return isset($this->falsePositivesMap[strtolower($word)]);
    }

    public function isInsideHexToken(string $string, int $start, int $length): bool
    {
        $end = $start + $length;
        $strLen = strlen($string);

        $tokenStart = $start;
        while ($tokenStart > 0 && preg_match('/[0-9a-fA-F\-]/', $string[$tokenStart - 1])) {
            $tokenStart--;
        }

        $tokenEnd = $end;
        while ($tokenEnd < $strLen && preg_match('/[0-9a-fA-F\-]/', $string[$tokenEnd])) {
            $tokenEnd++;
        }

        $token = substr($string, $tokenStart, $tokenEnd - $tokenStart);
        $token = trim($token, '-');

        if (preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $token)) {
            return true;
        }

        $stripped = str_replace('-', '', $token);
        if (strlen($stripped) >= 8 && preg_match('/^[0-9a-fA-F]+$/', $stripped) && preg_match('/[0-9]/', $stripped)) {
            return true;
        }

        return false;
    }

    public function isSpanningWordBoundary(string $matchedText, string $fullString, int $matchStart): bool
    {
        if (!preg_match('/\s+/', $matchedText)) {
            return false;
        }

        $parts = preg_split('/\s+/', $matchedText);

        if (count($parts) <= 1) {
            return false;
        }

        $singleCharCount = 0;
        foreach ($parts as $part) {
            if (mb_strlen($part, 'UTF-8') === 1 && preg_match('/[a-z]/iu', $part)) {
                $singleCharCount++;
            }
        }

        if ($singleCharCount === count($parts)) {
            return false;
        }

        $matchStartChar = mb_strlen(substr($fullString, 0, $matchStart), 'UTF-8');
        $matchEndChar = $matchStartChar + mb_strlen($matchedText, 'UTF-8');

        $embeddedAtStart = false;
        $embeddedAtEnd = false;

        if ($matchStartChar > 0) {
            $charBefore = mb_substr($fullString, $matchStartChar - 1, 1, 'UTF-8');
            if (preg_match('/' . self::WORD_CHAR . '/u', $charBefore)) {
                $embeddedAtStart = true;
            }
        }

        if ($matchEndChar < mb_strlen($fullString, 'UTF-8')) {
            $charAfter = mb_substr($fullString, $matchEndChar, 1, 'UTF-8');
            if (preg_match('/' . self::WORD_CHAR . '/u', $charAfter)) {
                $embeddedAtEnd = true;
            }
        }

        if ($embeddedAtStart && $embeddedAtEnd) {
            return true;
        }

        if ($embeddedAtStart && !$embeddedAtEnd) {
            $standaloneParts = array_slice($parts, 1);
            $standalonePortion = implode(' ', $standaloneParts);

            $hasLetter = preg_match('/[a-z]/iu', $standalonePortion);
            $hasNonLetter = preg_match('/[^a-z\s]/iu', $standalonePortion);

            if ($hasLetter && $hasNonLetter) {
                return false;
            }
            return true;
        }

        if (!$embeddedAtStart && $embeddedAtEnd) {
            $standaloneParts = array_slice($parts, 0, -1);
            $standalonePortion = implode(' ', $standaloneParts);

            $hasLetter = preg_match('/[a-z]/iu', $standalonePortion);
            $hasNonLetter = preg_match('/[^a-z\s]/iu', $standalonePortion);

            if ($hasLetter && $hasNonLetter) {
                return false;
            }
            return true;
        }

        return false;
    }

    public function getFullWordContext(string $string, int $start, int $length): string
    {
        $left = $start;
        $right = $start + $length;

        // Extend across multibyte word characters on both sides ($start/$length are byte offsets)
        if (preg_match('/' . self::WORD_CHAR . '+$/u', substr($string, 0, $start), $m)) {
            $left -= strlen($m[0]);
        }

        if (preg_match('/^' . self::WORD_CHAR . '+/u', substr($string, $right), $m)) {
            $right += strlen($m[0]);
        }

        return substr($string, $left, $right - $left);
    }
}
