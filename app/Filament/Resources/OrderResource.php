<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use App\Enums\OrderStatusEnum;
use App\Models\OrderItem;
use App\Models\Product;
use Faker\Core\Number;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Factories\Relationship;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Administrar';
    protected static ?string $navigationLabel = 'Ordenes';
    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Detalles de la orden')
                        ->schema([
                            Forms\Components\Select::make('customer_id')
                                ->relationship('customer', 'name')
                                ->disabledOn('edit')
                                ->searchable()
                                ->preload()
                                ->required()
                                /*->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->required(),
                                    Forms\Components\TextInput::make('email')
                                        ->required()
                                        ->email(),
                                ])*/,
                                Forms\Components\TextInput::make('number')
                                ->required()
                                ->disabled()
                                ->default('OR-'.random_int(100000, 9999999))
                                ->dehydrated()
                                ->maxLength(255),
                           
                                Forms\Components\ToggleButtons::make('status')
                                ->required()
                                ->inline()
                                ->options([
                                    'pending' => OrderStatusEnum::PENDING->value,
                                    'completed' => OrderStatusEnum::COMPLETED->value,
                                    'processing' => OrderStatusEnum::PROCESSING->value,
                                    'declined' => OrderStatusEnum::DECLINED->value
                                ])
                                ->colors([
                                    'pending' => 'info',
                                    'processing' => 'warning',
                                    'completed' => 'success',
                                    'declined' => 'danger',
                                ])
                                ->icons([
                                    'pending' => 'heroicon-o-exclamation-circle',
                                    'processing' => 'heroicon-o-arrow-path',
                                    'completed' => 'heroicon-o-check',
                                    'declined' => 'heroicon-o-x-mark'
                                ])
                                ->default('pending')
                                ->columnSpanFull(),
                        ])->columns(2),

                    Step::make('Productos')
                        ->schema([
                           Repeater::make('items')
                           ->relationship()
                            ->schema([
                                Select::make('product_id')
                                ->relationship('product', 'name')
                                ->label('Productos')
                                ->preload()
                                ->searchable()
                                ->required()
                                ->reactive()
                                ->distinct()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->columnSpan(6)
                                ->afterStateUpdated(fn ($state, Set $set) => 
                                                    $set('unit_price', Product::find($state)?->price ?? 0))
                                ->afterStateUpdated(fn ($state, Set $set) => 
                                                    $set('total_price', Product::find($state)?->price ?? 0)),

                            TextInput::make('quantity')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->live()
                                ->dehydrated()
                                ->columnSpan(1)
                                ->reactive()
                                ->required()
                                ->afterStateUpdated(fn($state, Set $set, Get $get) => $set('total_price', $state * $get('unit_price'))),
                            
                                TextInput::make('unit_price')
                                    ->label('Precio unitario')
                                    ->disabled()
                                    ->dehydrated()
                                    ->numeric()
                                    ->columnSpan(2)
                                    ->inputMode('decimal')
                                    ->required()
                                    ->extraInputAttributes(['style' => 'text-align:right']),

                                TextInput::make('total_price')
                                    ->label('Precio total')
                                    ->numeric()
                                    ->inputMode('decimal')
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(3)
                                    ->prefixIcon('heroicon-m-currency-dollar')
                                    ->prefixIconColor('success')
                                    ->extraInputAttributes(['style' => 'text-align:right'])
                            ])->columns(12),

                        Placeholder::make('grand_total')
                            ->label('Total a pagar')
                            ->content(function(Get $get, Set $set){
                                $total = 0;
                                if(!$repeaters = $get('items')){
                                    return $total;
                                }
                                foreach($repeaters as $key => $repeater){
                                    $total += $get("items.{$key}.total_price");
                                }
                                
                                $set('grand_total', $total);
                                return  '$'.number_format($total, 2);
                            })
                            ->extraAttributes(['style' => 'text-align:right']),

                            Hidden::make('grand_total')
                            ->default(0),
                            
                            MarkdownEditor::make('notes')
                                ->columnSpanFull()

                        ])->columnSpanFull(),
                        
                ])->columnSpanFull()

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                ->label('Num. Orden')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->colors([
                        'primary',
                        'info' => 'pending',
                        'warning' => 'processing',
                        'success' => 'completed',
                        'danger' => 'declined',
                    ])
                    ->icons([
                        'heroicon-o-x',
                        'heroicon-o-exclamation-circle' => 'pending',
                        'heroicon-o-x-mark' => 'declined',
                        'heroicon-o-check' => 'completed',
                        'heroicon-o-arrow-path' => 'processing'
                    ]),
                TextColumn::make('notes')
                    ->label('Notas')
                    ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('grand_total')
                    ->money('USD')
                    ->label('Total'),
                    //->icon('heroicon-m-currency-dollar')
                    //->iconColor('success'),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                ])
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}