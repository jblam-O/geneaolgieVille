<?php

namespace App\Controller;

use App\Entity\Family;
use App\Entity\Person;
use App\Entity\Union;
use App\Repository\FamilyRepository;
use App\Repository\GenderRepository;
use App\Repository\PersonRepository;
use App\Repository\UnionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class GenealogyApiController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FamilyRepository $familyRepository,
        private readonly PersonRepository $personRepository,
        private readonly UnionRepository $unionRepository,
        private readonly GenderRepository $genderRepository,
    ) {
    }

    #[Route('/offline-session', methods: ['GET'])]
    public function offlineSession(): JsonResponse
    {
        return $this->json(['account' => $this->accountIdentifier()], headers: ['Cache-Control' => 'no-store']);
    }

    #[Route('/offline-sync', methods: ['POST'])]
    public function offlineSync(Request $request): JsonResponse
    {
        $operation = $this->payload($request);
        if (($operation['account'] ?? null) !== $this->accountIdentifier()) return $this->json(['error' => 'Reconnectez-vous au même compte pour synchroniser.'], 403);
        $key = (string) ($operation['id'] ?? '');
        if (!preg_match('/^[a-f0-9-]{36}$/', $key)) return $this->json(['error' => 'Identifiant invalide.'], 422);
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        try {
            $account = $this->accountIdentifier();
            $previous = $connection->fetchOne('SELECT response FROM offline_receipt WHERE account_identifier = ? AND operation_id = ?', [$account, $key]);
            if ($previous !== false) {
                $connection->commit();
                return new JsonResponse(json_decode($previous, true));
            }
            $url = (string) ($operation['url'] ?? '');
            $method = (string) ($operation['method'] ?? '');
            $inner = new Request(content: json_encode($operation['body'] ?? [], JSON_THROW_ON_ERROR));
            if ($url === '/api/families' && $method === 'POST') $response = $this->createFamily($inner);
            elseif (preg_match('#^/api/families/(\d+)/people$#', $url, $match) && $method === 'POST') $response = $this->createPerson((int) $match[1], $inner);
            elseif (preg_match('#^/api/people/(\d+)$#', $url, $match) && $method === 'PATCH') $response = $this->updatePerson((int) $match[1], $inner);
            elseif (preg_match('#^/api/people/(\d+)/relations$#', $url, $match) && $method === 'POST') $response = $this->createRelation((int) $match[1], $inner);
            else $response = $this->json(['error' => 'Opération inconnue.'], 422);
            if ($response->getStatusCode() >= 400) { $connection->rollBack(); return $response; }
            $connection->insert('offline_receipt', ['account_identifier' => $account, 'operation_id' => $key, 'response' => $response->getContent()]);
            $connection->commit();
            return $response;
        } catch (\Throwable $error) {
            if ($connection->isTransactionActive()) $connection->rollBack();
            throw $error;
        }
    }

    #[Route('/genealogy', name: 'api_genealogy', methods: ['GET'])]
    public function document(Request $request): JsonResponse
    {
        $families = $this->familiesForUser();
        $selectedId = $request->query->getInt('family');
        $selected = $selectedId ? $this->familyRepository->findOneBy(['id' => $selectedId, 'accountIdentifier' => $this->accountIdentifier()]) : ($families[0] ?? null);

        return $this->json([
            'families' => array_map(fn (Family $family) => ['id' => $family->getId(), 'name' => $family->getName()], $families),
            'selectedFamilyId' => $selected?->getId(),
            'document' => $selected ? $this->serializeDocument($selected) : null,
        ]);
    }

    #[Route('/families', name: 'api_family_create', methods: ['POST'])]
    public function createFamily(Request $request): JsonResponse
    {
        $payload = $this->payload($request);
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') return $this->json(['error' => 'Le nom de la famille est obligatoire.'], Response::HTTP_UNPROCESSABLE_ENTITY);

        $family = (new Family())->setName($name)->setAccountIdentifier($this->accountIdentifier());
        $this->entityManager->persist($family);
        $this->entityManager->flush();

        return $this->json(['id' => $family->getId(), 'name' => $family->getName()], Response::HTTP_CREATED);
    }

    #[Route('/families/{id}/people', name: 'api_person_create', methods: ['POST'])]
    public function createPerson(int $id, Request $request): JsonResponse
    {
        $family = $this->familyForUser($id);
        $person = $this->newPerson($family, $this->payload($request));
        $this->entityManager->persist($person);
        $this->entityManager->flush();

        return $this->json($this->serializePerson($person), Response::HTTP_CREATED);
    }

    #[Route('/people/{id}', name: 'api_person_update', methods: ['PATCH'])]
    public function updatePerson(int $id, Request $request): JsonResponse
    {
        $person = $this->personForUser($id);
        $payload = $this->payload($request);
        $this->applyPersonPayload($person, $payload);
        $this->entityManager->flush();

        return $this->json($this->serializePerson($person));
    }

    #[Route('/people/{id}/relations', name: 'api_relation_create', methods: ['POST'])]
    public function createRelation(int $id, Request $request): JsonResponse
    {
        $person = $this->personForUser($id);
        $payload = $this->payload($request);
        $type = (string) ($payload['type'] ?? '');
        if (!in_array($type, ['parent', 'child', 'sibling'], true)) {
            return $this->json(['error' => 'Type de relation inconnu.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $relative = null;
        if (!empty($payload['personId'])) {
            $relative = $this->personForUser((int) $payload['personId']);
            if ($relative->getFamily()?->getId() !== $person->getFamily()?->getId()) {
                throw $this->createNotFoundException('Cette personne n’appartient pas à la famille sélectionnée.');
            }
            if ($relative->getId() === $person->getId()) {
                return $this->json(['error' => 'Une personne ne peut pas être liée à elle-même.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $this->applyPersonPayload($relative, $payload);
        } else {
            $relative = $this->newPerson($person->getFamily(), $payload);
            $this->entityManager->persist($relative);
        }
        if ($type === 'parent') $person->addParent($relative);
        if ($type === 'child') $relative->addParent($person);
        if ($type === 'sibling') foreach ($person->getParent() as $parent) $relative->addParent($parent);
        $this->entityManager->flush();

        return $this->json($this->serializePerson($relative), Response::HTTP_CREATED);
    }

    private function serializeDocument(Family $family): array
    {
        $people = [];
        foreach ($family->getPeople() as $person) $people[$person->getId()] = $this->serializePerson($person);

        $unions = [];
        foreach ($this->unionRepository->findForFamily($family) as $union) {
            $unions['u-'.$union->getId()] = [
                'id' => 'u-'.$union->getId(),
                'partnerIds' => array_values(array_filter(array_map(fn (Person $person) => $person->getId(), $union->getPerson1()->toArray()))),
                'marriage' => $union->getStartdate() ? ['date' => $union->getStartdate()->format('Y-m-d')] : null,
                'children' => array_map(fn (Person $child) => ['personId' => $child->getId(), 'relationship' => 'biological'], $union->getChildren()->toArray()),
            ];
        }

        foreach ($family->getPeople() as $child) {
            $parents = $child->getParent()->toArray();
            if ($parents === []) continue;
            $alreadyLinked = array_filter($unions, fn (array $union) => in_array($child->getId(), array_column($union['children'], 'personId'), true));
            if ($alreadyLinked !== []) continue;
            $unions['parent-'.$child->getId()] = [
                'id' => 'parent-'.$child->getId(),
                'partnerIds' => array_values(array_filter(array_map(fn (Person $parent) => $parent->getId(), $parents))),
                'marriage' => null,
                'children' => [['personId' => $child->getId(), 'relationship' => 'biological']],
            ];
        }

        $root = $family->getPeople()->first();
        $root = $root instanceof Person ? $root : null;
        return ['id' => 'family-'.$family->getId(), 'title' => $family->getName(), 'rootPersonId' => $root?->getId(), 'people' => $people, 'unions' => $unions];
    }

    private function serializePerson(Person $person): array
    {
        return [
            'id' => (string) $person->getId(),
            'givenName' => $person->getFirstName(),
            'familyName' => $person->getLastName(),
            'sex' => $this->serializeGender($person),
            'birth' => $person->getBirthdate() ? ['date' => $person->getBirthdate()->format('Y-m-d'), 'place' => $person->getBirthPlace()] : null,
            'death' => $person->getDeathdate() ? ['date' => $person->getDeathdate()->format('Y-m-d'), 'place' => $person->getDeathPlace()] : null,
            'lifeEvents' => $person->getLifeEvents(),
            'photoUrl' => $person->getPhotoUrls()[0] ?? null,
            'photoUrls' => $person->getPhotoUrls(),
        ];
    }

    private function newPerson(?Family $family, array $payload): Person
    {
        $person = (new Person())->setFamily($family);
        $this->applyPersonPayload($person, $payload);
        return $person;
    }

    private function applyPersonPayload(Person $person, array $payload): void
    {
        if (array_key_exists('givenName', $payload)) $person->setFirstName($this->requiredPersonName($payload['givenName'], 'Nouvelle'));
        if (array_key_exists('lastName', $payload)) $person->setLastName($this->requiredPersonName($payload['lastName'], 'Personne'));
        if (array_key_exists('birthDate', $payload)) $person->setBirthdate($this->date($payload['birthDate']));
        if (array_key_exists('birthPlace', $payload)) $person->setBirthPlace($this->nullableString($payload['birthPlace']));
        if (array_key_exists('deathDate', $payload)) $person->setDeathdate($this->date($payload['deathDate']));
        if (array_key_exists('deathPlace', $payload)) $person->setDeathPlace($this->nullableString($payload['deathPlace']));
        if (array_key_exists('lifeEvents', $payload) && is_array($payload['lifeEvents'])) $person->setLifeEvents(array_values($payload['lifeEvents']));
        if (array_key_exists('sex', $payload)) $person->setGender($this->genderForSex($payload['sex']));
    }

    private function serializeGender(Person $person): string
    {
        $name = strtolower((string) $person->getGender()?->getName());
        if (in_array($name, ['m', 'male', 'masculin', 'homme'], true)) return 'M';
        if (in_array($name, ['f', 'female', 'féminin', 'feminin', 'femme'], true)) return 'F';
        return 'X';
    }

    private function genderForSex(mixed $sex): ?\App\Entity\Gender
    {
        $sex = strtoupper(trim((string) $sex));
        if (!in_array($sex, ['M', 'F'], true)) return null;

        $names = $sex === 'M' ? ['Male', 'Masculin', 'Homme', 'M'] : ['Female', 'Féminin', 'Feminin', 'Femme', 'F'];
        foreach ($names as $name) {
            $gender = $this->genderRepository->findOneBy(['name' => $name]);
            if ($gender) return $gender;
        }

        $gender = (new \App\Entity\Gender())->setName($sex === 'M' ? 'Male' : 'Female');
        $this->entityManager->persist($gender);
        return $gender;
    }

    private function familyForUser(int $id): Family
    {
        $family = $this->familyRepository->findOneBy(['id' => $id, 'accountIdentifier' => $this->accountIdentifier()]);
        if (!$family) throw $this->createNotFoundException('Famille introuvable.');
        return $family;
    }

    private function personForUser(int $id): Person
    {
        $person = $this->personRepository->find($id);
        if (!$person || !$person->getFamily() || $person->getFamily()->getAccountIdentifier() !== $this->accountIdentifier()) throw $this->createNotFoundException('Personne introuvable.');
        return $person;
    }

    /** @return list<Family> */
    private function familiesForUser(): array { return $this->familyRepository->findForAccount($this->accountIdentifier()); }
    private function accountIdentifier(): string { return $this->getUser()?->getUserIdentifier() ?? throw $this->createAccessDeniedException(); }
    private function payload(Request $request): array { $data = json_decode($request->getContent(), true); return is_array($data) ? $data : []; }
    private function nullableString(mixed $value): ?string { $value = trim((string) $value); return $value === '' ? null : $value; }
    private function requiredPersonName(mixed $value, string $fallback): string { $value = trim((string) $value); return $value === '' ? $fallback : $value; }
    private function date(mixed $value): ?\DateTime { $value = trim((string) $value); return $value === '' ? null : new \DateTime($value); }
}
