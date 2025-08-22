# AI Reasoning Agent Documentation

## Overview

The AI Reasoning Agent is an advanced implementation that transforms the traditional static query processing into a dynamic, interactive reasoning system. Instead of generating all SQL queries at once, the LLM can now think, ask questions, and iterate through data analysis step-by-step.

## Key Features

### 🧠 **Interactive Reasoning Loop**
- **THINK**: The LLM analyzes what it knows and what it needs
- **SQL**: Generates specific queries to gather required data
- **ANSWER**: Provides final comprehensive answers after analysis

### 🔄 **Multi-Step Analysis**
- Iterative data exploration
- Context-aware query generation
- Progressive reasoning with data feedback

### 🛡️ **Enhanced Security**
- Read-only operations (SELECT only)
- Query timeout protection
- Result set size limits
- Comprehensive logging

### 📊 **Performance Analytics**
- Reasoning efficiency scoring
- Step-by-step analysis
- Performance optimization suggestions

## How It Works

### 1. **Question Analysis**
```php
$question = "Which clients took loans in the last 3 months and have balances over 5 million?";
$result = $aiAgent->processRequest($question);
```

### 2. **Table Identification**
The agent identifies relevant database tables:
- `clients` - for client information
- `loans` - for loan data
- `accounts` - for balance information

### 3. **Reasoning Loop Execution**
The LLM goes through multiple reasoning steps:

#### Step 1: Initial Thinking
```
THINK: I need to find clients who took loans recently. Let me first check the loans table structure and see what data I can get about recent loans.
```

#### Step 2: Data Query
```
SQL: SELECT client_id, loan_amount, disbursed_date, balance FROM loans WHERE disbursed_date >= CURRENT_DATE - INTERVAL '3 months' LIMIT 100;
```

#### Step 3: Analysis
```
THINK: Now I have recent loans. I need to filter those with balance > 5 million and get the client names.
```

#### Step 4: Follow-up Query
```
SQL: SELECT c.name, c.id, l.loan_amount, l.balance FROM clients c JOIN loans l ON c.id = l.client_id WHERE l.disbursed_date >= CURRENT_DATE - INTERVAL '3 months' AND l.balance > 5000000 LIMIT 100;
```

#### Step 5: Final Answer
```
ANSWER: Based on the data, the clients who took loans in the last 3 months and have balances over 5 million are: Alice John (ID: 123, Balance: 7.2M), Ibrahim Musa (ID: 456, Balance: 6.8M), and Fatuma Ally (ID: 789, Balance: 5.5M).
```

## API Usage

### Basic Usage
```php
use App\Services\AiAgentService;

$aiAgent = new AiAgentService();

// Process a question with reasoning
$result = $aiAgent->processRequest("How many clients do we have and what's their average loan amount?");
```

### Response Structure
```php
[
    'response' => 'Final answer from the AI',
    'original_query' => 'Original question',
    'sql_queries' => ['SELECT queries executed'],
    'sql_results' => ['Query results'],
    'reasoning_steps' => [
        [
            'step' => 1,
            'action' => 'THINK',
            'content' => 'Reasoning content'
        ],
        [
            'step' => 2,
            'action' => 'SQL',
            'content' => 'SELECT query'
        ],
        [
            'step' => 3,
            'action' => 'ANSWER',
            'content' => 'Final answer'
        ]
    ],
    'timestamp' => '2024-01-01 12:00:00',
    'method' => 'reasoning_loop',
    'relevant_tables' => ['clients', 'loans'],
    'table_schemas' => ['table schemas']
]
```

### Reasoning Mode Control
```php
// Enable/disable reasoning mode
$aiAgent->setReasoningMode(true);  // Enable reasoning
$aiAgent->setReasoningMode(false); // Disable reasoning (fallback to SQL-first)

// Check current mode
$isEnabled = $aiAgent->isReasoningEnabled();
```

### Performance Analysis
```php
// Get reasoning statistics
$stats = $aiAgent->getReasoningStats($result['reasoning_steps']);

// Analyze reasoning efficiency
$efficiency = $aiAgent->analyzeReasoningEfficiency($result['reasoning_steps'], $result['response']);

// Get comprehensive summary
$summary = $aiAgent->getReasoningSummary(
    $result['reasoning_steps'],
    $result['sql_queries'],
    $result['sql_results'],
    $result['response']
);
```

## Configuration

### Environment Variables
```env
GROQ_API_KEY=your_groq_api_key
OPENAI_API_KEY=your_openai_api_key
TOGETHER_API_KEY=your_together_api_key
```

