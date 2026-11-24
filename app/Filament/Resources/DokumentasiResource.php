<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DokumentasiResource\Pages;
use App\Models\Dokumentasi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DokumentasiResource extends Resource
{
    protected static ?string $model = Dokumentasi::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Manajemen Arsip';

    protected static ?string $navigationLabel = 'Dokumentasi';
    
    protected static ?string $pluralModelLabel = 'Dokumentasi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('kegiatan_id')
                    ->relationship('kegiatan', 'nama_kegiatan')
                    ->required(),
                Forms\Components\Select::make('tipe')
                    ->options(['foto' => 'Foto'])
                    ->required()
                    ->reactive(),
                Forms\Components\FileUpload::make('file_path')
                    ->directory('dokumentasi/file')
                    ->visibility('public')
                    ->disk('public')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->label('File Dokumentasi')
                    ->image()
                    ->imagePreviewHeight('250')
                    ->hint('Unggah file baru untuk menggantikan file yang sudah ada.'),
                Forms\Components\Textarea::make('deskripsi')
                    ->columnSpanFull()
                    ->required()
                    ->placeholder('Masukkan deskripsi dokumentasi'),
                // Forms\Components\Select::make('user_id')
                //     ->relationship('user', 'name')
                //     ->label('Diupload oleh')
                //     ->default(Auth::id())
                //     ->required(),
                    // ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kegiatan.nama_kegiatan')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('file_path')
                    ->label('Preview')
                    ->disk('public')
                    ->visible(fn ($record) => $record && $record->tipe === 'foto'),
                Tables\Columns\TextColumn::make('tipe')
                    ->badge(),
                Tables\Columns\TextColumn::make('deskripsi')
                    ->limit(50),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Dokumentasi')
                    ->modalDescription('Apakah Anda yakin ingin menghapus dokumentasi ini? Tindakan ini tidak dapat dibatalkan dan file akan dihapus dari penyimpanan.')
                    ->modalSubmitActionLabel('Ya, Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Dokumentasi Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus data yang dipilih? Semua file terkait akan dihapus dari penyimpanan.')
                        ->modalSubmitActionLabel('Ya, Hapus Semua'),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\TextEntry::make('kegiatan.nama_kegiatan'),
                Infolists\Components\TextEntry::make('tipe')->badge(),
                Infolists\Components\TextEntry::make('deskripsi'),
                Infolists\Components\TextEntry::make('user.name')
                    ->label('Diinput oleh'),
                Infolists\Components\ImageEntry::make('file_path')
                    ->label('Preview Gambar')
                    ->height(200)
                    ->width(250)
                    ->disk('public')
                    ->visible(fn ($record) => $record && $record->tipe === 'foto'),
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
            'index' => Pages\ListDokumentasis::route('/'),
            'create' => Pages\CreateDokumentasi::route('/create'),
            'edit' => Pages\EditDokumentasi::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // Menyertakan relasi 'kegiatan'
        return parent::getEloquentQuery()->with(['kegiatan']);
    }
}
