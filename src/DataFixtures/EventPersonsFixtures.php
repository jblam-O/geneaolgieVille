<?php

namespace App\DataFixtures;

use App\Entity\Civilization;
use App\Entity\Events;
use App\Entity\Period;
use App\Entity\Person;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EventPersonsFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $civilizations = [
            'egypt' => ["name" => "Egypte", "color" => "#eba611"],
            'greece' => ["name" => "Grece", "color" => "#1f77b4"],
            'rome' => ["name" => "Rome", "color" => "#d62728"],
            'renaissance' => ["name" => "Renaissance", "color" => "#8fbf7a"],
        ];

        $civilizationEntities = [];
        foreach ($civilizations as $key => $data) {
            $civilization = (new Civilization())
                ->setName($key)
                ->setLabel($data['name'])
                ->setColor($data['color']);
            $manager->persist($civilization);
            $civilizationEntities[$key] = $civilization;
        }

        $periods = [
            'ancien' => ['name' => 'Ancien Empire', 'color' => '#e8a838'],
            'moyen' => ['name' => 'Moyen Empire', 'color' => '#e8a838'],
            'archaique' => ['name' => 'Période archaïque', 'color' => '#5b9bd5'],
            'classique' => ['name' => 'Période classique', 'color' => '#5b9bd5'],
            'hellenistique' => ['name' => 'Période hellénistique', 'color' => '#5b9bd5'],
            'royal' => ['name' => 'Période royale', 'color' => '#c0504d'],
            'republique' => ['name' => 'République', 'color' => '#c0504d'],
            'empire' => ['name' => 'Empire', 'color' => '#c0504d'],
            'renaissance' => ['name' => 'Renaissance', 'color' => '#8fbf7a'],
        ];

        $periodEntities = [];
        foreach ($periods as $key => $data) {
            $period = (new Period())
                ->setName($key)
                ->setLabel($data['name'])
                ->setColor($data['color']);
            $manager->persist($period);
            $periodEntities[$key] = $period;
        }

        $eventsData = [
            [
                'year' => -3000,
                'label' => '3000 av. J.-C.',
                'civ' => 'egypt',
                'period' => 'ancien',
                'persons' => ['Narmer'],
                'title' => 'Unification de l\'Egypte',
                'summary' => 'Le roi Narmer unifie la Haute et la Basse Egypte, fondant la premiere dynastie.',
                'detail' => 'Cette unification marque le debut de l\'une des plus longues civilisations de l\'histoire, avec Memphis comme premiere capitale. L\'ecriture hieroglyphique commence a se developper.',
                'emoji' => '👑',
            ],
            [
                'year' => -2560,
                'label' => '2560 av. J.-C.',
                'civ' => 'egypt',
                'period' => 'ancien',
                'persons' => ['Kheops'],
                'title' => 'Grande Pyramide de Gizeh',
                'summary' => 'Construction de la pyramide de Kheops, l\'une des sept merveilles du monde antique.',
                'detail' => 'Haute de 146 metres a l\'origine, elle restera le plus haut edifice du monde pendant plus de 3 800 ans. Environ 2,3 millions de blocs de pierre furent utilises.',
                'emoji' => '🔺',
            ],
            [
                'year' => -2000,
                'label' => '2000 av. J.-C.',
                'civ' => 'egypt',
                'period' => 'moyen',
                'persons' => ['XIIe dynastie'],
                'title' => 'Moyen Empire egyptien',
                'summary' => 'Age d\'or de la litterature et de l\'art egyptiens sous la XIIe dynastie.',
                'detail' => 'Le Moyen Empire voit l\'expansion du commerce, la construction de forteresses en Nubie et la redaction de grands textes comme le Conte de Sinouhe.',
                'emoji' => '📜',
            ],
            [
                'year' => -776,
                'label' => '776 av. J.-C.',
                'civ' => 'greece',
                'period' => 'archaique',
                'persons' => [],
                'title' => 'Premiers Jeux Olympiques',
                'summary' => 'Les premiers Jeux Olympiques sont organises a Olympie en l\'honneur de Zeus.',
                'detail' => 'Pendant plus de 1 000 ans, les cites grecques observeront une treve sacree pour permettre aux athletes de participer. La seule epreuve initiale est la course du stade (~192 m).',
                'emoji' => '🏛️',
            ],
            [
                'year' => -508,
                'label' => '508 av. J.-C.',
                'civ' => 'greece',
                'period' => 'classique',
                'persons' => ['Clisthene'],
                'title' => 'Naissance de la Democratie',
                'summary' => 'Clisthene instaure les reformes democratiques a Athenes.',
                'detail' => 'Ce systeme permet aux citoyens de voter directement les lois. L\'ecclesia (assemblee) se reunit sur la colline de la Pnyx. C\'est le fondement de nos democraties modernes.',
                'emoji' => '⚖️',
            ],
            [
                'year' => -334,
                'label' => '334 av. J.-C.',
                'civ' => 'greece',
                'period' => 'hellenistique',
                'persons' => ['Alexandre'],
                'title' => 'Conquetes d\'Alexandre',
                'summary' => 'Alexandre le Grand commence sa campagne contre l\'Empire perse.',
                'detail' => 'En seulement 11 ans, il cree l\'un des plus grands empires de l\'histoire, s\'etendant de la Grece jusqu\'a l\'Inde. Il fonde plus de 20 villes portant son nom.',
                'emoji' => '⚔️',
            ],
            [
                'year' => -753,
                'label' => '753 av. J.-C.',
                'civ' => 'rome',
                'period' => 'royal',
                'persons' => ['Romulus'],
                'title' => 'Fondation de Rome',
                'summary' => 'Selon la legende, Romulus fonde la ville de Rome sur le mont Palatin.',
                'detail' => 'Romulus et Remus, jumeaux eleves par une louve, se disputent l\'emplacement de la nouvelle cite. Romulus trace le sillon sacre du pomoerium qui delimitera la ville.',
                'emoji' => '🐺',
            ],
            [
                'year' => -509,
                'label' => '509 av. J.-C.',
                'civ' => 'rome',
                'period' => 'republique',
                'persons' => [],
                'title' => 'Republique Romaine',
                'summary' => 'Expulsion du dernier roi etrusque et creation de la Republique.',
                'detail' => 'Le Senat et les consuls remplacent la monarchie. Ce systeme politique durera pres de 500 ans et influencera profondement les institutions occidentales.',
                'emoji' => '🏛️',
            ],
            [
                'year' => -44,
                'label' => '44 av. J.-C.',
                'civ' => 'rome',
                'period' => 'republique',
                'persons' => ['Jules Cesar'],
                'title' => 'Assassinat de Cesar',
                'summary' => 'Jules Cesar est assassine aux ides de mars par un groupe de senateurs.',
                'detail' => 'Apres avoir ete nomme dictateur a vie, Cesar est poignarde 23 fois au Senat. Sa mort declenche une serie de guerres civiles qui meneront a la fin de la Republique.',
                'emoji' => '🗡️',
            ],
            [
                'year' => 80,
                'label' => '80 apr. J.-C.',
                'civ' => 'rome',
                'period' => 'empire',
                'persons' => ['Titus'],
                'title' => 'Inauguration du Colisee',
                'summary' => 'L\'amphitheatre Flavien est inaugure avec 100 jours de jeux.',
                'detail' => 'Pouvant accueillir 50 000 spectateurs, le Colisee est un chef-d\'oeuvre d\'ingenierie avec son systeme de voiles (velarium) et ses 80 entrees. Des combats de gladiateurs et des reconstitutions navales y sont organises.',
                'emoji' => '🏟️',
            ],
            [
                'year' => 476,
                'label' => '476 apr. J.-C.',
                'civ' => 'rome',
                'period' => 'empire',
                'persons' => ['Odoacre', 'Romulus Augustule'],
                'title' => 'Chute de Rome',
                'summary' => 'Odoacre depose le dernier empereur romain d\'Occident, Romulus Augustule.',
                'detail' => 'Cet evenement marque traditionnellement la fin de l\'Antiquite et le debut du Moyen Age en Europe occidentale. L\'Empire romain d\'Orient (Byzance) perdurera jusqu\'en 1453.',
                'emoji' => '🌅',
            ],
            [
                'year' => 1450,
                'label' => '1450',
                'civ' => 'renaissance',
                'period' => 'renaissance',
                'persons' => ['Gutenberg'],
                'title' => 'Imprimerie de Gutenberg',
                'summary' => 'Diffusion de l\'imprimerie a caracteres mobiles en Europe.',
                'detail' => 'Les ateliers se multiplient et accelerent la circulation des textes, stimulant l\'humanisme et les sciences.',
                'emoji' => '🖨️',
            ],
            [
                'year' => 1504,
                'label' => '1504',
                'civ' => 'renaissance',
                'period' => 'renaissance',
                'persons' => ['Michel-Ange'],
                'title' => 'David de Michel-Ange',
                'summary' => 'La statue monumentale est achevee a Florence.',
                'detail' => 'Symbole de la Renaissance florentine, elle celebre l\'ideal humain et la maitrise des proportions.',
                'emoji' => '🗿',
            ],
            [
                'year' => 1512,
                'label' => '1512',
                'civ' => 'renaissance',
                'period' => 'renaissance',
                'persons' => ['Michel-Ange'],
                'title' => 'Fresques de la chapelle Sixtine',
                'summary' => 'Michel-Ange acheve le plafond de la chapelle Sixtine.',
                'detail' => 'Le cycle iconographique devient un jalon de l\'art occidental et du renouveau artistique.',
                'emoji' => '🎨',
            ],
        ];

        $personsByName = [];
        foreach ($eventsData as $eventData) {
            $event = (new Events())
                ->setYear($eventData['year'])
                ->setLabel($eventData['label'])
                ->setCivilization($civilizationEntities[$eventData['civ']])
                ->setPeriod($periodEntities[$eventData['period']])
                ->setTitle($eventData['title'])
                ->setSummary($eventData['summary'])
                ->setDetail($eventData['detail'])
                ->setEmoji($eventData['emoji']);

            foreach ($eventData['persons'] as $personName) {
                $person = $this->getOrCreatePerson($personName, $eventData['year'], $personsByName, $manager);
                $person->addEvent($event);
            }

            $manager->persist($event);
        }

        $manager->flush();
    }

    /**
     * @param array<string, Person> $personsByName
     */
    private function getOrCreatePerson(
        string $name,
        int $eventYear,
        array &$personsByName,
        ObjectManager $manager
    ): Person {
        if (isset($personsByName[$name])) {
            return $personsByName[$name];
        }

        [$firstName, $lastName] = $this->splitName($name);
        $birth = $this->createDateFromYear($eventYear);
        $death = $this->createDateFromYear($eventYear + 1);

        $person = (new Person())
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setBirthdate($birth)
            ->setDeathdate($death);

        $manager->persist($person);
        $personsByName[$name] = $person;

        return $person;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        if (count($parts) <= 1) {
            $value = $parts[0] ?? $name;

            return [$value, $value];
        }

        $firstName = array_shift($parts);
        $lastName = implode(' ', $parts);

        return [$firstName, $lastName];
    }

    private function createDateFromYear(int $year): \DateTime
    {
        $yearString = $year >= 0 ? sprintf('%04d', $year) : '-' . sprintf('%04d', abs($year));
        $dateString = sprintf('%s-01-01', $yearString);
        $date = \DateTime::createFromFormat('!Y-m-d', $dateString);

        if ($date === false) {
            return new \DateTime('0001-01-01');
        }

        return $date;
    }

}
