<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Permiso</title>
    <style>
        @page {
            margin: 1.5cm;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #111827;
        }

        /* Una sola hoja: centrado (Dompdf maneja bien tablas; altura fija aprox. área útil carta con márgenes). */
        .page-fill {
            width: 100%;
            min-height: 24.5cm;
            height: 24.5cm;
            border-collapse: collapse;
        }

        .page-fill td {
            vertical-align: top;
            text-align: center;
            /* Empuja el bloque hacia abajo (quedaba algo alto al centrarse en Dompdf). */
            padding-top: 3cm;
            padding-left: 0;
            padding-right: 0;
            padding-bottom: 0;
        }

        .stack {
            display: inline-block;
            text-align: center;
        }

        .side {
            width: 8.56cm;
            height: 5.4cm;
            margin-left: auto;
            margin-right: auto;
            border: 0.2mm solid #d1d5db;
            border-radius: 1.5mm;
            overflow: hidden;
            display: block;
            background: #fff;
        }

        .side + .side {
            margin-top: 1.4cm;
        }

        .side img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: contain;
        }
    </style>
</head>
<body>
    <table class="page-fill">
        <tr>
            <td>
                <div class="stack">
                    <div class="side">
                        <img src="{{ $frontDataUri }}" alt="Frente del permiso">
                    </div>
                    <div class="side">
                        <img src="{{ $reverseDataUri }}" alt="Reverso del permiso">
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
