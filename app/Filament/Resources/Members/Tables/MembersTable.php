<?php

namespace App\Filament\Resources\Members\Tables;

use App\Application\Club\CurrentClub;
use App\Domain\Membership\Enums\MembershipStatus;
use Carbon\CarbonImmutable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('club.name')
                    ->label('Verein')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('member_number')
                    ->label('Mitgliedsnummer')
                    ->searchable(),
                TextColumn::make('first_name')
                    ->label('Vorname')
                    ->searchable(),
                TextColumn::make('last_name')
                    ->label('Nachname')
                    ->searchable(),
                TextColumn::make('birth_date')
                    ->label('Geburtsdatum')
                    ->date()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Telefonnummer')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('joined_at')
                    ->label('Beitrittsdatum')
                    ->date()
                    ->sortable(),
                TextColumn::make('left_at')
                    ->label('Austrittsdatum')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Zuletzt aktualisiert am')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('street')
                    ->label('Straße')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('house_number')
                    ->label('Hausnummer')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('postal_code')
                    ->label('Postleitzahl')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('city')
                    ->label('Stadt')
                    ->searchable(),
                TextColumn::make('country_code')
                    ->label('Land')
                    ->searchable(),
                TextColumn::make('mobile')
                    ->label('Mobiltelefon')
                    ->searchable(),
                TextColumn::make('membershipType.name')
                    ->label('Mitgliedschaftstyp')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Mitgliedsstatus')
                    ->options([
                        MembershipStatus::Active->value => 'Aktiv',
                        MembershipStatus::Suspended->value => 'Gesperrt',
                        MembershipStatus::Left->value => 'Ausgetreten',
                    ]),
                SelectFilter::make('membership_type_id')
                    ->label('Mitgliedsart')
                    ->relationship('membershipType', 'name', static fn (Builder $query): Builder => $query
                        ->where('club_id', app(CurrentClub::class)->id()))
                    ->preload(),
                Filter::make('joined_at')
                    ->label('Eintrittszeitraum')
                    ->schema([
                        DatePicker::make('from')->label('Eintritt ab'),
                        DatePicker::make('until')->label('Eintritt bis'),
                    ])
                    ->query(static fn (Builder $query, array $data): Builder => $query
                        ->when(filled($data['from'] ?? null), static fn (Builder $query): Builder => $query->where('joined_at', '>=', $data['from']))
                        ->when(filled($data['until'] ?? null), static fn (Builder $query): Builder => $query->where('joined_at', '<', CarbonImmutable::parse($data['until'])->addDay()->toDateString())))
                    ->indicateUsing(static function (array $data): array {
                        $indicators = [];

                        if (filled($data['from'] ?? null)) {
                            $indicators[] = 'Eintritt ab '.CarbonImmutable::parse($data['from'])->format('d.m.Y');
                        }

                        if (filled($data['until'] ?? null)) {
                            $indicators[] = 'Eintritt bis '.CarbonImmutable::parse($data['until'])->format('d.m.Y');
                        }

                        return $indicators;
                    }),
                SelectFilter::make('membership_years')
                    ->label('Zugehörigkeit mindestens')
                    ->options(collect([5, 10, 15, 20, 25, 30, 40, 50, 60, 70, 75, 80])
                        ->mapWithKeys(static fn (int $years): array => [$years => $years.' Jahre'])->all())
                    ->query(static function (Builder $query, array $data): Builder {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        return $query
                            ->where('status', '!=', MembershipStatus::Left->value)
                            ->whereNull('left_at')
                            ->where('joined_at', '<', CarbonImmutable::today()->subYearsNoOverflow((int) $data['value'])->addDay()->toDateString());
                    }),
                Filter::make('membership_anniversary')
                    ->label('Vereinsjubiläum')
                    ->schema([
                        Select::make('years')
                            ->label('Jubiläum (Mitgliedsjahre)')
                            ->multiple()
                            ->options(collect(range(5, 100, 5))
                                ->mapWithKeys(static fn (int $years): array => [$years => $years.' Jahre'])->all()),
                        Select::make('year')
                            ->label('Jubiläumsjahr')
                            ->options(collect(range(now()->year - 5, now()->year + 5))
                                ->mapWithKeys(static fn (int $year): array => [$year => (string) $year])->all())
                            ->default(now()->year),
                    ])
                    ->query(static function (Builder $query, array $data): Builder {
                        if (blank($data['years'] ?? null)) {
                            return $query;
                        }

                        $year = (int) ($data['year'] ?? now()->year);

                        return $query
                            ->where('status', '!=', MembershipStatus::Left->value)
                            ->whereNull('left_at')
                            ->where(static function (Builder $query) use ($data, $year): void {
                                foreach ($data['years'] as $years) {
                                    $joinedYear = CarbonImmutable::create($year - (int) $years, 1, 1);
                                    $query->orWhere(static fn (Builder $query): Builder => $query
                                        ->where('joined_at', '>=', $joinedYear->toDateString())
                                        ->where('joined_at', '<', $joinedYear->addYear()->toDateString()));
                                }
                            });
                    })
                    ->indicateUsing(static function (array $data): ?string {
                        if (blank($data['years'] ?? null)) {
                            return null;
                        }

                        return 'Vereinsjubiläum '.($data['year'] ?? now()->year).': '.implode(', ', $data['years']).' Jahre';
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