### Service Configuration
```php
// In config/services.php
'groq' => [
    'api_key' => env('GROQ_API_KEY'),
],
'openai' => [
    'api_key' => env('OPENAI_API_KEY'),
],
'together' => [
    'api_key' => env('TOGETHER_API_KEY'),
],
```

## Safety Features

### SQL Security
- **Read-only operations**: Only SELECT queries allowed
- **Dangerous keyword detection**: Blocks INSERT, UPDATE, DELETE, etc.
- **Query timeout**: 30-second timeout per query
- **Result limits**: Automatic LIMIT 100 for large result sets

### Error Handling
- **Graceful degradation**: Falls back to traditional SQL-first approach
- **Comprehensive logging**: All operations logged for audit
- **Exception handling**: Robust error handling with detailed messages

## Performance Optimization

### Loop Limits
- **Maximum steps**: 10 reasoning steps per question
- **Timeout protection**: Prevents infinite loops
- **Memory management**: Efficient data handling

### Caching
- **Provider health**: Cached for 5 minutes
- **Schema information**: Cached for performance
- **Query results**: Not cached (always fresh data)

## Use Cases

### 1. **Complex Financial Analysis**
```php
$question = "What's the total value of loans in arrears and which clients are affected?";
```

### 2. **Multi-Table Queries**
```php
$question = "Show me clients who took loans in the last 3 months and have balances over 5 million";
```

### 3. **Trend Analysis**
```php
$question = "Which branches have the highest transaction volumes this month?";
```

### 4. **Risk Assessment**
```php
$question = "What's the average loan amount by client category and which categories have the highest default rates?";
```

## Benefits

### 🎯 **Accuracy**
- Step-by-step reasoning reduces errors
- Context-aware query generation
- Progressive data validation

### ⚡ **Efficiency**
- Targeted queries instead of broad searches
- Iterative refinement
- Optimized data retrieval

### 🔍 **Transparency**
- Visible reasoning process
- Auditable query trail
- Performance metrics

### 🛡️ **Security**
- Enhanced safety measures
- Comprehensive logging
- Error handling

## Testing

### Run the Test Script
```bash
php test_reasoning_agent.php
```

### Expected Output
```
🤖 AI Reasoning Agent Test
========================

Question 1: How many clients do we have and what's their average loan amount?
--------------------------------------------------------------------------------
✅ Processing Time: 2450.32ms
🔧 Method Used: reasoning_loop
🧠 Reasoning Steps: 4
📊 SQL Queries Executed: 2

🧠 Reasoning Process:
  Step 1 (THINK): I need to find the total number of clients and calculate their average loan amount...
  Step 2 (SQL): SELECT COUNT(*) as total_clients FROM clients...
  Step 3 (SQL): SELECT AVG(loan_amount) as avg_loan FROM loans...
  Step 4 (ANSWER): Based on the data, we have 1,247 clients with an average loan amount of...

📈 Reasoning Analysis:
  Efficiency Score: 85/100
  Quality: excellent
  Total Records Retrieved: 2

💬 Final Answer:
Based on the data, we have 1,247 clients with an average loan amount of $2,450,000.
```

## Troubleshooting

### Common Issues

1. **Loop Limit Reached**
   - Increase loop limit in `executeReasoningLoop()`
   - Check if question is too complex
   - Review reasoning steps for inefficiency

2. **SQL Execution Errors**
   - Verify table and column names
   - Check database permissions
   - Review query syntax

3. **Provider Failures**
   - Check API keys
   - Verify provider health
   - Review network connectivity

### Debug Mode
```php
// Enable detailed logging
Log::setLevel('debug');

// Check reasoning steps
foreach ($result['reasoning_steps'] as $step) {
    Log::debug('Reasoning Step', $step);
}
```

## Future Enhancements

### Planned Features
- **Query optimization**: Automatic query performance analysis
- **Caching layer**: Intelligent result caching
- **Learning system**: Improve reasoning based on past interactions
- **Multi-language support**: Support for different natural languages
- **Advanced analytics**: More sophisticated performance metrics

### Integration Opportunities
- **Dashboard integration**: Real-time reasoning visualization
- **API endpoints**: RESTful API for external access
- **WebSocket support**: Real-time reasoning updates
- **Mobile integration**: Mobile app support

## Conclusion

The AI Reasoning Agent represents a significant advancement in AI-powered data analysis. By enabling the LLM to think, ask questions, and iterate through data analysis, it provides more accurate, efficient, and transparent results compared to traditional static query approaches.

The system maintains high security standards while providing comprehensive performance analytics and robust error handling, making it suitable for production use in financial management systems. 