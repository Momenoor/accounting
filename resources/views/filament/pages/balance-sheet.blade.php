<x-filament::page>
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            {{ $this->form }}
        </div>

        <div class="space-y-6">
            <x-filament::card>
                <h2 class="text-lg font-medium mb-4">Assets</h2>
                {{ $this->table->group('type', 'asset') }}
                <div class="border-t pt-2 mt-2 font-bold">
                    <div class="flex justify-between">
                        <span>Total Assets</span>
                        <span>AED {{ $this->getTotal('asset') }}</span>
                    </div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <h2 class="text-lg font-medium mb-4">Liabilities</h2>
                {{ $this->table->group('type', 'liability') }}
                <div class="border-t pt-2 mt-2 font-bold">
                    <div class="flex justify-between">
                        <span>Total Liabilities</span>
                        <span>AED {{ $this->getTotal('liability') }}</span>
                    </div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <h2 class="text-lg font-medium mb-4">Equity</h2>
                {{ $this->table->group('type', 'equity') }}
                <div class="border-t pt-2 mt-2 font-bold">
                    <div class="flex justify-between">
                        <span>Total Equity</span>
                        <span>AED {{ $this->getTotal('equity') }}</span>
                    </div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="flex justify-between items-center p-4">
                    <span class="text-lg font-bold">Assets = Liabilities + Equity</span>
                    <span class="text-lg font-bold {{ $this->isBalanced() ? 'text-success-600' : 'text-danger-600' }}">
                        AED {{ $this->getTotal('asset') }} = AED {{ $this->getTotal('liability') }} + AED {{ $this->getTotal('equity') }}
                    </span>
                </div>
            </x-filament::card>
        </div>
    </div>
</x-filament::page>
