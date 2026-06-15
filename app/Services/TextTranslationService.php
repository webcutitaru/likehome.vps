<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class TextTranslationService
{
    private const CHUNK_MAX_BYTES = 3500;

    public function translate(string $text, string $targetLocale, string $sourceLocale = 'ro'): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if ($targetLocale === $sourceLocale) {
            return $text;
        }

        $chunks = $this->splitIntoChunks($text);
        $translated = [];

        foreach ($chunks as $chunk) {
            if ($chunk === '') {
                $translated[] = $chunk;

                continue;
            }

            if (preg_match('/^\r?\n\r?\n$/', $chunk)) {
                $translated[] = $chunk;

                continue;
            }

            $translated[] = $this->translateChunk(trim($chunk), $targetLocale, $sourceLocale);
            usleep(250_000);
        }

        return $this->reassemble($translated);
    }

    /**
     * @return list<string>
     */
    private function splitIntoChunks(string $text): array
    {
        if (strlen($text) <= self::CHUNK_MAX_BYTES) {
            return [$text];
        }

        $parts = preg_split('/(\r\n\r\n|\n\n)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (! is_array($parts)) {
            return [$text];
        }

        $chunks = [];
        $buffer = '';

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if (preg_match('/^\r?\n\r?\n$/', $part)) {
                $buffer .= $part;

                continue;
            }

            if (strlen($buffer) + strlen($part) > self::CHUNK_MAX_BYTES && trim($buffer) !== '') {
                $chunks[] = $buffer;
                $buffer = '';
            }

            $buffer .= $part;
        }

        if (trim($buffer) !== '') {
            $chunks[] = $buffer;
        }

        return $chunks !== [] ? $chunks : [$text];
    }

    /**
     * @param list<string> $parts
     */
    private function reassemble(array $parts): string
    {
        $out = implode('', $parts);

        return trim(preg_replace("/\n{3,}/", "\n\n", $out) ?? $out);
    }

    private function translateChunk(string $text, string $targetLocale, string $sourceLocale): string
    {
        $response = Http::timeout(30)
            ->retry(3, 500, throw: false)
            ->get('https://translate.googleapis.com/translate_a/single', [
                'client' => 'gtx',
                'sl' => $sourceLocale,
                'tl' => $targetLocale,
                'dt' => 't',
                'q' => $text,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(sprintf(
                'Translation request failed (%s → %s): HTTP %d',
                $sourceLocale,
                $targetLocale,
                $response->status()
            ));
        }

        $data = $response->json();
        if (! is_array($data) || ! isset($data[0]) || ! is_array($data[0])) {
            throw new RuntimeException(sprintf(
                'Unexpected translation response (%s → %s)',
                $sourceLocale,
                $targetLocale
            ));
        }

        $out = '';
        foreach ($data[0] as $segment) {
            if (is_array($segment) && isset($segment[0])) {
                $out .= (string) $segment[0];
            }
        }

        if ($out === '') {
            throw new RuntimeException(sprintf(
                'Empty translation result (%s → %s)',
                $sourceLocale,
                $targetLocale
            ));
        }

        return $out;
    }
}
