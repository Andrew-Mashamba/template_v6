#!/bin/bash

# SACCOS Core System - End of Day Test Runner
# This script runs all end-of-day related tests

echo "================================================"
echo "SACCOS Core System - End of Day Test Suite"
echo "================================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to run a specific test
run_test() {
    local test_name=$1
    local test_path=$2
    
    echo -e "${YELLOW}Running: ${test_name}${NC}"
    echo "----------------------------------------"
    
    if php artisan test --filter="$test_path" --stop-on-failure; then
        echo -e "${GREEN}✓ ${test_name} passed${NC}"
    else
        echo -e "${RED}✗ ${test_name} failed${NC}"
        exit 1
    fi
    
    echo ""
}

# Function to run all end-of-day tests
run_all_tests() {
    echo "Running all End of Day tests..."
    echo ""
    
    # Unit Tests
    echo -e "${YELLOW}=== UNIT TESTS ===${NC}"
    run_test "DailyActivityStatus Model Tests" "DailyActivityStatusTest"
    
    # Feature Tests
    echo -e "${YELLOW}=== FEATURE TESTS ===${NC}"
    run_test "Daily System Activities Service Tests" "DailySystemActivitiesTest"
    run_test "EndOfDay Livewire Component Tests" "EndOfDayLivewireTest"
    
    # Integration Tests
    echo -e "${YELLOW}=== INTEGRATION TESTS ===${NC}"
    run_test "End of Day Integration Tests" "EndOfDayIntegrationTest"
    
    echo -e "${GREEN}================================================${NC}"
    echo -e "${GREEN}All End of Day tests passed successfully!${NC}"
    echo -e "${GREEN}================================================${NC}"
}

# Function to run a specific test file
run_specific_test() {
    local test_file=$1
    echo "Running specific test: $test_file"
    php artisan test "$test_file" --stop-on-failure
}

# Function to run tests with coverage
run_with_coverage() {
    echo "Running End of Day tests with code coverage..."
    php artisan test --coverage --filter="EndOfDay|DailyActivity|DailySystem" --min=70
}

# Function to run quick smoke test
run_smoke_test() {
    echo "Running End of Day smoke test..."
    echo ""
    
    # Just test the most critical functionality
    php artisan test --filter="test_execute_daily_activities_successfully|test_component_renders_successfully|test_complete_end_of_day_flow" --stop-on-failure
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Smoke test passed${NC}"
    else
        echo -e "${RED}✗ Smoke test failed${NC}"
        exit 1
    fi
}

# Main menu
echo "Select test option:"
echo "1. Run all End of Day tests"
echo "2. Run unit tests only"
echo "3. Run feature tests only"
echo "4. Run integration tests only"
echo "5. Run with code coverage"
echo "6. Run quick smoke test"
echo "7. Run specific test file"
echo ""

if [ $# -eq 0 ]; then
    read -p "Enter option (1-7): " option
else
    option=$1
fi

case $option in
    1)
        run_all_tests
        ;;
    2)
        echo "Running unit tests only..."
        php artisan test tests/Unit/Models/DailyActivityStatusTest.php
        ;;
    3)
        echo "Running feature tests only..."
        php artisan test --filter="DailySystemActivitiesTest|EndOfDayLivewireTest"
        ;;
    4)
        echo "Running integration tests only..."
        php artisan test tests/Feature/EndOfDay/EndOfDayIntegrationTest.php
        ;;
    5)
        run_with_coverage
        ;;
    6)
        run_smoke_test
        ;;
    7)
        read -p "Enter test file path: " test_file
        run_specific_test "$test_file"
        ;;
    *)
        echo "Invalid option"
        exit 1
        ;;
esac

echo ""
echo "Test run completed at: $(date)"