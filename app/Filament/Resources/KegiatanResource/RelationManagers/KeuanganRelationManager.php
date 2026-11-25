<?php

namespace App\Filament\Resources\KegiatanResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Support\SecureFile;

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
                    ->visibility('private')
                    ->disk(SecureFile::diskName())
                    ->getUploadedFileUsing(SecureFile::uploadedFileUsing())
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
                Tables\Columns\TextColumn::make('tanggal')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('jenis_transaksi')->badge()->color(fn (string $state): string => match ($state) {
                    'pemasukan' => 'success',
                    'pengeluaran' => 'warning',
                }),
                Tables\Columns\TextColumn::make('jumlah')->money('IDR'),
                Tables\Columns\TextColumn::make('keterangan')->limit(50),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_transaksi')
                    ->label('Jenis Transaksi')
                    ->options([
                        'pemasukan' => 'Pemasukan',
                        'pengeluaran' => 'Pengeluaran',
                    ]),

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
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
