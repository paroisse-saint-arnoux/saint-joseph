<?php

namespace App\Filament\Resources\ConsultationProjects\Pages;

use App\Filament\Resources\ConsultationProjects\ConsultationProjectResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditConsultationProject extends EditRecord
{
    protected static string $resource = ConsultationProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->modalDescription('Les avis déjà donnés sur ce projet seront supprimés. Pour le retirer du vote sans perdre les avis, décochez plutôt « Proposé au vote ».'),
        ];
    }
}
