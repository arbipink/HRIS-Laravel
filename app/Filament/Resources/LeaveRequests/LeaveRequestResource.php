<?php

namespace App\Filament\Resources\LeaveRequests;

use App\Enums\EmployeeRole;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Filament\Resources\LeaveRequests\Pages\ManageLeaveRequests;
use App\Models\Employee;
use App\Models\LeaveRequest;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use UnitEnum;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowRightOnRectangle;

    protected static string|UnitEnum|null $navigationGroup = 'Management';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->label('Employee')
                    ->options(Employee::with('user')->get()->pluck('user.name', 'id'))
                    ->default(Auth::user()->employee?->id)
                    ->disabled()
                    ->dehydrated()
                    ->required(),
                Select::make('manager_id')
                    ->label('Manager')
                    ->options(Employee::with('user')->get()->pluck('user.name', 'id'))
                    ->default(function () {
                        $employee = Auth::user()->employee;

                        if (! $employee || ! $employee->department_id) {
                            return null;
                        }

                        return Employee::where('department_id', $employee->department_id)
                            ->where('role', EmployeeRole::MANAGER)
                            ->first()?->id;
                    })
                    ->disabled()
                    ->dehydrated()
                    ->required(),
                Select::make('hrd_id')
                    ->label('HRD')
                    ->options(
                        Employee::where('role', EmployeeRole::HRD)
                            ->with('user')
                            ->get()
                            ->pluck('user.name', 'id')
                    )
                    ->default(fn () => Employee::where('role', EmployeeRole::HRD)->first()?->id)
                    ->disabled()
                    ->dehydrated()
                    ->required(),
                Select::make('type')
                    ->options(LeaveType::class)
                    ->default('ANNUAL')
                    ->live()
                    ->required(),

                TextInput::make('reason')
                    ->required()
                    ->helperText(fn ($get) => match ($get('type') instanceof LeaveType ? $get('type')->value : $get('type')) {
                        'ANNUAL' => 'Syarat: Setelah masa kerja 12 bulan.',
                        'MATERNITY' => 'Syarat: 1.5 bulan pra-lahir + 1.5 bulan pasca-lahir.',
                        'MISCARRIAGE' => 'Syarat: Wajib istirahat (Gugur Kandungan).',
                        'MENSTRUAL' => 'Syarat: Hari pertama & kedua haid jika sakit.',
                        'SICK' => 'Syarat: Sedang sakit dan tidak bisa bekerja.',
                        'MARRIAGE' => 'Syarat: Pernikahan karyawan sendiri.',
                        'PATERNITY' => 'Syarat: Istri melahirkan atau keguguran.',
                        'BEREAVEMENT' => 'Syarat: Meninggalnya suami/istri, orang tua/mertua, atau anak/menantu.',
                        default => null,
                    }),

                DatePicker::make('start_date')
                    ->required()
                    ->helperText(fn ($get) => match ($get('type') instanceof LeaveType ? $get('type')->value : $get('type')) {
                        'ANNUAL' => 'Jatah: 12 Hari per tahun.',
                        'MATERNITY' => 'Jatah: Maksimal 3 Bulan.',
                        'MISCARRIAGE' => 'Jatah: Maksimal 1.5 Bulan.',
                        'MENSTRUAL' => 'Jatah: Maksimal 2 Hari.',
                        'SICK' => 'Jatah: Sesuai keterangan dokter.',
                        'MARRIAGE' => 'Jatah: 3 Hari.',
                        'PATERNITY' => 'Jatah: 2 Hari.',
                        'BEREAVEMENT' => 'Jatah: 2 Hari.',
                        default => null,
                    }),

                DatePicker::make('end_date')
                    ->required()
                    ->afterOrEqual('start_date')
                    ->helperText(function ($get) {
                        $type = $get('type');
                        $typeValue = $type instanceof LeaveType ? $type->value : $type;

                        if ($typeValue === 'ANNUAL') {
                            $employee = Auth::user()->employee;

                            if (! $employee) {
                                return null;
                            }

                            return "Jatah anda tersisa {$employee->remaining_leave_days} hari";
                        }

                        return null;
                    })
                    ->rules([
                        fn ($get) => function (string $attribute, $value, Closure $fail) use ($get) {
                            $type = $get('type');
                            $typeValue = $type instanceof LeaveType ? $type->value : $type;

                            if ($typeValue === 'ANNUAL') {
                                $employee = Auth::user()->employee;

                                if (! $employee || $employee->remaining_leave_days <= 0) {
                                    $fail('Anda tidak memiliki sisa cuti tahunan.');

                                    return;
                                }

                                $startDate = Carbon::parse($get('start_date'));
                                $endDate = Carbon::parse($value);
                                $daysRequested = $startDate->diffInDays($endDate) + 1;

                                if ($daysRequested > $employee->remaining_leave_days) {
                                    $fail("Pengajuan {$daysRequested} hari melebihi sisa cuti anda ({$employee->remaining_leave_days} hari).");
                                }
                            }
                        },
                    ]),

                FileUpload::make('attachment_path')
                    ->directory('leave-requests')
                    ->visibility('private')
                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                    ->maxSize(15000)
                    ->columnSpanFull()
                    ->openable()
                    ->downloadable()
                    ->previewable()
                    ->helperText(fn ($get) => match ($get('type') instanceof LeaveType ? $get('type')->value : $get('type')) {
                        'SICK' => 'Wajib: Unggah Surat Dokter.',
                        'MARRIAGE' => 'Wajib: Unggah Undangan atau Sertifikat Pernikahan.',
                        'MATERNITY', 'MISCARRIAGE', 'PATERNITY' => 'Wajib: Unggah Surat/Konfirmasi Medis.',
                        'BEREAVEMENT' => 'Wajib: Unggah Surat Kematian.',
                        default => 'Unggah dokumen pendukung jika tersedia.',
                    }),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('employee.user.name')
                    ->label('Employee'),
                TextEntry::make('type')
                    ->badge(),
                TextEntry::make('reason'),
                TextEntry::make('start_date')
                    ->date(),
                TextEntry::make('end_date')
                    ->date(),
                TextEntry::make('status')
                    ->badge()
                    ->visible(fn (LeaveRequest $record) => $record->status !== LeaveStatus::PENDING),
                TextEntry::make('manager_status')
                    ->badge()
                    ->visible(fn (LeaveRequest $record) => $record->manager_status !== LeaveStatus::PENDING),
                TextEntry::make('manager.user.name')
                    ->label('Manager')
                    ->placeholder('-'),
                TextEntry::make('manager_reason')
                    ->label('Manager Reason')
                    ->visible(fn (LeaveRequest $record) => $record->manager_reason),
                TextEntry::make('hrd_status')
                    ->badge()
                    ->visible(fn (LeaveRequest $record) => $record->hrd_status !== LeaveStatus::PENDING),
                TextEntry::make('hrd.user.name')
                    ->label('HRD')
                    ->placeholder('-'),
                TextEntry::make('hrd_reason')
                    ->label('HRD Reason')
                    ->visible(fn (LeaveRequest $record) => $record->hrd_reason),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('attachment_path')
                    ->label('Attachment')
                    ->formatStateUsing(fn () => 'View Document')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->url(fn (LeaveRequest $record) => route('leave-requests.attachment.view', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (LeaveRequest $record) => $record->attachment_path),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();

                if (! $user || ! $user->employee) {
                    return $query;
                }

                $employee = $user->employee;

                if ($employee->role === EmployeeRole::EMPLOYEE) {
                    return $query->where('employee_id', $employee->id);
                }

                if ($employee->role === EmployeeRole::MANAGER) {
                    return $query->where('manager_status', LeaveStatus::PENDING);
                }

                if ($employee->role === EmployeeRole::HRD) {
                    return $query->where('hrd_status', LeaveStatus::PENDING)
                        ->where('manager_status', LeaveStatus::APPROVED);
                }

                return $query;
            })
            ->columns([
                TextColumn::make('employee.user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('manager_status')
                    ->badge()
                    ->visible(fn () => in_array(Auth::user()?->employee?->role, [EmployeeRole::MANAGER, EmployeeRole::EMPLOYEE])),
                TextColumn::make('hrd_status')
                    ->badge()
                    ->visible(fn () => in_array(Auth::user()?->employee?->role, [EmployeeRole::HRD, EmployeeRole::EMPLOYEE])),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (LeaveRequest $record) => $record->status === LeaveStatus::PENDING &&
                        $record->manager_status === LeaveStatus::PENDING &&
                        $record->hrd_status === LeaveStatus::PENDING &&
                        $record->employee_id === Auth::user()->employee?->id
                    ),
                DeleteAction::make()
                    ->visible(fn (LeaveRequest $record) => $record->status === LeaveStatus::PENDING &&
                        $record->manager_status === LeaveStatus::PENDING &&
                        $record->hrd_status === LeaveStatus::PENDING &&
                        $record->employee_id === Auth::user()->employee?->id
                    ),
                Action::make('download_pdf')
                    ->icon('heroicon-o-document-arrow-down')
                    ->label('Download PDF')
                    ->color('info')
                    ->url(fn (LeaveRequest $record) => route('leave-requests.download-pdf', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (LeaveRequest $record) => $record->status === LeaveStatus::APPROVED),
                Action::make('approve')
                    ->icon(Heroicon::Check)
                    ->color('success')
                    ->schema([
                        Textarea::make('reason')
                            ->label('Reason')
                            ->placeholder('Optional reason for approval...')
                            ->rows(3),
                    ])
                    ->action(function (LeaveRequest $record, array $data) {
                        $employee = Auth::user()?->employee;

                        if ($employee->role === EmployeeRole::MANAGER) {
                            $record->manager_status = LeaveStatus::APPROVED;
                            $record->manager_id = $employee->id;
                            $record->manager_reason = $data['reason'];
                        } elseif ($employee->role === EmployeeRole::HRD) {
                            $record->hrd_status = LeaveStatus::APPROVED;
                            $record->hrd_id = $employee->id;
                            $record->hrd_reason = $data['reason'];
                        }

                        if ($record->manager_status === LeaveStatus::APPROVED && $record->hrd_status === LeaveStatus::APPROVED) {
                            $record->status = LeaveStatus::APPROVED;
                        }

                        $record->save();

                        Notification::make()
                            ->title('Leave request approved successfully')
                            ->success()
                            ->send();
                    })
                    ->visible(function () {
                        $employee = Auth::user()?->employee;
                        if (! $employee) {
                            return false;
                        }

                        return in_array($employee->role, [EmployeeRole::MANAGER, EmployeeRole::HRD]);
                    }),
                Action::make('reject')
                    ->icon(Heroicon::XMark)
                    ->color('danger')
                    ->schema([
                        Textarea::make('reason')
                            ->label('Reason')
                            ->placeholder('Provide a reason for rejection...')
                            ->rows(3)
                            ->required(),
                    ])
                    ->action(function (LeaveRequest $record, array $data) {
                        $employee = Auth::user()?->employee;

                        if ($employee->role === EmployeeRole::MANAGER) {
                            $record->manager_status = LeaveStatus::REJECTED;
                            $record->manager_id = $employee->id;
                            $record->manager_reason = $data['reason'];
                        } elseif ($employee->role === EmployeeRole::HRD) {
                            $record->hrd_status = LeaveStatus::REJECTED;
                            $record->hrd_id = $employee->id;
                            $record->hrd_reason = $data['reason'];
                        }

                        $record->status = LeaveStatus::REJECTED;
                        $record->save();

                        Notification::make()
                            ->title('Leave request rejected successfully')
                            ->success()
                            ->send();
                    })
                    ->visible(function () {
                        $employee = Auth::user()?->employee;
                        if (! $employee) {
                            return false;
                        }

                        return in_array($employee->role, [EmployeeRole::MANAGER, EmployeeRole::HRD]);
                    }),
            ])
            ->toolbarActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLeaveRequests::route('/'),
        ];
    }

    private static function isImage(?string $path): bool
    {
        if (! $path) {
            return false;
        }

        return Str::endsWith(Str::lower($path), ['.jpg', '.jpeg', '.png', '.webp']);
    }

    private static function isPdf(?string $path): bool
    {
        if (! $path) {
            return false;
        }

        return Str::endsWith(Str::lower($path), '.pdf');
    }
}
