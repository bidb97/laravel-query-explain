<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Query Analysis Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">Query Analysis Dashboard</h1>

        @if(count($queries) > 0)
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Line</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Label</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SQL Query</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">EXPLAIN</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($queries as $query)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $query->className }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $query->methodName }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $query->lineNumber ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ $query->label }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <pre class="text-sm bg-gray-100 p-2 rounded overflow-x-auto max-w-md"><code>{{ substr($query->sqlQuery, 0, 100) }}{{ strlen($query->sqlQuery) > 100 ? '...' : '' }}</code></pre>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($query->explainResults && !isset($query->explainResults['error']))
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Available
                                    </span>
                                    <a href="{{ route('query-explain.query', [
                                        'className' => urlencode($query->className),
                                        'methodName' => urlencode($query->methodName),
                                        'label' => urlencode($query->label)
                                    ]) }}" class="ml-2 text-sm text-blue-600 hover:text-blue-900">
                                        View Details
                                    </a>
                                @elseif($query->explainResults && isset($query->explainResults['error']))
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-80">
                                        Error
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-80">
                                        Not Available
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6 text-sm text-gray-600">
                <p>Total queries found: {{ count($queries) }}</p>
            </div>
        @else
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <h3 class="text-lg font-medium text-gray-900 mb-2">No queries found</h3>
                <p class="text-gray-500">No queries with the QueryExplain attribute were found in your codebase.</p>
            </div>
        @endif
    </div>
</body>
</html>
