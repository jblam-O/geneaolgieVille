<?php

namespace App\DataFixtures;

use App\Entity\Family;
use App\Entity\Gender;
use App\Entity\Person;
use App\Entity\Union;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LouisXIVFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $male = (new Gender())->setName('Male');
        $female = (new Gender())->setName('Female');
        $manager->persist($male);
        $manager->persist($female);

        $bourbon = (new Family())->setName('Maison de Bourbon');
        $habsbourg = (new Family())->setName('Maison de Habsbourg');
        $medici = (new Family())->setName('Maison de Medici');
        $wittelsbach = (new Family())->setName('Maison de Wittelsbach');
        $manager->persist($bourbon);
        $manager->persist($habsbourg);
        $manager->persist($medici);
        $manager->persist($wittelsbach);

        $henriIv = $this->createPerson('Henri', 'IV', '1553-12-13', '1610-05-14', $male)->setFamily($bourbon);
        $marieDeMedici = $this->createPerson('Marie', 'de Medici', '1575-04-26', '1642-07-03', $female)->setFamily($medici);
        $louisXiii = $this->createPerson('Louis', 'XIII', '1601-09-27', '1643-05-14', $male)->setFamily($bourbon);
        $anneAutriche = $this->createPerson('Anne', "d'Autriche", '1601-09-22', '1666-01-20', $female)->setFamily($habsbourg);
        $philippeIii = $this->createPerson('Philippe', 'III d\'Espagne', '1578-04-14', '1621-03-31', $male)->setFamily($habsbourg);
        $margueriteAutriche = $this->createPerson('Marguerite', "d'Autriche", '1584-12-25', '1611-10-03', $female)->setFamily($habsbourg);
        $philippeIv = $this->createPerson('Philippe', 'IV d\'Espagne', '1605-04-08', '1665-09-17', $male)->setFamily($habsbourg);
        $elisabethFrance = $this->createPerson('Elisabeth', 'de France', '1602-11-22', '1644-10-06', $female)->setFamily($bourbon);

        $louisXiv = $this->createPerson('Louis', 'XIV', '1638-09-05', '1715-09-01', $male)->setFamily($bourbon);
        $marieTherese = $this->createPerson('Marie-Therese', "d'Espagne", '1638-09-20', '1683-07-30', $female)->setFamily($habsbourg);
        $grandDauphin = $this->createPerson('Louis', 'de France', '1661-11-01', '1711-04-14', $male)->setFamily($bourbon);
        $marieAnneBaviere = $this->createPerson('Marie-Anne', 'de Baviere', '1660-03-28', '1690-05-20', $female)->setFamily($wittelsbach);

        $ducBourgogne = $this->createPerson('Louis', 'de France', '1682-08-06', '1712-02-18', $male)->setFamily($bourbon);
        $ducAnjou = $this->createPerson('Philippe', 'de France', '1683-12-19', '1746-07-09', $male)->setFamily($bourbon);
        $ducBerry = $this->createPerson('Charles', 'de France', '1686-08-31', '1714-05-04', $male)->setFamily($bourbon);

        $unionHenri = $this->createUnion($henriIv, $marieDeMedici, '1600-12-17', '1610-05-14');
        $unionHenri->addChild($louisXiii);
        $louisXiii->addParent($henriIv)->addParent($marieDeMedici);

        $unionPhilippeIii = $this->createUnion($philippeIii, $margueriteAutriche, '1599-04-18', '1611-10-03');
        $unionPhilippeIii->addChild($anneAutriche);
        $anneAutriche->addParent($philippeIii)->addParent($margueriteAutriche);

        $unionLouisXiii = $this->createUnion($louisXiii, $anneAutriche, '1615-11-25', '1643-05-14');
        $unionLouisXiii->addChild($louisXiv);
        $louisXiv->addParent($louisXiii)->addParent($anneAutriche);

        $unionPhilippeIv = $this->createUnion($philippeIv, $elisabethFrance, '1615-10-18', '1644-10-06');
        $unionPhilippeIv->addChild($marieTherese);
        $marieTherese->addParent($philippeIv)->addParent($elisabethFrance);

        $unionLouisXiv = $this->createUnion($louisXiv, $marieTherese, '1660-06-09', '1683-07-30');
        $unionLouisXiv->addChild($grandDauphin);
        $grandDauphin->addParent($louisXiv)->addParent($marieTherese);

        $unionDauphin = $this->createUnion($grandDauphin, $marieAnneBaviere, '1680-03-07', '1690-05-20');
        $unionDauphin->addChild($ducBourgogne);
        $unionDauphin->addChild($ducAnjou);
        $unionDauphin->addChild($ducBerry);
        $ducBourgogne->addParent($grandDauphin)->addParent($marieAnneBaviere);
        $ducAnjou->addParent($grandDauphin)->addParent($marieAnneBaviere);
        $ducBerry->addParent($grandDauphin)->addParent($marieAnneBaviere);

        foreach ([
            $henriIv,
            $marieDeMedici,
            $louisXiii,
            $anneAutriche,
            $philippeIii,
            $margueriteAutriche,
            $philippeIv,
            $elisabethFrance,
            $louisXiv,
            $marieTherese,
            $grandDauphin,
            $marieAnneBaviere,
            $ducBourgogne,
            $ducAnjou,
            $ducBerry,
        ] as $person) {
            $manager->persist($person);
        }

        foreach ([
            $unionHenri,
            $unionPhilippeIii,
            $unionLouisXiii,
            $unionPhilippeIv,
            $unionLouisXiv,
            $unionDauphin,
        ] as $union) {
            $manager->persist($union);
        }

        $manager->flush();
    }

    private function createPerson(
        string $firstName,
        string $lastName,
        string $birth,
        string $death,
        Gender $gender
    ): Person {
        return (new Person())
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setBirthdate(new \DateTime($birth))
            ->setDeathdate(new \DateTime($death))
            ->setGender($gender);
    }

    private function createUnion(
        Person $personA,
        Person $personB,
        string $start,
        string $end
    ): Union {
        $union = (new Union())
            ->setStartdate(new \DateTime($start))
            ->setEnddate(new \DateTime($end));

        $union->addPerson1($personA);
        $union->addPerson1($personB);

        return $union;
    }
}
