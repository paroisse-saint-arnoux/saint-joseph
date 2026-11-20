<?php

namespace App\Filament\Resources\OilOrders;

use App\Enums\OilOrderStatus;
use App\Filament\Resources\OilOrders\Pages\ManageOilOrders;
use App\Models\OilOrder;
use App\Models\OilProduct;
use App\Support\OilOrders;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

/** Réservations de bouteilles d'huile (/commande-huile) : suivi (à retirer / retirée / annulée), stocks, export Excel. */
class OilOrderResource extends Resource
{
    protected static ?string $model = OilOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $modelLabel = 'commande';

    protected static ?string $pluralModelLabel = 'commandes d\'huile';

    protected static ?string $navigationLabel = 'Commandes et stocks';

    protected static string|\UnitEnum|null $navigationGroup = 'Huile d\'olive';

    protected static ?string $slug = 'huile/commandes';

    protected static ?string $recordTitleAttribute = 'number';

    public static function getNavigationBadge(): ?string
    {
        $count = OilOrder::where('status', OilOrderStatus::Reserved)->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Commandes à retirer';
    }

    /** Met à jour les chiffres du haut de la page (widget OilStock) après une action. */
    public static function refreshStock(): \Closure
    {
        return fn ($livewire) => $livewire->dispatch('oil-orders-updated');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextEntry::make('summary')->label('Bouteilles')->columnSpanFull()
                    ->state(fn (OilOrder $record) => $record->summary().' — '.$record->total()),
                TextEntry::make('status')->label('Statut')->badge(),
                TextEntry::make('created_at')->label('Commandée le')->dateTime('d/m/Y à H:i'),
                TextInput::make('first_name')->label('Prénom')->required()->maxLength(80),
                TextInput::make('last_name')->label('Nom')->required()->maxLength(80),
                TextInput::make('phone')->label('Téléphone')->tel()->required()->maxLength(30)
                    ->helperText('Format international : +33612345678.'),
                TextInput::make('email')->label('Email')->email()->required()->maxLength(190),
                Textarea::make('notes')->label('Note interne')->rows(3)->columnSpanFull()
                    ->helperText('Visible seulement dans l\'admin et dans l\'export (paiement, remarque…).'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('items'))
            // Sur téléphone, pas de défilement horizontal : nom (n° et total dessous), une colonne par format de bouteille,
            // statut (bouton « Retirer » tant que la commande est à retirer) et menu ⋮ ; le reste à partir d'un écran moyen.
            ->columns([
                TextColumn::make('number')->label('N°')->searchable()->sortable(query: fn ($query, string $direction) => $query->orderBy('id', $direction))->weight('bold')
                    ->description(fn (OilOrder $record) => $record->created_at->format('d/m H:i'))->visibleFrom('sm'),
                TextColumn::make('last_name')->label('Nom')->sortable()->searchable(['first_name', 'last_name', 'phone', 'email', 'number'])
                    ->formatStateUsing(fn (OilOrder $record) => $record->fullName())
                    ->description(fn (OilOrder $record) => $record->number.' · '.$record->total())
                    ->wrap(),
                ...OilProduct::ordered()->get()->map(fn (OilProduct $product) => TextColumn::make('bottles_'.$product->id)
                    ->label($product->label)
                    ->wrapHeader()
                    ->state(fn (OilOrder $record) => (int) $record->items->where('product_id', $product->id)->sum('quantity') ?: '·')
                    ->alignCenter()
                    ->size(TextSize::Large)
                    ->weight(FontWeight::Bold)
                    ->color(fn ($state) => $state === '·' ? 'gray' : null))->all(),
                TextColumn::make('phone')->label('Téléphone')
                    ->formatStateUsing(fn (OilOrder $record) => $record->phoneForDisplay())
                    ->url(fn (OilOrder $record) => 'tel:'.$record->phone)
                    ->color('primary')->visibleFrom('md'),
                TextColumn::make('email')->label('Email')->toggleable(isToggledHiddenByDefault: true)->visibleFrom('lg'),
                TextColumn::make('total_cents')->label('Total')->alignEnd()->sortable()->visibleFrom('md')
                    ->formatStateUsing(fn (int $state) => OilOrders::money($state)),
                // À retirer : bouton « Retirer » (un clic, réversible par le menu ⋮) ; ensuite, le statut.
                TextColumn::make('status')->label('Statut')->sortable()->badge()
                    ->formatStateUsing(fn (OilOrderStatus $state) => $state === OilOrderStatus::Reserved ? 'Retirer' : $state->getLabel())
                    ->icon(fn (OilOrderStatus $state) => $state === OilOrderStatus::Reserved ? 'heroicon-m-check' : null)
                    ->color(fn (OilOrderStatus $state) => $state === OilOrderStatus::Reserved ? 'primary' : $state->getColor())
                    ->size(TextSize::Small)
                    ->tooltip(fn (OilOrder $record) => $record->status === OilOrderStatus::Reserved ? 'Les bouteilles ont été données : marquer la commande comme retirée' : null)
                    ->action(Action::make('collect')
                        ->action(function (OilOrder $record) {
                            if ($record->status === OilOrderStatus::Reserved) {
                                OilOrders::collect($record);
                            }
                        })
                        ->after(self::refreshStock())),
                TextColumn::make('notes')->label('Note')->limit(40)->toggleable()->visibleFrom('lg'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(OilOrderStatus::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()->label('Modifier')->after(self::refreshStock()),
                    Action::make('reopen')
                        ->label('Remettre « à retirer »')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('gray')
                        ->visible(fn (OilOrder $record) => $record->status !== OilOrderStatus::Reserved)
                        ->requiresConfirmation()
                        ->modalDescription(fn (OilOrder $record) => $record->status === OilOrderStatus::Cancelled
                            ? 'Les bouteilles de cette commande seront de nouveau retirées du stock.'
                            : 'La commande sera de nouveau marquée comme non retirée.')
                        ->action(function (OilOrder $record) {
                            try {
                                OilOrders::reopen($record);
                            } catch (ValidationException $e) {
                                Notification::make()->title($e->getMessage())->danger()->send();
                            }
                        })
                        ->after(self::refreshStock()),
                    Action::make('cancel')
                        ->label('Annuler')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->visible(fn (OilOrder $record) => $record->status !== OilOrderStatus::Cancelled)
                        ->requiresConfirmation()
                        ->modalHeading(fn (OilOrder $record) => 'Annuler la commande '.$record->number.' ?')
                        ->modalDescription('Ses bouteilles reviennent dans le stock et peuvent de nouveau être réservées. La personne n\'est pas prévenue.')
                        ->modalSubmitActionLabel('Annuler la commande')
                        ->action(fn (OilOrder $record) => OilOrders::cancel($record))
                        ->after(self::refreshStock()),
                    Action::make('resend')
                        ->label('Renvoyer l\'e-mail')
                        ->icon('heroicon-o-envelope')
                        ->color('gray')
                        ->visible(fn (OilOrder $record) => $record->status === OilOrderStatus::Reserved)
                        ->requiresConfirmation()
                        ->modalDescription(fn (OilOrder $record) => 'Le récapitulatif de la commande sera de nouveau envoyé à '.$record->email.'.')
                        ->action(fn (OilOrder $record) => OilOrders::sendConfirmation($record)
                            ? Notification::make()->title('E-mail envoyé')->success()->send()
                            : Notification::make()->title('L\'e-mail n\'a pas pu être envoyé')->danger()->send()),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageOilOrders::route('/'),
        ];
    }
}
