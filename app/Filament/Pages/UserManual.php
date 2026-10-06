<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use UnitEnum;

final class UserManual extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|UnitEnum|null $navigationGroup = 'Hilfe';

    protected static ?string $navigationLabel = 'Benutzerhandbuch';

    protected static ?string $title = 'Benutzerhandbuch';

    protected static ?string $slug = 'benutzerhandbuch';

    protected string $view = 'filament.pages.user-manual';

    /**
     * @return array{manual: HtmlString}
     */
    protected function getViewData(): array
    {
        $markdown = Str::replace(
            ['[Projektdokumentation](PROJEKT-DOKUMENTATION.md)', '[Changelog](../CHANGELOG.md)'],
            ['Projektdokumentation im Repository', 'Changelog im Repository'],
            File::get(base_path('docs/BENUTZERHANDBUCH.md')),
        );

        return [
            'manual' => new HtmlString(Str::markdown($markdown, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
                'heading_permalink' => [
                    'apply_id_to_heading' => true,
                    'id_prefix' => '',
                    'fragment_prefix' => '',
                    'insert' => 'none',
                ],
            ], [new HeadingPermalinkExtension])),
        ];
    }
}
