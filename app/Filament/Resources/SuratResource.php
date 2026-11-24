<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SuratResource\Pages;
use App\Filament\Resources\SuratResource\RelationManagers;
use App\Models\Surat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class SuratResource extends Resource
{
    protected static ?string $model = Surat::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Manajemen Arsip';

    protected static ?string $navigationLabel = 'Surat'; 
    
    protected static ?string $pluralModelLabel = 'Surat'; 

    

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
                    ->placeholder('Nomor Surat'),
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
                    ->visibility('public')
                    ->label('File Surat (PDF)'),
                // Forms\Components\Select::make('user_id')
                //     ->relationship('user', 'name')
                //     ->label('Diinput oleh')
                //     ->default(Auth::id())
                //     ->required(),
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
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('perihal')
                    ->limit(50),
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
                Infolists\Components\Section::make('Informasi Surat')
                    ->schema([
                        Infolists\Components\TextEntry::make('status'),
                        Infolists\Components\TextEntry::make('no_surat'),
                        Infolists\Components\TextEntry::make('pengirim'),
                        Infolists\Components\TextEntry::make('tanggal_terima')
                            ->date(),
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
}
