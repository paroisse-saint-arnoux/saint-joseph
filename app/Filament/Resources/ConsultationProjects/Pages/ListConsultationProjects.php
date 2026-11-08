<?php

namespace App\Filament\Resources\ConsultationProjects\Pages;

use App\Filament\Resources\ConsultationProjects\ConsultationProjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListConsultationProjects extends ListRecords
{
    protected static string $resource = ConsultationProjectResource::class;

    protected static ?string $title = 'Projets de la consultation';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
