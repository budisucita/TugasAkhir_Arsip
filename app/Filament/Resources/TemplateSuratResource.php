<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TemplateSuratResource\Pages;
use App\Filament\Resources\TemplateSuratResource\RelationManagers;
use App\Models\TemplateSurat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;

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
                    ->disk('public')
                    ->preserveFilenames()
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ])
                    ->maxSize(10240)
                    ->required(),
            ]);
     } 

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Tables\Columns\TextColumn::make('nama_dokumen')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('file_path')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('created_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('updated_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('nama_dokumen')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('download')
                    ->label('Download')
                    ->url(fn ($record) => asset('storage/' . $record->file_path))
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
