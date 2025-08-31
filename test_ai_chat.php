<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Chat Test (No Auth)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .chat-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            min-height: 400px;
            margin-bottom: 20px;
            overflow-y: auto;
        }
        .message {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
        }
        .user-message {
            background: #007bff;
            color: white;
            text-align: right;
        }
        .ai-message {
            background: #e9ecef;
            color: #333;
        }
        .input-group {
            display: flex;
            gap: 10px;
        }
        input[type="text"] {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .loading {
            text-align: center;
            color: #666;
            padding: 20px;
        }
    </style>
</head>
<body>
    <h1>AI Chat Test (Direct Claude CLI)</h1>
    <p>This is a test interface that directly calls the Claude CLI without authentication.</p>
    
    <div id="chat-container" class="chat-container"></div>
    
    <div class="input-group">
        <input type="text" id="message-input" placeholder="Type your message..." />
        <button id="send-btn" onclick="sendMessage()">Send</button>
    </div>
    
    <div id="error-display"></div>

    <script>
        const sessionId = 'test_' + Date.now();
        let isLoading = false;
        
        function addMessage(content, isUser = false) {
            const container = document.getElementById('chat-container');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message ' + (isUser ? 'user-message' : 'ai-message');
            messageDiv.innerHTML = content;
            container.appendChild(messageDiv);
            container.scrollTop = container.scrollHeight;
        }
        
        async function sendMessage() {
            if (isLoading) return;
            
            const input = document.getElementById('message-input');
            const message = input.value.trim();
            
            if (!message) return;
            
            isLoading = true;
            document.getElementById('send-btn').disabled = true;
            
            // Add user message
            addMessage(message, true);
            input.value = '';
            
            // Show loading
            const loadingId = 'loading_' + Date.now();
            const container = document.getElementById('chat-container');
            const loadingDiv = document.createElement('div');
            loadingDiv.id = loadingId;
            loadingDiv.className = 'loading';
            loadingDiv.innerHTML = 'Claude is thinking...';
            container.appendChild(loadingDiv);
            
            try {
                // Call test AI route
                const response = await fetch('/test-ai/process', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        message: message,
                        sessionId: sessionId
                    })
                });
                
                const data = await response.json();
                
                // Remove loading
                document.getElementById(loadingId)?.remove();
                
                if (data.success) {
                    addMessage(data.message);
                } else {
                    addMessage('<span style="color: red;">Error: ' + (data.error || 'Unknown error') + '</span>');
                }
                
            } catch (error) {
                document.getElementById(loadingId)?.remove();
                addMessage('<span style="color: red;">Error: ' + error.message + '</span>');
            } finally {
                isLoading = false;
                document.getElementById('send-btn').disabled = false;
            }
        }
        
        // Allow Enter key to send
        document.getElementById('message-input').addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        // Initial message
        addMessage('Hello! I am Claude CLI running locally. Ask me anything!');
    </script>
</body>
</html>