<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $title }}</title>
    <style>
        @media only screen and (max-width: 620px) {
            .email-shell { padding: 0 !important; }
            .email-card { width: 100% !important; border-radius: 0 !important; }
            .email-content { padding: 26px 22px 30px !important; }
            .email-title { font-size: 26px !important; line-height: 32px !important; }
            .email-illustration { width: 100% !important; max-width: 330px !important; }
            .email-button { display: block !important; text-align: center !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background:#f7f8fb; color:#111827; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;">
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:transparent;">
        {{ $preheader }}
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; background:#f7f8fb;">
        <tr>
            <td class="email-shell" align="center" style="padding:32px 16px;">
                <table class="email-card" role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:600px; max-width:600px; table-layout:fixed; background:#ffffff; border:1px solid #edf0f5; border-radius:8px; overflow:hidden;">
                    <tr>
                        <td style="padding:0; font-size:0; line-height:0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="34%" height="6" style="width:34%; height:6px; background:#facc15;"></td>
                                    <td width="33%" height="6" style="width:33%; height:6px; background:#ec4899;"></td>
                                    <td width="33%" height="6" style="width:33%; height:6px; background:#8b5cf6;"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-content" style="padding:34px 44px 38px;">
                            @if ($showBrand)
                                <div style="margin:0 0 24px; color:#111827; font-size:24px; font-weight:800; line-height:30px;">Spot<span style="color:#ec4899;">On</span></div>
                            @endif

                            <div style="margin:0 0 28px; text-align:center;">
                                <img
                                    class="email-illustration"
                                    src="{{ $imageUrl }}"
                                    width="380"
                                    alt="{{ $imageAlt }}"
                                    style="display:inline-block; width:100%; max-width:380px; height:auto; border:0; outline:none; text-decoration:none;"
                                >
                            </div>

                            <p style="margin:0 0 8px; color:#8b5cf6; font-size:12px; font-weight:800; line-height:18px; text-transform:uppercase;">{{ $eyebrow }}</p>
                            <h1 class="email-title" style="margin:0 0 20px; color:#111827; font-size:30px; font-weight:800; line-height:37px;">{{ $title }}</h1>

                            <p style="margin:0 0 14px; color:#1f2937; font-size:17px; font-weight:700; line-height:26px;">{{ $greeting }}</p>
                            <p style="margin:0 0 14px; color:#4b5563; font-size:16px; line-height:25px;">{{ $intro }}</p>

                            @foreach ($lines as $line)
                                <p style="margin:0 0 14px; color:#4b5563; font-size:16px; line-height:25px;">{{ $line }}</p>
                            @endforeach

                            @if ($actionLabel && $actionUrl)
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:28px 0 18px;">
                                    <tr>
                                        <td align="center" bgcolor="#111827" style="border-radius:8px;">
                                            <a class="email-button" href="{{ $actionUrl }}" style="display:inline-block; padding:14px 24px; color:#ffffff; font-size:16px; font-weight:800; line-height:20px; text-decoration:none;">{{ $actionLabel }}</a>
                                        </td>
                                    </tr>
                                </table>
                                <p style="margin:0 0 22px; color:#9ca3af; font-size:12px; line-height:18px; overflow-wrap:anywhere; word-break:break-word;">
                                    Se il pulsante non funziona, apri questo link:<br>
                                    <a href="{{ $actionUrl }}" style="display:inline-block; max-width:100%; color:#7c3aed; text-decoration:underline; overflow-wrap:anywhere; word-break:break-all;">{{ $actionUrl }}</a>
                                </p>
                            @endif

                            @if ($notice)
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:24px;">
                                    <tr>
                                        <td style="padding:14px 16px; background:#f5f3ff; border-left:4px solid #8b5cf6; border-radius:6px; color:#4b5563; font-size:14px; line-height:21px;">
                                            {{ $notice }}
                                        </td>
                                    </tr>
                                </table>
                            @endif
                        </td>
                    </tr>
                </table>

                <p style="margin:20px auto 0; color:#9ca3af; font-size:12px; line-height:18px; text-align:center;">
                    SpotOn &middot; Incontri reali, connessioni autentiche<br>
                    Hai bisogno di aiuto? <a href="mailto:{{ $supportEmail }}" style="color:#7c3aed;">{{ $supportEmail }}</a>
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
