<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KeuanganResource\Pages;
use App\Models\Keuangan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class KeuanganResource extends Resource
{
    protected static ?string $model = Keuangan::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Manajemen Arsip';

    protected static ?string $navigationLabel = 'Keuangan'; 
    
    protected static ?string $pluralModelLabel = 'Keuangan'; 

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('kegiatan_id')
                    ->relationship('kegiatan', 'nama_kegiatan')
                    ->label('Untuk Kegiatan')
                    ->required(),
                Forms\Components\DatePicker::make('tanggal')
                    ->required(),
                Forms\Components\Select::make('jenis_transaksi')
                    ->options(['pemasukan' => 'Pemasukan', 'pengeluaran' => 'Pengeluaran'])
                    ->required(),
                Forms\Components\TextInput::make('jumlah')
                    ->required()
                    ->integer()
                    ->minValue(1)
                    ->prefix('Rp')
                    ->placeholder('Contoh: 100000')
                    ->validationMessages([
                        'required' => 'Jumlah transaksi wajib diisi.',
                        'integer' => 'Jumlah transaksi harus berupa angka.',
                        'min' => 'Jumlah transaksi minimal Rp 1.',
                    ]),
                Forms\Components\Textarea::make('keterangan')
                    ->columnSpanFull()
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Masukkan keterangan transaksi'),
                Forms\Components\FileUpload::make('bukti_file')
                    ->directory('keuangan/bukti')
                    ->visibility('public')
                    ->disk('public')
                    ->label('File Bukti')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(10240)
                    ->validationMessages([
                        'mimetypes' => 'File Bukti harus berformat PDF, JPG, atau PNG.',
                        'max' => 'Ukuran File Bukti maksimal 10 MB.',
                        'required' => 'File Bukti transaksi wajib diunggah.',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['kegiatan']))
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('kegiatan.nama_kegiatan')
                    ->default('Umum')
                    ->searchable(),
                Tables\Columns\TextColumn::make('keterangan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('jenis_transaksi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pemasukan' => 'success',
                        'pengeluaran' => 'warning',
                    }),
                Tables\Columns\TextColumn::make('jumlah')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_transaksi')
                    ->label('Jenis Transaksi')
                    ->options([
                        'pemasukan' => 'Pemasukan',
                        'pengeluaran' => 'Pengeluaran',
                    ]),

                Tables\Filters\SelectFilter::make('kegiatan_id')
                    ->label('Kegiatan')
                    ->relationship('kegiatan', 'nama_kegiatan')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('jumlah')
                    ->label('Rentang Jumlah')
                    ->form([
                        Forms\Components\TextInput::make('jumlah_min')
                            ->label('Jumlah Minimal')
                            ->numeric()
                            ->prefix('Rp'),
                        Forms\Components\TextInput::make('jumlah_max')
                            ->label('Jumlah Maksimal')
                            ->numeric()
                            ->prefix('Rp'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            filled($data['jumlah_min'] ?? null),
                            fn (Builder $q) => $q->where('jumlah', '>=', $data['jumlah_min'])
                        )
                        ->when(
                            filled($data['jumlah_max'] ?? null),
                            fn (Builder $q) => $q->where('jumlah', '<=', $data['jumlah_max'])
                        )
                    )
                    ->indicateUsing(function (array $data): array {
                        $indikator = [];
                        if (filled($data['jumlah_min'] ?? null)) {
                            $indikator[] = 'Min: Rp ' . number_format((float) $data['jumlah_min'], 0, ',', '.');
                        }
                        if (filled($data['jumlah_max'] ?? null)) {
                            $indikator[] = 'Maks: Rp ' . number_format((float) $data['jumlah_max'], 0, ',', '.');
                        }
                        return $indikator;
                    }),

                Tables\Filters\Filter::make('periode')
                    ->label('Periode Tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari')->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai')->label('Sampai Tanggal'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            filled($data['dari'] ?? null),
                            fn (Builder $q) => $q->whereDate('tanggal', '>=', $data['dari'])
                        )
                        ->when(
                            filled($data['sampai'] ?? null),
                            fn (Builder $q) => $q->whereDate('tanggal', '<=', $data['sampai'])
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Keuangan')
                    ->schema([
                        Infolists\Components\TextEntry::make('kegiatan.nama_kegiatan'),
                        Infolists\Components\TextEntry::make('tanggal')->date('d M Y'),
                        Infolists\Components\TextEntry::make('jenis_transaksi')->badge(),
                        Infolists\Components\TextEntry::make('jumlah')
                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.')),
                        Infolists\Components\TextEntry::make('keterangan'),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Diinput oleh'),
                    ])->columns(2),

                Infolists\Components\Section::make('Preview File Bukti')
                    ->schema([
                        // Ini akan memanggil view pdf
                        Infolists\Components\ViewEntry::make('bukti_file')
                            ->view('filament.infolists.components.pdf-viewer')
                            ->label('') // Kosongkan label agar tidak muncul judul
                    ])
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
            'index' => Pages\ListKeuangans::route('/'),
            'create' => Pages\CreateKeuangan::route('/create'),
            'edit' => Pages\EditKeuangan::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['kegiatan', 'user']);
    }
}
