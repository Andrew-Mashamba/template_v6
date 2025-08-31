<div class="terminal-container" x-data="terminalConsole()" x-init="init()" style="height: 100vh;">
    <!-- Terminal Header -->
    <div class="terminal-header bg-gray-800 text-white p-4 rounded-t-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="flex space-x-2">
                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                    <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                </div>
                <h3 class="text-lg font-semibold">Terminal Console</h3>
                <span class="text-sm text-gray-400">SACCOS Core System</span>
            </div>
            
            <div class="flex items-center space-x-4">
                <!-- Claude Bridge Controls -->
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-400">Claude Bridge:</span>
                    @if($claudeBridgeActive)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <span class="w-2 h-2 bg-green-400 rounded-full mr-1"></span>
                            Active
                        </span>
                        <button wire:click="stopClaudeBridge" class="text-red-400 hover:text-red-300 text-sm">
                            Stop
                        </button>
                    @else
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <span class="w-2 h-2 bg-red-400 rounded-full mr-1"></span>
                            Inactive
                        </span>
                        <button wire:click="startClaudeBridge" class="text-green-400 hover:text-green-300 text-sm">
                            Start
                        </button>
                    @endif
                </div>
                
                <!-- Terminal Controls -->
                <div class="flex items-center space-x-2">
                    <button wire:click="clearOutput" class="text-gray-400 hover:text-white text-sm">
                        Clear
                    </button>
                    <button wire:click="getSystemInfo" class="text-gray-400 hover:text-white text-sm">
                        Info
                    </button>
                    <button wire:click="viewClaudeLogs" class="text-blue-400 hover:text-blue-300 text-sm">
                        View Logs
                    </button>
                    <button wire:click="checkClaudeRequests" class="text-purple-400 hover:text-purple-300 text-sm">
                        Check Requests
                    </button>
                    @if($isRunning)
                        <button wire:click="stopProcess" class="text-red-400 hover:text-red-300 text-sm">
                            Stop Process
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Terminal Output -->
    <div class="terminal-output bg-black text-green-400 p-4 font-mono text-sm overflow-y-auto" 
         style="height: 500px; max-height: 70vh;"
         x-ref="output"
         wire:poll.10s>
        <div class="whitespace-pre-wrap" style="font-family: 'Courier New', monospace;">
            @if(empty($output))
                <div class="text-gray-500">
                    <div class="mb-4">
                        <span class="text-green-400">$</span> Welcome to SACCOS Terminal Console
                    </div>
                    <div class="mb-2">Available commands:</div>
                    <div class="ml-4 space-y-1 text-sm">
                        <div>• <span class="text-blue-400">ls</span> - List directory contents</div>
                        <div>• <span class="text-blue-400">cd [path]</span> - Change directory</div>
                        <div>• <span class="text-blue-400">php artisan</span> - Laravel commands</div>
                        <div>• <span class="text-blue-400">php zona_ai/write_response.php</span> - Claude response writer</div>
                        <div>• <span class="text-blue-400">./zona_ai/start_claude_bridge.sh</span> - Start Claude bridge</div>
                    </div>
                    <div class="mt-4">
                        <span class="text-green-400">$</span> Type your command below...
                    </div>
                </div>
            @else
                {!! nl2br(e($output)) !!}
            @endif
        </div>
    </div>

    <!-- Command Input -->
    <div class="terminal-input bg-gray-900 p-4 rounded-b-lg">
        <div class="flex items-center space-x-2">
            <span class="text-green-400 font-mono">$</span>
            <input type="text" 
                   wire:model="command" 
                   wire:keydown.enter="executeCommand"
                   wire:keydown.arrow-up="navigateHistory('up')"
                   wire:keydown.arrow-down="navigateHistory('down')"
                   class="flex-1 bg-transparent text-white font-mono outline-none border-none"
                   placeholder="Enter command..."
                   x-ref="commandInput"
                   :disabled="$wire.isRunning">
            
            @if($isRunning)
                <div class="flex items-center space-x-2">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-green-400"></div>
                    <span class="text-green-400 text-sm">Running...</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Claude Bridge Actions -->
        <div class="bg-white rounded-lg shadow p-4">
            <h4 class="font-semibold text-gray-800 mb-3">🤖 Claude Bridge</h4>
            <div class="space-y-2">
                <button wire:click="startClaudeBridge" 
                        class="w-full bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm">
                    Start Bridge
                </button>
                <button wire:click="testClaudeBridge" 
                        class="w-full bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded text-sm">
                    Test Connection
                </button>
                <button wire:click="stopClaudeBridge" 
                        class="w-full bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded text-sm">
                    Stop Bridge
                </button>
                <button wire:click="viewClaudeLogs" 
                        class="w-full bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm">
                    View Logs
                </button>
                <button wire:click="checkClaudeRequests" 
                        class="w-full bg-purple-500 hover:bg-purple-600 text-white px-3 py-2 rounded text-sm">
                    Check Requests
                </button>
            </div>
        </div>

        <!-- System Commands -->
        <div class="bg-white rounded-lg shadow p-4">
            <h4 class="font-semibold text-gray-800 mb-3">💻 System</h4>
            <div class="space-y-2">
                <button wire:click="executeCommand('php artisan --version')" 
                        class="w-full bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm">
                    Laravel Version
                </button>
                <button wire:click="executeCommand('php --version')" 
                        class="w-full bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm">
                    PHP Version
                </button>
                <button wire:click="listDirectory" 
                        class="w-full bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm">
                    List Directory
                </button>
            </div>
        </div>

        <!-- SACCOS Commands -->
        <div class="bg-white rounded-lg shadow p-4">
            <h4 class="font-semibold text-gray-800 mb-3">🏦 SACCOS</h4>
            <div class="space-y-2">
                <button wire:click='executeCommand("php artisan claude:test-local \"How many members?\"")' 
                        class="w-full bg-purple-500 hover:bg-purple-600 text-white px-3 py-2 rounded text-sm">
                    Test Claude
                </button>
                <button wire:click="executeCommand('php artisan migrate:status')" 
                        class="w-full bg-purple-500 hover:bg-purple-600 text-white px-3 py-2 rounded text-sm">
                    Migration Status
                </button>
                <button wire:click="executeCommand('php artisan route:list --compact')" 
                        class="w-full bg-purple-500 hover:bg-purple-600 text-white px-3 py-2 rounded text-sm">
                    Routes
                </button>
            </div>
        </div>

        <!-- File Operations -->
        <div class="bg-white rounded-lg shadow p-4">
            <h4 class="font-semibold text-gray-800 mb-3">📁 Files</h4>
            <div class="space-y-2">
                <button wire:click="executeCommand('ls -la zona_ai/')" 
                        class="w-full bg-orange-500 hover:bg-orange-600 text-white px-3 py-2 rounded text-sm">
                    List Zona AI
                </button>
                <button wire:click="executeCommand('ls -la storage/app/claude-bridge/')" 
                        class="w-full bg-orange-500 hover:bg-orange-600 text-white px-3 py-2 rounded text-sm">
                    List Bridge Files
                </button>
                <button wire:click="executeCommand('tail -n 20 zona_ai/zona.log')" 
                        class="w-full bg-orange-500 hover:bg-orange-600 text-white px-3 py-2 rounded text-sm">
                    View Logs
                </button>
            </div>
        </div>
    </div>

    <!-- Status Bar -->
    <div class="mt-4 bg-gray-100 rounded-lg p-3">
        <div class="flex items-center justify-between text-sm text-gray-600">
            <div class="flex items-center space-x-4">
                <span>Directory: <span class="font-mono">{{ $currentDirectory }}</span></span>
                <span>History: {{ count($commandHistory) }} commands</span>
                @if($claudeBridgeActive)
                    <span class="text-green-600">Claude Bridge: Active (PID: {{ $claudeProcessId }})</span>
                @else
                    <span class="text-red-600">Claude Bridge: Inactive</span>
                @endif
            </div>
            <div class="flex items-center space-x-2">
                <span>Theme: {{ $theme }}</span>
                <span>Font: {{ $fontSize }}</span>
            </div>
        </div>
    </div>
