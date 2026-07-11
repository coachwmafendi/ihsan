<?php

namespace App\Filament\Admin\Pages;

use App\Enums\UserRole;
use App\Jobs\SendAdminNotificationToOrgs;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\AdminToOrgAdminNotification;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class SendNotificationToOrgs extends Page
{
    protected static ?string $navigationLabel = 'Send Notification';

    protected static ?string $title = 'Send Notification to Organization Admins';

    protected static ?int $navigationSort = 26;

    protected string $view = 'filament.admin.pages.send-notification-to-orgs';

    public ?array $data = [];

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-bell';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return null;
    }

    protected function getAuthUser(): ?User
    {
        return Auth::user();
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && $user->role === UserRole::SuperAdmin;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->columns(1)
            ->schema([
                Checkbox::make('send_to_all')
                    ->label('Send to all organizations')
                    ->live()
                    ->disabled(fn (): bool => ! self::canAccess()),

                Select::make('organization_ids')
                    ->label('Organizations')
                    ->options(fn () => Organization::all()->pluck('name', 'id'))
                    ->multiple()
                    ->required(fn ($get) => ! $get('send_to_all'))
                    ->hidden(fn ($get) => $get('send_to_all'))
                    ->disabled(fn (): bool => ! self::canAccess())
                    ->searchable()
                    ->placeholder('Select organizations'),

                Textarea::make('message')
                    ->label('Notification Message')
                    ->columnSpanFull()
                    ->required()
                    ->maxLength(1000)
                    ->live()
                    ->helperText(fn (?string $state): string => sprintf('%d / 1000 characters', strlen($state ?? '')))
                    ->disabled(fn (): bool => ! self::canAccess())
                    ->rows(5),

                Select::make('type')
                    ->label('Notification Type')
                    ->options([
                        'info' => 'Info',
                        'success' => 'Success',
                        'warning' => 'Warning',
                        'error' => 'Error',
                    ])
                    ->default('info')
                    ->required()
                    ->disabled(fn (): bool => ! self::canAccess()),

                FileUpload::make('image')
                    ->label('Notification Image')
                    ->image()
                    ->disk('public')
                    ->directory('notifications')
                    ->maxSize(2048)
                    ->disabled(fn (): bool => ! self::canAccess()),
            ]);
    }

    public function mount(): void
    {
        abort_unless(self::canAccess(), 403);

        $this->data = [
            'send_to_all' => false,
            'organization_ids' => [],
            'message' => '',
            'type' => 'info',
            'image' => null,
        ];
    }

    public function sendNotification(): void
    {
        abort_unless(self::canAccess(), 403);

        $data = $this->form->getState();

        $organizationIds = $data['send_to_all']
            ? Organization::pluck('id')->all()
            : $data['organization_ids'];

        $this->validate([
            'data.message' => 'required|string|max:1000',
            'data.type' => 'required|in:info,success,warning,error',
        ]);

        if (! $data['send_to_all'] && empty($organizationIds)) {
            Notification::make()
                ->title('Please select at least one organization.')
                ->danger()
                ->send();

            return;
        }

        $sender = $this->getAuthUser();
        $senderName = $sender ? $sender->name : 'Platform Admin';

        $adminCount = User::query()
            ->whereIn('organization_id', $organizationIds)
            ->where('role', UserRole::NgoAdmin)
            ->count();

        SendAdminNotificationToOrgs::dispatch(
            message: $data['message'],
            senderName: $senderName,
            organizationIds: $organizationIds,
            type: $data['type'],
            image: $data['image'] ?? null,
        );

        Notification::make()
            ->title('Notifications Queued')
            ->body("Queued notification for {$adminCount} organization admin(s). It will be sent in the background.")
            ->success()
            ->send();

        $this->form->fill([
            'send_to_all' => false,
            'organization_ids' => [],
            'message' => '',
            'type' => 'info',
            'image' => null,
        ]);
    }

    public function getSentNotifications()
    {
        return DatabaseNotification::query()
            ->where('type', AdminToOrgAdminNotification::class)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    public function deleteNotification(string $notificationId): void
    {
        abort_unless(self::canAccess(), 403);

        $notification = DatabaseNotification::find($notificationId);

        if ($notification) {
            $notification->delete();

            Notification::make()
                ->title('Notification deleted')
                ->success()
                ->send();
        }
    }

    public function deleteAllNotifications(): void
    {
        abort_unless(self::canAccess(), 403);

        DatabaseNotification::query()
            ->where('type', AdminToOrgAdminNotification::class)
            ->delete();

        Notification::make()
            ->title('All notifications deleted')
            ->success()
            ->send();
    }
}
