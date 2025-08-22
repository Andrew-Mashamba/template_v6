@extends('layouts.app')

@section('title', 'AI Agent Conversation')

@section('content')
<div class="flex h-screen bg-gray-50">
    <!-- Sidebar -->
    <div class="w-64 bg-white shadow-lg">
        <div class="p-4 border-b">
            <h2 class="text-lg font-semibold text-gray-900">AI Agent</h2>
            <p class="text-sm text-gray-600">Session: {{ substr($session_id, 0, 8) }}...</p>
        </div>
        
        <div class="p-4">
            <button onclick="newConversation()" class="w-full bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-md mb-4 transition duration-200">
                New Conversation
            </button>
            
            <div class="space-y-2">
                <h3 class="text-sm font-medium text-gray-700 mb-2">Recent Conversations</h3>
                <div id="conversationList" class="space-y-1">
                    <!-- Conversation history will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Main Chat Area -->
    <div class="flex-1 flex flex-col">
        <!-- Chat Header -->
        <div class="bg-white border-b px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">AI Assistant</h1>
                    <p class="text-sm text-gray-600">Ask me anything about your financial system</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button onclick="clearHistory()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                    <button onclick="exportConversation()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Chat Messages -->
        <div id="chatMessages" class="flex-1 overflow-y-auto p-6 space-y-4">
            <!-- Welcome Message -->
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex-1 bg-white rounded-lg p-4 shadow-sm border">
                    <p class="text-gray-900">Hello! I'm your AI assistant. I can help you with:</p>
                    <ul class="mt-2 text-sm text-gray-600 space-y-1">
                        <li>• Financial data analysis and reporting</li>
                        <li>• Account management and transactions</li>
                        <li>• Loan processing and assessment</li>
                        <li>• System queries and troubleshooting</li>
                        <li>• General questions about your operations</li>
                    </ul>
                    <p class="mt-2 text-sm text-gray-600">How can I help you today?</p>
                </div>
            </div>

            <!-- Load previous messages -->
            @foreach($recent_interactions as $interaction)
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex-1 bg-gray-100 rounded-lg p-4">
                    <p class="text-gray-900">{{ $interaction['query'] }}</p>
                </div>
            </div>

            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex-1 bg-white rounded-lg p-4 shadow-sm border">
                    <p class="text-gray-900">{{ $interaction['response'] }}</p>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Input Area -->
        <div class="bg-white border-t p-4">
            <form id="chatForm" class="flex space-x-4">
                <div class="flex-1">
                    <textarea 
                        id="messageInput" 
                        rows="1" 
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                        placeholder="Type your message here..."
                        onkeydown="handleKeyDown(event)"
                    ></textarea>
                </div>
                <button 
                    type="submit" 
                    id="sendButton"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg transition duration-200 flex items-center space-x-2"
                >
                    <span>Send</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white rounded-lg p-6">
            <div class="flex items-center space-x-3">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
                <span class="text-gray-700">AI is thinking...</span>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let isLoading = false;

// Handle form submission
document.getElementById('chatForm').addEventListener('submit', function(e) {
    e.preventDefault();
    sendMessage();
});

// Handle Enter key
function handleKeyDown(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
}

// Send message to AI
function sendMessage() {
    const input = document.getElementById('messageInput');
    const message = input.value.trim();
    
    if (!message || isLoading) return;
    
    // Add user message to chat
    addMessage(message, 'user');
    input.value = '';
    
    // Show loading
    showLoading();
    
    // Send to AI
    fetch('{{ route("ai-agent.process") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            query: message,
            context: {
                session_id: '{{ $session_id }}'
            }
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            addMessage(data.data.response, 'ai');
        } else {
            addMessage('Sorry, I encountered an error. Please try again.', 'ai', true);
        }
    })
    .catch(error => {
        hideLoading();
        addMessage('Sorry, I encountered an error. Please try again.', 'ai', true);
    });
}

// Add message to chat
function addMessage(content, sender, isError = false) {
    const chatMessages = document.getElementById('chatMessages');
    const messageDiv = document.createElement('div');
    messageDiv.className = 'flex items-start space-x-3';
    
    const iconDiv = document.createElement('div');
    iconDiv.className = 'flex-shrink-0';
    
    const icon = document.createElement('div');
    icon.className = sender === 'user' 
        ? 'w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center'
        : 'w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center';
    
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.className = 'w-5 h-5 ' + (sender === 'user' ? 'text-gray-600' : 'text-white');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('viewBox', '0 0 24 24');
    
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('stroke-linecap', 'round');
    path.setAttribute('stroke-linejoin', 'round');
    path.setAttribute('stroke-width', '2');
    
    if (sender === 'user') {
        path.setAttribute('d', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z');
    } else {
        path.setAttribute('d', 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z');
    }
    
    svg.appendChild(path);
    icon.appendChild(svg);
    iconDiv.appendChild(icon);
    
    const contentDiv = document.createElement('div');
    contentDiv.className = 'flex-1 ' + (sender === 'user' 
        ? 'bg-gray-100 rounded-lg p-4' 
        : 'bg-white rounded-lg p-4 shadow-sm border');
    
    if (isError) {
        contentDiv.className += ' border-red-200 bg-red-50';
    }
    
    contentDiv.innerHTML = `<p class="${isError ? 'text-red-700' : 'text-gray-900'}">${content}</p>`;
    
    messageDiv.appendChild(iconDiv);
    messageDiv.appendChild(contentDiv);
    chatMessages.appendChild(messageDiv);
    
    // Scroll to bottom
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Show loading overlay
function showLoading() {
    isLoading = true;
    document.getElementById('loadingOverlay').classList.remove('hidden');
    document.getElementById('sendButton').disabled = true;
}

// Hide loading overlay
function hideLoading() {
    isLoading = false;
    document.getElementById('loadingOverlay').classList.add('hidden');
    document.getElementById('sendButton').disabled = false;
}

// New conversation
function newConversation() {
    if (confirm('Start a new conversation? This will clear the current session.')) {
        window.location.href = '{{ route("ai-agent.conversation") }}';
    }
}

// Clear history
function clearHistory() {
    if (confirm('Clear conversation history? This action cannot be undone.')) {
        fetch('{{ route("ai-agent.conversation.clear") }}', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                session_id: '{{ $session_id }}'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to clear history');
            }
        });
    }
}

// Export conversation
function exportConversation() {
    // Implementation for conversation export
    alert('Export functionality will be implemented here');
}

// Auto-resize textarea
document.getElementById('messageInput').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});
</script>
@endpush 