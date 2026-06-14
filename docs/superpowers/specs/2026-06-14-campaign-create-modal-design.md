# Design: Dashboard "Create Campaign" Opens Modal

## Context
- The dashboard (`/app/dashboard`) currently has two "Create Campaign" entry points that redirect to `/app/campaigns/create`:
  1. Quick action button in the "Quick actions" card.
  2. Empty-state action inside "Top campaigns" card when no campaigns exist.
- The `/app/campaigns` index page already shows a lightweight modal for creating a campaign: choose **New campaign** or **Clone campaign**, enter a name, then click **Create campaign**. After creation it redirects to the campaign edit page.

## Goal
Change both dashboard "Create Campaign" entry points so they open the same lightweight campaign-creation modal instead of redirecting to the full create page.

## Decision
Use **Approach 1: Extract the modal into a reusable Livewire component**.

## Architecture

```
App\Livewire\App\Campaigns\CampaignCreateModal   (new)
├── state: showCreateModal, createMode, newCampaignName, cloneCampaignId
├── computed: cloneableCampaigns, organization
├── methods: open/close/createCampaign
└── view: resources/views/livewire/app/campaigns/create-modal.blade.php

Parent components updated to embed the modal component and dispatch
an event to open it:
├── App\Livewire\App\Campaigns\CampaignIndex + index.blade.php
└── App\Livewire\App\Dashboard + dashboard.blade.php
```

## Components

### New files
- `app/Livewire/App/Campaigns/CampaignCreateModal.php`
- `resources/views/livewire/app/campaigns/create-modal.blade.php`

### Updated files
- `app/Livewire/App/Campaigns/CampaignIndex.php`
  - Remove inline modal state and methods.
- `resources/views/livewire/app/campaigns/index.blade.php`
  - Replace inline modal markup with `<livewire:app.campaigns.campaign-create-modal />`.
  - Change create button to dispatch `open-create-campaign-modal`.
- `app/Livewire/App/Dashboard.php`
  - No new backend logic; will render the child component in its view.
- `resources/views/livewire/app/dashboard.blade.php`
  - Change both create buttons to dispatch `open-create-campaign-modal`.
  - Include child modal component at the bottom of the page.

## Data Flow
1. User clicks **Create Campaign** in the dashboard (or campaigns page).
2. Parent component dispatches `open-create-campaign-modal`.
3. `CampaignCreateModal` listens via `#[On('open-create-campaign-modal')]` and sets `showCreateModal = true`.
4. User chooses new/clone, enters a name, and submits.
5. Component creates the campaign as a draft (clone copies the source campaign settings), dispatches a success notification, closes the modal, and redirects to `app.campaigns.edit`.

## Error Handling
- Empty campaign name → dispatch danger notification, do not submit.
- Clone mode without a selected campaign → dispatch danger notification.
- Selected clone campaign missing or not owned by the organization → dispatch danger notification.
- Modal state resets when closed or after successful creation.

## Testing
- Create `tests/Feature/App/CampaignCreateModalTest.php`:
  - Modal is hidden by default and becomes visible after the open event.
  - Create a new draft campaign and verify redirect to edit route.
  - Clone an existing campaign and verify copied fields.
- Update `tests/Feature/App/DashboardTest.php`:
  - Verify "Create Campaign" text still appears.
  - (Optional) Verify the dashboard renders the `CampaignCreateModal` child component.

## Success Criteria
- [ ] Both dashboard "Create Campaign" buttons open the modal instead of navigating away.
- [ ] The modal behaves identically to the existing modal on the campaigns page.
- [ ] Existing campaigns-page modal continues to work unchanged.
- [ ] New feature tests pass.
- [ ] Existing test suite remains green.
