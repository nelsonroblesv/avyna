<?php

namespace App\Filament\App\Resources;

use App\Enums\CfdiTypeEnum;
use App\Enums\SociedadTypeEnum;
use App\Filament\App\Resources\CustomerResource\Pages;
use App\Filament\App\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use App\Models\Municipality;
use App\Models\State;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter as FiltersFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Administrar';
    protected static ?string $navigationLabel = 'Clientes';
    protected static ?string $breadcrumb = "Clientes";
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Basicos')
                        ->description('Informacion Personal')
                        ->schema([
                            
                         Hidden::make('user_id')->default(fn() => auth()->id()),

                            Select::make('zone_id')
                                ->relationship('zone', 'name')
                                ->label('Zona asignada:')
                                ->required(),

                            TextInput::make('name')
                                ->label('Nombre completo')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->suffixIcon('heroicon-m-user'),

                            TextInput::make('alias')
                                ->label('Alias')
                                //->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->suffixIcon('heroicon-m-user-circle'),

                            TextInput::make('email')
                                ->label('Correo electrónico')
                                ->email()
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255)
                                ->suffixIcon('heroicon-m-at-symbol'),

                            TextInput::make('phone')
                                ->label('Teléfono')
                                ->tel()
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(50)
                                ->suffixIcon('heroicon-m-phone'),

                            DatePicker::make('birthday')
                                ->label('Fecha de nacimiento')
                                ->suffixIcon('heroicon-m-cake')
                                ->required(),

                            Select::make('type')
                                ->label('Tipo')
                                ->required()
                                ->options([
                                    'par' => 'Par',
                                    'non' => 'Non'
                                ]),

                            FileUpload::make('avatar')
                                ->label('Avatar')
                                ->image()
                                ->avatar()
                                ->imageEditor()
                                ->circleCropper()
                                ->directory('customer-avatar')
                        ])->columns(2),

                    Wizard\Step::make('Negocio')
                        ->description('Informacion del establecimiento')
                        ->schema([
                            TextInput::make('address')
                                ->label('Dirección')
                                ->helperText('Calle, Núm. Ext., Núm. Int., Colonia, Intersecciones')
                                // ->required()
                                ->maxLength(255)
                                ->suffixIcon('heroicon-m-map')
                                ->columnSpanFull(),

                            Select::make('state_id')
                                ->label('Estado')
                                ->options(State::query()->pluck('name', 'id'))
                                ->reactive()
                                ->searchable()
                                ->preload(),
                            //->required()
                            // ->afterStateUpdated(fn(callable $set) => $set('municipality_id', null)),

                            Select::make('municipality_id')
                                ->label('Municipio')
                                ->options(function (callable $get) {
                                    $stateId = $get('state_id');

                                    if (!$stateId) {
                                        return [];
                                    }

                                    return Municipality::whereHas('state', function ($query) use ($stateId) {
                                        $query->where('state_id', $stateId);
                                    })->pluck('name', 'id');
                                })
                                ->disabled(function (callable $get) {
                                    return !$get('state_id');
                                })
                                ->reactive()
                                //  ->required()
                                ->searchable()
                                ->preload(),

                            TextInput::make('locality')
                                //  ->required()
                                ->label('Localidad'),

                            TextInput::make('zip_code')
                                ->label('Código Postal')
                                //   ->required()
                                ->numeric()
                                ->maxLength(5)
                                ->suffixIcon('heroicon-m-hashtag'),

                            TextInput::make('coordinate')
                                ->label('Coordenadas Google Maps')
                                ->helperText('Formato: 20.1845751, -90.1334567')
                                //   ->required()
                                ->maxLength(255)
                                ->suffixIcon('heroicon-m-map-pin'),

                            Section::make('Fotos del establecimiento')
                                ->collapsible()
                                ->schema([
                                    FileUpload::make('front_image')
                                        ->label('Foto Exterior')
                                        ->helperText('Carga una foto del exterior del establecimiento')
                                        ->image()
                                        //  ->required()
                                        ->imageEditor()
                                        ->directory('customer-images'),

                                    FileUpload::make('inside_image')
                                        ->label('Foto Interior')
                                        ->helperText('Carga una foto del interior del establecimiento')
                                        ->image()
                                        //   ->required()
                                        ->imageEditor()
                                        ->directory('customer-images'),
                                ])->columns(2),

                            Section::make('Informacion adicional')
                                ->collapsible()
                                ->schema([
                                    MarkdownEditor::make('extra')
                                        //  ->required()
                                        ->label('Datos extra del cliente')
                                ])->icon('heroicon-o-information-circle')
                        ])->columns(2),

                    Wizard\Step::make('Fiscales')
                        ->description('Datos de facturacion')
                        ->schema([
                            Section::make('Cliente con facturacion')
                                ->description('Despliega unicamente si el cliente cuenta con datos de facturacion')
                                ->schema([
                                TextInput::make('name_facturacion')
                                    ->label('Nombre')
                                    //   ->required()
                                    ->suffixIcon('heroicon-m-user-circle'),

                                TextInput::make('razon_social')
                                    ->label('Razon Social')
                                    //   ->required()
                                    ->suffixIcon('heroicon-m-building-library'),

                                TextInput::make('address_facturacion')
                                    ->label('Direccion')
                                    //   ->required()
                                    ->suffixIcon('heroicon-m-map-pin'),

                                TextInput::make('postal_code_facturacion')
                                    ->label('Codigo Postal')
                                    ->numeric()
                                    //   ->required()
                                    ->suffixIcon('heroicon-m-hashtag'),

                                Select::make('tipo_cfdi')
                                    ->label('Tipo de CFDI')
                                    ->options([
                                        'Ingreso' => CfdiTypeEnum::INGRESO->value,
                                        'Egreso' => CfdiTypeEnum::EGRESO->value,
                                        'Traslado' => CfdiTypeEnum::TRASLADO->value,
                                        'Nomina' => CfdiTypeEnum::NOMINA->value
                                    ])
                                    ->suffixIcon('heroicon-m-document-text'),

                                Select::make('tipo_razon_social')
                                    ->label('Tipo de Razon Social')
                                    ->options([
                                        'Sociedad Anonima' => SociedadTypeEnum::S_ANONIMA->value,
                                        'Sociedad Civil' => SociedadTypeEnum::S_CIVIL->value,
                                    ])
                                    ->suffixIcon('heroicon-m-document-text'),

                                FileUpload::make('cfdi_document')
                                    ->columnSpanFull()
                                    ->label('CFDI')
                                    ->helperText('Carga un CFDI en formato PDF')
                                    //   ->required()
                                    ->directory('customer-cfdi')
                            ])->collapsed()

                        ])->columns(2),                            
                               
                ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Clientes')
            ->description('Gestion de clientes.')
           ->modifyQueryUsing(fn (Builder $query) => $query->where('user_id', auth()->id()))
            ->columns([
                ImageColumn::make('avatar')
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),

                TextColumn::make('birthday')
                    ->label('Fecha de nacimiento')
                    ->date()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('alias')
                    ->label('Alias')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->colors([
                        'info' => 'par',
                        'warning' => 'non',
                    ]),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('Telefono')
                    ->searchable(),

                TextColumn::make('address')
                    ->label('Direccion')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('state.name')
                    ->label('Estado')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('municipality.name')
                    ->label('Municipio')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('locality')
                    ->label('Localidad')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('zip_code')
                    ->label('Codigo postal')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                ImageColumn::make('front_image')
                    ->label('Exterior')
                    ->toggleable(isToggledHiddenByDefault: true),

                ImageColumn::make('inside_image')
                    ->label('Interior')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('coordinate')
                    ->label('Coordenadas')
                    ->url(fn(Customer $record): string => "http://maps.google.com/maps?q=loc: {$record->coordinate}")
                    ->icon('heroicon-o-map-pin')
                    ->iconColor('danger')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_visible')
                    ->label('Visible')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Fecha registro')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
            ])
            ->filters([
                FiltersFilter::make('is_active')
                    ->query(fn(Builder $query): Builder => $query->where('is_active', true))
                    ->label('Activos')
                    ->toggle(),

                FiltersFilter::make('type')
                    ->query(fn(Builder $query): Builder => $query->where('type', 'par'))
                    ->label('Pares')
                    ->toggle()
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                  //  Tables\Actions\DeleteAction::make()
                ])
            ])
            ->bulkActions([
               
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
            'view' => Pages\ViewCustomer::route('/{record}'),
        ];
    }
}
