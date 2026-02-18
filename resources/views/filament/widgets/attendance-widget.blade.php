<x-filament-widgets::widget>
    <x-filament::section>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <div class="flex items-center">
                @if (!$isEmployee)
                    <div class="flex w-full items-center justify-center p-3 rounded-lg bg-danger-50 text-danger-600 dark:bg-danger-950/20">
                        <span class="text-sm font-medium">No Employee Record</span>
                    </div>
                @else
                    @if (!$activeAttendance)
                        <x-filament::button 
                            wire:click="clockIn" 
                            color="success"
                            icon="heroicon-m-play"
                            class="w-full h-12 text-lg"
                        >
                            Clock In
                        </x-filament::button>

                    @elseif (!$activeAttendance->clock_out)
                        <x-filament::button 
                            wire:click="clockOut" 
                            color="danger"
                            icon="heroicon-m-stop"
                            class="w-full h-12"
                        >
                            Clock Out
                        </x-filament::button>

                    @else
                        <div class="flex w-full h-12 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                            <x-filament::icon
                                icon="heroicon-m-check-circle"
                                class="h-5 w-5 text-success-500"
                            />
                            <span class="font-medium text-gray-700 dark:text-gray-200">
                                Shift Completed
                            </span>
                        </div>
                    @endif
                @endif
            </div>

            <div class="flex flex-col items-center justify-center rounded-lg border border-gray-100 bg-white py-2 dark:border-gray-700 dark:bg-gray-900">
                <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">
                    Today's Status
                </span>

                @if ($activeAttendance)
                    <div class="flex items-center gap-3">
                        <x-filament::badge 
                            :color="$activeAttendance->status->getColor()"
                            size="lg"
                        >
                            {{ $activeAttendance->status->getLabel() }}
                        </x-filament::badge>

                        @if($activeAttendance->clock_in)
                            <span class="font-mono text-sm font-semibold text-gray-600 dark:text-gray-300">
                                {{ \Carbon\Carbon::parse($activeAttendance->clock_in)->format('H:i') }}
                            </span>
                        @endif
                    </div>
                @else
                    <span class="text-sm text-gray-400 italic">Not clocked in yet</span>
                @endif
            </div>

        </div>
    </x-filament::section>
</x-filament-widgets::widget>