<div>


    <div class="w-2/3 mx-auto bg-white p-6 shadow-md">
        <!-- Title Section -->
        <div class="text-center">
            <h2 class="text-lg font-bold uppercase">NBC SACCOS LTD</h2>
            <h3 class="text-base font-semibold uppercase">TRIAL BALANCE AS AT {{date("Y-m-d")}}</h3>
        </div>

        <!-- Date and Currency Section -->
        <div class="flex justify-end mt-4">
            <table class="border border-black text-xs text-right">
                <thead>
                <tr class="border border-black">
                    <th class="px-4 py-2 border">31.12.2023</th>
                    <th class="px-4 py-2 border">31.12.2022</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td class="px-4 py-2 border">TSHS</td>
                    <td class="px-4 py-2 border">TSHS</td>
                </tr>
                </tbody>
            </table>
        </div>
    <div class="p-2 shadow-md sm:rounded-lg bg-gray-100">

        @php
            $currentYear = Carbon\Carbon::now()->year;

            $accounts = DB::table('accounts')
                ->where('account_level', 2)
                ->whereYear('created_at', $currentYear)
                ->orderBy('created_at', 'asc')
                ->get();

            // Initialize total variables
            $total_debit = 0;
            $total_credit = 0;
            $total_balance = 0;

            // Calculate totals
            foreach ($accounts as $account) {
                $total_debit += $account->debit ?? 0;   // Use null coalescing to avoid null values
                $total_credit += $account->credit ?? 0; // Use null coalescing to avoid null values
                $total_balance += $account->balance ?? 0; // Use null coalescing to avoid null values
            }
        @endphp

        <table class="w-full text-sm text-left text-blue-100 mb-4">
            <thead class="text-xs text-black bg-blue-100 dark:text-white w-full">
            <tr class="w-full">
                <th scope="col" class="border-r px-6 py-4 text-xs text-black">Account Name</th>
                <th scope="col" class="border-r px-6 py-4 text-left text-xs">Account Number</th>
                <th scope="col" class="border-r px-6 py-4 text-left text-xs text-right">Debit</th>
                <th scope="col" class="border-r px-6 py-4 text-right text-xs text-right">Credit</th>
                <th scope="col" class="border-r px-6 py-4 text-right text-xs text-right">Balance</th>
            </tr>
            </thead>

            <tbody>
            @foreach ($accounts as $account)
                <tr class="bg-blue-50 border-b border-blue-200 text-black w-full">
                    <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-left">{{ $account->account_name }}</td>
                    <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-left">{{ $account->account_number }}</td>
                    <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-right">{{ number_format($account->debit ?? 0, 2) }}</td>
                    <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-right">{{ number_format($account->credit ?? 0, 2) }}</td>
                    <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-right">{{ number_format($account->balance ?? 0, 2) }}</td>
                </tr>
            @endforeach
            <tr class="bg-blue-50 border-b border-blue-200 text-black w-full">
                <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-left font-bold">TOTAL</td>
                <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-left"></td>
                <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-right font-bold">{{ number_format($total_debit, 2) }}</td>
                <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-right font-bold">{{ number_format($total_credit, 2) }}</td>
                <td class="whitespace-nowrap border-r px-4 py-2 dark:border-neutral-500 text-xs text-right font-bold">{{ number_format($total_balance, 2) }}</td>
            </tr>
            </tbody>

        </table>

    </div>
    </div>

</div>
