<x-filament::page>
    <div class="space-y-6">
        {{-- Date Filters --}}
        <div class="bg-white rounded-lg shadow p-6">
            {{ $this->form }}
        </div>

        {{-- Assets Table --}}
        <x-filament::card>
            <h2 class="text-lg font-medium mb-4">Assets</h2>
            <table class="w-full table-auto border-collapse border border-gray-300">
                <thead>
                <tr class="bg-gray-100">
                    <th class="border px-3 py-2 text-left">Account</th>
                    <th class="border px-3 py-2 text-right">Opening Balance</th>
                    <th class="border px-3 py-2 text-right">Period Change</th>
                    <th class="border px-3 py-2 text-right">Ending Balance</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($assets as $account)
                    <tr>
                        <td class="border px-3 py-2">{{ $account['name'] }}</td>
                        <td class="border px-3 py-2 text-right">{{ number_format($account['opening_balance'], 2) }}</td>
                        <td class="border px-3 py-2 text-right">{{ number_format($account['period_change'], 2) }}</td>
                        <td class="border px-3 py-2 text-right">{{ number_format($account['ending_balance'], 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </x-filament::card>

        {{-- Liabilities Table --}}
        <x-filament::card>
            <h2 class="text-lg font-medium mb-4">Liabilities</h2>
            <table class="w-full table-auto border-collapse border border-gray-300">
                <thead>
                <tr class="bg-gray-100">
                    <th class="border px-3 py-2 text-left">Account</th>
                    <th class="border px-3 py-2 text-right">Opening Balance</th>
                    <th class="border px-3 py-2 text-right">Period Change</th>
                    <th class="border px-3 py-2 text-right">Ending Balance</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($liabilities as $account)
                    <tr>
                        <td class="border px-3 py-2">{{ $account['name'] }}</td>
                        <td class="border px-3 py-2 text-right">{{ number_format($account['opening_balance'], 2) }}</td>
                        <td class="border px-3 py-2 text-right">{{ number_format($account['period_change'], 2) }}</td>
                        <td class="border px-3 py-2 text-right">{{ number_format($account['ending_balance'], 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </x-filament::card>

        {{-- Equity Table --}}
        <x-filament::card>
            <h2 class="text-lg font-medium mb-4">Equity</h2>
            <table class="w-full table-auto border-collapse border border-gray-300">
                <thead>
                <tr class="bg-gray-100">
                    <th class="border px-3 py-2 text-left">Account</th>
                    <th class="border px-3 py-2 text-right">Opening Balance</th>
                    <th class="border px-3 py-2 text-right">Period Change</th>
                    <th class="border px-3 py-2 text-right">Ending Balance</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($equity as $account)
                    <tr>
                        <td class="border px-3 py-2">{{ $account['name'] }}</td>
                        <td class="border px-3 py-2 text-right">{{ number_format($account['opening_balance'], 2) }}</td>
                        <td class="border px-3 py-2 text-right">{{ number_format($account['period_change'], 2) }}</td>
                        <td class="border px-3 py-2 text-right">{{ number_format($account['ending_balance'], 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </x-filament::card>

        <x-filament::card>
            <h2 class="text-lg font-medium mb-4">Net Profit & Loss</h2>

            <table class="w-full text-left table-auto">
                <thead>
                <tr>
                    <th class="border px-4 py-2">Type</th>
                    <th class="border px-4 py-2">Amount (AED)</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td class="border px-4 py-2">Total Revenue</td>
                    <td class="border px-4 py-2">{{ $totalRevenue }}</td>
                </tr>
                <tr>
                    <td class="border px-4 py-2">Total Expense</td>
                    <td class="border px-4 py-2">{{ $totalExpense }}</td>
                </tr>
                <tr class="{{ $netProfitLoss >= 0 ? 'text-green-600' : 'text-red-600' }} font-bold">
                    <td class="border px-4 py-2">Net Profit / (Loss)</td>
                    <td class="border px-4 py-2">{{ $netProfitLoss }}</td>
                </tr>
                </tbody>
            </table>
        </x-filament::card>

        {{-- Balance Check --}}
        <x-filament::card>
            <div class="flex justify-between items-center p-4">
                <span class="text-lg font-bold">Assets = Liabilities + Equity - Net Profit</span>
                <span
                    class="text-lg font-bold {{ $isBalanced ? 'text-success-600' : 'text-danger-600' }}">
                    AED {{ $totalAssets }} = AED {{ $totalLiabilities }} + AED {{ \Illuminate\Support\Str::remove('-',$totalEquity) }} - AED {{\Illuminate\Support\Str::remove('-',$netProfitLoss)}}
                </span>
            </div>
        </x-filament::card>
    </div>
</x-filament::page>
