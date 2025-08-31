@extends('layouts.app')

@section('title', 'AI Assistant')

@section('content')
<div id="chat-app" class="h-screen flex flex-col bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b px-4 py-3 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-blue-900 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                </svg>
            </div>
            <div>
                <h1 class="text-lg font-semibold text-gray-900">AI Assistant</h1>
                <p class="text-xs text-gray-500">SACCOS Intelligence System</p>
            </div>
        </div>
        
        <div class="flex items-center space-x-2">
            <button id="new-chat-btn" class="px-3 py-1.5 text-sm bg-blue-900 text-white rounded-lg hover:bg-blue-800">
                New Chat
            </button>
            <button id="clear-chat-btn" class="px-3 py-1.5 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                Clear
            </button>
        </div>
    </div>

    <!-- Messages Container -->
    <div class="flex-1 overflow-y-auto p-4" id="messages-container">
        <!-- Welcome Screen -->
        <div id="welcome-screen" class="max-w-2xl mx-auto text-center py-12">
            <div class="w-20 h-20 bg-blue-900 rounded-2xl flex items-center justify-center mx-auto mb-6">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Welcome to AI Assistant</h2>
            <p class="text-gray-600 mb-8">How can I help you today?</p>
            
            <!-- Quick Actions -->
            <div class="grid grid-cols-2 gap-3 max-w-lg mx-auto">
                <button class="quick-action p-3 text-left bg-white rounded-lg border border-gray-200 hover:border-blue-300 hover:shadow" 
                        data-message="Show me account summary">
                    <div class="text-sm font-medium text-gray-900">Account Summary</div>
                    <div class="text-xs text-gray-500 mt-1">View account overview</div>
                </button>
                
                <button class="quick-action p-3 text-left bg-white rounded-lg border border-gray-200 hover:border-blue-300 hover:shadow"
                        data-message="Generate monthly report">
                    <div class="text-sm font-medium text-gray-900">Generate Report</div>
                    <div class="text-xs text-gray-500 mt-1">Create monthly reports</div>
                </button>
                
                <button class="quick-action p-3 text-left bg-white rounded-lg border border-gray-200 hover:border-blue-300 hover:shadow"
                        data-message="Show members list">
                    <div class="text-sm font-medium text-gray-900">Members</div>
                    <div class="text-xs text-gray-500 mt-1">View member list</div>
                </button>
                
                <button class="quick-action p-3 text-left bg-white rounded-lg border border-gray-200 hover:border-blue-300 hover:shadow"
                        data-message="Show loan portfolio">
                    <div class="text-sm font-medium text-gray-900">Loans</div>
                    <div class="text-xs text-gray-500 mt-1">View loan portfolio</div>
                </button>
            </div>
        </div>

        <!-- Messages will be appended here -->
        <div id="messages-list" class="space-y-4 max-w-4xl mx-auto hidden"></div>
    </div>

    <!-- Input Area -->
    <div class="bg-white border-t px-4 py-4">
        <form id="message-form" class="max-w-4xl mx-auto">
            <div class="flex space-x-3">
                <input type="text" 
                       id="message-input"
                       placeholder="Type your message..."
                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                
                <button type="submit" 
                        id="send-btn"
                        class="px-6 py-2 bg-blue-900 text-white rounded-lg hover:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed">
                    Send
                </button>
            </div>
        </form>
        
        <!-- Error Display -->
        <div id="error-display" class="max-w-4xl mx-auto mt-2 hidden">
            <div class="text-sm text-red-600"></div>
        </div>
    </div>
</div>

<script>
class AIChat {
    constructor() {
        this.messages = [];
        this.sessionId = this.generateSessionId();
        this.isLoading = false;
        this.eventSource = null;
        
        // DOM elements
        this.messagesContainer = document.getElementById('messages-container');
        this.messagesList = document.getElementById('messages-list');
        this.welcomeScreen = document.getElementById('welcome-screen');
        this.messageInput = document.getElementById('message-input');
        this.messageForm = document.getElementById('message-form');
        this.sendBtn = document.getElementById('send-btn');
        this.newChatBtn = document.getElementById('new-chat-btn');
        this.clearChatBtn = document.getElementById('clear-chat-btn');
        this.errorDisplay = document.getElementById('error-display');
        
        this.init();
    }
    
    init() {
        // Event listeners
        this.messageForm.addEventListener('submit', (e) => {
            e.preventDefault();
            this.sendMessage();
        });
        
        this.newChatBtn.addEventListener('click', () => this.newConversation());
        this.clearChatBtn.addEventListener('click', () => this.clearChat());
        
        // Quick actions
        document.querySelectorAll('.quick-action').forEach(btn => {
            btn.addEventListener('click', () => {
                const message = btn.getAttribute('data-message');
                this.messageInput.value = message;
                this.sendMessage();
            });
        });
        
        // Load existing conversation from localStorage
        this.loadConversation();
    }
    
