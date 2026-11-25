<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TemplateSuratResource\Pages;
use App\Models\TemplateSurat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class TemplateSuratResource extends Resource
{
    protected static ?string $model = TemplateSurat::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'Manajemen Arsip';

    protected static ?string $navigationLabel = 'Template Surat';
    
    protected static ?string $pluralModelLabel = 'Template Surat';

    public static function form(Form $form): Form
    
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_dokumen')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Nama Dokumen Template'),
                Forms\Components\FileUpload::make('file_path')
                    ->label('File Template')
                    ->directory('template-surat')
                    ->visibility('public')
                    ->disk(config('filesystems.default'))
                    // Tanpa preserveFilenames(): Filament menghasilkan nama berkas
                    // unik (ULID) sehingga unggahan dengan nama sama tidak menimpa
                    // berkas lama secara diam-diam di penyimpanan.
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ])
                    ->maxSize(10240)
                    ->validationMessages([
                        'mimetypes' => 'File Template harus berformat PDF, DOC, atau DOCX.',
                        'max' => 'Ukuran File Template maksimal 10 MB.',
                        'required' => 'File Template wajib diunggah.',
                    ])
                    ->required(),
            ]);
     } 

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_dokumen')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('nama_dokumen')
                    ->form([
                        Forms\Components\TextInput::make('nama_dokumen')
                            ->label('Nama Dokumen')
                            ->placeholder('Cari berdasarkan nama dokumen'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            filled($data['nama_dokumen'] ?? null),
                            fn (Builder $q) => $q->where('nama_dokumen', 'like', '%' . $data['nama_dokumen'] . '%')
                        )
                    )
                    ->indicateUsing(fn (array $data): ?string => filled($data['nama_dokumen'] ?? null)
                        ? 'Nama: ' . $data['nama_dokumen']
                        : null),

                Tables\Filters\Filter::make('periode')
                    ->label('Periode Unggah')
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
                Tables\Actions\EditAction::make(),
                Action::make('download')
                    ->label('Download')
                    ->url(fn ($record) => filled($record->file_path)
                        ? Storage::disk(config('filesystems.default'))->url($record->file_path)
                        : null)
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListTemplateSurats::route('/'),
            'create' => Pages\CreateTemplateSurat::route('/create'),
            'edit' => Pages\EditTemplateSurat::route('/{record}/edit'),
        ];
    }
}
