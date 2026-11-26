<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SuratResource\Pages;
use App\Models\Surat;
use App\Support\SecureFile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class SuratResource extends Resource
{
    protected static ?string $model = Surat::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Manajemen Arsip';

    protected static ?string $navigationLabel = 'Surat'; 
    
    protected static ?string $pluralModelLabel = 'Surat'; 

    /**
     * Bulan dalam angka Romawi untuk kode bulan pada nomor surat.
     *
     * @var array<int, string>
     */
    private const BULAN_ROMAWI = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
        7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
    ];

    /**
     * Kode jenis surat berdasarkan statusnya.
     *
     * @var array<string, string>
     */
    private const KODE_JENIS_SURAT = [
        'masuk' => 'SM',
        'keluar' => 'ST-DB',
    ];



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('status')
                    ->options(['masuk' => 'Surat Masuk', 'keluar' => 'Surat Keluar'])
                    ->required(),
                Forms\Components\TextInput::make('no_surat')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true)
                    ->placeholder('Nomor Surat')
                    ->helperText('Gunakan tombol "Generate" untuk membuat nomor surat otomatis berformat [urut]/[jenis]/[bulan]/[tahun].')
                    ->suffixAction(
                        Forms\Components\Actions\Action::make('generate_nomor')
                            ->label('Generate')
                            ->icon('heroicon-m-sparkles')
                            ->action(function (Get $get, Set $set): void {
                                $set('no_surat', static::generateNomorSurat($get('status')));
                            })
                    ),
                Forms\Components\TextInput::make('pengirim')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('Nama Pengirim / Instansi'),
                Forms\Components\DatePicker::make('tanggal_terima')
                    ->required(),
                Forms\Components\Textarea::make('perihal')
                    ->required()
                    ->columnSpanFull()
                    ->placeholder('Perihal Surat'),
                Forms\Components\FileUpload::make('file_surat')
                    ->required()
                    ->directory('surat/file')
                    ->visibility('private')
                    ->disk(SecureFile::diskName())
                    ->getUploadedFileUsing(SecureFile::uploadedFileUsing())
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(10240)
                    ->validationMessages([
                        'mimetypes' => 'File Surat harus berformat PDF.',
                        'max' => 'Ukuran File Surat maksimal 10 MB.',
                        'required' => 'File Surat wajib diunggah.',
                    ])
                    ->label('File Surat (PDF)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('no_surat')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pengirim')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tanggal_terima')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('perihal')
                    ->limit(50),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Jenis Surat')
                    ->options([
                        'masuk' => 'Surat Masuk',
                        'keluar' => 'Surat Keluar',
                    ]),

                Tables\Filters\Filter::make('pengirim')
                    ->form([
                        Forms\Components\TextInput::make('pengirim')
                            ->label('Pengirim / Instansi')
                            ->placeholder('Cari berdasarkan pengirim'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            filled($data['pengirim'] ?? null),
                            fn (Builder $q) => $q->where('pengirim', 'like', '%' . $data['pengirim'] . '%')
                        )
                    )
                    ->indicateUsing(fn (array $data): ?string => filled($data['pengirim'] ?? null)
                        ? 'Pengirim: ' . $data['pengirim']
                        : null),

                Tables\Filters\Filter::make('periode')
                    ->label('Periode Tanggal Terima')
                    ->form([
                        Forms\Components\DatePicker::make('dari')->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai')->label('Sampai Tanggal'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            filled($data['dari'] ?? null),
                            fn (Builder $q) => $q->whereDate('tanggal_terima', '>=', $data['dari'])
                        )
                        ->when(
                            filled($data['sampai'] ?? null),
                            fn (Builder $q) => $q->whereDate('tanggal_terima', '<=', $data['sampai'])
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
                Infolists\Components\Section::make('Informasi Surat')
                    ->schema([
                        Infolists\Components\TextEntry::make('status'),
                        Infolists\Components\TextEntry::make('no_surat'),
                        Infolists\Components\TextEntry::make('pengirim'),
                        Infolists\Components\TextEntry::make('tanggal_terima')
                            ->date('d M Y'),
                        Infolists\Components\TextEntry::make('perihal')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Diinput oleh'),
                    ])->columns(2),
                
                Infolists\Components\Section::make('Preview File Surat')
                    ->schema([
                        // Ini akan memanggil view pdf
                        Infolists\Components\ViewEntry::make('file_surat')
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
            'index' => Pages\ListSurats::route('/'),
            'create' => Pages\CreateSurat::route('/create'),
            'edit' => Pages\EditSurat::route('/{record}/edit'),
        ];
    }

    /**
     * Hasilkan nomor surat otomatis berformat:
     * [nomor_urut]/[kode_jenis_surat]/[kode_bulan]/[tahun]
     * (contoh: 001/ST-DB/XI/2026).
     *
     * Nomor urut dihitung dari nomor surat tertinggi pada bulan & tahun
     * berjalan sehingga tidak terjadi duplikasi, meski beberapa draft dibuat
     * secara berurutan.
     */
    public static function generateNomorSurat(?string $status): string
    {
        $now = now();
        $tahun = (int) $now->year;
        $kodeBulan = self::BULAN_ROMAWI[(int) $now->month] ?? 'I';
        $kodeJenis = self::KODE_JENIS_SURAT[$status] ?? 'ST-DB';

        // Cari nomor urut tertinggi untuk bulan & tahun berjalan.
        $max = 0;

        Surat::query()
            ->where('no_surat', 'like', '%/' . $kodeBulan . '/' . $tahun)
            ->pluck('no_surat')
            ->each(function ($nomor) use (&$max): void {
                if (preg_match('#^(\d+)/#', (string) $nomor, $matches)) {
                    $max = max($max, (int) $matches[1]);
                }
            });

        // Naikkan urutan sampai menemukan nomor yang benar-benar belum dipakai.
        $urut = $max + 1;

        do {
            $kandidat = str_pad((string) $urut, 3, '0', STR_PAD_LEFT)
                . '/' . $kodeJenis . '/' . $kodeBulan . '/' . $tahun;
            $urut++;
        } while (Surat::where('no_surat', $kandidat)->exists());

        return $kandidat;
    }
}
