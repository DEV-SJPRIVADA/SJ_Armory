<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Vista previa relación</title>
    <style>
        @page { margin: 96px 48px 88px; }
        body {
            font-family: "Times New Roman", serif;
            font-size: 11pt;
            color: #000;
            line-height: 1.25;
        }
        .branding-top,
        .branding-bottom {
            position: fixed;
            left: 0;
            right: 0;
        }
        .branding-top { top: -96px; }
        .branding-bottom { bottom: -88px; }
        .branding-top img,
        .branding-bottom img {
            width: 100%;
            display: block;
        }
        .cover-page,
        .weapon-page,
        .permit-page {
            page-break-after: always;
        }
        .permit-page:last-child {
            page-break-after: auto;
        }
        p {
            margin: 0;
        }
        .date-line,
        .line {
            margin-bottom: 2px;
        }
        .spacer-sm { height: 14px; }
        .spacer-md { height: 22px; }
        .spacer-lg { height: 56px; }
        .justified { text-align: justify; }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        .summary-table th,
        .summary-table td {
            border: 1px solid #000;
            padding: 4px 5px;
            text-align: center;
            font-size: 9pt;
        }
        .summary-table th {
            background: #bfbfbf;
            font-weight: 700;
        }
        .weapon-title {
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .photo-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px;
            margin-bottom: 14px;
        }
        .photo-grid td,
        .permit-grid td {
            text-align: center;
            vertical-align: middle;
        }
        .photo-frame img {
            width: 100%;
            height: 156px;
            object-fit: fill;
            display: block;
        }
        .photo-label {
            font-size: 9pt;
            padding-top: 4px;
            text-align: center;
        }
        .imprint-wrapper {
            width: 148px;
            margin: 0 auto;
            text-align: center;
        }
        .imprint-wrapper img {
            width: 148px;
            height: 34px;
            object-fit: fill;
            display: block;
        }
        .permit-single {
            width: 198px;
            margin: 38px auto 0;
            text-align: center;
        }
        .permit-single img {
            width: 198px;
            height: 142px;
            object-fit: fill;
            display: block;
        }
        .permit-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 8px;
            margin-top: 36px;
        }
        .permit-grid img {
            width: 164px;
            height: 114px;
            object-fit: fill;
            display: block;
            margin: 0 auto;
        }
        .placeholder {
            border: 1px solid #999;
            color: #444;
            display: inline-block;
            width: 100%;
            text-align: center;
            box-sizing: border-box;
        }
        .placeholder.photo { padding: 68px 0; }
        .placeholder.imprint { padding: 10px 0; }
        .placeholder.permit { padding: 50px 0; }
    </style>
</head>
<body>
    @if (!empty($branding['header']))
        <div class="branding-top"><img src="{{ $branding['header'] }}" alt=""></div>
    @endif
    @if (!empty($branding['footer']))
        <div class="branding-bottom"><img src="{{ $branding['footer'] }}" alt=""></div>
    @endif

    <div class="cover-page">
        <p class="date-line">{{ $date_line }}</p>
        @foreach ($recipient_lines as $line)
            <p class="line">{{ $line }}</p>
        @endforeach

        <div class="spacer-sm"></div>
        <p class="line">{{ $reference }}</p>
        <div class="spacer-sm"></div>
        <p class="line">{{ $greeting }}</p>
        <div class="spacer-sm"></div>
        <p class="justified">{{ $body }}</p>

        <table class="summary-table">
            <thead>
                <tr>
                    @foreach ($summary_headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($summary_rows as $row)
                    <tr>
                        @foreach ($row as $value)
                            <td>{{ $value }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="spacer-md"></div>
        @foreach ($closing_lines as $index => $line)
            <p class="line" @if($index === 1) style="margin-top: 8px;" @endif>{{ $line }}</p>
        @endforeach
        <div class="spacer-lg"></div>
        @foreach ($signature_lines as $line)
            <p class="line">{{ $line }}</p>
        @endforeach

        @if (!empty($annex))
            <p class="line" style="margin-top: 12px; font-size: 10pt;">{{ $annex }}</p>
        @endif
    </div>

    @foreach ($weapon_pages as $weapon)
        <div class="weapon-page">
            <p class="weapon-title">{{ $weapon['title'] }}</p>

            <table class="photo-grid">
                @foreach (array_chunk($weapon['photos'], 2) as $pair)
                    <tr>
                        @foreach ($pair as $photo)
                            <td width="50%">
                                @if ($photo['src'])
                                    <div class="photo-frame">
                                        <img src="{{ $photo['src'] }}" alt="{{ $photo['label'] }}">
                                    </div>
                                @else
                                    <div class="placeholder photo">Sin imagen</div>
                                @endif
                                <div class="photo-label">{{ $photo['label'] }}</div>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </table>

            <div class="imprint-wrapper">
                @if ($weapon['imprint'])
                    <img src="{{ $weapon['imprint'] }}" alt="Impronta">
                @else
                    <div class="placeholder imprint">Sin imagen</div>
                @endif
                <div class="photo-label">IMPRONTA</div>
            </div>
        </div>
    @endforeach

    @if ($permit_mode === 'single')
        <div class="permit-page">
            <div class="permit-single">
                @if ($single_permit)
                    <img src="{{ $single_permit }}" alt="Salvoconducto">
                @else
                    <div class="placeholder permit">Sin salvoconducto</div>
                @endif
            </div>
        </div>
    @else
        @foreach ($permit_pages as $page)
            <div class="permit-page">
                <table class="permit-grid">
                    @foreach ($page as $row)
                        <tr>
                            @foreach ([0, 1] as $index)
                                @php($permit = $row[$index] ?? null)
                                <td width="50%">
                                    @if ($permit && !empty($permit['src']))
                                        <img src="{{ $permit['src'] }}" alt="Salvoconducto">
                                    @else
                                        <div class="placeholder permit">Sin salvoconducto</div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </table>
            </div>
        @endforeach
    @endif
</body>
</html>
