# AI Agent Service Documentation

## Overview

The AI Agent Service is a comprehensive Laravel-based solution that provides intelligent conversational AI capabilities with multiple provider support, fallback mechanisms, and security features. It's designed to replace n8n workflows with native Laravel services.

## Features

- **Multi-Provider Support**: Groq, OpenAI, Together.ai with automatic fallback
- **Memory Management**: Conversation history and context preservation
- **Security Validation**: Input/output validation and security checks
- **SQL Query Execution**: Safe database query execution
- **Real-time Interface**: Livewire-powered chat interface
- **Provider Health Monitoring**: Automatic health checks and failover
- **Rate Limiting**: Built-in rate limiting and usage tracking

## Architecture

### Core Services

1. **AiAgentService** - Main orchestration service
2. **AiProviderService** - Provider management and health monitoring
3. **AiMemoryService** - Conversation memory and history
4. **AiValidationService** - Input/output validation and security

### Components

1. **AiAgentController** - API endpoints and web interface
2. **AiAgentChat** - Livewire component for real-time chat

## Installation

### 1. Environment Configuration

Add the following to your `.env` file:

```env
# AI Provider API Keys
GROQ_API_KEY=your_groq_api_key_here
OPENAI_API_KEY=your_openai_api_key_here
TOGETHER_API_KEY=your_together_api_key_here

# Optional: Custom URLs and Models
GROQ_API_URL=https://api.groq.com/openai/v1/chat/completions
GROQ_DEFAULT_MODEL=llama3-8b-8192
GROQ_TIMEOUT=30
GROQ_RATE_LIMIT=1000

OPENAI_API_URL=https://api.openai.com/v1/chat/completions
OPENAI_DEFAULT_MODEL=gpt-3.5-turbo
OPENAI_TIMEOUT=60
OPENAI_RATE_LIMIT=3000

TOGETHER_API_URL=https://api.together.xyz/v1/chat/completions
TOGETHER_DEFAULT_MODEL=meta-llama/Llama-2-70b-chat-hf
TOGETHER_TIMEOUT=45
TOGETHER_RATE_LIMIT=500
```

### 2. Database Migration

Run the migration to create the AI interactions table:

```bash
php artisan migrate
```

### 3. Service Registration

The services are auto-discovered by Laravel. No additional registration is required.

## Usage

### Basic Usage

```php
use App\Services\AiAgentService;

class YourController extends Controller
{
    public function processQuery(Request $request)
    {
        $aiService = app(AiAgentService::class);
        
        $response = $aiService->processRequest(
            $request->input('query'),
            [
                'user_id' => auth()->id(),
                'user_permissions' => ['read', 'write']
            ]
        );
        
        return response()->json($response);
    }
}
```

### Web Interface

Access the AI agent through the web interface:

- **Dashboard**: `/ai-agent` - Monitor providers and statistics
- **Conversation**: `/ai-agent/conversation` - Chat interface

### API Endpoints

#### Process AI Request
```http
POST /api/ai-agent/process
Content-Type: application/json

{
    "query": "What is the total balance of all accounts?",
    "context": {
        "user_id": 1,
        "user_permissions": ["read"]
    },
    "options": {
        "provider": "groq"
    }
}
```

#### Execute SQL Query
```http
POST /api/ai-agent/sql
Content-Type: application/json

{
    "sql": "SELECT COUNT(*) as total_accounts FROM accounts WHERE status = 'active'",
    "params": []
}
```

#### Get Provider Status
```http
GET /api/ai-agent/providers/status
```

#### Test Provider
```http
POST /api/ai-agent/providers/test
Content-Type: application/json

{
    "provider": "groq",
    "message": "Hello, this is a test message."
}
```

#### Get Conversation History
```http
GET /api/ai-agent/conversation/history?limit=10
```

#### Search Interactions
```http
POST /api/ai-agent/conversation/search
Content-Type: application/json

{
    "query": "balance",
    "filters": {
        "date_from": "2024-01-01",
        "date_to": "2024-01-31"
    }
}
```

#### Clear History
```http
DELETE /api/ai-agent/conversation/clear
Content-Type: application/json

{
    "session_id": "session_123"
}
```

#### Get Statistics
```http
GET /api/ai-agent/stats?timeframe=24h
```

## Service Configuration

### Provider Configuration

```php
use App\Services\AiProviderService;

$providerService = app(AiProviderService::class);

// Update provider configuration
$providerService->updateProviderConfig('groq', [
    'timeout' => 45,
    'rate_limit' => 1500
]);

// Enable/disable provider
$providerService->setProviderStatus('openai', false);
```

### Validation Configuration

```php
use App\Services\AiValidationService;

$validationService = app(AiValidationService::class);

// Update security patterns
$validationService->updatePatterns('sql_injection', [
    '/\b(union|select|insert|update|delete|drop|create|alter)\b/i',
    '/[\'";]/'
]);
```

## Security Features

### Input Validation

The service includes comprehensive input validation:

