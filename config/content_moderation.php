<?php

return [
    'blocked_patterns' => [
        'threat_ammazzare' => '/\b(?:ti\s+)?ammazz(?:o{1,2}|i{1,2}|a{1,2}|ati|atevi)\b/',
        'threat_uccidere' => '/\b(?:ti\s+)?uccid(?:o{1,2}|i{1,2}|a{1,2}|iti|etevi)\b/',
        'threat_spaccare' => '/\bti\s+spacc(?:o{1,2}|hi|a)\b/',
        'threat_harm' => '/\bti\s+faccio\s+male\b/',
        'threat_hit' => '/\bti\s+(?:meno|picchi(?:o{1,2}|a))\b/',
        'threat_destroy' => '/\bti\s+distrugg(?:o{1,2}|i|e)\b/',
        'death_wish' => '/\b(?:devi\s+morire|muori)\b/',
    ],
    // Usati solo per bypass con separatori/spazi artificiali e frasi ad alta confidenza.
    'compact_blocked_patterns' => [
        'compact_threat_ammazzare' => '/tiammazz(?:o{1,2}|i{1,2}|a{1,2})/',
        'compact_threat_uccidere' => '/tiuccid(?:o{1,2}|i{1,2}|a{1,2})/',
        'compact_death_wish' => '/devimorire/',
    ],
    'blocked_phrases' => [
        'ammazzati',
        'ti ammazzo',
        'ti uccido',
        'ucciditi',
        'violenza sessuale',
        'stupro',
        'materiale pedopornografico',
        'heil hitler',
    ],
    'warning_patterns' => [
        'direct_insult' => '/\b(?:sei|siete)\s+(?:(?:un|uno|una|dei|degli|delle)\s+)?(?:idiot(?:a{1,2}|i)|cretin[oaie]|coglion[ei]|stronz[oaie]|imbecill[ei]|deficient[ei])\b/',
    ],
    'warning_phrases' => [
        'vaffanculo',
        'pezzo di merda',
        'troia',
        'puttana',
    ],
];
