#!/bin/bash

# SACCOS Queue Manager Script
# This script helps manage queue workers for the notification system

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# Function to check if Laravel is properly configured
check_laravel() {
    if [ ! -f "artisan" ]; then
        print_error "This script must be run from the Laravel root directory"
        exit 1
    fi
}

# Function to display header
show_header() {
    clear
    echo "========================================="
    echo "   SACCOS Queue Manager"
    echo "========================================="
    echo ""
}

# Function to check queue status
check_status() {
    print_info "Checking queue status..."
    echo ""
    
    # Check pending jobs
    PENDING=$(php artisan tinker --execute="echo DB::table('jobs')->count();" 2>/dev/null)
    echo "📋 Pending jobs: $PENDING"
    
    # Check failed jobs
    FAILED=$(php artisan tinker --execute="echo DB::table('failed_jobs')->count();" 2>/dev/null)
    if [ "$FAILED" -gt 0 ]; then
        print_warning "Failed jobs: $FAILED"
    else
        echo "✅ Failed jobs: $FAILED"
    fi
    
    # Check if worker is running
    WORKER_COUNT=$(ps aux | grep "[p]hp artisan queue:work" | wc -l)
    echo ""
    if [ "$WORKER_COUNT" -gt 0 ]; then
        print_success "Queue workers running: $WORKER_COUNT"
        echo ""
        echo "Active workers:"
        ps aux | grep "[p]hp artisan queue:work" | awk '{print "  PID:", $2, "Started:", $9}'
    else
        print_warning "No queue workers are currently running"
    fi
    
    # Show queue sizes
    echo ""
    print_info "Queue sizes:"
    php artisan queue:monitor notifications:10,default:25 2>/dev/null || echo "  Unable to monitor queues"
}

# Function to start queue worker
start_worker() {
    local QUEUE=${1:-"notifications"}
    local TRIES=${2:-3}
    local TIMEOUT=${3:-60}
    
    print_info "Starting queue worker..."
    echo "  Queue: $QUEUE"
    echo "  Max tries: $TRIES"
    echo "  Timeout: ${TIMEOUT}s"
    echo ""
    
    # Start worker in background
    nohup php artisan queue:work --queue=$QUEUE --tries=$TRIES --timeout=$TIMEOUT > storage/logs/queue-worker.log 2>&1 &
    
    if [ $? -eq 0 ]; then
        print_success "Queue worker started successfully (PID: $!)"
        echo "  Log file: storage/logs/queue-worker.log"
    else
        print_error "Failed to start queue worker"
    fi
}

# Function to start multiple workers
start_multiple_workers() {
    local COUNT=${1:-2}
    print_info "Starting $COUNT queue workers..."
    
    for i in $(seq 1 $COUNT); do
        nohup php artisan queue:work --queue=notifications,default --tries=3 --timeout=60 > storage/logs/queue-worker-$i.log 2>&1 &
        print_success "Worker $i started (PID: $!)"
    done
}

# Function to stop all workers
stop_workers() {
    print_info "Stopping all queue workers..."
    
    # Get PIDs of all queue workers
    PIDS=$(ps aux | grep "[p]hp artisan queue:work" | awk '{print $2}')
    
    if [ -z "$PIDS" ]; then
        print_warning "No queue workers are running"
        return
    fi
    
    # Kill each worker
    for PID in $PIDS; do
        kill $PID 2>/dev/null
        if [ $? -eq 0 ]; then
            echo "  Stopped worker (PID: $PID)"
        fi
    done
    
    print_success "All queue workers stopped"
}

# Function to restart workers
restart_workers() {
    print_info "Restarting queue workers..."
    stop_workers
    echo ""
    sleep 2
    start_worker
}

# Function to process failed jobs
retry_failed() {
    FAILED=$(php artisan tinker --execute="echo DB::table('failed_jobs')->count();" 2>/dev/null)
    
    if [ "$FAILED" -eq 0 ]; then
        print_info "No failed jobs to retry"
        return
    fi
    
    print_warning "Found $FAILED failed jobs"
    echo ""
    
    # Show failed jobs
    php artisan queue:failed
    
    echo ""
    read -p "Do you want to retry all failed jobs? (y/n): " -n 1 -r
    echo ""
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        php artisan queue:retry all
        print_success "All failed jobs have been pushed back to the queue"
    fi
}

# Function to clear all jobs
clear_all() {
    print_warning "This will clear all pending and failed jobs!"
    read -p "Are you sure? (y/n): " -n 1 -r
    echo ""
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        php artisan queue:clear
        php artisan queue:flush
        print_success "All jobs cleared"
    else
        print_info "Operation cancelled"
    fi
}