- **SQL Injection Prevention**: Pattern-based detection
- **XSS Prevention**: HTML/script tag filtering
- **Command Injection Prevention**: System command detection
- **Path Traversal Prevention**: Directory traversal detection
- **Sensitive Data Protection**: API keys, passwords, etc.

### Rate Limiting

Built-in rate limiting per user:
- Default: 100 requests per hour per user
- Configurable per provider
- Automatic blocking of excessive requests

### Permission-Based Access

User permissions are checked for:
- SQL query execution
- Sensitive data access
- Administrative operations

## Memory Management

### Conversation Memory

```php
use App\Services\AiMemoryService;

$memoryService = app(AiMemoryService::class);

// Add interaction to memory
$memoryService->addInteraction(
    "What is the account balance?",
    "The account balance is $1,000",
    ['user_id' => 1]
);

// Get recent interactions
$interactions = $memoryService->getRecentInteractions(10);

// Get conversation context
$context = $memoryService->getFormattedContext(5);
```

### Memory Features

- **Session-based**: Each conversation has a unique session ID
- **Persistent Storage**: Interactions stored in database
- **Context Preservation**: Previous interactions influence responses
- **Automatic Cleanup**: Old interactions automatically removed

## Error Handling

### Common Errors

1. **Provider Unavailable**
   ```json
   {
       "success": false,
       "error": "All AI providers failed. Attempted: groq, openai, together"
   }
   ```

2. **Validation Failed**
   ```json
   {
       "success": false,
       "errors": ["Query contains potentially harmful content"]
   }
   ```

3. **Rate Limit Exceeded**
   ```json
   {
       "success": false,
       "error": "Rate limit exceeded. Please try again later."
   }
   ```

### Error Logging

All errors are automatically logged with:
- Error type and message
- User context
- Request details
- Timestamp

## Monitoring and Analytics

### Provider Health Monitoring

- Automatic health checks every 5 minutes
- Response time tracking
- Success/failure rate monitoring
- Automatic failover to healthy providers

### Usage Statistics

- Total requests per provider
- Success/failure rates
- Average response times
- User activity patterns

### Performance Metrics

- Request processing time
- Memory usage
- Database query performance
- Cache hit rates

## Customization

### Custom Providers

To add a new AI provider:

1. Add configuration to `config/services.php`
2. Update `AiProviderService` with provider details
3. Add API key to `.env` file

### Custom Validation Rules

```php
// Add custom validation patterns
$validationService->updatePatterns('custom', [
    '/your-custom-pattern/'
]);
```

### Custom System Prompts

Modify the system prompt in `AiAgentService::getSystemPrompt()` to customize AI behavior.

## Troubleshooting

### Common Issues

1. **Provider Not Responding**
   - Check API keys in `.env`
   - Verify network connectivity
   - Check provider status endpoint

2. **Validation Errors**
   - Review input content
   - Check security patterns
   - Verify user permissions

3. **Memory Issues**
   - Check database connectivity
   - Verify table exists
   - Check cache configuration

### Debug Mode

Enable debug logging in `.env`:
```env
LOG_LEVEL=debug
```

### Health Checks

Run provider health checks:
```php
$providerService = app(AiProviderService::class);
$status = $providerService->getProviderStatus();
```

## Best Practices

1. **API Key Management**
   - Use environment variables
   - Rotate keys regularly
   - Monitor usage

2. **Error Handling**
   - Always handle exceptions
   - Log errors appropriately
   - Provide user-friendly messages

3. **Performance**
   - Use caching for repeated queries
   - Implement rate limiting
   - Monitor response times

4. **Security**
   - Validate all inputs
   - Sanitize outputs
   - Check user permissions

## Migration from n8n

### Step-by-Step Migration

1. **Export n8n Workflow**
   - Export your n8n workflow JSON
   - Identify nodes and connections

2. **Map to Laravel Services**
   - HTTP Request nodes → AiAgentService
   - JavaScript Code nodes → Custom logic in services
   - Database nodes → AiAgentService::executeSqlQuery()

3. **Update API Calls**
   - Replace n8n webhook URLs with Laravel routes
   - Update authentication headers
   - Modify response handling

4. **Test and Validate**
   - Test all functionality
   - Verify error handling
   - Check performance

### Example Migration

**n8n Workflow:**
```javascript
// HTTP Request to OpenAI
const response = await $http.post('https://api.openai.com/v1/chat/completions', {
    headers: { 'Authorization': 'Bearer ' + $env.OPENAI_API_KEY },
    body: { model: 'gpt-3.5-turbo', messages: [{ role: 'user', content: $input.all()[0].json.query }] }
});
```

**Laravel Equivalent:**
```php
$response = $aiAgentService->processRequest($request->input('query'));
```

## Support

For issues and questions:
1. Check the troubleshooting section
2. Review error logs
3. Test with different providers
4. Verify configuration settings

## License

This AI Agent Service is part of the NBC SACCOS template and follows the same licensing terms. 