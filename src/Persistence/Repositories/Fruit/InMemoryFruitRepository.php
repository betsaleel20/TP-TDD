<?php

namespace App\Persistence\Repositories\Fruit;

use App\Application\Entities\Fruit\Fruit;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Enums\FruitStatus;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;

class InMemoryFruitRepository implements FruitRepository
{

    /**
     * @var Fruit[]
     */
    public array $fruits = [];

    public function __construct()
    {

        $this->fruits['frt001']['reference'] = 'Ref01';
        $this->fruits['frt001']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt001']['price'] = 1000;
        $this->fruits['frt002']['reference'] = 'Ref01';
        $this->fruits['frt002']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt002']['price'] = 1000;
        $this->fruits['frt003']['reference'] = 'Ref01';
        $this->fruits['frt003']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt003']['price'] = 1000;
        $this->fruits['frt004']['reference'] = 'Ref01';
        $this->fruits['frt004']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt004']['price'] = 1000;
        $this->fruits['frt005']['reference'] = 'Ref01';
        $this->fruits['frt005']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt005']['price'] = 1000;
        $this->fruits['frt006']['reference'] = 'Ref01';
        $this->fruits['frt006']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt006']['price'] = 1000;
        $this->fruits['frt007']['reference'] = 'Ref01';
        $this->fruits['frt007']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt007']['price'] = 1000;
        $this->fruits['frt008']['reference'] = 'Ref01';
        $this->fruits['frt008']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt008']['price'] = 1000;
        $this->fruits['frt009']['reference'] = 'Ref01';
        $this->fruits['frt009']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt009']['price'] = 1000;
        $this->fruits['frt010']['reference'] = 'Ref01';
        $this->fruits['frt010']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt010']['price'] = 1000;

        $this->fruits['frt011']['reference'] = 'Ref02';
        $this->fruits['frt011']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt011']['price'] = 2000;
        $this->fruits['frt012']['reference'] = 'Ref02';
        $this->fruits['frt012']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt012']['price'] = 2000;
        $this->fruits['frt013']['reference'] = 'Ref02';
        $this->fruits['frt013']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt013']['price'] = 2000;
        $this->fruits['frt014']['reference'] = 'Ref02';
        $this->fruits['frt014']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt014']['price'] = 2000;
        $this->fruits['frt015']['reference'] = 'Ref02';
        $this->fruits['frt015']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt015']['price'] = 2000;
        $this->fruits['frt016']['reference'] = 'Ref02';
        $this->fruits['frt016']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt016']['price'] = 2000;
        $this->fruits['frt017']['reference'] = 'Ref02';
        $this->fruits['frt017']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt017']['price'] = 2000;
        $this->fruits['frt018']['reference'] = 'Ref02';
        $this->fruits['frt018']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt018']['price'] = 2000;
        $this->fruits['frt019']['reference'] = 'Ref02';
        $this->fruits['frt019']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt019']['price'] = 2000;
        $this->fruits['frt020']['reference'] = 'Ref02';
        $this->fruits['frt020']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt020']['price'] = 2000;

        $this->fruits['frt021']['reference'] = 'Ref03';
        $this->fruits['frt021']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt021']['price'] = 3000;
        $this->fruits['frt022']['reference'] = 'Ref03';
        $this->fruits['frt022']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt022']['price'] = 3000;
        $this->fruits['frt023']['reference'] = 'Ref03';
        $this->fruits['frt023']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt023']['price'] = 3000;
        $this->fruits['frt024']['reference'] = 'Ref03';
        $this->fruits['frt024']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt024']['price'] = 3000;
        $this->fruits['frt025']['reference'] = 'Ref03';
        $this->fruits['frt025']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt025']['price'] = 3000;
        $this->fruits['frt026']['reference'] = 'Ref03';
        $this->fruits['frt026']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt026']['price'] = 3000;
        $this->fruits['frt027']['reference'] = 'Ref03';
        $this->fruits['frt027']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt027']['price'] = 3000;
        $this->fruits['frt028']['reference'] = 'Ref03';
        $this->fruits['frt028']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt028']['price'] = 3000;
        $this->fruits['frt029']['reference'] = 'Ref03';
        $this->fruits['frt029']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt029']['price'] = 3000;
        $this->fruits['frt030']['reference'] = 'Ref03';
        $this->fruits['frt030']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt030']['price'] = 3000;
        $this->fruits['frt031']['reference'] = 'Ref03';
        $this->fruits['frt031']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt031']['price'] = 3000;
        $this->fruits['frt032']['reference'] = 'Ref03';
        $this->fruits['frt032']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt032']['price'] = 3000;
        $this->fruits['frt033']['reference'] = 'Ref03';
        $this->fruits['frt033']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt033']['price'] = 3000;
        $this->fruits['frt034']['reference'] = 'Ref03';
        $this->fruits['frt034']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt034']['price'] = 3000;
        $this->fruits['frt035']['reference'] = 'Ref03';
        $this->fruits['frt035']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt035']['price'] = 3000;
        $this->fruits['frt036']['reference'] = 'Ref03';
        $this->fruits['frt036']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt036']['price'] = 3000;
        $this->fruits['frt037']['reference'] = 'Ref03';
        $this->fruits['frt037']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['frt037']['price'] = 3000;

    }

    public function byReference(FruitReference $fruitRef): ?Fruit
    {
        $result = array_filter(
            $this->fruits,
            fn($f) => $f['reference'] === $fruitRef->referenceValue() && $f['status'] === FruitStatus::AVAILABLE->value
        );
        if(!$result){
            return null;
        }
        return Fruit::create(
            new Id(array_key_first($result)),
            new FruitReference($result[array_key_first($result)]['reference'], $result[array_key_first($result)]['price']),
            FruitStatus::in($result[array_key_first($result)]['status'])
        );
    }

    /**
     * @param FruitReference $reference
     * @return Fruit[]|null
     */
    public function allByReference(FruitReference $reference): ?array
    {
        $fruitsByReference = array_filter(
            $this->fruits,
                fn($f) => $f['reference'] === $reference->referenceValue() && $f['status'] === FruitStatus::AVAILABLE->value
        );
        if(count($fruitsByReference) === 0){
            return null;
        }

        $asObjectsFruit = [];
        foreach ($fruitsByReference as $key => $occurrence) {
            $fruit = Fruit::create(
                new Id($key),
                new FruitReference($occurrence['reference'], $occurrence['price']),
                FruitStatus::in($occurrence['status'])
            );
            $asObjectsFruit[] = $fruit;
        }
        return $asObjectsFruit;
    }

    /**
     * @param Fruit $fruit
     * @return void
     */
    public function save(Fruit $fruit):void
    {
        $this->fruits[$fruit->id()->value()]['reference'] = $fruit->reference()->referenceValue();
        $this->fruits[$fruit->id()->value()]['price'] = $fruit->reference()->price();
        $this->fruits[$fruit->id()->value()]['status'] = $fruit->status()->value;
    }

    /**
     * @return Fruit[]
     */
    public function all(): array
    {
        $fruits = $this->fruits;
        $asFruitObject = [];
        foreach ($fruits as $key => $occurrence) {
            $fruit = Fruit::create(
                new Id($key),
                new FruitReference($occurrence['reference'], $occurrence['price']),
                FruitStatus::in($occurrence['status'])
            );
            if($fruit->status()->value === FruitStatus::AVAILABLE->value){
                $asFruitObject[] =  $fruit;
            }
        }
        return $asFruitObject;
    }

    public function saveMany(array $soldFruits): void
    {
        foreach ($soldFruits as $soldFruit) {
            $this->save($soldFruit);
        }
    }
}