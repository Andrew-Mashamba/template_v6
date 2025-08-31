<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DirectDatabaseQueryService;

class QueryDatabase extends Command
{
    protected $signature = 'db:query {question}';
    protected $description = 'Query the database using natural language';

    public function handle()
    {
        $question = $this->argument('question');
        
        $this->info("Question: " . $question);
        $this->line(str_repeat('-', 60));
        
        $service = new DirectDatabaseQueryService();
        $result = $service->processQuery($question);
        
        if ($result['success']) {
            $this->info("\nAnswer:");
            $this->line($result['message']);
        } else {
            $this->error("\nError: " . $result['message']);
        }
        
        return Command::SUCCESS;
    }
}