# 🤖 AI Agent Setup Guide

## Quick Setup

Your AI agent service is now ready! Here's how to get it running:

### 1. Add Environment Variables

Copy the contents of `ai_agent_env_config.txt` to your `.env` file:

```env
# AI Agent Service Configuration
GROQ_API_KEY=your_groq_api_key_here
OPENAI_API_KEY=your_openai_api_key_here
TOGETHER_API_KEY=your_together_api_key_here

# Configuration (optional - defaults provided)
GROQ_API_URL=https://api.groq.com/openai/v1/chat/completions
GROQ_DEFAULT_MODEL=meta-llama/llama-4-scout-17b-16e-instruct
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

### 2. Database Setup

The migration has already been run. The `ai_interactions` table is created.

### 3. Test the Setup

Run the test script to verify everything is working:

```bash
php test_ai_agent.php
```

### 4. Access the AI Agent

Once your Laravel application is running:

- **Dashboard**: `http://your-domain/ai-agent`
- **Chat Interface**: `http://your-domain/ai-agent/conversation`

### 5. API Endpoints

You can also use the AI agent via API:

```bash
# Process a query
curl -X POST http://your-domain/api/ai-agent/process \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "query": "What is the total balance of all accounts?",
    "context": {
      "user_id": 1,
      "user_permissions": ["read"]
    }
  }'

# Get provider status
curl http://your-domain/api/ai-agent/providers/status

# Test a specific provider
curl -X POST http://your-domain/api/ai-agent/providers/test \
  -H "Content-Type: application/json" \
  -d '{
    "provider": "groq",
    "message": "Hello, this is a test message."
  }'
```

## Features Available

✅ **Multi-Provider Support**: Groq, OpenAI, Together.ai with automatic fallback
✅ **Real-time Chat Interface**: Livewire-powered conversation interface
✅ **Security Validation**: Input/output validation and security checks
✅ **Memory Management**: Conversation history and context preservation
✅ **SQL Query Execution**: Safe database operations
✅ **Provider Health Monitoring**: Automatic health checks
✅ **Rate Limiting**: Built-in protection against abuse

## Provider Models

- **Groq**: `meta-llama/llama-4-scout-17b-16e-instruct` (default)
- **OpenAI**: `gpt-3.5-turbo`
- **Together.ai**: `meta-llama/Llama-2-70b-chat-hf`

## Troubleshooting

### If providers are not responding:

1. Check your API keys in `.env`
2. Verify network connectivity
3. Run the test script: `php test_ai_agent.php`
4. Check the provider status endpoint

### If you get validation errors:

1. Review your input content
2. Check user permissions
3. Ensure you're authenticated

### If the chat interface doesn't load:

1. Make sure Livewire is properly installed
2. Check browser console for JavaScript errors
3. Verify routes are accessible

## Migration from n8n

This AI agent service replaces n8n workflows:

- **HTTP Request nodes** → `AiAgentService::processRequest()`
- **JavaScript Code nodes** → Custom logic in services
- **Database nodes** → `AiAgentService::executeSqlQuery()`
- **Webhook endpoints** → Laravel API routes

## Next Steps

1. **Customize the system prompt** in `AiAgentService::getSystemPrompt()`
2. **Add custom validation rules** in `AiValidationService`
3. **Extend with additional providers** if needed
4. **Integrate with your existing workflows**

## Support

- Check the full documentation in `docs/AI_AGENT_SERVICE.md`
- Review the test files for examples
- Check Laravel logs for detailed error information

---

🎉 **Your AI agent is ready to use!** Start chatting at `/ai-agent/conversation` 