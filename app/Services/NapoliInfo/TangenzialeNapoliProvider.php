<?php

namespace App\Services\NapoliInfo;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use RuntimeException;

class TangenzialeNapoliProvider implements SourceProvider
{
    private const URL = 'https://www.tangenzialedinapoli.it/it/viabilit%C3%A0/';

    public function id(): string
    {
        return 'tangenziale_napoli';
    }

    public function name(): string
    {
        return 'Tangenziale di Napoli';
    }

    public function fetch(): array
    {
        $html = Http::timeout(10)->withHeaders(['User-Agent' => 'SpotOnInfoBot/1.0 (+https://www.spotonapp.cloud)'])
            ->get(self::URL)->throw()->body();
        $dom = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            if (! $dom->loadHTML($html)) {
                throw new RuntimeException('HTML della fonte non leggibile');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $links = (new \DOMXPath($dom))->query('//a[contains(concat(" ", normalize-space(@class), " "), " list-title ")]');
        if (! $links || $links->length === 0) {
            throw new RuntimeException('Nessun avviso nella pagina viabilità');
        }

        $events = [];
        foreach ($links as $link) {
            $title = trim(preg_replace('/\s+/u', ' ', $link->textContent) ?? '');
            $url = preg_replace('/\?.*$/', '', $link->attributes?->getNamedItem('href')?->nodeValue ?? '');
            if (! is_string($url) || ! preg_match('~^https://www\.tangenzialedinapoli\.it/w/[a-zA-Z0-9._-]+$~', $url)) {
                continue;
            }
            if (! preg_match('/chiusur|viabilit|traffico|lavori|circolazion/iu', $title)) {
                continue;
            }
            if (! preg_match_all('/\b(\d{1,2})[.\/-](\d{1,2})[.\/-](20\d{2})\b/', $title, $dates, PREG_SET_ORDER)) continue;
            $last = end($dates);
            if (! checkdate((int) $last[2], (int) $last[1], (int) $last[3])
                || Carbon::create((int) $last[3], (int) $last[2], (int) $last[1], 23, 59, 59, 'Europe/Rome')->isPast()) continue;
            $events[] = [
                'external_id' => hash('sha256', $url),
                'source_url' => $url,
                'title' => mb_substr($title, 0, 180),
                'raw_hash' => hash('sha256', $title),
            ];
            // La pagina è ordinata dal più recente: un solo avviso rappresenta il post corrente.
            break;
        }
        if ($events === []) throw new RuntimeException('Nessun avviso valido nella pagina viabilità');

        return $events;
    }
}
