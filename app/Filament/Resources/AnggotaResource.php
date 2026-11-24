<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnggotaResource\Pages;
use App\Models\Anggota;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AnggotaResource extends Resource
{
    protected static ?string $model = Anggota::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Anggota'; 
    
    protected static ?string $pluralModelLabel = 'Anggota'; 

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('Masukkan nama lengkap'),
                Forms\Components\TextInput::make('kontak')
                    ->tel()
                    ->maxLength(50)
                    ->required()
                    ->placeholder('Contoh: 081234567890'),
                Forms\Components\Select::make('status')
                    ->options(['aktif' => 'Aktif', 'tidak aktif' => 'Tidak Aktif'])
                    ->required(),
                Forms\Components\Textarea::make('alamat')
                    ->columnSpanFull()
                    ->required()
                    ->placeholder('Masukkan alamat lengkap'),
                Forms\Components\FileUpload::make('foto')
                    ->image()
                    ->directory('anggota/foto')
                    ->visibility('public')
                    ->maxSize(5120)
                    ->validationMessages([
                        'image' => 'File Foto harus berupa gambar.',
                        'max' => 'Ukuran Foto maksimal 5 MB.',
                        'required' => 'Foto anggota wajib diunggah.',
                    ])
                    ->label('Foto Anggota')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('foto')
                    ->circular(),
                Tables\Columns\TextColumn::make('nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('kontak')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'aktif' => 'success',
                        'tidak aktif' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnggotas::route('/'),
            'create' => Pages\CreateAnggota::route('/create'),
            'edit' => Pages\EditAnggota::route('/{record}/edit'),
        ];
    }
}
