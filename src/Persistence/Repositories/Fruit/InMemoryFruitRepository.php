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
        $this->fruits = [
            Fruit::create(new Id('001'), new FruitReference('Ref01',1000)),
            Fruit::create(new Id('002'), new FruitReference('Ref01',1000)),
            Fruit::create(new Id('003'), new FruitReference('Ref01',1000)),
            Fruit::create(new Id('004'), new FruitReference('Ref01',1000)),
            Fruit::create(new Id('005'), new FruitReference('Ref01',1000)),
            Fruit::create(new Id('006'), new FruitReference('Ref01',1000)),
            Fruit::create(new Id('007'), new FruitReference('Ref01',1000)),
            Fruit::create(new Id('008'), new FruitReference('Ref01',1000)),
            Fruit::create(new Id('009'), new FruitReference('Ref01',1000)),
            Fruit::create(new Id('010'), new FruitReference('Ref01',1000)),

            Fruit::create(new Id('011'), new FruitReference('Ref02',2000)),
            Fruit::create(new Id('012'), new FruitReference('Ref02',2000)),
            Fruit::create(new Id('013'), new FruitReference('Ref02',2000)),
            Fruit::create(new Id('014'), new FruitReference('Ref02',2000)),
            Fruit::create(new Id('015'), new FruitReference('Ref02',2000)),
            Fruit::create(new Id('016'), new FruitReference('Ref02',2000)),
            Fruit::create(new Id('017'), new FruitReference('Ref02',2000)),
            Fruit::create(new Id('018'), new FruitReference('Ref02',2000)),
            Fruit::create(new Id('019'), new FruitReference('Ref02',2000)),
            Fruit::create(new Id('020'), new FruitReference('Ref02',2000)),

            Fruit::create(new Id('021'), new FruitReference('Ref03',3000)),
            Fruit::create(new Id('022'), new FruitReference('Ref03',3000)),
            Fruit::create(new Id('023'), new FruitReference('Ref03',3000)),
            Fruit::create(new Id('024'), new FruitReference('Ref03',3000)),
            Fruit::create(new Id('026'), new FruitReference('Ref03',3000)),
            Fruit::create(new Id('027'), new FruitReference('Ref03',3000)),
            Fruit::create(new Id('028'), new FruitReference('Ref03',3000)),
            Fruit::create(new Id('029'), new FruitReference('Ref03',3000)),
            Fruit::create(new Id('030'), new FruitReference('Ref03',3000)),
        ];
    }

    public function byReference(FruitReference $fruitRef): ?Fruit
    {
        $result = array_values(array_filter(
            $this->fruits,
            fn(Fruit $f) => $f->reference()->referenceValue() === $fruitRef->referenceValue()
        ));

        return count($result) > 0 ? $result[0] : null;
    }

    /**
     * @param FruitReference $reference
     * @return Fruit[]|null
     */
    public function allByReference(FruitReference $reference): ?array
    {
        $fruitsByReference = array_values(array_filter(
            $this->fruits,
            fn(Fruit $f)=>$f->reference()->referenceValue() === $reference->referenceValue() && $f->status()->value != FruitStatus::OCCUPIED->value
        ));
        return count($fruitsByReference) > 0 ? $fruitsByReference : null;
    }

    /**
     * @param Fruit $fruit
     * @return void
     */
    public function save(Fruit $fruit):void
    {
        $this->fruits = array_values(array_filter(
            $this->fruits,
            fn(Fruit $f)=>$f->id()->value() !== $fruit->id()->value()
        ));
        $this->fruits[] =$fruit;
    }
}