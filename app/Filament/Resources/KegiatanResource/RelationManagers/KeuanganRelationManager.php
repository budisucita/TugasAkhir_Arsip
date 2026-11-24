<?php

namespace App\Filament\Resources\KegiatanResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class KeuanganRelationManager extends RelationManager
{
    protected static string $relationship = 'keuangan';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('kegiatan_id')
                    ->relationship('kegiatan', 'nama_kegiatan')
                    ->label('Untuk Kegiatan')
                    ->default(fn (RelationManager $livewire): int => $livewire->ownerRecord->id)
                    ->disabled()
                    ->dehydrated(),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Diinput oleh')
                    ->default(Auth::id())
                    ->disabled()
                    ->dehydrated(),
                Forms\Components\DatePicker::make('tanggal')
                    ->required(),
                Forms\Components\Select::make('jenis_transaksi')
                    ->options(['pemasukan' => 'Pemasukan', 'pengeluaran' => 'Pengeluaran'])
                    ->required(),
                Forms\Components\TextInput::make('jumlah')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->prefix('Rp')
                    ->validationMessages([
                        'required' => 'Jumlah transaksi wajib diisi.',
                        'numeric' => 'Jumlah transaksi harus berupa angka.',
                        'min' => 'Jumlah transaksi minimal Rp 1.',
                    ]),
                Forms\Components\Textarea::make('keterangan')
                    ->columnSpanFull()
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('bukti_file')
                    ->directory('keuangan/bukti')
                    ->visibility('public')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(10240)
                    ->validationMessages([
                        'mimetypes' => 'File Bukti harus berformat PDF, JPG, atau PNG.',
                        'max' => 'Ukuran File Bukti maksimal 10 MB.',
                        'required' => 'File Bukti transaksi wajib diunggah.',
                    ])
                    ->label('File Bukti')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('keterangan')
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')->date()->sortable(),
                Tables\Columns\TextColumn::make('jenis_transaksi')->badge()->color(fn (string $state): string => match ($state) {
                    'pemasukan' => 'success',
                    'pengeluaran' => 'warning',
                }),
                Tables\Columns\TextColumn::make('jumlah')->money('IDR'),
                Tables\Columns\TextColumn::make('keterangan')->limit(50),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
