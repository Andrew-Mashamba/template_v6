#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$modelsPath = __DIR__ . '/app/Models';
$relationships = [];

// Get all PHP files in Models directory
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($modelsPath)
);

foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $className = basename($file->getFilename(), '.php');
        
        // Extract table name
        if (preg_match('/protected\s+\$table\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $tableMatch)) {
            $tableName = $tableMatch[1];
        } else {
            // Convert class name to table name (snake_case plural)
            $tableName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
            $tableName = str_replace('_model', '', $tableName) . 's';
        }
        
        // Extract relationships
        $modelRelations = [];
        
        // BelongsTo relationships
        if (preg_match_all('/public\s+function\s+(\w+)\(\)[^{]*\{[^}]*belongsTo\(([^,\)]+)(?:,\s*[\'"]([^\'"]+)[\'"])?(?:,\s*[\'"]([^\'"]+)[\'"])?\)/', $content, $belongsToMatches, PREG_SET_ORDER)) {
            foreach ($belongsToMatches as $match) {
                $modelRelations[] = [
                    'type' => 'belongsTo',
                    'method' => $match[1],
                    'related_model' => trim($match[2], '\\'),
                    'foreign_key' => $match[3] ?? null,
                    'owner_key' => $match[4] ?? null
                ];
            }
        }
        
        // HasMany relationships
        if (preg_match_all('/public\s+function\s+(\w+)\(\)[^{]*\{[^}]*hasMany\(([^,\)]+)(?:,\s*[\'"]([^\'"]+)[\'"])?(?:,\s*[\'"]([^\'"]+)[\'"])?\)/', $content, $hasManyMatches, PREG_SET_ORDER)) {
            foreach ($hasManyMatches as $match) {
                $modelRelations[] = [
                    'type' => 'hasMany',
                    'method' => $match[1],
                    'related_model' => trim($match[2], '\\'),
                    'foreign_key' => $match[3] ?? null,
                    'local_key' => $match[4] ?? null
                ];
            }
        }
        
        // HasOne relationships
        if (preg_match_all('/public\s+function\s+(\w+)\(\)[^{]*\{[^}]*hasOne\(([^,\)]+)(?:,\s*[\'"]([^\'"]+)[\'"])?(?:,\s*[\'"]([^\'"]+)[\'"])?\)/', $content, $hasOneMatches, PREG_SET_ORDER)) {
            foreach ($hasOneMatches as $match) {
                $modelRelations[] = [
                    'type' => 'hasOne',
                    'method' => $match[1],
                    'related_model' => trim($match[2], '\\'),
                    'foreign_key' => $match[3] ?? null,
                    'local_key' => $match[4] ?? null
                ];
            }
        }
        
        // BelongsToMany relationships
        if (preg_match_all('/public\s+function\s+(\w+)\(\)[^{]*\{[^}]*belongsToMany\(([^,\)]+)/', $content, $belongsToManyMatches, PREG_SET_ORDER)) {
            foreach ($belongsToManyMatches as $match) {
                $modelRelations[] = [
                    'type' => 'belongsToMany',
                    'method' => $match[1],
                    'related_model' => trim($match[2], '\\')
                ];
            }
        }
        
        if (!empty($modelRelations)) {
            $relationships[$className] = [
                'table' => $tableName,
                'relations' => $modelRelations
            ];
        }
    }
}

// Output as JSON for easy processing
echo json_encode($relationships, JSON_PRETTY_PRINT);