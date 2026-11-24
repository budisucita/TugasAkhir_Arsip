<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KegiatanResource\Pages;
use App\Filament\Resources\KegiatanResource\RelationManagers;
use App\Models\Kegiatan;
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


class KegiatanResource extends Resource
{
    protected static ?string $model = Kegiatan::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Manajemen Acara';

    protected static ?string $navigationLabel = 'Kegiatan'; 
    
    protected static ?string $pluralModelLabel = 'Kegiatan'; 

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Forms\Components\Select::make('user_id')
                //     ->relationship('user', 'name')
                //     ->label('Penanggung Jawab')
                //     ->default(Auth::id())
                //     ->required(),
                Forms\Components\TextInput::make('nama_kegiatan')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('Masukkan nama kegiatan'),
                Forms\Components\Select::make('status')
                    ->options([
                        'Direncanakan' => 'Direncanakan',
                        'Berlangsung' => 'Berlangsung',
                        'Selesai' => 'Selesai',
                        'Dibatalkan' => 'Dibatalkan',
                    ])
                    ->required(),
                Forms\Components\DatePicker::make('tanggal_mulai')
                    ->required(),
                Forms\Components\DatePicker::make('tanggal_selesai')
                    ->minDate(fn (Forms\Get $get) => $get('tanggal_mulai'))
                    ->afterOrEqual('tanggal_mulai'),
                Forms\Components\RichEditor::make('deskripsi')
                    ->columnSpanFull()
                    ->required(),
                Forms\Components\FileUpload::make('proposal_file')
                    ->directory('kegiatan/proposal')
                    ->visibility('public')
                    ->disk('public')
                    ->label('File Proposal'),
                Forms\Components\FileUpload::make('rab_file')
                    ->directory('kegiatan/rab')
                    ->visibility('public')
                    ->disk('public')
                    ->label('File RAB'),
                Forms\Components\FileUpload::make('lpj_file')
                    ->directory('kegiatan/lpj')
                    ->visibility('public')
                    ->disk('public')
                    ->label('File LPJ'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_kegiatan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Direncanakan' => 'gray',
                        'Berlangsung' => 'warning',
                        'Selesai' => 'success',
                        'Dibatalkan' => 'danger',
                    }),
                // Tables\Columns\TextColumn::make('user.name')
                //     ->label('Penanggung Jawab')
                //     ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_mulai')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('proposal_file')
                    ->boolean()
                    ->label('Proposal'),
                Tables\Columns\IconColumn::make('rab_file')
                    ->boolean()
                    ->label('RAB'),
                Tables\Columns\IconColumn::make('lpj_file')
                    ->boolean()
                    ->label('LPJ'),
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
            RelationManagers\KeuanganRelationManager::class,
            RelationManagers\DokumentasiRelationManager::class, 
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Detail Kegiatan')
                    ->schema([
                        Infolists\Components\TextEntry::make('nama_kegiatan'),
                        // Infolists\Components\TextEntry::make('user.name')
                        //     ->label('Penanggung Jawab'),
                        Infolists\Components\TextEntry::make('tanggal_mulai')
                            ->date(),
                        Infolists\Components\TextEntry::make('tanggal_selesai')
                            ->date(),
                        Infolists\Components\TextEntry::make('deskripsi')
                            ->columnSpanFull()
                            ->html(),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Diinput oleh'),
                    ])->columns(2),

                Infolists\Components\Section::make('Preview File Kegiatan')
                    ->schema([
                        // Ini akan memanggil view pdf
                        Infolists\Components\ViewEntry::make('proposal_file')
                            ->view('filament.infolists.components.pdf-viewer')
                            ->label('Proposal'),
                        Infolists\Components\ViewEntry::make('rab_file')
                            ->view('filament.infolists.components.pdf-viewer')
                            ->label('RAB'),
                        Infolists\Components\ViewEntry::make('lpj_file')
                            ->view('filament.infolists.components.pdf-viewer')
                            ->label('LPJ'),
                    ])
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKegiatans::route('/'),
            'create' => Pages\CreateKegiatan::route('/create'),
            'edit' => Pages\EditKegiatan::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // Menyertakan relasi 'user'
        return parent::getEloquentQuery()->with(['user']);
    }
}
