<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }} — Malayznbeat</title>
    <style>
        @page { size: A4 landscape; margin: 12mm 10mm; }
        * { box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 9pt; color: #111827; margin: 0; padding: 0; }

        .toolbar {
            background: #1f2937; color: #fff; padding: 12px 20px;
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 50;
        }
        .toolbar h2 { margin: 0; font-size: 14pt; font-weight: 600; }
        .toolbar .actions { display: flex; gap: 8px; }
        .toolbar button {
            background: #4f46e5; color: #fff; border: none; padding: 8px 16px;
            border-radius: 6px; font-size: 11pt; cursor: pointer; font-weight: 500;
        }
        .toolbar button:hover { background: #6366f1; }
        .toolbar button.secondary { background: #374151; }
        .toolbar button.secondary:hover { background: #4b5563; }

        .page { padding: 24px; max-width: 100%; }
        h1 { font-size: 18pt; margin: 0 0 4px 0; color: #111827; font-weight: 600; }
        .meta { color: #6b7280; font-size: 9pt; margin-bottom: 18px; }

        table { width: 100%; border-collapse: collapse; }
        thead { background: #111827; color: #fff; }
        th { padding: 8px 6px; text-align: left; font-weight: 600; font-size: 8.5pt; text-transform: uppercase; letter-spacing: 0.03em; }
        td { padding: 6px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        tbody tr:nth-child(even) { background: #f9fafb; }

        .stage-pill {
            display: inline-block; padding: 2px 6px; border-radius: 4px;
            font-size: 8pt; font-weight: 600;
        }
        .stage-published { background: #d1fae5; color: #065f46; }
        .stage-approved  { background: #d1fae5; color: #065f46; }
        .stage-revisions { background: #fef3c7; color: #92400e; }
        .stage-other     { background: #e5e7eb; color: #374151; }

        .footer { margin-top: 18px; color: #9ca3af; font-size: 8pt; text-align: center; }

        @media print {
            .toolbar { display: none; }
            .page { padding: 0; }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <h2>{{ $title }} ({{ count($articles) }})</h2>
        <div class="actions">
            <button onclick="window.print()">Save as PDF</button>
            <button onclick="window.close()" class="secondary">Close</button>
        </div>
    </div>

    <div class="page">
        <h1>{{ $title }}</h1>
        <p class="meta">Generated {{ $generated_at->format('M j, Y · H:i') }} · {{ count($articles) }} {{ Str::plural('article', count($articles)) }} · Malayznbeat Content Hub</p>

        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Title</th>
                    <th>Client</th>
                    <th>Sales</th>
                    <th>Writer</th>
                    <th>Stage</th>
                    <th>Submitted</th>
                    <th>Published</th>
                    <th>URL</th>
                </tr>
            </thead>
            <tbody>
                @forelse($articles as $a)
                    @php
                        $stageClass = match($a->current_stage->value) {
                            'published'        => 'stage-published',
                            'approved'         => 'stage-approved',
                            'revisions'        => 'stage-revisions',
                            default            => 'stage-other',
                        };
                    @endphp
                    <tr>
                        <td>{{ $a->article_code }}</td>
                        <td>{{ $a->title }}</td>
                        <td>{{ $a->client?->displayName() ?? '—' }}</td>
                        <td>{{ $a->salesRep?->name ?? '—' }}</td>
                        <td>{{ $a->techWriter?->name ?? '—' }}</td>
                        <td><span class="stage-pill {{ $stageClass }}">{{ $a->current_stage->label() }}</span></td>
                        <td>{{ $a->submitted_at?->format('M j, Y') ?? '—' }}</td>
                        <td>{{ $a->published_at?->format('M j, Y') ?? '—' }}</td>
                        <td style="word-break: break-all; max-width: 180px;">
                            @if($a->published_url)
                                <a href="{{ $a->published_url }}" target="_blank" rel="noopener" style="color:#4f46e5; text-decoration: none;">{{ Str::limit($a->published_url, 50) }}</a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="text-align:center; padding: 20px; color: #9ca3af;">No articles match the filters.</td></tr>
                @endforelse
            </tbody>
        </table>

        <p class="footer">Malayznbeat Content Hub · Generated {{ $generated_at->format('Y-m-d H:i') }}</p>
    </div>

    <script>
        // Auto-trigger the print dialog after a short pause so toolbar/styles render first
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 400);
        });
    </script>
</body>
</html>
