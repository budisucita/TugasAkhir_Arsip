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
                    ->date()
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
                //
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
                        Infolists\Components\TextEntry::make('tanggal')->date(),
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
