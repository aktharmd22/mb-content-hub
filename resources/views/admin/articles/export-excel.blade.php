<?php /** @var array $columns; @var iterable $articles; @var string $title; */ ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 11pt; }
        th { background-color: #1a1d23; color: #ffffff; font-weight: bold; padding: 8px 12px; border: 1px solid #454c58; text-align: left; }
        td { padding: 6px 12px; border: 1px solid #d4d4d8; }
        tr:nth-child(even) td { background-color: #f9fafb; }
        .meta { font-size: 10pt; color: #6b7280; margin-bottom: 8px; }
        h1 { font-size: 14pt; color: #1a1d23; margin: 0 0 4px 0; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="meta">Generated {{ now()->format('M j, Y H:i') }} · {{ count($articles) }} {{ Str::plural('article', count($articles)) }} · Malayznbeat Content Hub</p>
    <table>
        <thead>
            <tr>
                @foreach($columns as $col)
                    <th>{{ $col }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($articles as $a)
                <tr>
                    <td>{{ $a->article_code }}</td>
                    <td>{{ $a->title }}</td>
                    <td>{{ $a->articleType?->name }}</td>
                    <td>{{ $a->client?->name }}</td>
                    <td>{{ $a->salesRep?->name }}</td>
                    <td>{{ $a->techWriter?->name }}</td>
                    <td>{{ $a->current_stage->label() }}</td>
                    <td>{{ ucfirst($a->priority) }}</td>
                    <td>{{ $a->deadline?->format('Y-m-d') }}</td>
                    <td>{{ $a->word_count_target }}</td>
                    <td>{{ $a->submitted_at?->format('Y-m-d H:i') }}</td>
                    <td>{{ $a->published_at?->format('Y-m-d H:i') }}</td>
                    <td>{{ $a->published_url }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
