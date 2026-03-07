<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between gap-x-3">
            <div class="flex-1">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ $isIndividual ? __('My Fines This Month') : __('Company Fines This Month') }}
                </h3>

                <p class="mt-1 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                    Rp {{ number_format($totalFines, 0, ',', '.') }}
                </p>

                <div class="mt-1 flex items-center gap-x-1 text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ $countFines }} {{ str('record')->plural($countFines) }}</span>
                </div>
            </div>

            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-danger-50 text-danger-600 dark:bg-danger-500/10 dark:text-danger-400">
                <x-filament::icon
                    icon="heroicon-o-banknotes"
                    class="h-6 w-6"
                />
            </div>
        </div>

        <div class="mt-4">
            <div class="h-1.5 w-full rounded-full bg-gray-100 dark:bg-gray-800">
                <div 
                    class="h-full rounded-full bg-danger-500" 
                    style="width: {{ min(100, ($totalFines > 0 ? 100 : 0)) }}%"
                ></div>
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 italic">
                {{ $isIndividual ? __('Fines are automatically calculated based on attendance rules.') : __('Overview of all employee fines for the current period.') }}
            </p>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