# Function to monitor queue in real-time
monitor_realtime() {
    print_info "Starting real-time queue monitor (Press Ctrl+C to stop)..."
    echo ""
    
    while true; do
        clear
        show_header
        echo "Real-time Queue Monitor"
        echo "$(date '+%Y-%m-%d %H:%M:%S')"
        echo "----------------------------------------"
        
        # Show queue status
        PENDING=$(php artisan tinker --execute="echo DB::table('jobs')->count();" 2>/dev/null)
        FAILED=$(php artisan tinker --execute="echo DB::table('failed_jobs')->count();" 2>/dev/null)
        WORKER_COUNT=$(ps aux | grep "[p]hp artisan queue:work" | wc -l)
        
        echo "📋 Pending jobs: $PENDING"
        echo "❌ Failed jobs: $FAILED"
        echo "⚙️  Active workers: $WORKER_COUNT"
        echo ""
        
        # Show recent job activity
        echo "Recent Job Activity:"
        php artisan tinker --execute="
            \$recent = DB::table('jobs')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(['id', 'queue', 'attempts', 'created_at']);
            foreach(\$recent as \$job) {
                echo '  Job #' . \$job->id . ' - Queue: ' . \$job->queue . ' - Attempts: ' . \$job->attempts . PHP_EOL;
            }
        " 2>/dev/null || echo "  No recent jobs"
        
        echo ""
        echo "Press Ctrl+C to stop monitoring..."
        
        sleep 5
    done
}

# Function to test notification
test_notification() {
    print_info "Testing notification system..."
    echo ""
    
    php artisan payment:test-link --type=installments
    
    echo ""
    print_success "Test notification dispatched. Check the queue status to monitor processing."
}

# Function to show logs
show_logs() {
    print_info "Recent queue worker logs:"
    echo ""
    
    if [ -f "storage/logs/queue-worker.log" ]; then
        tail -50 storage/logs/queue-worker.log
    else
        print_warning "No queue worker log file found"
    fi
    
    echo ""
    print_info "Recent Laravel logs related to notifications:"
    echo ""
    
    if [ -f "storage/logs/laravel-$(date +%Y-%m-%d).log" ]; then
        grep -i "notification\|queue\|mail\|sms" storage/logs/laravel-$(date +%Y-%m-%d).log | tail -20
    fi
}

# Function to run queue worker with supervisor config
generate_supervisor_config() {
    print_info "Generating Supervisor configuration..."
    
    cat > storage/supervisor-queue-worker.conf << EOF
[program:saccos-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $(pwd)/artisan queue:work database --queue=notifications,default --sleep=3 --tries=3 --timeout=60
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=$(whoami)
numprocs=2
redirect_stderr=true
stdout_logfile=$(pwd)/storage/logs/supervisor-queue.log
stopwaitsecs=3600
EOF
    
    print_success "Supervisor configuration generated at: storage/supervisor-queue-worker.conf"
    echo ""
    echo "To use with Supervisor:"
    echo "  1. Copy the config: sudo cp storage/supervisor-queue-worker.conf /etc/supervisor/conf.d/"
    echo "  2. Reload supervisor: sudo supervisorctl reread"
    echo "  3. Update supervisor: sudo supervisorctl update"
    echo "  4. Start workers: sudo supervisorctl start saccos-queue-worker:*"
}

# Main menu
show_menu() {
    echo ""
    echo "Queue Management Options:"
    echo "========================="
    echo "  1) Check Status"
    echo "  2) Start Worker"
    echo "  3) Start Multiple Workers"
    echo "  4) Stop All Workers"
    echo "  5) Restart Workers"
    echo "  6) Retry Failed Jobs"
    echo "  7) Clear All Jobs"
    echo "  8) Monitor Real-time"
    echo "  9) Test Notification"
    echo "  10) Show Logs"
    echo "  11) Generate Supervisor Config"
    echo "  0) Exit"
    echo ""
}

# Main script execution
check_laravel
show_header

# Check if command line argument is provided
if [ $# -gt 0 ]; then
    case $1 in
        start)
            start_worker "${@:2}"
            ;;
        stop)
            stop_workers
            ;;
        restart)
            restart_workers
            ;;
        status)
            check_status
            ;;
        monitor)
            monitor_realtime
            ;;
        test)
            test_notification
            ;;
        *)
            print_error "Unknown command: $1"
            echo "Usage: $0 [start|stop|restart|status|monitor|test]"
            exit 1
            ;;
    esac
    exit 0
fi

# Interactive mode
while true; do
    show_menu
    read -p "Select an option: " option
    echo ""
    
    case $option in
        1)
            check_status
            ;;
        2)
            start_worker
            ;;
        3)
            read -p "How many workers? (default: 2): " count
            start_multiple_workers ${count:-2}
            ;;
        4)
            stop_workers
            ;;
        5)
            restart_workers
            ;;
        6)
            retry_failed
            ;;
        7)
            clear_all
            ;;
        8)
            monitor_realtime
            ;;
        9)
            test_notification
            ;;
        10)
            show_logs
            ;;
        11)
            generate_supervisor_config
            ;;
        0)
            print_info "Exiting..."
            exit 0
            ;;
        *)
            print_error "Invalid option"
            ;;
    esac
    
    echo ""
    read -p "Press Enter to continue..." dummy
    show_header
done