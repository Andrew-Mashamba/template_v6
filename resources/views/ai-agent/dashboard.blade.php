@extends('layouts.app')

@section('title', 'AI Agent Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">AI Agent Dashboard</h1>
        <p class="text-gray-600">Monitor and manage your AI agent services</p>
    </div>

    <!-- Provider Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        @foreach($providers as $name => $provider)
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 {{ $provider['enabled'] ? 'border-green-500' : 'border-red-500' }}">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">{{ $provider['name'] }}</h3>
                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $provider['enabled'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $provider['enabled'] ? 'Enabled' : 'Disabled' }}
                </span>
            </div>
            
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Status:</span>
                    <span class="{{ $provider['healthy'] ? 'text-green-600' : 'text-red-600' }}">
                        {{ $provider['healthy'] ? 'Healthy' : 'Unhealthy' }}
                    </span>
                </div>
                
                @if(isset($provider['stats']))
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Total Requests:</span>
                    <span class="font-medium">{{ $provider['stats']['total_requests'] ?? 0 }}</span>
                </div>
                
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Success Rate:</span>
                    <span class="font-medium">
                        @if(($provider['stats']['total_requests'] ?? 0) > 0)
                            {{ round((($provider['stats']['successful_requests'] ?? 0) / ($provider['stats']['total_requests'] ?? 1)) * 100, 1) }}%
                        @else
                            0%
                        @endif
                    </span>
                </div>
                @endif
            </div>
            
            <div class="mt-4">
                <button onclick="testProvider('{{ $name }}')" class="w-full bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium py-2 px-4 rounded-md transition duration-200">
                    Test Provider
                </button>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Statistics Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Validation Statistics -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Validation Statistics (24h)</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Requests:</span>
                    <span class="font-medium">{{ $stats['total_requests'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Validated Requests:</span>
                    <span class="font-medium">{{ $stats['validated_requests'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Security Violations:</span>
                    <span class="font-medium text-red-600">{{ $stats['security_violations'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Content Violations:</span>
                    <span class="font-medium text-red-600">{{ $stats['content_violations'] ?? 0 }}</span>
                </div>
            </div>
        </div>

        <!-- Recent Interactions -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Interactions</h3>
            <div class="space-y-3 max-h-64 overflow-y-auto">
                @forelse($recent_interactions as $interaction)
                <div class="border-l-4 border-blue-500 pl-4 py-2">
                    <p class="text-sm font-medium text-gray-900">{{ Str::limit($interaction['query'], 50) }}</p>
                    <p class="text-xs text-gray-500">{{ $interaction['metadata']['timestamp'] ?? 'Unknown' }}</p>
                </div>
                @empty
                <p class="text-gray-500 text-sm">No recent interactions</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('ai-agent.conversation') }}" class="bg-blue-500 hover:bg-blue-600 text-white text-center py-3 px-4 rounded-md transition duration-200">
                Start Conversation
            </a>
            <button onclick="refreshStats()" class="bg-green-500 hover:bg-green-600 text-white py-3 px-4 rounded-md transition duration-200">
                Refresh Statistics
            </button>
            <button onclick="exportData()" class="bg-purple-500 hover:bg-purple-600 text-white py-3 px-4 rounded-md transition duration-200">
                Export Data
            </button>
        </div>
    </div>
</div>

<!-- Test Provider Modal -->
<div id="testModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Test Provider</h3>
                <div id="testResult" class="mb-4"></div>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeTestModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 py-2 px-4 rounded-md transition duration-200">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function testProvider(providerName) {
    const modal = document.getElementById('testModal');
    const resultDiv = document.getElementById('testResult');
    
    resultDiv.innerHTML = '<div class="text-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto"></div><p class="mt-2 text-gray-600">Testing provider...</p></div>';
    modal.classList.remove('hidden');
    
    fetch('{{ route("ai-agent.providers.test") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            provider: providerName,
            message: 'Hello, this is a test message.'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = `
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    <p class="font-medium">Test Successful!</p>
                    <p class="text-sm mt-1">Response Time: ${data.data.response_time}ms</p>
                    <p class="text-sm mt-1">Response: ${data.data.response}</p>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <p class="font-medium">Test Failed!</p>
                    <p class="text-sm mt-1">${data.error}</p>
                </div>
            `;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <p class="font-medium">Test Failed!</p>
                <p class="text-sm mt-1">Network error occurred</p>
            </div>
        `;
    });
}

function closeTestModal() {
    document.getElementById('testModal').classList.add('hidden');
}

function refreshStats() {
    location.reload();
}

function exportData() {
    // Implementation for data export
    alert('Export functionality will be implemented here');
}
</script>
@endpush 