@extends('layouts.app')

@section('title', 'Terminal Console - SACCOS Core System')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Terminal Console</h1>
        <p class="text-gray-600">Run console applications and manage Claude Bridge directly in your browser</p>
    </div>

    <!-- Security Notice -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">Security Notice</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>This terminal has access to your server's file system and can execute commands. Only authorized users should access this feature.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Terminal Component -->
    @livewire('terminal.terminal-console')

    <!-- Help Section -->
    <div class="mt-8 bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Quick Reference</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Claude Bridge Commands -->
            <div>
                <h3 class="font-medium text-gray-900 mb-3">🤖 Claude Bridge</h3>
                <div class="space-y-2 text-sm">
                    <div class="bg-gray-50 p-2 rounded">
                        <code class="text-blue-600">./zona_ai/start_claude_bridge.sh</code>
                        <p class="text-gray-600 mt-1">Start Claude Bridge</p>
                    </div>
                    <div class="bg-gray-50 p-2 rounded">
                        <code class="text-blue-600">php zona_ai/write_response.php [id] [message]</code>
                        <p class="text-gray-600 mt-1">Write Claude response</p>
                    </div>
                    <div class="bg-gray-50 p-2 rounded">
                        <code class="text-blue-600">php artisan claude:test-local "question"</code>
                        <p class="text-gray-600 mt-1">Test Claude connection</p>
                    </div>
                </div>
            </div>

            <!-- System Commands -->
            <div>
                <h3 class="font-medium text-gray-900 mb-3">💻 System</h3>
                <div class="space-y-2 text-sm">
                    <div class="bg-gray-50 p-2 rounded">
                        <code class="text-blue-600">php artisan --version</code>
                        <p class="text-gray-600 mt-1">Laravel version</p>
                    </div>
                    <div class="bg-gray-50 p-2 rounded">
                        <code class="text-blue-600">php artisan migrate:status</code>
                        <p class="text-gray-600 mt-1">Database migrations</p>
                    </div>
                    <div class="bg-gray-50 p-2 rounded">
                        <code class="text-blue-600">php artisan route:list</code>
                        <p class="text-gray-600 mt-1">List routes</p>
                    </div>
                </div>
            </div>

            <!-- File Operations -->
            <div>
                <h3 class="font-medium text-gray-900 mb-3">📁 Files</h3>
                <div class="space-y-2 text-sm">
                    <div class="bg-gray-50 p-2 rounded">
                        <code class="text-blue-600">ls -la zona_ai/</code>
                        <p class="text-gray-600 mt-1">List Zona AI files</p>
                    </div>
                    <div class="bg-gray-50 p-2 rounded">
                        <code class="text-blue-600">tail -f zona_ai/zona.log</code>
                        <p class="text-gray-600 mt-1">Monitor logs</p>
                    </div>
                    <div class="bg-gray-50 p-2 rounded">
                        <code class="text-blue-600">ps aux | grep claude</code>
                        <p class="text-gray-600 mt-1">Check Claude processes</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features -->
    <div class="mt-8 bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Features</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-3">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-gray-900">Real-time Command Execution</h3>
                        <p class="text-sm text-gray-500">Execute commands and see output in real-time</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-gray-900">Claude Bridge Management</h3>
                        <p class="text-sm text-gray-500">Start, stop, and monitor Claude Bridge directly</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-gray-900">Command History</h3>
                        <p class="text-sm text-gray-500">Navigate through previous commands with arrow keys</p>
                    </div>
                </div>
            </div>
            
            <div class="space-y-3">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-gray-900">Process Management</h3>
                        <p class="text-sm text-gray-500">Start and stop long-running processes</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-gray-900">Quick Actions</h3>
                        <p class="text-sm text-gray-500">One-click buttons for common operations</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-gray-900">Responsive Design</h3>
                        <p class="text-sm text-gray-500">Works on desktop, tablet, and mobile devices</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
