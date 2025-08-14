<?php

namespace App\Filament\Resources\SharedExpenses\Pages;

use App\Filament\Resources\SharedExpenses\SharedExpenseResource;
use App\Models\BudgetImage;
use App\Models\SharedExpense;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageSharedExpenses extends ManageRecords
{
    protected static string $resource = SharedExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->modalWidth('4xl')
                ->mutateFormDataUsing(function (array $data): array {
                    $data['created_by'] = auth()->id();
                    $data['status'] = 'active';

                    return $data;
                })
                ->after(function (SharedExpense $record, array $data): void {
                    // Handle photo uploads
                    if (! empty($data['photos'])) {
                        foreach ($data['photos'] as $photo) {
                            BudgetImage::create([
                                'imageable_type' => SharedExpense::class,
                                'imageable_id' => $record->id,
                                'filename' => basename($photo),
                                'original_name' => basename($photo),
                                'mime_type' => \Storage::mimeType($photo),
                                'size' => \Storage::size($photo),
                                'path' => $photo,
                                'uploaded_by' => auth()->id(),
                            ]);
                        }
                    }

                    Notification::make()
                        ->title('Friend Expense Created Successfully')
                        ->body('Friends can now see this expense and make payments')
                        ->success()
                        ->send();
                }),
        ];
    }
}
