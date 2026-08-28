<?php

namespace App\Controller;

use App\Entity\Person;
use App\Repository\EventsRepository;
use App\Repository\FamilyRepository;
use App\Repository\PersonRepository;
use App\Repository\CivilizationRepository;
use App\Repository\PeriodRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TreeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->redirectToRoute('app_trees');
    }

    #[Route('/trees', name: 'app_trees')]
    public function index(
        Request $request,
        FamilyRepository $familyRepository,
        PersonRepository $personRepository
    ): Response {
        $families = $familyRepository->findBy([], ['name' => 'ASC']);
        if ($families === []) {
            return $this->render('tree/index.html.twig', [
                'families' => [],
                'selectedFamily' => null,
                'trees' => [],
            ]);
        }

        $selectedId = $request->query->getInt('family');
        $viewMode = $request->query->get('view', 'unions');
        if (!in_array($viewMode, ['unions', 'parents'], true)) {
            $viewMode = 'unions';
        }
        $selectedFamily = $selectedId ? $familyRepository->find($selectedId) : null;
        if ($selectedFamily === null) {
            $selectedFamily = $families[0];
        }

        $familyPeople = $personRepository->findBy(['family' => $selectedFamily]);

        $trees = [];
        $parentLevels = [];
        if ($viewMode === 'parents') {
            $parentLevels = $this->buildParentLevels($familyPeople);
        } else {
            $trees = $this->buildUnionTrees($familyPeople);
        }

        return $this->render('tree/index.html.twig', [
            'families' => $families,
            'selectedFamily' => $selectedFamily,
            'viewMode' => $viewMode,
            'trees' => $trees,
            'parentLevels' => $parentLevels,
        ]);
    }

    #[Route('/trees/custom', name: 'app_trees_custom')]
    public function custom(): Response
    {
        return $this->render('tree/custom.html.twig');
    }

    #[Route('/antics', name: 'app_antics')]
    public function antics(
        EventsRepository $eventsRepository,
        CivilizationRepository $civilizationRepository,
        PeriodRepository $periodRepository
    ): Response
    {
        $events = [];
        foreach ($eventsRepository->findBy([], ['year' => 'ASC']) as $event) {
            $persons = [];
            foreach ($event->getPersons() as $person) {
                $firstName = $person->getFirstName() ?? '';
                $lastName = $person->getLastName() ?? '';
                $fullName = trim($firstName . ' ' . $lastName);
                if ($fullName !== '') {
                    $persons[] = $fullName;
                }
            }

            $events[] = [
                'id' => $event->getId(),
                'year' => $event->getYear(),
                'label' => $event->getLabel(),
                'civ' => $event->getCivilization()?->getName(),
                'period' => $event->getPeriod()?->getName(),
                'persons' => $persons,
                'title' => $event->getTitle(),
                'summary' => $event->getSummary(),
                'detail' => $event->getDetail(),
                'emoji' => $event->getEmoji(),
            ];
        }

        $civilizations = [];
        foreach ($civilizationRepository->findBy([], ['name' => 'ASC']) as $civilization) {
            $civilizations[$civilization->getName()] = $this->buildTimelineMeta(
                $civilization->getLabel() ?? $civilization->getName() ?? '',
                $civilization->getColor() ?? '#d4af37'
            );
        }

        $periods = [];
        foreach ($periodRepository->findBy([], ['name' => 'ASC']) as $period) {
            $periods[$period->getName()] = $this->buildTimelineMeta(
                $period->getLabel() ?? $period->getName() ?? '',
                $period->getColor() ?? '#d4af37'
            );
        }

        // $quizQuestions = [
        //     [
        //         'id' => 1,
        //         'question' => 'En quelle annee l\'Egypte a-t-elle ete unifiee ?',
        //         'correct' => '3000 av. J.-C.',
        //         'options' => ['3000 av. J.-C.', '2560 av. J.-C.', '1000 av. J.-C.', '500 av. J.-C.'],
        //     ],
        //     [
        //         'id' => 2,
        //         'question' => 'Qui a construit la Grande Pyramide de Gizeh ?',
        //         'correct' => 'Kheops',
        //         'options' => ['Narmer', 'Kheops', 'Toutankhamon', 'Ramses II'],
        //     ],
        //     [
        //         'id' => 4,
        //         'question' => 'Quel evenement sportif a commence en 776 av. J.-C. ?',
        //         'correct' => 'Les Jeux Olympiques',
        //         'options' => ['Les Jeux Isthmiques', 'Les Jeux Olympiques', 'Les Jeux Nemeens', 'Les Jeux Pythiques'],
        //     ],
        //     [
        //         'id' => 5,
        //         'question' => 'Qui a instaure les reformes democratiques a Athenes ?',
        //         'correct' => 'Clisthene',
        //         'options' => ['Pericles', 'Solon', 'Clisthene', 'Platon'],
        //     ],
        //     [
        //         'id' => 7,
        //         'question' => 'Quel animal a eleve Romulus et Remus selon la legende ?',
        //         'correct' => 'Une louve',
        //         'options' => ['Un aigle', 'Un lion', 'Une louve', 'Un loup'],
        //     ],
        //     [
        //         'id' => 9,
        //         'question' => 'Combien de fois Jules Cesar a-t-il ete poignarde ?',
        //         'correct' => '23 fois',
        //         'options' => ['10 fois', '15 fois', '23 fois', '30 fois'],
        //     ],
        //     [
        //         'id' => 10,
        //         'question' => 'Combien de spectateurs le Colisee pouvait-il accueillir ?',
        //         'correct' => '50 000',
        //         'options' => ['30 000', '50 000', '80 000', '100 000'],
        //     ],
        //     [
        //         'id' => 11,
        //         'question' => 'Qui a depose le dernier empereur romain d\'Occident ?',
        //         'correct' => 'Odoacre',
        //         'options' => ['Alaric', 'Odoacre', 'Attila', 'Stilichon'],
        //     ],
        //     [
        //         'id' => 12,
        //         'question' => 'Quel inventeur est lie a la diffusion de l\'imprimerie en Europe ?',
        //         'correct' => 'Gutenberg',
        //         'options' => ['Gutenberg', 'Copernic', 'Galilee', 'Vesale'],
        //     ],
        // ];

        $defaultConfig = [
            'timeline_title' => 'Civilisations Anciennes',
            'timeline_subtitle' => '3000 av. J.-C. — 2000 apr. J.-C.',
            'background_color' => '#1a1a2e',
            'accent_color' => '#d4af37',
            'text_color' => '#e8e0d0',
            'card_color' => '#252545',
            'muted_color' => '#a89b8a',
            'font_family' => 'Cinzel',
            'font_size' => 16,
        ];

        $uiText = [
            'pageTitle' => 'Ancient Civilizations Timeline',
            'tagline' => 'Chronologie Interactive',
            'title' => 'Civilisations Anciennes',
            'subtitle' => '3000 av. J.-C. — 2000 apr. J.-C.',
            'progressLabel' => 'Evenements explores',
            'quizButton' => 'Quiz',
            'tabCiv' => 'Civilisations',
            'tabPeriod' => 'Periodes',
            'tabPerson' => 'Personnes',
            'quizTitle' => 'Quiz Historique',
            'quizEmpty' => 'Aucune question disponible.',
            'nextQuestion' => 'Question Suivante',
        ];

        return $this->render('tree/antics.html.twig', [
            'events' => $events,
            'civilizations' => $civilizations,
            'periods' => $periods,
            // 'quizQuestions' => $quizQuestions,
            'defaultConfig' => $defaultConfig,
            'uiText' => $uiText,
        ]);
    }

    /** @return array{name: string, color: string, bg: string, border: string} */
    private function buildTimelineMeta(string $name, string $color): array
    {
        $hex = ltrim($color, '#');
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            $hex = 'd4af37';
            $color = '#d4af37';
        }

        $red = hexdec(substr($hex, 0, 2));
        $green = hexdec(substr($hex, 2, 2));
        $blue = hexdec(substr($hex, 4, 2));

        return [
            'name' => $name,
            'color' => $color,
            'bg' => sprintf('rgba(%d,%d,%d,0.1)', $red, $green, $blue),
            'border' => sprintf('rgba(%d,%d,%d,0.3)', $red, $green, $blue),
        ];
    }

    /**
     * @param array<int, Person> $familyPeople
     * @return array<int, array{person: Person, unions: array<int, array{partners: array<int, Person>, children: array<int, array>}>}>
     */
    private function buildUnionTrees(array $familyPeople): array
    {
        $roots = [];
        foreach ($familyPeople as $person) {
            if ($person->getChildishUnion() === null) {
                $roots[] = $person;
            }
        }

        if ($roots === []) {
            $roots = $familyPeople;
        }

        $visited = [];
        $trees = [];
        foreach ($roots as $root) {
            $trees[] = $this->buildUnionTree($root, $visited);
        }

        return $trees;
    }

    /**
     * @param array<int, Person> $familyPeople
     * @return array<int, array<int, Person>>
     */
    private function buildParentLevels(array $familyPeople): array
    {
        $childrenByParent = [];
        foreach ($familyPeople as $person) {
            foreach ($person->getParent() as $parent) {
                $parentId = $parent->getId() ?? spl_object_id($parent);
                $childrenByParent[$parentId][] = $person;
            }
        }

        $roots = [];
        foreach ($familyPeople as $person) {
            if ($person->getParent()->isEmpty()) {
                $roots[] = $person;
            }
        }

        if ($roots === []) {
            $roots = $familyPeople;
        }

        $visited = [];
        $levels = [];
        $queue = $roots;
        while ($queue !== []) {
            $current = [];
            $next = [];
            foreach ($queue as $person) {
                $personId = $person->getId() ?? spl_object_id($person);
                if (isset($visited[$personId])) {
                    continue;
                }

                $visited[$personId] = true;
                $current[] = $person;

                foreach ($childrenByParent[$personId] ?? [] as $child) {
                    $next[] = $child;
                }
            }

            if ($current !== []) {
                $levels[] = $current;
            }

            $queue = $next;
        }

        return $levels;
    }

    /**
     * @param array<int, bool> $visited
     * @return array{person: Person, unions: array<int, array{partners: array<int, Person>, children: array<int, array>}>}
     */
    private function buildUnionTree(Person $person, array &$visited): array
    {
        $personId = $person->getId() ?? spl_object_id($person);
        if (isset($visited[$personId])) {
            return [
                'person' => $person,
                'unions' => [],
            ];
        }

        $visited[$personId] = true;

        $unionsData = [];
        foreach ($person->getUnions() as $union) {
            $partners = [];
            foreach ($union->getPerson1() as $partner) {
                if ($partner !== $person) {
                    $partners[] = $partner;
                }
            }

            $children = [];
            foreach ($union->getChildren() as $child) {
                $children[] = $this->buildUnionTree($child, $visited);
            }

            $unionsData[] = [
                'partners' => $partners,
                'children' => $children,
            ];
        }

        return [
            'person' => $person,
            'unions' => $unionsData,
        ];
    }

}
