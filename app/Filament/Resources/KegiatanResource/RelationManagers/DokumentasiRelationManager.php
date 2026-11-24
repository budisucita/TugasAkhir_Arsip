<?php

namespace App\Filament\Resources\KegiatanResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class DokumentasiRelationManager extends RelationManager
{
    protected static string $relationship = 'dokumentasi';

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
                    ->label('Diupload oleh')
                    ->default(Auth::id())
                    ->disabled()
                    ->dehydrated(),
                Forms\Components\Select::make('tipe')
                    ->options(['foto' => 'Foto', 'video' => 'Video'])
                    ->required(),
                Forms\Components\FileUpload::make('file_path')
                    ->directory('dokumentasi/file')
                    ->visibility('public')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'video/mp4'])
                    ->maxSize(20480)
                    ->validationMessages([
                        'mimetypes' => 'File dokumentasi harus berformat JPG, PNG, atau MP4.',
                        'max' => 'Ukuran file dokumentasi maksimal 20 MB.',
                        'required' => 'File dokumentasi wajib diunggah.',
                    ])
                    ->required()
                    ->label('File'),
                Forms\Components\Textarea::make('deskripsi')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('deskripsi')
            ->columns([
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
                Tables\Filters\SelectFilter::make('tipe')
                    ->label('Tipe')
                    ->options([
                        'foto' => 'Foto',
                        'video' => 'Video',
                    ]),
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
