<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use App\Entity\Family;
use App\Entity\Gender;
use App\Entity\Person;
use App\Entity\Union;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LouisXIVFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['demo'];
    }

    public function load(ObjectManager $manager): void
    {
        $male = (new Gender())->setName('Male');
        $female = (new Gender())->setName('Female');
        $manager->persist($male);
        $manager->persist($female);

        $families = [
            $this->createFamily('Maison Valmont', 'demo_familles'),
            $this->createFamily('Maison Lenoir', 'demo_familles'),
        ];

        foreach ($families as $family) {
            $manager->persist($family);
        }

        $trees = [
            [
                'family' => $families[0],
                'surname' => 'Valmont',
                'rootYear' => 1860,
                'branches' => [3, 2, 4, 2],
            ],
            [
                'family' => $families[1],
                'surname' => 'Lenoir',
                'rootYear' => 1875,
                'branches' => [2, 3, 2, 3],
            ],
        ];

        foreach ($trees as $tree) {
            $this->buildImaginaryTree(
                $manager,
                $tree['family'],
                $tree['surname'],
                $tree['rootYear'],
                $tree['branches'],
                $male,
                $female
            );
        }

        $manager->flush();
    }

    private function createFamily(string $name, ?string $accountIdentifier = null): Family
    {
        return (new Family())
            ->setName($name)
            ->setAccountIdentifier($accountIdentifier);
    }

    /**
     * @param array<int, int> $childrenByGeneration
     */
    private function buildImaginaryTree(
        ObjectManager $manager,
        Family $family,
        string $surname,
        int $rootYear,
        array $childrenByGeneration,
        Gender $male,
        Gender $female
    ): void {
        $generation = $this->createCouple(
            $manager,
            $family,
            $surname,
            'Adrien',
            'Claire',
            $rootYear,
            $male,
            $female
        );

        foreach ($childrenByGeneration as $level => $childrenCount) {
            $personA = $generation[0];

            $partnerA = $this->createPerson(
                $manager,
                $family,
                $this->makeFirstName($surname, $level + 1, 1, 'partner'),
                $this->makeLastName($surname, $level + 1),
                $personA->getBirthdate()->format('Y') + 2,
                $female,
                $personA->getBirthdate()->format('Y') + 78
            );

            $unionA = $this->createUnion(
                $manager,
                $personA,
                $partnerA,
                $personA->getBirthdate()->format('Y') + 24,
                $personA->getBirthdate()->format('Y') + 70
            );

            $nextGeneration = [];
            for ($childIndex = 1; $childIndex <= $childrenCount; $childIndex++) {
                $child = $this->createPerson(
                    $manager,
                    $family,
                    $this->makeFirstName($surname, $level + 2, $childIndex, 'child'),
                    $surname,
                    $personA->getBirthdate()->format('Y') + 26 + ($childIndex * 2),
                    $childIndex % 2 === 0 ? $female : $male,
                    $personA->getBirthdate()->format('Y') + 90
                );

                $unionA->addChild($child);
                $child->addParent($personA)->addParent($partnerA);
                $nextGeneration[] = $child;
            }

            $heir = $nextGeneration[0];
            $heirPartner = $this->createPerson(
                $manager,
                $family,
                $this->makeFirstName($surname, $level + 2, 1, 'partner'),
                $this->makeLastName($surname, $level + 2),
                $heir->getBirthdate()->format('Y') + 2,
                $male,
                $heir->getBirthdate()->format('Y') + 82
            );

            $generation = [$heir, $heirPartner];
        }
    }

    /**
     * @return array<int, Person>
     */
    private function createCouple(
        ObjectManager $manager,
        Family $family,
        string $surname,
        string $firstA,
        string $firstB,
        int $birthYear,
        Gender $genderA,
        Gender $genderB
    ): array {
        $personA = $this->createPerson($manager, $family, $firstA, $surname, $birthYear, $genderA, $birthYear + 74);
        $personB = $this->createPerson($manager, $family, $firstB, $surname, $birthYear + 2, $genderB, $birthYear + 78);

        $this->createUnion($manager, $personA, $personB, $birthYear + 22, $birthYear + 78);

        return [$personA, $personB];
    }

    private function createPerson(
        ObjectManager $manager,
        Family $family,
        string $firstName,
        string $lastName,
        int $birthYear,
        Gender $gender,
        int $deathYear
    ): Person {
        $person = (new Person())
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setBirthdate(new \DateTime(sprintf('%d-01-01', $birthYear)))
            ->setDeathdate(new \DateTime(sprintf('%d-12-31', $deathYear)))
            ->setGender($gender)
            ->setFamily($family);

        $manager->persist($person);

        return $person;
    }

    private function createUnion(
        ObjectManager $manager,
        Person $personA,
        Person $personB,
        int $startYear,
        int $endYear
    ): Union {
        $union = (new Union())
            ->setStartdate(new \DateTime(sprintf('%d-06-15', $startYear)))
            ->setEnddate(new \DateTime(sprintf('%d-06-15', $endYear)));

        $union->addPerson1($personA);
        $union->addPerson1($personB);

        $manager->persist($union);

        return $union;
    }

    private function makeFirstName(string $surname, int $generation, int $index, string $role): string
    {
        $suffix = strtoupper(substr($surname, 0, 2));

        return sprintf('%s_%s_%d_%d', ucfirst($role), $suffix, $generation, $index);
    }

    private function makeLastName(string $surname, int $generation): string
    {
        return sprintf('%s_%d', $surname, $generation);
    }
}
