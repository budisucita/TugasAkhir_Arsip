<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LogResource\Pages;
use App\Models\Anggota;
use App\Models\Kegiatan;
use App\Models\Keuangan;
use App\Models\Log;
use App\Models\Surat;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Group;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;


class LogResource extends Resource
{
    protected static ?string $model = Log::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Log Aktivitas';

    protected static ?string $modelLabel = 'Log Aktivitas';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user.name')
                    ->label('User')
                    ->disabled(),
                Forms\Components\TextInput::make('action')
                    ->label('Aksi')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('created_at')
                    ->label('Waktu')
                    ->disabled(),
                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi')
                    ->rows(2)
                    ->columnSpanFull()
                    ->disabled(),
                Forms\Components\KeyValue::make('before')
                    ->label('Data Lama')
                    ->disabled(), 
                Forms\Components\KeyValue::make('after')
                    ->label('Data Baru')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user']))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pelaku')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('action')
                    ->label('Aksi')
                    ->badge() // Tampilan badge agar lebih bagus
                    ->colors([
                        'success' => 'created',
                        'warning' => 'updated',
                        'danger' => 'deleted',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'created' => 'Dibuat',
                        'updated' => 'Diubah',
                        'deleted' => 'Dihapus',
                        default => ucfirst($state),
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('model')
                    ->label('Objek')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50) // Batasi panjang teks di tabel
                    ->tooltip(function (TextColumn $column): ?string {
                        return $column->getState();
                    }) // Munculkan teks asli saat di-hover
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->label('Waktu'),
            ])
            ->defaultSort('created_at', 'desc') // Urutkan dari yang terbaru
            ->filters([
                // 0. Filter berdasarkan kategori modul (Keuangan, Kegiatan, dst).
                SelectFilter::make('model')
                    ->label('Modul')
                    ->options([
                        Keuangan::class => 'Keuangan',
                        Kegiatan::class => 'Kegiatan',
                        Surat::class => 'Surat',
                        Anggota::class => 'Anggota',
                    ]),

                // 1. Filter berdasarkan Aksi (Action)
                SelectFilter::make('action')
                    ->label('Jenis Aksi')
                    ->options([
                        'created' => 'Dibuat',
                        'updated' => 'Diubah',
                        'deleted' => 'Dihapus',
                    ]),

                // 2. Filter berdasarkan User (Pelaku)
                // Gunakan closure agar query daftar user hanya dijalankan saat filter dirender,
                // bukan setiap kali LogResource dimuat.
                SelectFilter::make('user_id')
                    ->label('User')
                    ->options(fn () => User::pluck('name', 'id'))
                    ->searchable(),

                // 3. Filter berdasarkan rentang tanggal
                Filter::make('periode')
                    ->label('Periode Waktu')
                    ->form([
                        Forms\Components\DatePicker::make('dari')->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai')->label('Sampai Tanggal'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            filled($data['dari'] ?? null),
                            fn (Builder $q) => $q->whereDate('created_at', '>=', $data['dari'])
                        )
                        ->when(
                            filled($data['sampai'] ?? null),
                            fn (Builder $q) => $q->whereDate('created_at', '<=', $data['sampai'])
                        )
                    )
                    ->indicateUsing(function (array $data): array {
                        $indikator = [];
                        if (filled($data['dari'] ?? null)) {
                            $indikator[] = 'Dari: ' . $data['dari'];
                        }
                        if (filled($data['sampai'] ?? null)) {
                            $indikator[] = 'Sampai: ' . $data['sampai'];
                        }
                        return $indikator;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(), // Tombol untuk melihat detail (Infolist)
            ])
            ->bulkActions([
                // Biasanya log tidak boleh dihapus massal, jadi dikosongkan aman
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Grup Kolom Kiri: Detail Utama
                Group::make([
                    Section::make('Detail Log')
                        ->schema([
                            // Ganti 'causer.name' menjadi 'user.name'
                            TextEntry::make('user.name')
                                ->label('Dilakukan Oleh')
                                ->placeholder('Sistem / User Terhapus'),

                            // Ganti 'description' jadi 'action' (karena description blm ada di model Anda)
                            TextEntry::make('action')
                                ->label('Aksi')
                                ->badge()
                                ->colors([
                                    'success' => 'created',
                                    'warning' => 'updated',
                                    'danger'  => 'deleted',
                                ])
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    'created' => 'Dibuat',
                                    'updated' => 'Diubah',
                                    'deleted' => 'Dihapus',
                                    default => ucfirst($state),
                                }),

                            // Objek/model log ditampilkan eksplisit (Keuangan/Kegiatan)
                            // agar tidak salah label untuk log kegiatan operasional.
                            TextEntry::make('model')
                                ->label('Objek')
                                ->badge()
                                ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—'),

                            TextEntry::make('description')
                                ->label('Deskripsi')
                                ->placeholder('—')
                                ->columnSpanFull(),

                            TextEntry::make('created_at')
                                ->label('Waktu')
                                ->dateTime('d M Y, H:i:s'),
                        ])
                ])->columnSpan(['lg' => 1]),

                // Grup Kolom Kanan: Perbandingan Data
                Group::make([
                    Section::make('Perubahan Data')
                        ->schema([
                            // Data mentah (raw JSON) kolom before/after hanya boleh
                            // dibaca oleh admin. Peran pengurus hanya melihat
                            // metadata log (pelaku, aksi, objek, waktu).
                            KeyValueEntry::make('before')
                                ->label('Data Lama (Before)')
                                ->keyLabel('Kolom')
                                ->valueLabel('Nilai')
                                ->visible(fn ($state): bool => Auth::user()?->role === 'admin' && ! empty($state)),

                            KeyValueEntry::make('after')
                                ->label('Data Baru (After)')
                                ->keyLabel('Kolom')
                                ->valueLabel('Nilai')
                                ->visible(fn ($state): bool => Auth::user()?->role === 'admin' && ! empty($state)),
                        ])
                ])->columnSpan(['lg' => 2]),

            ])->columns(['lg' => 3]);
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
            'index' => Pages\ListLogs::route('/'),
            'view' => Pages\ViewLog::route('/{record}'),
        ];
    }

    // Fungsi ini untuk mencegah pembuatan log baru dari Filament
    public static function canCreate(): bool
    {
        return false;
    }

    // Opsional: Mencegah edit log agar data akurat
    public static function canEdit($record): bool
    {
        return false;
    }
}
