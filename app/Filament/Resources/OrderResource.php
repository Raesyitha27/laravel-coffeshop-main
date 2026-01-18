<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Kreait\Firebase\Factory;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Pesanan Masuk';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID Pesanan')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('Pelanggan')->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Proses' => 'warning',
                        'Selesai'  => 'success',
                        'Batal'    => 'danger',
                        default    => 'gray',
                    }),
                Tables\Columns\TextColumn::make('totalPrice')->money('IDR'),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->actions([
                Tables\Actions\Action::make('verify')
                    ->label('Selesaikan Pesanan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->hidden(fn ($record) => $record->status === 'Selesai')
                    ->action(function ($record) {
                        try {
                            // 1. Update MySQL
                            $record->update(['status' => 'Selesai']);

                            // 2. Update Firebase (HANYA field status agar items tidak hilang)
                            $factory = (new Factory)
                                ->withServiceAccount(storage_path('app/service-account.json'))
                                ->withDatabaseUri(env('FIREBASE_DATABASE_URL'));
                            
                            $database = $factory->createDatabase();
                            
                            // Menuju path: Orders/{ID_MYSQL}/status
                            $database->getReference('Orders/' . $record->id)
                                ->update([
                                    'status' => 'Selesai',
                                ]);

                            Notification::make()->title('Pesanan Selesai!')->success()->send();

                        } catch (\Exception $e) {
                            Notification::make()->title('Gagal Update Firebase')->danger()->body($e->getMessage())->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function ($record) {
                        // Hapus data di Firebase jika pesanan dihapus di Admin
                        try {
                            $factory = (new Factory)
                                ->withServiceAccount(storage_path('app/service-account.json'))
                                ->withDatabaseUri(env('FIREBASE_DATABASE_URL'));
                            $database = $factory->createDatabase();
                            $database->getReference('Orders/' . $record->id)->remove();
                        } catch (\Exception $e) {}
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}