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

        $this->fruits['001']['reference'] = 'Ref01';
        $this->fruits['001']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['001']['price'] = 1000;
        $this->fruits['002']['reference'] = 'Ref01';
        $this->fruits['002']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['002']['price'] = 1000;
        $this->fruits['003']['reference'] = 'Ref01';
        $this->fruits['003']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['003']['price'] = 1000;
        $this->fruits['004']['reference'] = 'Ref01';
        $this->fruits['004']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['004']['price'] = 1000;
        $this->fruits['005']['reference'] = 'Ref01';
        $this->fruits['005']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['005']['price'] = 1000;
        $this->fruits['006']['reference'] = 'Ref01';
        $this->fruits['006']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['006']['price'] = 1000;
        $this->fruits['007']['reference'] = 'Ref01';
        $this->fruits['007']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['007']['price'] = 1000;
        $this->fruits['008']['reference'] = 'Ref01';
        $this->fruits['008']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['008']['price'] = 1000;
        $this->fruits['009']['reference'] = 'Ref01';
        $this->fruits['009']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['009']['price'] = 1000;
        $this->fruits['010']['reference'] = 'Ref01';
        $this->fruits['010']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['010']['price'] = 1000;

        $this->fruits['011']['reference'] = 'Ref02';
        $this->fruits['011']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['011']['price'] = 2000;
        $this->fruits['012']['reference'] = 'Ref02';
        $this->fruits['012']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['012']['price'] = 2000;
        $this->fruits['013']['reference'] = 'Ref02';
        $this->fruits['013']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['013']['price'] = 2000;
        $this->fruits['014']['reference'] = 'Ref02';
        $this->fruits['014']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['014']['price'] = 2000;
        $this->fruits['015']['reference'] = 'Ref02';
        $this->fruits['015']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['015']['price'] = 2000;
        $this->fruits['016']['reference'] = 'Ref02';
        $this->fruits['016']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['016']['price'] = 2000;
        $this->fruits['017']['reference'] = 'Ref02';
        $this->fruits['017']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['017']['price'] = 2000;
        $this->fruits['018']['reference'] = 'Ref02';
        $this->fruits['018']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['018']['price'] = 2000;
        $this->fruits['019']['reference'] = 'Ref02';
        $this->fruits['019']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['019']['price'] = 2000;
        $this->fruits['020']['reference'] = 'Ref02';
        $this->fruits['020']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['020']['price'] = 2000;

        $this->fruits['021']['reference'] = 'Ref03';
        $this->fruits['021']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['021']['price'] = 3000;
        $this->fruits['022']['reference'] = 'Ref03';
        $this->fruits['022']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['022']['price'] = 3000;
        $this->fruits['023']['reference'] = 'Ref03';
        $this->fruits['023']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['023']['price'] = 3000;
        $this->fruits['024']['reference'] = 'Ref03';
        $this->fruits['024']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['024']['price'] = 3000;
        $this->fruits['025']['reference'] = 'Ref03';
        $this->fruits['025']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['025']['price'] = 3000;
        $this->fruits['026']['reference'] = 'Ref03';
        $this->fruits['026']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['026']['price'] = 3000;
        $this->fruits['027']['reference'] = 'Ref03';
        $this->fruits['027']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['027']['price'] = 3000;
        $this->fruits['028']['reference'] = 'Ref03';
        $this->fruits['028']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['028']['price'] = 3000;
        $this->fruits['029']['reference'] = 'Ref03';
        $this->fruits['029']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['029']['price'] = 3000;
        $this->fruits['030']['reference'] = 'Ref03';
        $this->fruits['030']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['030']['price'] = 3000;
        $this->fruits['031']['reference'] = 'Ref03';
        $this->fruits['031']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['031']['price'] = 3000;
        $this->fruits['032']['reference'] = 'Ref03';
        $this->fruits['032']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['032']['price'] = 3000;
        $this->fruits['033']['reference'] = 'Ref03';
        $this->fruits['033']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['033']['price'] = 3000;
        $this->fruits['034']['reference'] = 'Ref03';
        $this->fruits['034']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['034']['price'] = 3000;
        $this->fruits['035']['reference'] = 'Ref03';
        $this->fruits['035']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['035']['price'] = 3000;
        $this->fruits['036']['reference'] = 'Ref03';
        $this->fruits['036']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['036']['price'] = 3000;
        $this->fruits['037']['reference'] = 'Ref03';
        $this->fruits['037']['status'] = FruitStatus::AVAILABLE->value;
        $this->fruits['037']['price'] = 3000;

    }

    public function byReference(FruitReference $fruitRef): ?Fruit
    {
        $result = array_filter(
            $this->fruits,
            fn($f) => $f['reference'] === $fruitRef->referenceValue()
        );
        if(!$result){
            return null;
        }
        $fruit = new Fruit(
            new Id(array_key_first($result)),
            new FruitReference($result[array_key_first($result)]['reference'], $result[array_key_first($result)]['price'])
        );
        $fruit->changeStatus($result[array_key_first($result)]['status']);
        return $fruit;
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
            $fruit = new Fruit(
                new Id($key),
                new FruitReference($occurrence['reference'], $occurrence['price'])
            );
            $fruit->changeStatus(FruitStatus::in($occurrence['status']));
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
            $fruit = new Fruit(
                new Id($key),
                new FruitReference($occurrence['reference'], $occurrence['price'])
            );
            $fruit->changeStatus(FruitStatus::in($occurrence['status']));
            if($fruit->status()->value === FruitStatus::AVAILABLE->value){
                $asFruitObject[] =  $fruit;
            }
        }
        return $asFruitObject;
    }
}