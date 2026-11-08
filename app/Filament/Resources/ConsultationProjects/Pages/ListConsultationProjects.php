<?php

namespace App\Filament\Resources\ConsultationProjects\Pages;

use App\Filament\Resources\ConsultationProjects\ConsultationProjectResource;
use App\Support\ConsultationPoster;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListConsultationProjects extends ListRecords
{
    protected static string $resource = ConsultationProjectResource::class;

    protected static ?string $title = 'Admin de la consultation';

    protected function getHeaderActions(): array
    {
        return [
            // Une affichette A5 paysage par projet (infos + QR code), à poser sous la maquette.
            Action::make('posters')
                ->label('Affichettes PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(fn () => response()->streamDownload(fn () => print (ConsultationPoster::pdf()), 'affichettes-consultation.pdf', ['Content-Type' => 'application/pdf'])),
            CreateAction::make(),
        ];
    }
}
