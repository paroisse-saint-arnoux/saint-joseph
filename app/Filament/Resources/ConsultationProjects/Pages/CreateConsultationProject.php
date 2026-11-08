<?php

namespace App\Filament\Resources\ConsultationProjects\Pages;

use App\Filament\Resources\ConsultationProjects\ConsultationProjectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateConsultationProject extends CreateRecord
{
    protected static string $resource = ConsultationProjectResource::class;

    protected static bool $canCreateAnother = false;
}
