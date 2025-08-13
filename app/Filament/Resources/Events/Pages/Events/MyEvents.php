<?php

namespace App\Filament\Resources\Events\Pages\Events;

use App\Filament\Resources\Events\EventResource;
use Filament\Resources\Pages\Page;

class MyEvents extends Page
{
    protected static string $resource = EventResource::class;

    protected static ?string $title = 'My Events';

    protected static ?string $navigationLabel = 'My Events';

    protected string $view = 'filament.resources.events.pages.events.my-events';

    public $upcomingEvents = [];

    public $pastEvents = [];

    public function mount(): void
    {
        $this->loadEvents();
    }

    protected function loadEvents(): void
    {
        $user = auth()->user();

        $this->upcomingEvents = $user->organizedEvents()
            ->with(['acceptedInvitees'])
            ->upcoming()
            ->orderBy('start_date', 'asc')
            ->get()
            ->toArray();

        $this->pastEvents = $user->organizedEvents()
            ->with(['acceptedInvitees'])
            ->past()
            ->orderBy('start_date', 'desc')
            ->get()
            ->toArray();
    }
}
