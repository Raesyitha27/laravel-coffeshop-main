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

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Tambahkan skema form jika Anda ingin mengedit detail pesanan secara manual
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID Pesanan')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama Pelanggan')
                    ->searchable(),

                // Status Badge: Menggunakan label yang sama dengan logika di Android
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Diproses' => 'warning', // Warna Orange di Filament
                        'Selesai'  => 'success', // Warna Hijau di Filament
                        'Batal'    => 'danger',
                        default    => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total Pembayaran')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Pesan')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->actions([
                // TOMBOL TERIMA PESANAN (UPDATE KE SELESAI)
                Tables\Actions\Action::make('verify')
                    ->label('Selesaikan Pesanan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    // Tombol hanya muncul jika status pesanan adalah 'Diproses'
                    ->hidden(fn ($record) => $record->status === 'Selesai')
                    ->action(function ($record) {
                        try {
                            // 1. Update Status di Database MySQL
                            $record->update(['status' => 'Selesai']);

                            // 2. Inisialisasi Firebase
                            $path = storage_path('app/service-account.json');
                            $url  = env('FIREBASE_DATABASE_URL');

                            if (!file_exists($path)) {
                                throw new \Exception("Kunci Firebase (service-account.json) tidak ditemukan.");
                            }

                            $factory = (new Factory)
                                ->withServiceAccount($path)
                                ->withDatabaseUri($url);

                            $database = $factory->createDatabase();

                            // 3. Update Status di Firebase Realtime Database
                            // Menuju path: Orders/{orderId}/status
                            $database->getReference('Orders/' . $record->id)
                                ->update([
                                    'status' => 'Selesai',
                                ]);

                            // Kirim notifikasi sukses ke dashboard Filament
                            Notification::make()
                                ->title('Berhasil!')
                                ->body("Pesanan #{$record->id} telah diperbarui ke Selesai.")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            // Jika Firebase gagal, MySQL tidak di-rollback tapi admin diberi tahu
                            Notification::make()
                                ->title('Peringatan: Firebase Tidak Update')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),

                Tables\Actions\EditAction::make(),
                
                // Menghapus data di MySQL dan Firebase sekaligus
                Tables\Actions\DeleteAction::make()
                    ->after(function ($record) {
                        try {
                            $factory = (new Factory)
                                ->withServiceAccount(storage_path('app/service-account.json'))
                                ->withDatabaseUri(env('FIREBASE_DATABASE_URL'));
                            
                            $database = $factory->createDatabase();
                            $database->getReference('Orders/' . $record->id)->remove();
                        } catch (\Exception $e) {
                            // Abaikan jika data di Firebase memang sudah tidak ada
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
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