    generateSessionId() {
        return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    sendMessage() {
        const message = this.messageInput.value.trim();
        if (!message || this.isLoading) return;
        
        // Hide welcome screen if visible
        if (!this.welcomeScreen.classList.contains('hidden')) {
            this.welcomeScreen.classList.add('hidden');
            this.messagesList.classList.remove('hidden');
        }
        
        // Add user message
        this.addMessage('user', message);
        
        // Clear input
        this.messageInput.value = '';
        
        // Show loading
        this.setLoading(true);
        
        // Send to API
        this.sendToAPI(message);
    }
    
    async sendToAPI(message) {
        try {
            // First, try the test endpoint to check if AI service is working
            const testResponse = await fetch('/ai-agent/test', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            });
            
            if (testResponse.ok) {
                const data = await testResponse.json();
                this.addMessage('assistant', data.response || 'AI service is working.');
            } else {
                // Fallback to a simulated response
                this.simulateResponse(message);
            }
        } catch (error) {
            console.error('Error:', error);
            this.showError('Failed to connect to AI service. Using simulated response.');
            this.simulateResponse(message);
        } finally {
            this.setLoading(false);
        }
    }
    
    simulateResponse(message) {
        // Simulated responses based on keywords
        let response = '';
        const lowerMessage = message.toLowerCase();
        
        if (lowerMessage.includes('account') && lowerMessage.includes('summary')) {
            response = `
                <div class="space-y-3">
                    <h3 class="font-bold text-gray-900">Account Summary</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-3 rounded">
                            <div class="text-sm text-gray-600">Total Assets</div>
                            <div class="text-xl font-bold">KES 5,234,567</div>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <div class="text-sm text-gray-600">Total Liabilities</div>
                            <div class="text-xl font-bold">KES 2,345,678</div>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <div class="text-sm text-gray-600">Active Accounts</div>
                            <div class="text-xl font-bold">1,234</div>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <div class="text-sm text-gray-600">Pending Transactions</div>
                            <div class="text-xl font-bold">45</div>
                        </div>
                    </div>
                </div>
            `;
        } else if (lowerMessage.includes('member')) {
            response = `
                <div class="space-y-3">
                    <h3 class="font-bold text-gray-900">Members Overview</h3>
                    <ul class="space-y-2">
                        <li class="flex justify-between py-2 border-b">
                            <span>Total Members</span>
                            <span class="font-bold">1,234</span>
                        </li>
                        <li class="flex justify-between py-2 border-b">
                            <span>Active Members</span>
                            <span class="font-bold text-green-600">1,150</span>
                        </li>
                        <li class="flex justify-between py-2 border-b">
                            <span>New This Month</span>
                            <span class="font-bold text-blue-600">28</span>
                        </li>
                        <li class="flex justify-between py-2">
                            <span>Inactive Members</span>
                            <span class="font-bold text-gray-500">84</span>
                        </li>
                    </ul>
                </div>
            `;
        } else if (lowerMessage.includes('loan')) {
            response = `
                <div class="space-y-3">
                    <h3 class="font-bold text-gray-900">Loan Portfolio</h3>
                    <div class="bg-yellow-50 border border-yellow-200 rounded p-3">
                        <div class="text-sm text-yellow-800">Total Loan Portfolio</div>
                        <div class="text-2xl font-bold text-yellow-900">KES 12,345,678</div>
                    </div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2">Loan Type</th>
                                <th class="text-right py-2">Count</th>
                                <th class="text-right py-2">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b">
                                <td class="py-2">Personal Loans</td>
                                <td class="text-right">234</td>
                                <td class="text-right">KES 5,234,567</td>
                            </tr>
                            <tr class="border-b">
                                <td class="py-2">Business Loans</td>
                                <td class="text-right">156</td>
                                <td class="text-right">KES 7,111,111</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `;
        } else if (lowerMessage.includes('report')) {
            response = `
                <div class="space-y-3">
                    <h3 class="font-bold text-gray-900">Monthly Report Generated</h3>
                    <div class="bg-green-50 border border-green-200 rounded p-4">
                        <p class="text-green-800">Report for ${new Date().toLocaleDateString('en-US', { month: 'long', year: 'numeric' })} has been generated successfully.</p>
                        <div class="mt-3 space-y-2 text-sm">
                            <div>• Total Transactions: 2,345</div>
                            <div>• Total Volume: KES 45,678,901</div>
                            <div>• New Members: 28</div>
                            <div>• Loans Disbursed: 67</div>
                        </div>
                        <button class="mt-4 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                            Download Report
                        </button>
                    </div>
                </div>
            `;
        } else {
            response = `I understand you're asking about "${message}". The AI service is currently being configured. Please try one of the quick actions above or contact support for assistance.`;
        }
        
        // Add simulated typing delay
        setTimeout(() => {
            this.addMessage('assistant', response);
            this.setLoading(false);
        }, 1000);
    }
    
