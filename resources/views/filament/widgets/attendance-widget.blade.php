<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center" x-data="{
            isLoading: false,
            async handleAction(action) {
                this.isLoading = true;
                if (!navigator.geolocation) {
                    $wire.notifyError('Geolocation is not supported by your browser.');
                    this.isLoading = false;
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const { latitude, longitude } = position.coords;
                        $wire[action](latitude, longitude).finally(() => {
                            this.isLoading = false;
                        });
                    },
                    (error) => {
                        let message = 'An unknown error occurred while retrieving your location.';
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                message = 'Location access was denied. Please enable it to continue.';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                message = 'Location information is unavailable.';
                                break;
                            case error.TIMEOUT:
                                message = 'The request to get your location timed out.';
                                break;
                        }
                        $wire.notifyError(message);
                        this.isLoading = false;
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 5000,
                        maximumAge: 0
                    }
                );
            }
        }">

            <div class="flex items-center">
                @if (!$isEmployee)
                    <div class="flex items-center justify-center p-3 rounded-lg bg-danger-50 text-danger-600 dark:bg-danger-950/20">
                        <span class="text-sm font-medium">No Employee Record</span>
                    </div>
                @else
                    @if (!$activeAttendance)
                        <x-filament::button 
                            x-on:click="handleAction('clockIn')" 
                            color="success"
                            icon="heroicon-m-play"
                            class="h-12 text-lg px-6" 
                            x-bind:disabled="isLoading"
                        >
                            <span x-show="!isLoading">Clock In</span>
                            <span x-show="isLoading" class="flex items-center">
                                <x-filament::loading-indicator class="h-5 w-5 mr-2" />
                                Processing...
                            </span>
                        </x-filament::button>

                    @elseif (!$activeAttendance->clock_out)
                        <x-filament::button 
                            x-on:click="$dispatch('open-modal', { id: 'clock-out-confirmation' })" 
                            color="danger"
                            icon="heroicon-m-stop"
                            class="h-12 px-6"
                            x-bind:disabled="isLoading"
                        >
                            <span x-show="!isLoading">Clock Out</span>
                            <span x-show="isLoading" class="flex items-center">
                                <x-filament::loading-indicator class="h-5 w-5 mr-2" />
                                Processing...
                            </span>
                        </x-filament::button>

                        <x-filament::modal id="clock-out-confirmation">
                            <x-slot name="heading">
                                {{ $isEarly ? 'Early Clock Out' : 'Clock Out Confirmation' }}
                            </x-slot>

                            <x-slot name="description">
                                @if($isEarly)
                                    You are clocking out before your shift ends. This will be marked as <strong>Early Leave</strong>. Are you sure you want to proceed?
                                @else
                                    Are you sure you want to clock out for the day?
                                @endif
                            </x-slot>

                            <div class="flex justify-end gap-3">
                                <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'clock-out-confirmation' })">
                                    Cancel
                                </x-filament::button>
                                <x-filament::button color="danger" x-on:click="handleAction('clockOut'); $dispatch('close-modal', { id: 'clock-out-confirmation' })">
                                    Confirm Clock Out
                                </x-filament::button>
                            </div>
                        </x-filament::modal>

                    @else
                        <div class="flex h-12 items-center justify-center px-6 gap-2 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
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
        </div>
    </x-filament::section>
</x-filament-widgets::widget>