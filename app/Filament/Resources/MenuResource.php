<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuResource\Pages;
use App\Filament\Resources\MenuResource\RelationManagers;
use App\Models\Menu;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\Section::make('Informasi Kopi')
            ->schema([
                Forms\Components\TextInput::make('nama_menu')->label('Nama Kopi')->required(),
                Forms\Components\TextInput::make('harga')->numeric()->prefix('Rp')->required(),
                Forms\Components\TextInput::make('picUrl')->label('Link Foto (Firebase URL)'),
                Forms\Components\TextInput::make('extra')->placeholder('Contoh: Milk, Sugar'),
                Forms\Components\TextInput::make('quantity')->numeric()->label('Stok Awal'),
                Forms\Components\Textarea::make('deskripsi')->columnSpanFull(),
                Forms\Components\Hidden::make('kategori')->default('Kopi'),
            ])->columns(2)
    ]);
}

   public static function table(Table $table): Table
{
    return $table
        ->poll('1s')
        ->columns([
            Tables\Columns\ImageColumn::make('picUrl')
                ->label('Foto')
                ->circular(),
            Tables\Columns\TextColumn::make('nama_menu')
                ->label('Nama Kopi')
                ->searchable(),
            Tables\Columns\TextColumn::make('harga')
                ->money('IDR'),
            Tables\Columns\TextColumn::make('quantity')
                ->label('Stok Terkini')
                ->color(fn (int $state): string => $state <= 5 ? 'danger' : 'success')
                ->sortable(),
        ]);
}

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenus::route('/'),
            'create' => Pages\CreateMenu::route('/create'),
            'edit' => Pages\EditMenu::route('/{record}/edit'),
        ];
    }
}