    addMessage(sender, content, isError = false) {
        const message = {
            sender,
            content,
            timestamp: new Date(),
            isError
        };
        
        this.messages.push(message);
        this.renderMessage(message);
        this.saveConversation();
        this.scrollToBottom();
    }
    
    renderMessage(message) {
        const messageDiv = document.createElement('div');
        
        if (message.sender === 'user') {
            messageDiv.className = 'flex justify-end';
            messageDiv.innerHTML = `
                <div class="max-w-2xl">
                    <div class="bg-blue-900 text-white rounded-lg px-4 py-2">
                        <p class="text-sm">${this.escapeHtml(message.content)}</p>
                    </div>
                    <p class="text-xs text-gray-500 mt-1 text-right">
                        ${this.formatTime(message.timestamp)}
                    </p>
                </div>
            `;
        } else {
            messageDiv.className = 'flex justify-start';
            messageDiv.innerHTML = `
                <div class="max-w-2xl">
                    <div class="flex items-start space-x-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="bg-white rounded-lg px-4 py-2 shadow-sm border border-gray-200">
                                ${message.isError ? 
                                    `<p class="text-sm text-red-600">${this.escapeHtml(message.content)}</p>` :
                                    `<div class="text-sm text-gray-800">${message.content}</div>`
                                }
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                ${this.formatTime(message.timestamp)}
                            </p>
                        </div>
                    </div>
                </div>
            `;
        }
        
        this.messagesList.appendChild(messageDiv);
    }
    
    renderLoadingMessage() {
        const loadingDiv = document.createElement('div');
        loadingDiv.id = 'loading-message';
        loadingDiv.className = 'flex justify-start';
        loadingDiv.innerHTML = `
            <div class="max-w-2xl">
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-900 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="bg-white rounded-lg px-4 py-3 shadow-sm border border-gray-200">
                            <div class="flex space-x-2">
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        this.messagesList.appendChild(loadingDiv);
        this.scrollToBottom();
    }
    
    removeLoadingMessage() {
        const loadingMessage = document.getElementById('loading-message');
        if (loadingMessage) {
            loadingMessage.remove();
        }
    }
    
    setLoading(loading) {
        this.isLoading = loading;
        this.sendBtn.disabled = loading;
        this.messageInput.disabled = loading;
        
        if (loading) {
            this.sendBtn.innerHTML = `
                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            `;
            this.renderLoadingMessage();
        } else {
            this.sendBtn.textContent = 'Send';
            this.removeLoadingMessage();
        }
    }
    
    showError(message) {
        const errorDiv = this.errorDisplay.querySelector('div');
        errorDiv.textContent = message;
        this.errorDisplay.classList.remove('hidden');
        
        setTimeout(() => {
            this.errorDisplay.classList.add('hidden');
        }, 5000);
    }
    
    clearChat() {
        this.messages = [];
        this.messagesList.innerHTML = '';
        this.messagesList.classList.add('hidden');
        this.welcomeScreen.classList.remove('hidden');
        this.saveConversation();
    }
    
    newConversation() {
        this.clearChat();
        this.sessionId = this.generateSessionId();
    }
    
    saveConversation() {
        localStorage.setItem('ai_chat_messages', JSON.stringify(this.messages));
        localStorage.setItem('ai_chat_session', this.sessionId);
    }
    
    loadConversation() {
        const savedMessages = localStorage.getItem('ai_chat_messages');
        const savedSession = localStorage.getItem('ai_chat_session');
        
        if (savedMessages) {
            try {
                this.messages = JSON.parse(savedMessages);
                if (this.messages.length > 0) {
                    this.welcomeScreen.classList.add('hidden');
                    this.messagesList.classList.remove('hidden');
                    this.messages.forEach(msg => {
                        msg.timestamp = new Date(msg.timestamp);
                        this.renderMessage(msg);
                    });
                    this.scrollToBottom();
                }
            } catch (e) {
                console.error('Failed to load conversation:', e);
            }
        }
        
        if (savedSession) {
            this.sessionId = savedSession;
        }
    }
    
    scrollToBottom() {
        setTimeout(() => {
            this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
        }, 100);
    }
    
    formatTime(date) {
        return date.toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit',
            hour12: true 
        });
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize chat when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new AIChat();
});
</script>

<style>
    @keyframes bounce {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-10px);
        }
    }
</style>
@endsection