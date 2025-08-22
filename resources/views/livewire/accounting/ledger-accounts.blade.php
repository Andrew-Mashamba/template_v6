<div class="w-full bg-white">
    <!-- message container -->



    <div>



        <div class="bg-white rounded rounded-lg shadow-sm max-w-7xl mx-auto p-4  rounded-lg shadow-lg">




                <div class="relative overflow-x-auto rounded-lg bg-white p-4">

                    <!-- Detailed Tables for Each Asset Account -->
                    @foreach($asset_accounts as $income_account)
                        @php
                            $category_accounts = DB::table($income_account->category_name)->get();
                            $total_amount = 0;
                        @endphp



                        <a name="{{ $income_account->category_name }}"></a>

                            @foreach($category_accounts as $category_account)
                                @php
                                    $balance = DB::table('accounts')
                                        ->where('sub_category_code', $category_account->sub_category_code)
                                        ->value('balance');
                                    $total_amount += $balance;
                                @endphp
                            <hr  class="boder-b-0 my-8"/>
                                <div class="bg-blue-50 border-b border-blue-400 text-black w-full">
                                    <div class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-sm uppercase">
                                        {{
                                            DB::table('accounts')
                                            ->where('sub_category_code', $category_account->sub_category_code)
                                            ->value('account_name')
                                        }}
                                    </div>

                                    @php
                                        $currentYear = Carbon\Carbon::now()->year;
                                        $lastYear = $currentYear - 1;

                                        // Query to get the last year's closing balance
                                        $lastYearClosingBalance = DB::table('general_ledger')
                                            ->where('sub_category_code', $category_account->sub_category_code)
                                            ->whereYear('created_at', $lastYear)
                                            ->orderBy('created_at', 'desc')
                                            ->first(['record_on_account_number_balance']); // Assuming 'balance' is the field holding the balance

                                        $openingBalance = $lastYearClosingBalance->balance ?? 0;

                                        // Query the general ledger for the current year transactions
                                        $transactions = DB::table('general_ledger')
                                            ->where('sub_category_code', $category_account->sub_category_code)
                                            ->whereYear('created_at', $currentYear)
                                            ->orderBy('created_at', 'asc')
                                            ->get();

                                        // Prepare an array to hold the formatted transaction data
                                        $formattedTransactions = [];
                                        $runningBalance = $openingBalance;
                                        $totalDebit = 0;
                                        $totalCredit = 0;

                                        // Initialize the monthly data structure
                                        $monthlyData = [];

                                        // Process transactions to aggregate debit and credit amounts by month
                                        foreach ($transactions as $transaction) {
                                            // Get the month and year for the transaction
                                            $monthYear = Carbon\Carbon::parse($transaction->created_at);
                                            $monthRange = $monthYear->format('F Y');
                                            $startOfMonth = $monthYear->startOfMonth()->format('d.m.Y');
                                            $endOfMonth = $monthYear->endOfMonth()->format('d.m.Y');

                                            // Add transaction details to the appropriate month
                                            if (!isset($monthlyData[$monthRange])) {
                                                $monthlyData[$monthRange] = [
                                                    'debit' => 0,
                                                    'credit' => 0,
                                                    'startDate' => $startOfMonth,
                                                    'endDate' => $endOfMonth,
                                                    'monthYear' => $monthYear, // Store the Carbon object for accurate year/month later
                                                ];
                                            }

                                            $monthlyData[$monthRange]['debit'] += $transaction->debit ?? 0;
                                            $monthlyData[$monthRange]['credit'] += $transaction->credit ?? 0;
                                        }

                                        // Generate formatted transactions from monthly data
                                        $monthsInYear = [];
                                        for ($i = 1; $i <= 12; $i++) {
                                            $monthName = Carbon\Carbon::createFromDate($currentYear, $i, 1)->format('F Y');
                                            $monthsInYear[$monthName] = [
                                                'debit' => 0,
                                                'credit' => 0,
                                                'monthYear' => Carbon\Carbon::createFromDate($currentYear, $i, 1),
                                            ];
                                        }

                                        // Merge monthlyData with monthsInYear, including zero months
                                        foreach ($monthlyData as $monthRange => $amounts) {
                                            $monthsInYear[$monthRange]['debit'] = $amounts['debit'];
                                            $monthsInYear[$monthRange]['credit'] = $amounts['credit'];
                                        }

                                        // Create the final formatted transactions
                                        foreach ($monthsInYear as $monthRange => $amounts) {
                                            // Calculate the running balance
                                            $runningBalance += $amounts['credit'] - $amounts['debit'];

                                            // Accumulate total debit and credit
                                            $totalDebit += $amounts['debit'];
                                            $totalCredit += $amounts['credit'];

                                            // Add entry for each month
                                            $formattedTransactions[] = [
                                                'date' => $amounts['monthYear']->endOfMonth()->format('d.m.Y'),
                                                'transaction_details' => 'Receipts and Payments ' . $monthRange,
                                                'l_folio' => $amounts['monthYear']->format('F') . ' 01-' . $amounts['monthYear']->daysInMonth . '/' . $currentYear,
                                                'debit' => number_format($amounts['debit'], 2),
                                                'credit' => number_format($amounts['credit'], 2),
                                                'balance' => number_format($runningBalance, 2),
                                            ];
                                        }

                                        // Add the opening balance at the start of the year
                                        array_unshift($formattedTransactions, [
                                            'date' => '01.01.' . $currentYear,
                                            'transaction_details' => 'Opening balance',
                                            'l_folio' => 'January 01-31/' . $currentYear,
                                            'debit' => number_format($openingBalance, 2),
                                            'credit' => '',
                                            'balance' => number_format($openingBalance, 2),
                                        ]);

                                        // Calculate closing balance
                                        $closingBalance = $runningBalance;

                                        // Include closing balance in the total row
                                        $formattedTransactions[] = [
                                            'date' => 'Total',
                                            'transaction_details' => 'Total Transactions',
                                            'l_folio' => '',
                                            'debit' => number_format($totalDebit, 2),
                                            'credit' => number_format($totalCredit, 2),
                                            'balance' => number_format($closingBalance, 2),
                                        ];
                                    @endphp





                                    <table class="w-full text-sm text-left text-blue-100 mb-4">
                                        <thead class="text-xs text-black bg-blue-100 dark:text-white w-full">
                                        <tr class="w-full">
                                            <th scope="col" class="border-r px-6 py-4  text-xs ">Date</th>
                                            <th scope="col" class="border-r px-6 py-4 text-left text-xs ">Transaction Details</th>
                                            <th scope="col" class="border-r px-6 py-4  text-left text-xs">L/Folio</th>
                                            <th scope="col" class="border-r px-6 py-4  text-right text-xs">Debit</th>
                                            <th scope="col" class="border-r px-6 py-4  text-right text-xs">Credit</th>
                                            <th scope="col" class="border-r px-6 py-4  text-right text-xs">Balance</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach ($formattedTransactions as $transaction)
                                            @if($transaction['date']=='Total')
                                                <tr class="bg-gray-200 text-black uppercase w-full">
                                                    <td class="whitespace-nowrap px-6 py-2 font-medium text-xs">Total</td>
                                                    <td class="whitespace-nowrap px-6 py-2 text-right text-xs"></td>
                                                    <td class="whitespace-nowrap px-6 py-2 text-right  text-xs"></td>
                                                    <td class="whitespace-nowrap px-6 py-2 text-right  text-xs">{{ number_format($totalDebit, 2) }}</td>
                                                    <td class="whitespace-nowrap px-6 py-2 text-right text-xs">{{ number_format($totalCredit, 2) }}</td>
                                                    <td class="whitespace-nowrap px-6 py-2 text-right text-xs">{{ number_format($closingBalance, 2) }}</td>
                                                </tr>
                                                @else
                                            <tr class="bg-blue-50 border-b border-blue-200 text-black w-full">
                                                <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-left">
                                                    {{ $transaction['date'] }}
                                                </td>
                                                <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-left">
                                                    {{ $transaction['transaction_details'] }}
                                                </td>
                                                <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-left">
                                                    {{ $transaction['l_folio'] }}
                                                </td>
                                                <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-right">
                                                    {{ $transaction['debit'] }}
                                                </td>
                                                <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-right">
                                                    {{ $transaction['credit'] }}
                                                </td>
                                                <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-right">
                                                    {{ $transaction['balance'] }}
                                                </td>
                                            </tr>
                                            @endif
                                        @endforeach
                                        </tbody>
                                        <tfoot>

                                        </tfoot>
                                    </table>






                                </div>
                            @endforeach

                    @endforeach
                </div>


                <div class="relative overflow-x-auto rounded-lg bg-white p-4">

                <!-- Detailed Tables for Each Asset Account -->
                @foreach($liability_accounts as $income_account)
                    @php
                        $category_accounts = DB::table($income_account->category_name)->get();
                        $total_amount = 0;
                    @endphp

                    <hr id="{{ $income_account->category_name }}" class="boder-b-0 my-8"/>

                    <a name="{{ $income_account->category_name }}"></a>

                    @foreach($category_accounts as $category_account)
                        @php
                            $balance = DB::table('accounts')
                                ->where('sub_category_code', $category_account->sub_category_code)
                                ->value('balance');
                            $total_amount += $balance;
                        @endphp

                        <div class="bg-blue-50 border-b border-blue-400 text-black w-full">
                            <div class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-sm uppercase">
                                {{
                                    DB::table('accounts')
                                    ->where('sub_category_code', $category_account->sub_category_code)
                                    ->value('account_name')
                                }}
                            </div>

                            @php
                                $currentYear = Carbon\Carbon::now()->year;
                                $lastYear = $currentYear - 1;

                                // Query to get the last year's closing balance
                                $lastYearClosingBalance = DB::table('general_ledger')
                                    ->where('sub_category_code', $category_account->sub_category_code)
                                    ->whereYear('created_at', $lastYear)
                                    ->orderBy('created_at', 'desc')
                                    ->first(['record_on_account_number_balance']); // Assuming 'balance' is the field holding the balance

                                $openingBalance = $lastYearClosingBalance->balance ?? 0;

                                // Query the general ledger for the current year transactions
                                $transactions = DB::table('general_ledger')
                                    ->where('sub_category_code', $category_account->sub_category_code)
                                    ->whereYear('created_at', $currentYear)
                                    ->orderBy('created_at', 'asc')
                                    ->get();

                                // Prepare an array to hold the formatted transaction data
                                $formattedTransactions = [];
                                $runningBalance = $openingBalance;
                                $totalDebit = 0;
                                $totalCredit = 0;

                                // Initialize the monthly data structure
                                $monthlyData = [];

                                // Process transactions to aggregate debit and credit amounts by month
                                foreach ($transactions as $transaction) {
                                    // Get the month and year for the transaction
                                    $monthYear = Carbon\Carbon::parse($transaction->created_at);
                                    $monthRange = $monthYear->format('F Y');
                                    $startOfMonth = $monthYear->startOfMonth()->format('d.m.Y');
                                    $endOfMonth = $monthYear->endOfMonth()->format('d.m.Y');

                                    // Add transaction details to the appropriate month
                                    if (!isset($monthlyData[$monthRange])) {
                                        $monthlyData[$monthRange] = [
                                            'debit' => 0,
                                            'credit' => 0,
                                            'startDate' => $startOfMonth,
                                            'endDate' => $endOfMonth,
                                            'monthYear' => $monthYear, // Store the Carbon object for accurate year/month later
                                        ];
                                    }

                                    $monthlyData[$monthRange]['debit'] += $transaction->debit ?? 0;
                                    $monthlyData[$monthRange]['credit'] += $transaction->credit ?? 0;
                                }

                                // Generate formatted transactions from monthly data
                                $monthsInYear = [];
                                for ($i = 1; $i <= 12; $i++) {
                                    $monthName = Carbon\Carbon::createFromDate($currentYear, $i, 1)->format('F Y');
                                    $monthsInYear[$monthName] = [
                                        'debit' => 0,
                                        'credit' => 0,
                                        'monthYear' => Carbon\Carbon::createFromDate($currentYear, $i, 1),
                                    ];
                                }

                                // Merge monthlyData with monthsInYear, including zero months
                                foreach ($monthlyData as $monthRange => $amounts) {
                                    $monthsInYear[$monthRange]['debit'] = $amounts['debit'];
                                    $monthsInYear[$monthRange]['credit'] = $amounts['credit'];
                                }

                                // Create the final formatted transactions
                                foreach ($monthsInYear as $monthRange => $amounts) {
                                    // Calculate the running balance
                                    $runningBalance += $amounts['credit'] - $amounts['debit'];

                                    // Accumulate total debit and credit
                                    $totalDebit += $amounts['debit'];
                                    $totalCredit += $amounts['credit'];

                                    // Add entry for each month
                                    $formattedTransactions[] = [
                                        'date' => $amounts['monthYear']->endOfMonth()->format('d.m.Y'),
                                        'transaction_details' => 'Receipts and Payments ' . $monthRange,
                                        'l_folio' => $amounts['monthYear']->format('F') . ' 01-' . $amounts['monthYear']->daysInMonth . '/' . $currentYear,
                                        'debit' => number_format($amounts['debit'], 2),
                                        'credit' => number_format($amounts['credit'], 2),
                                        'balance' => number_format($runningBalance, 2),
                                    ];
                                }

                                // Add the opening balance at the start of the year
                                array_unshift($formattedTransactions, [
                                    'date' => '01.01.' . $currentYear,
                                    'transaction_details' => 'Opening balance',
                                    'l_folio' => 'January 01-31/' . $currentYear,
                                    'debit' => number_format($openingBalance, 2),
                                    'credit' => '',
                                    'balance' => number_format($openingBalance, 2),
                                ]);

                                // Calculate closing balance
                                $closingBalance = $runningBalance;

                                // Include closing balance in the total row
                                $formattedTransactions[] = [
                                    'date' => 'Total',
                                    'transaction_details' => 'Total Transactions',
                                    'l_folio' => '',
                                    'debit' => number_format($totalDebit, 2),
                                    'credit' => number_format($totalCredit, 2),
                                    'balance' => number_format($closingBalance, 2),
                                ];
                            @endphp





                            <table class="w-full text-sm text-left text-blue-100 mb-4">
                                <thead class="text-xs text-black bg-blue-100 dark:text-white w-full">
                                <tr class="w-full">
                                    <th scope="col" class="border-r px-6 py-4  text-xs ">Date</th>
                                    <th scope="col" class="border-r px-6 py-4 text-left text-xs ">Transaction Details</th>
                                    <th scope="col" class="border-r px-6 py-4  text-left text-xs">L/Folio</th>
                                    <th scope="col" class="border-r px-6 py-4  text-right text-xs">Debit</th>
                                    <th scope="col" class="border-r px-6 py-4  text-right text-xs">Credit</th>
                                    <th scope="col" class="border-r px-6 py-4  text-right text-xs">Balance</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($formattedTransactions as $transaction)
                                    @if($transaction['date']=='Total')
                                        <tr class="bg-gray-200 text-black uppercase w-full">
                                            <td class="whitespace-nowrap px-6 py-2 font-medium text-xs">Total</td>
                                            <td class="whitespace-nowrap px-6 py-2 text-right text-xs"></td>
                                            <td class="whitespace-nowrap px-6 py-2 text-right  text-xs"></td>
                                            <td class="whitespace-nowrap px-6 py-2 text-right  text-xs">{{ number_format($totalDebit, 2) }}</td>
                                            <td class="whitespace-nowrap px-6 py-2 text-right text-xs">{{ number_format($totalCredit, 2) }}</td>
                                            <td class="whitespace-nowrap px-6 py-2 text-right text-xs">{{ number_format($closingBalance, 2) }}</td>
                                        </tr>
                                    @else
                                        <tr class="bg-blue-50 border-b border-blue-200 text-black w-full">
                                            <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-left">
                                                {{ $transaction['date'] }}
                                            </td>
                                            <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-left">
                                                {{ $transaction['transaction_details'] }}
                                            </td>
                                            <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-left">
                                                {{ $transaction['l_folio'] }}
                                            </td>
                                            <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-right">
                                                {{ $transaction['debit'] }}
                                            </td>
                                            <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-right">
                                                {{ $transaction['credit'] }}
                                            </td>
                                            <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-right">
                                                {{ $transaction['balance'] }}
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                                </tbody>
                                <tfoot>

                                </tfoot>
                            </table>






                        </div>
                    @endforeach

                @endforeach
            </div>




                <div class="relative overflow-x-auto rounded-lg bg-white p-4">

                <!-- Detailed Tables for Each Asset Account -->
                @foreach($capital_accounts as $income_account)
                    @php
                        $category_accounts = DB::table($income_account->category_name)->get();
                        $total_amount = 0;
                    @endphp

                    <hr id="{{ $income_account->category_name }}" class="boder-b-0 my-8"/>

                    <a name="{{ $income_account->category_name }}"></a>

                    @foreach($category_accounts as $category_account)
                        @php
                            $balance = DB::table('accounts')
                                ->where('sub_category_code', $category_account->sub_category_code)
                                ->value('balance');
                            $total_amount += $balance;
                        @endphp

                        <div class="bg-blue-50 border-b border-blue-400 text-black w-full">
                            <div class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-sm uppercase">
                                {{
                                    DB::table('accounts')
                                    ->where('sub_category_code', $category_account->sub_category_code)
                                    ->value('account_name')
                                }}
                            </div>

                            @php
                                $currentYear = Carbon\Carbon::now()->year;
                                $lastYear = $currentYear - 1;

                                // Query to get the last year's closing balance
                                $lastYearClosingBalance = DB::table('general_ledger')
                                    ->where('sub_category_code', $category_account->sub_category_code)
                                    ->whereYear('created_at', $lastYear)
                                    ->orderBy('created_at', 'desc')
                                    ->first(['record_on_account_number_balance']); // Assuming 'balance' is the field holding the balance

                                $openingBalance = $lastYearClosingBalance->balance ?? 0;

                                // Query the general ledger for the current year transactions
                                $transactions = DB::table('general_ledger')
                                    ->where('sub_category_code', $category_account->sub_category_code)
                                    ->whereYear('created_at', $currentYear)
                                    ->orderBy('created_at', 'asc')
                                    ->get();

                                // Prepare an array to hold the formatted transaction data
                                $formattedTransactions = [];
                                $runningBalance = $openingBalance;
                                $totalDebit = 0;
                                $totalCredit = 0;

                                // Initialize the monthly data structure
                                $monthlyData = [];

                                // Process transactions to aggregate debit and credit amounts by month
                                foreach ($transactions as $transaction) {
                                    // Get the month and year for the transaction
                                    $monthYear = Carbon\Carbon::parse($transaction->created_at);
                                    $monthRange = $monthYear->format('F Y');
                                    $startOfMonth = $monthYear->startOfMonth()->format('d.m.Y');
                                    $endOfMonth = $monthYear->endOfMonth()->format('d.m.Y');

                                    // Add transaction details to the appropriate month
                                    if (!isset($monthlyData[$monthRange])) {
                                        $monthlyData[$monthRange] = [
                                            'debit' => 0,
                                            'credit' => 0,
                                            'startDate' => $startOfMonth,
                                            'endDate' => $endOfMonth,
                                            'monthYear' => $monthYear, // Store the Carbon object for accurate year/month later
                                        ];
                                    }

                                    $monthlyData[$monthRange]['debit'] += $transaction->debit ?? 0;
                                    $monthlyData[$monthRange]['credit'] += $transaction->credit ?? 0;
                                }

                                // Generate formatted transactions from monthly data
                                $monthsInYear = [];
                                for ($i = 1; $i <= 12; $i++) {
                                    $monthName = Carbon\Carbon::createFromDate($currentYear, $i, 1)->format('F Y');
                                    $monthsInYear[$monthName] = [
                                        'debit' => 0,
                                        'credit' => 0,
                                        'monthYear' => Carbon\Carbon::createFromDate($currentYear, $i, 1),
                                    ];
                                }

                                // Merge monthlyData with monthsInYear, including zero months
                                foreach ($monthlyData as $monthRange => $amounts) {
                                    $monthsInYear[$monthRange]['debit'] = $amounts['debit'];
                                    $monthsInYear[$monthRange]['credit'] = $amounts['credit'];
                                }

                                // Create the final formatted transactions
                                foreach ($monthsInYear as $monthRange => $amounts) {
                                    // Calculate the running balance
                                    $runningBalance += $amounts['credit'] - $amounts['debit'];

                                    // Accumulate total debit and credit
                                    $totalDebit += $amounts['debit'];
                                    $totalCredit += $amounts['credit'];

                                    // Add entry for each month
                                    $formattedTransactions[] = [
                                        'date' => $amounts['monthYear']->endOfMonth()->format('d.m.Y'),
                                        'transaction_details' => 'Receipts and Payments ' . $monthRange,
                                        'l_folio' => $amounts['monthYear']->format('F') . ' 01-' . $amounts['monthYear']->daysInMonth . '/' . $currentYear,
                                        'debit' => number_format($amounts['debit'], 2),
                                        'credit' => number_format($amounts['credit'], 2),
                                        'balance' => number_format($runningBalance, 2),
                                    ];
                                }

                                // Add the opening balance at the start of the year
                                array_unshift($formattedTransactions, [
                                    'date' => '01.01.' . $currentYear,
                                    'transaction_details' => 'Opening balance',
                                    'l_folio' => 'January 01-31/' . $currentYear,
                                    'debit' => number_format($openingBalance, 2),
                                    'credit' => '',
                                    'balance' => number_format($openingBalance, 2),
                                ]);

                                // Calculate closing balance
                                $closingBalance = $runningBalance;

                                // Include closing balance in the total row
                                $formattedTransactions[] = [
                                    'date' => 'Total',
                                    'transaction_details' => 'Total Transactions',
                                    'l_folio' => '',
                                    'debit' => number_format($totalDebit, 2),
                                    'credit' => number_format($totalCredit, 2),
                                    'balance' => number_format($closingBalance, 2),
                                ];
                            @endphp





                            <table class="w-full text-sm text-left text-blue-100 mb-4">
                                <thead class="text-xs text-black bg-blue-100 dark:text-white w-full">
                                <tr class="w-full">
                                    <th scope="col" class="border-r px-6 py-4  text-xs ">Date</th>
                                    <th scope="col" class="border-r px-6 py-4 text-left text-xs ">Transaction Details</th>
                                    <th scope="col" class="border-r px-6 py-4  text-left text-xs">L/Folio</th>
                                    <th scope="col" class="border-r px-6 py-4  text-right text-xs">Debit</th>
                                    <th scope="col" class="border-r px-6 py-4  text-right text-xs">Credit</th>
                                    <th scope="col" class="border-r px-6 py-4  text-right text-xs">Balance</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($formattedTransactions as $transaction)
                                    @if($transaction['date']=='Total')
                                        <tr class="bg-gray-200 text-black uppercase w-full">
                                            <td class="whitespace-nowrap px-6 py-2 font-medium text-xs">Total</td>
                                            <td class="whitespace-nowrap px-6 py-2 text-right text-xs"></td>
                                            <td class="whitespace-nowrap px-6 py-2 text-right  text-xs"></td>
                                            <td class="whitespace-nowrap px-6 py-2 text-right  text-xs">{{ number_format($totalDebit, 2) }}</td>
                                            <td class="whitespace-nowrap px-6 py-2 text-right text-xs">{{ number_format($totalCredit, 2) }}</td>
                                            <td class="whitespace-nowrap px-6 py-2 text-right text-xs">{{ number_format($closingBalance, 2) }}</td>
                                        </tr>
                                    @else
                                        <tr class="bg-blue-50 border-b border-blue-200 text-black w-full">
                                            <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-left">
                                                {{ $transaction['date'] }}
                                            </td>
                                            <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-left">
                                                {{ $transaction['transaction_details'] }}
                                            </td>
                                            <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-left">
                                                {{ $transaction['l_folio'] }}
                                            </td>
                                            <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-right">
                                                {{ $transaction['debit'] }}
                                            </td>
                                            <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-right">
                                                {{ $transaction['credit'] }}
                                            </td>
                                            <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-right">
                                                {{ $transaction['balance'] }}
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                                </tbody>
                                <tfoot>

                                </tfoot>
                            </table>






                        </div>
                    @endforeach

                @endforeach
            </div>



                <div class="relative overflow-x-auto rounded-lg bg-white p-4">

                <!-- Detailed Tables for Each Asset Account -->
                @foreach($income_accounts as $income_account)
                    @php
                        $category_accounts = DB::table($income_account->category_name)->get();
                        $total_amount = 0;
                    @endphp

                    <hr id="{{ $income_account->category_name }}" class="boder-b-0 my-8"/>

                    <a name="{{ $income_account->category_name }}"></a>

                    @foreach($category_accounts as $category_account)
                        @php
                            $balance = DB::table('accounts')
                                ->where('sub_category_code', $category_account->sub_category_code)
                                ->value('balance');
                            $total_amount += $balance;
                        @endphp

                        <div class="bg-blue-50 border-b border-blue-400 text-black w-full">
                            <div class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-sm uppercase">
                                {{
                                    DB::table('accounts')
                                    ->where('sub_category_code', $category_account->sub_category_code)
                                    ->value('account_name')
                                }}
                            </div>

                            @php
                                $currentYear = Carbon\Carbon::now()->year;
                                $lastYear = $currentYear - 1;

                                // Query to get the last year's closing balance
                                $lastYearClosingBalance = DB::table('general_ledger')
                                    ->where('sub_category_code', $category_account->sub_category_code)
                                    ->whereYear('created_at', $lastYear)
                                    ->orderBy('created_at', 'desc')
                                    ->first(['record_on_account_number_balance']); // Assuming 'balance' is the field holding the balance

                                $openingBalance = $lastYearClosingBalance->balance ?? 0;

                                // Query the general ledger for the current year transactions
                                $transactions = DB::table('general_ledger')
                                    ->where('sub_category_code', $category_account->sub_category_code)
                                    ->whereYear('created_at', $currentYear)
                                    ->orderBy('created_at', 'asc')
                                    ->get();

                                // Prepare an array to hold the formatted transaction data
                                $formattedTransactions = [];
                                $runningBalance = $openingBalance;
                                $totalDebit = 0;
                                $totalCredit = 0;

                                // Initialize the monthly data structure
                                $monthlyData = [];

                                // Process transactions to aggregate debit and credit amounts by month
                                foreach ($transactions as $transaction) {
                                    // Get the month and year for the transaction
                                    $monthYear = Carbon\Carbon::parse($transaction->created_at);
                                    $monthRange = $monthYear->format('F Y');
                                    $startOfMonth = $monthYear->startOfMonth()->format('d.m.Y');
                                    $endOfMonth = $monthYear->endOfMonth()->format('d.m.Y');

                                    // Add transaction details to the appropriate month
                                    if (!isset($monthlyData[$monthRange])) {
                                        $monthlyData[$monthRange] = [
                                            'debit' => 0,
                                            'credit' => 0,
                                            'startDate' => $startOfMonth,
                                            'endDate' => $endOfMonth,
                                            'monthYear' => $monthYear, // Store the Carbon object for accurate year/month later
                                        ];
                                    }

                                    $monthlyData[$monthRange]['debit'] += $transaction->debit ?? 0;
                                    $monthlyData[$monthRange]['credit'] += $transaction->credit ?? 0;
                                }

                                // Generate formatted transactions from monthly data
                                $monthsInYear = [];
                                for ($i = 1; $i <= 12; $i++) {
                                    $monthName = Carbon\Carbon::createFromDate($currentYear, $i, 1)->format('F Y');
                                    $monthsInYear[$monthName] = [
                                        'debit' => 0,
                                        'credit' => 0,
                                        'monthYear' => Carbon\Carbon::createFromDate($currentYear, $i, 1),
                                    ];
                                }

                                // Merge monthlyData with monthsInYear, including zero months
                                foreach ($monthlyData as $monthRange => $amounts) {
                                    $monthsInYear[$monthRange]['debit'] = $amounts['debit'];
                                    $monthsInYear[$monthRange]['credit'] = $amounts['credit'];
                                }

                                // Create the final formatted transactions
                                foreach ($monthsInYear as $monthRange => $amounts) {
                                    // Calculate the running balance
                                    $runningBalance += $amounts['credit'] - $amounts['debit'];

                                    // Accumulate total debit and credit
                                    $totalDebit += $amounts['debit'];
                                    $totalCredit += $amounts['credit'];

                                    // Add entry for each month
                                    $formattedTransactions[] = [
                                        'date' => $amounts['monthYear']->endOfMonth()->format('d.m.Y'),
                                        'transaction_details' => 'Receipts and Payments ' . $monthRange,
                                        'l_folio' => $amounts['monthYear']->format('F') . ' 01-' . $amounts['monthYear']->daysInMonth . '/' . $currentYear,
                                        'debit' => number_format($amounts['debit'], 2),
                                        'credit' => number_format($amounts['credit'], 2),
                                        'balance' => number_format($runningBalance, 2),
                                    ];
                                }

                                // Add the opening balance at the start of the year
                                array_unshift($formattedTransactions, [
                                    'date' => '01.01.' . $currentYear,
                                    'transaction_details' => 'Opening balance',
                                    'l_folio' => 'January 01-31/' . $currentYear,
                                    'debit' => number_format($openingBalance, 2),
                                    'credit' => '',
                                    'balance' => number_format($openingBalance, 2),
                                ]);

                                // Calculate closing balance
                                $closingBalance = $runningBalance;

                                // Include closing balance in the total row
                                $formattedTransactions[] = [
                                    'date' => 'Total',
                                    'transaction_details' => 'Total Transactions',
                                    'l_folio' => '',
                                    'debit' => number_format($totalDebit, 2),
                                    'credit' => number_format($totalCredit, 2),
                                    'balance' => number_format($closingBalance, 2),
                                ];
                            @endphp





                            <table class="w-full text-sm text-left text-blue-100 mb-4">
                                <thead class="text-xs text-black bg-blue-100 dark:text-white w-full">
                                <tr class="w-full">
                                    <th scope="col" class="border-r px-6 py-4  text-xs ">Date</th>
                                    <th scope="col" class="border-r px-6 py-4 text-left text-xs ">Transaction Details</th>
                                    <th scope="col" class="border-r px-6 py-4  text-left text-xs">L/Folio</th>
                                    <th scope="col" class="border-r px-6 py-4  text-right text-xs">Debit</th>
                                    <th scope="col" class="border-r px-6 py-4  text-right text-xs">Credit</th>
                                    <th scope="col" class="border-r px-6 py-4  text-right text-xs">Balance</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($formattedTransactions as $transaction)
                                    @if($transaction['date']=='Total')
                                        <tr class="bg-gray-200 text-black uppercase w-full">
                                            <td class="whitespace-nowrap px-6 py-2 font-medium text-xs">Total</td>
                                            <td class="whitespace-nowrap px-6 py-2 text-right text-xs"></td>
                                            <td class="whitespace-nowrap px-6 py-2 text-right  text-xs"></td>
                                            <td class="whitespace-nowrap px-6 py-2 text-right  text-xs">{{ number_format($totalDebit, 2) }}</td>
                                            <td class="whitespace-nowrap px-6 py-2 text-right text-xs">{{ number_format($totalCredit, 2) }}</td>
                                            <td class="whitespace-nowrap px-6 py-2 text-right text-xs">{{ number_format($closingBalance, 2) }}</td>
                                        </tr>
                                    @else
                                        <tr class="bg-blue-50 border-b border-blue-200 text-black w-full">
                                            <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-left">
                                                {{ $transaction['date'] }}
                                            </td>
                                            <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-left">
                                                {{ $transaction['transaction_details'] }}
                                            </td>
                                            <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-left">
                                                {{ $transaction['l_folio'] }}
                                            </td>
                                            <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-right">
                                                {{ $transaction['debit'] }}
                                            </td>
                                            <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-right">
                                                {{ $transaction['credit'] }}
                                            </td>
                                            <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-right">
                                                {{ $transaction['balance'] }}
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                                </tbody>
                                <tfoot>

                                </tfoot>
                            </table>






                        </div>
                    @endforeach

                @endforeach
            </div>



            <div class="relative overflow-x-auto rounded-lg bg-white p-4">

                <!-- Detailed Tables for Each Asset Account -->
                @foreach($expense_accounts as $income_account)
                    @php
                        $category_accounts = DB::table($income_account->category_name)->get();
                        $total_amount = 0;
                    @endphp

                    <hr id="{{ $income_account->category_name }}" class="boder-b-0 my-8"/>

                    <a name="{{ $income_account->category_name }}"></a>

                    @foreach($category_accounts as $category_account)
                        @php
                            $balance = DB::table('accounts')
                                ->where('sub_category_code', $category_account->sub_category_code)
                                ->value('balance');
                            $total_amount += $balance;
                        @endphp

                        <div class="bg-blue-50 border-b border-blue-400 text-black w-full">
                            <div class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-sm uppercase">
                                {{
                                    DB::table('accounts')
                                    ->where('sub_category_code', $category_account->sub_category_code)
                                    ->value('account_name')
                                }}
                            </div>

                            @php
                                $currentYear = Carbon\Carbon::now()->year;
                                $lastYear = $currentYear - 1;

                                // Query to get the last year's closing balance
                                $lastYearClosingBalance = DB::table('general_ledger')
                                    ->where('sub_category_code', $category_account->sub_category_code)
                                    ->whereYear('created_at', $lastYear)
                                    ->orderBy('created_at', 'desc')
                                    ->first(['record_on_account_number_balance']); // Assuming 'balance' is the field holding the balance

                                $openingBalance = $lastYearClosingBalance->balance ?? 0;

                                // Query the general ledger for the current year transactions
                                $transactions = DB::table('general_ledger')
                                    ->where('sub_category_code', $category_account->sub_category_code)
                                    ->whereYear('created_at', $currentYear)
                                    ->orderBy('created_at', 'asc')
                                    ->get();

                                // Prepare an array to hold the formatted transaction data
                                $formattedTransactions = [];
                                $runningBalance = $openingBalance;
                                $totalDebit = 0;
                                $totalCredit = 0;

                                // Initialize the monthly data structure
                                $monthlyData = [];

                                // Process transactions to aggregate debit and credit amounts by month
                                foreach ($transactions as $transaction) {
                                    // Get the month and year for the transaction
                                    $monthYear = Carbon\Carbon::parse($transaction->created_at);
                                    $monthRange = $monthYear->format('F Y');
                                    $startOfMonth = $monthYear->startOfMonth()->format('d.m.Y');
                                    $endOfMonth = $monthYear->endOfMonth()->format('d.m.Y');

                                    // Add transaction details to the appropriate month
                                    if (!isset($monthlyData[$monthRange])) {
                                        $monthlyData[$monthRange] = [
                                            'debit' => 0,
                                            'credit' => 0,
                                            'startDate' => $startOfMonth,
                                            'endDate' => $endOfMonth,
                                            'monthYear' => $monthYear, // Store the Carbon object for accurate year/month later
                                        ];
                                    }

                                    $monthlyData[$monthRange]['debit'] += $transaction->debit ?? 0;
                                    $monthlyData[$monthRange]['credit'] += $transaction->credit ?? 0;
                                }

                                // Generate formatted transactions from monthly data
                                $monthsInYear = [];
                                for ($i = 1; $i <= 12; $i++) {
                                    $monthName = Carbon\Carbon::createFromDate($currentYear, $i, 1)->format('F Y');
                                    $monthsInYear[$monthName] = [
                                        'debit' => 0,
                                        'credit' => 0,
                                        'monthYear' => Carbon\Carbon::createFromDate($currentYear, $i, 1),
                                    ];
                                }

                                // Merge monthlyData with monthsInYear, including zero months
                                foreach ($monthlyData as $monthRange => $amounts) {
                                    $monthsInYear[$monthRange]['debit'] = $amounts['debit'];
                                    $monthsInYear[$monthRange]['credit'] = $amounts['credit'];
                                }

                                // Create the final formatted transactions
                                foreach ($monthsInYear as $monthRange => $amounts) {
                                    // Calculate the running balance
                                    $runningBalance += $amounts['credit'] - $amounts['debit'];

                                    // Accumulate total debit and credit
                                    $totalDebit += $amounts['debit'];
                                    $totalCredit += $amounts['credit'];

                                    // Add entry for each month
                                    $formattedTransactions[] = [
                                        'date' => $amounts['monthYear']->endOfMonth()->format('d.m.Y'),
                                        'transaction_details' => 'Receipts and Payments ' . $monthRange,
                                        'l_folio' => $amounts['monthYear']->format('F') . ' 01-' . $amounts['monthYear']->daysInMonth . '/' . $currentYear,
                                        'debit' => number_format($amounts['debit'], 2),
                                        'credit' => number_format($amounts['credit'], 2),
                                        'balance' => number_format($runningBalance, 2),
                                    ];
                                }

                                // Add the opening balance at the start of the year
                                array_unshift($formattedTransactions, [
                                    'date' => '01.01.' . $currentYear,
                                    'transaction_details' => 'Opening balance',
                                    'l_folio' => 'January 01-31/' . $currentYear,
                                    'debit' => number_format($openingBalance, 2),
                                    'credit' => '',
                                    'balance' => number_format($openingBalance, 2),
                                ]);

                                // Calculate closing balance
                                $closingBalance = $runningBalance;

                                // Include closing balance in the total row
                                $formattedTransactions[] = [
                                    'date' => 'Total',
                                    'transaction_details' => 'Total Transactions',
                                    'l_folio' => '',
                                    'debit' => number_format($totalDebit, 2),
                                    'credit' => number_format($totalCredit, 2),
                                    'balance' => number_format($closingBalance, 2),
                                ];
                            @endphp





                            <table class="w-full text-sm text-left text-blue-100 mb-4">
                                <thead class="text-xs text-black bg-blue-100 dark:text-white w-full">
                                <tr class="w-full">
                                    <th scope="col" class="border-r px-6 py-4  text-xs ">Date</th>
                                    <th scope="col" class="border-r px-6 py-4 text-left text-xs ">Transaction Details</th>
                                    <th scope="col" class="border-r px-6 py-4  text-left text-xs">L/Folio</th>
                                    <th scope="col" class="border-r px-6 py-4  text-right text-xs">Debit</th>
                                    <th scope="col" class="border-r px-6 py-4  text-right text-xs">Credit</th>
                                    <th scope="col" class="border-r px-6 py-4  text-right text-xs">Balance</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($formattedTransactions as $transaction)
                                    @if($transaction['date']=='Total')
                                        <tr class="bg-gray-200 text-black uppercase w-full">
                                            <td class="whitespace-nowrap px-6 py-2 font-medium text-xs">Total</td>
                                            <td class="whitespace-nowrap px-6 py-2 text-right text-xs"></td>
                                            <td class="whitespace-nowrap px-6 py-2 text-right  text-xs"></td>
                                            <td class="whitespace-nowrap px-6 py-2 text-right  text-xs">{{ number_format($totalDebit, 2) }}</td>
                                            <td class="whitespace-nowrap px-6 py-2 text-right text-xs">{{ number_format($totalCredit, 2) }}</td>
                                            <td class="whitespace-nowrap px-6 py-2 text-right text-xs">{{ number_format($closingBalance, 2) }}</td>
                                        </tr>
                                    @else
                                        <tr class="bg-blue-50 border-b border-blue-200 text-black w-full">
                                            <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-left">
                                                {{ $transaction['date'] }}
                                            </td>
                                            <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-left">
                                                {{ $transaction['transaction_details'] }}
                                            </td>
                                            <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-left">
                                                {{ $transaction['l_folio'] }}
                                            </td>
                                            <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-right">
                                                {{ $transaction['debit'] }}
                                            </td>
                                            <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-right">
                                                {{ $transaction['credit'] }}
                                            </td>
                                            <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-right">
                                                {{ $transaction['balance'] }}
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                                </tbody>
                                <tfoot>

                                </tfoot>
                            </table>






                        </div>
                    @endforeach

                @endforeach
            </div>






















        </div>


    </div>



</div>
