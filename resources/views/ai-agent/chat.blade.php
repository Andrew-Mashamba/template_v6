@extends('layouts.app')

@section('title', 'AI Agent - SACCO Assistant')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            {{-- Header --}}
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6">
                <h1 class="text-3xl font-bold mb-2">AI Agent - SACCO Assistant</h1>
                <p class="text-blue-100">Ask questions about your SACCO data and get intelligent responses</p>
            </div>

            {{-- Chat Interface --}}
            <div class="p-6">
                {{-- Question Input Form --}}
                <form id="ai-question-form" class="mb-8">
                    @csrf
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <input 
                                type="text" 
                                id="question-input" 
                                name="question" 
                                placeholder="Ask a question about your SACCO data..." 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required
                            >
                        </div>
                        <button 
                            type="submit" 
                            id="submit-btn"
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                        >
                            Ask AI
                        </button>
                    </div>
                </form>

                {{-- Loading Indicator --}}
                <div id="loading" class="hidden text-center py-8">
                    <div class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-800 rounded-lg">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Processing your question...
                    </div>
                </div>

                {{-- Response Container --}}
                <div id="response-container" class="hidden">
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">AI Response:</h3>
                        <div id="ai-response" class="prose max-w-none">
                            {{-- AI response will be inserted here --}}
                        </div>
                    </div>
                </div>

                {{-- Error Container --}}
                <div id="error-container" class="hidden">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Error</h3>
                                <p class="text-sm text-red-700 mt-1" id="error-message"></p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Sample Questions --}}
                <div class="mt-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Sample Questions:</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <button class="sample-question text-left p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="font-medium text-gray-800">How many liability accounts and list their names</div>
                            <div class="text-sm text-gray-600 mt-1">Get count and list of liability accounts</div>
                        </button>
                        <button class="sample-question text-left p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="font-medium text-gray-800">Show me all active loans</div>
                            <div class="text-sm text-gray-600 mt-1">Display active loan information</div>
                        </button>
                        <button class="sample-question text-left p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="font-medium text-gray-800">What is the total balance of all accounts?</div>
                            <div class="text-sm text-gray-600 mt-1">Calculate total account balances</div>
                        </button>
                        <button class="sample-question text-left p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="font-medium text-gray-800">List all clients with their contact information</div>
                            <div class="text-sm text-gray-600 mt-1">Show client directory</div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('ai-question-form');
    const questionInput = document.getElementById('question-input');
    const submitBtn = document.getElementById('submit-btn');
    const loading = document.getElementById('loading');
    const responseContainer = document.getElementById('response-container');
    const aiResponse = document.getElementById('ai-response');
    const errorContainer = document.getElementById('error-container');
    const errorMessage = document.getElementById('error-message');
    const sampleQuestions = document.querySelectorAll('.sample-question');

    // Handle form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        askQuestion(questionInput.value);
    });

    // Handle sample question clicks
    sampleQuestions.forEach(button => {
        button.addEventListener('click', function() {
            const questionText = this.querySelector('.font-medium').textContent;
            questionInput.value = questionText;
            askQuestion(questionText);
        });
    });

    function askQuestion(question) {
        if (!question.trim()) return;

        // Show loading state
        loading.classList.remove('hidden');
        responseContainer.classList.add('hidden');
        errorContainer.classList.add('hidden');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Processing...';

        // Make API request
        fetch('/api/ai-agent/ask', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ question: question })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Display response
                aiResponse.innerHTML = data.response;
                responseContainer.classList.remove('hidden');
            } else {
                // Display error
                errorMessage.textContent = data.message || 'An error occurred while processing your question.';
                errorContainer.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            errorMessage.textContent = 'Network error. Please try again.';
            errorContainer.classList.remove('hidden');
        })
        .finally(() => {
            // Hide loading state
            loading.classList.add('hidden');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Ask AI';
        });
    }
});
</script>
@endsection 