</div>

<script>
function terminalConsole() {
    return {
        init() {
            this.scrollToBottom();
            this.$watch('$wire.output', () => {
                this.scrollToBottom();
            });
        },
        
        scrollToBottom() {
            if (this.$wire.autoScroll) {
                const output = this.$refs.output;
                output.scrollTop = output.scrollHeight;
            }
        },
        
        focusInput() {
            this.$refs.commandInput.focus();
        }
    }
}

// Auto-focus on input when component loads
document.addEventListener('livewire:load', function () {
    Livewire.hook('message.processed', (message, component) => {
        if (component.fingerprint.name === 'terminal.terminal-console') {
            const input = document.querySelector('[wire\\:model="command"]');
            if (input) {
                input.focus();
            }
        }
    });
});
</script>

<style>
.terminal-container {
    font-family: 'Courier New', 'Monaco', 'Menlo', monospace;
}

.terminal-output {
    background: #000;
    color: #00ff00;
    border: 1px solid #333;
}

.terminal-output::-webkit-scrollbar {
    width: 8px;
}

.terminal-output::-webkit-scrollbar-track {
    background: #1a1a1a;
}

.terminal-output::-webkit-scrollbar-thumb {
    background: #333;
    border-radius: 4px;
}

.terminal-output::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* ANSI color support */
.terminal-output .ansi-green { color: #00ff00; }
.terminal-output .ansi-red { color: #ff0000; }
.terminal-output .ansi-yellow { color: #ffff00; }
.terminal-output .ansi-blue { color: #0000ff; }
.terminal-output .ansi-magenta { color: #ff00ff; }
.terminal-output .ansi-cyan { color: #00ffff; }
.terminal-output .ansi-white { color: #ffffff; }
.terminal-output .ansi-bold { font-weight: bold; }

/* Responsive design */
@media (max-width: 768px) {
    .terminal-output {
        height: 300px !important;
    }
    
    .terminal-header {
        padding: 0.5rem;
    }
    
    .terminal-header h3 {
        font-size: 1rem;
    }
}
</style>
