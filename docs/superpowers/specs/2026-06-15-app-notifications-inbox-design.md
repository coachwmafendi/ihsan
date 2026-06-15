# Design: Org Admin Notification Inbox

## Context
- The platform admin can send database notifications to organization admins from `/admin/send-notification-to-orgs`.
- Those notifications are stored as Laravel `DatabaseNotification` records with type `App\Notifications\AdminToOrgAdminNotification`.
- On the `/app` side, there is currently no inbox for organization admins to read, mark as read, or delete these notifications.
- The topbar bell icon is present but is a dead button (no link, no unread count).

## Goal
Expose a notification inbox page accessible from the topbar bell. Let the org admin mark items as read and delete them.

## Decision
Use **Approach A: dedicated `/app/notifications` page**.

## Architecture

```
App\Livewire\App\Notifications\Index   (new)
├── state: none persistent; pagination is handled by WithPagination
├── computed: notifications (paginated), unreadCount
├── methods: markAsRead(string $id), delete(string $id)
└── view: resources/views/livewire/app/notifications/index.blade.php

resources/views/components/topbar.blade.php   (updated)
├── Bell icon wraps an <a> to /app/notifications.
└── Show unread count badge when unreadCount > 0.

routes/web.php   (updated)
└── GET /app/notifications → App\Livewire\App\Notifications\Index
```

## Components

### New files
- `app/Livewire/App/Notifications/Index.php`
- `resources/views/livewire/app/notifications/index.blade.php`

### Updated files
- `resources/views/components/topbar.blade.php`
  - Wrap the bell button in a link to `/app/notifications` and display the unread count.
- `routes/web.php`
  - Add `Route::get('/app/notifications', App\Livewire\App\Notifications\Index::class)->name('app.notifications.index');`

## Data Flow
1. Admin sends notification from `/admin/send-notification-to-orgs`.
2. Notification is saved to `notifications` table attached to each org admin user.
3. Org admin clicks the topbar bell.
4. Browser navigates to `/app/notifications`.
5. `Index` component loads only notifications whose `type` is `AdminToOrgAdminNotification::class`, ordered newest first, paginated at 15.
6. User clicks **Mark as read** → `markAsRead()` is called; `read_at` is populated; list re-renders.
7. User clicks **Delete** → notification row is deleted and list re-renders.

## UI
- `layouts.app` shell with page title "Notifications".
- Empty state when there are no notifications.
- Notification rows show:
  - Type badge (info / success / warning / error).
  - Sender label "Platform Admin".
  - Message text.
  - Timestamp (`diffForHumans`).
  - Image thumbnail if `data.image` is present.
  - Action buttons: **Mark as read** (only for unread items) and **Delete**.
- Unread rows get subtle highlight (e.g. `bg-teal-50`).

## Error Handling
- Invalid notification ID passed to `markAsRead()` / `delete()` → silently ignore (model not found or type mismatch).
- Auth is enforced by the existing `EnsureNgoAdmin` + `RedirectIfStripeNotOnboarded` middleware group in `routes/web.php`.

## Testing
- `tests/Feature/App/Notifications/NotificationsIndexTest.php`:
  - Authenticated org admin sees a notification sent by the platform admin.
  - Marking a notification as read updates `read_at` and hides the mark-as-read button.
  - Deleting a notification removes it from the list.
  - Unread count appears in the topbar for unread notifications.

## Success Criteria
- [ ] `/app/notifications` renders a paginated list of platform notifications for the current org admin.
- [ ] Topbar bell links to `/app/notifications` and shows the unread count badge.
- [ ] Org admin can mark notifications as read.
- [ ] Org admin can delete notifications.
- [ ] New feature tests pass.
- [ ] Existing test suite remains green.
