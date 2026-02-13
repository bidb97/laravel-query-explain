<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Query Analysis - {{ $query->className }}::{{ $query->methodName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">Query Analysis</h1>

        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <h2 class="text-xl font-semibold mb-2">Query Details</h2>
                    <p><strong>Class:</strong> {{ $query->className }}</p>
                    <p><strong>Method:</strong> {{ $query->methodName }}</p>
                    <p><strong>Line Number:</strong> {{ $query->lineNumber ?? 'N/A' }}</p>
                    <p><strong>Label:</strong> {{ $query->label }}</p>
                </div>
            </div>

            <div class="mt-6">
                <h3 class="text-lg font-medium mb-2">Generated SQL Query</h3>
                <pre class="bg-gray-100 p-4 rounded-md overflow-x-auto"><code>{{ $query->sqlQuery }}</code></pre>
            </div>

            @if($query->explainResults)
            <div class="mt-6">
                <h3 class="text-lg font-medium mb-2">EXPLAIN Results</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead>
                            <tr class="bg-gray-100">
                                @foreach(array_keys($query->explainResults[0] ?? []) as $column)
                                    <th class="py-2 px-4 border-b text-left">{{ ucfirst(str_replace('_', ' ', $column)) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($query->explainResults as $row)
                                <tr class="border-b hover:bg-gray-50">
                                    @foreach($row as $value)
                                        <td class="py-2 px-4">{{ $value }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <div class="mt-6">
                <h3 class="text-lg font-medium mb-2">EXPLAIN Results</h3>
                <p class="text-gray-600">No EXPLAIN results available for this query.</p>
            </div>
            @endif

            <div class="mt-6">
                <a href="{{ route('query-explain.queries') }}" class="text-blue-600 hover:text-blue-800">← Back to All Queries</a>
            </div>
        </div>
    </div>
</body>
</html>
