<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class BudgetItem extends Model
{
    protected $fillable = [
        'event_id',
        'created_by',
        'title',
        'description',
        'type',
        'amount',
        'category',
        'split_among',
        'split_equally',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'split_among' => 'array',
        'split_equally' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BudgetPayment::class);
    }

    public function splits(): HasMany
    {
        return $this->hasMany(BudgetSplit::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(BudgetImage::class, 'imageable');
    }

    // Calculate and create splits for attendees
    public function createSplits(): void
    {
        if ($this->type !== 'expense') {
            return;
        }

        $attendees = collect();

        // Add accepted invitees
        $acceptedInvitees = $this->event->acceptedInvitees()->get();
        $attendees = $attendees->merge($acceptedInvitees);

        // Add organizer if not already included
        $organizer = $this->event->organizer;
        if (! $attendees->contains('id', $organizer->id)) {
            $attendees->push($organizer);
        }

        if ($attendees->count() === 0) {
            // Fallback: create split for organizer only
            $attendees->push($organizer);
        }

        $shareAmount = $this->amount / $attendees->count();

        foreach ($attendees as $attendee) {
            BudgetSplit::updateOrCreate(
                [
                    'budget_item_id' => $this->id,
                    'user_id' => $attendee->id,
                ],
                [
                    'share_amount' => $shareAmount,
                ]
            );
        }
    }

    // Update split statuses after a payment
    public function updateSplitStatuses(): void
    {
        foreach ($this->splits as $split) {
            if ($split->paid_amount >= $split->share_amount) {
                $split->status = $split->paid_amount > $split->share_amount ? 'overpaid' : 'paid';
            } elseif ($split->paid_amount > 0) {
                $split->status = 'partial';
            } else {
                $split->status = 'pending';
            }
            $split->save();
        }
    }

    // Get who has overpaid and by how much
    public function getCreditors(): array
    {
        $creditors = [];
        foreach ($this->splits()->where('status', 'overpaid')->get() as $split) {
            $creditors[] = [
                'user' => $split->user,
                'amount' => $split->paid_amount - $split->share_amount,
            ];
        }

        return $creditors;
    }

    // Get who still owes money
    public function getDebtors(): array
    {
        $debtors = [];
        foreach ($this->splits()->whereIn('status', ['pending', 'partial'])->get() as $split) {
            $debtors[] = [
                'user' => $split->user,
                'amount' => $split->share_amount - $split->paid_amount,
            ];
        }

        return $debtors;
    }
}
