<div class=" bg-white p-4 mb-4 ">

    <div class="container mx-auto mt-8   ">
        <h2 class="text-2xl font-bold mb-4  flex justify-center item-center  p-4 ">Individual Loans Outstanding As of {{ now()->format("Y-M-d") }}, NBC SACCOS LTD</h2>
        <div class="overflow-x-auto mt-4 ">

            <table class="min-w-full bg-white border border-gray-200 text-sm ">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left bg-blue-100 border border-gray-200">#</th>
                        <th class="px-4 py-2 text-left bg-blue-100 border border-gray-200">Member No </th>
                        <th class="px-4 py-2 text-left bg-blue-100 border border-gray-200">Names</th>

                        <th class="px-4 py-2 text-right bg-blue-100 border border-gray-200">Balance</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($loans as $loan)
                    <tr>
                        <td class="px-4 py-2 border-blue-200 border-gray-200">{{ $loop->iteration }}</td>
                        <td class="px-4 py-2 border-blue-200 border-gray-200">{{ $loan->client_number }}</td>
                        <td class="px-4 py-2 border-blue-200 border-gray-200">{{ $loan->name }}</td>

                        <td class="px-4 py-2 text-right border-blue-200 border-gray-200">{{ number_format($loan->balance, 2) }} TZS</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-blue-100">
                        <td colspan="3" class="px-4 py-2 text-center font-bold border border-gray-200">Total</td>
                        <td class="px-4 py-2 text-right font-bold border border-gray-200">{{ number_format($loan_summary, 2) }} TZS</td>
                    </tr>
                </tfoot>
            </table>


        </div>
    </div>





</div>
