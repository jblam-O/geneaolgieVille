<?php

namespace App\Controller;

use App\Entity\Town;
use App\Entity\TownEvent;
use App\Entity\TownEventMedia;
use App\Repository\TownRepository;
use App\Service\Geocoder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class HistoryTownsController extends AbstractController
{
    #[Route('/historyTowns', name: 'app_history_towns', methods: ['GET'])]
    public function index(Request $request, TownRepository $townRepository): Response
    {
        $towns = $townRepository->findBy([], ['name' => 'ASC']);
        $selected = $request->query->getString('town');
        $town = $selected !== '' ? $townRepository->findOneBy(['slug' => $selected]) : null;
        $town ??= $towns[0] ?? null;

        $events = [];
        if ($town instanceof Town) {
            foreach ($town->getEvents() as $event) {
                $events[] = [
                    'id' => $event->getId(), 'year' => $event->getYear(), 'dateLabel' => $event->getDateLabel(),
                    'title' => $event->getTitle(), 'summary' => $event->getSummary(), 'detail' => $event->getDetail(),
                    'address' => $event->getAddress(),
                    'latitude' => $event->getLatitude() ?? $town->getLatitude(),
                    'longitude' => $event->getLongitude() ?? $town->getLongitude(),
                    'media' => array_map(static fn (TownEventMedia $media): array => [
                        'type' => $media->getType(), 'url' => $media->getUrl(), 'name' => $media->getOriginalName(),
                    ], $event->getMedia()->toArray()),
                ];
            }
        }

        return $this->render('history_towns/index.html.twig', [
            'towns' => array_map(static fn (Town $item): array => [
                'name' => $item->getName(), 'slug' => $item->getSlug(),
                'latitude' => $item->getLatitude(), 'longitude' => $item->getLongitude(),
            ], $towns),
            'town' => $town,
            'events' => $events,
            'added' => $request->query->getBoolean('added'),
        ]);
    }

    #[Route('/historyTowns/geocode', name: 'app_history_towns_geocode', methods: ['GET'])]
    public function geocode(Request $request, Geocoder $geocoder): JsonResponse
    {
        if (!$this->isCsrfTokenValid('geocode-town-event', $request->query->getString('_token'))) {
            return $this->json(['error' => 'Jeton de formulaire invalide.'], 403);
        }
        $town = trim($request->query->getString('town'));
        $address = trim($request->query->getString('address'));
        if ($town === '' || $address === '') {
            return $this->json(['error' => 'Saisissez une ville et une adresse.'], 422);
        }
        $result = $geocoder->locate($address, $town);

        return $result
            ? $this->json($result)
            : $this->json(['error' => 'Adresse introuvable. Précisez le numéro, la rue et la ville.'], 404);
    }

    #[Route('/historyTowns/events/{id}/position', name: 'app_history_towns_position', methods: ['POST'])]
    public function updatePosition(TownEvent $event, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload) || !$this->isCsrfTokenValid('move-town-event', (string) ($payload['_token'] ?? ''))) {
            return $this->json(['error' => 'Jeton de formulaire invalide.'], 403);
        }
        $latitude = $payload['latitude'] ?? null;
        $longitude = $payload['longitude'] ?? null;
        if (!is_numeric($latitude) || !is_numeric($longitude)
            || (float) $latitude < -90 || (float) $latitude > 90
            || (float) $longitude < -180 || (float) $longitude > 180) {
            return $this->json(['error' => 'Position invalide.'], 422);
        }
        $event->setLatitude((float) $latitude)->setLongitude((float) $longitude);
        $entityManager->flush();

        return $this->json(['saved' => true]);
    }

    #[Route('/historyTowns/events', name: 'app_history_towns_add', methods: ['POST'])]
    public function add(
        Request $request,
        TownRepository $townRepository,
        EntityManagerInterface $entityManager,
        Geocoder $geocoder,
        SluggerInterface $slugger,
    ): Response {
        if (!$this->isCsrfTokenValid('add-town-event', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton de formulaire invalide.');
        }

        $townName = trim($request->request->getString('townName'));
        $town = $townRepository->findOneByNormalizedName($townName);
        $address = trim($request->request->getString('address'));
        $title = trim($request->request->getString('title'));
        $summary = trim($request->request->getString('summary'));
        $detail = trim($request->request->getString('detail'));
        $year = $request->request->getInt('year');
        if ($townName === '' || $address === '' || $title === '' || $summary === '' || $detail === '' || $year < -4000 || $year > 2100) {
            throw $this->createNotFoundException('Les informations de l’événement sont incomplètes.');
        }

        $latitude = $request->request->get('latitude');
        $longitude = $request->request->get('longitude');
        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            $location = $geocoder->locate($address, $townName);
            if (!$location) {
                throw $this->createNotFoundException('Adresse introuvable. Vérifiez la ville et l’adresse.');
            }
            $latitude = $location['latitude'];
            $longitude = $location['longitude'];
        }
        $latitude = (float) $latitude;
        $longitude = (float) $longitude;
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            throw $this->createNotFoundException('La position géographique est invalide.');
        }

        if (!$town) {
            $baseSlug = strtolower((string) $slugger->slug($townName));
            $baseSlug = $baseSlug !== '' ? $baseSlug : 'ville';
            $slug = $baseSlug;
            $suffix = 2;
            while ($townRepository->findOneBy(['slug' => $slug])) {
                $slug = $baseSlug.'-'.$suffix++;
            }
            $town = (new Town())->setName(mb_substr($townName, 0, 120))->setSlug($slug)
                ->setLatitude($latitude)->setLongitude($longitude);
            $entityManager->persist($town);
        }

        $event = (new TownEvent())
            ->setTown($town)->setYear($year)
            ->setDateLabel(trim($request->request->getString('dateLabel')) ?: (string) $year)
            ->setTitle(mb_substr($title, 0, 180))->setSummary($summary)->setDetail($detail)
            ->setAddress(mb_substr($address, 0, 255))
            ->setLatitude($latitude)->setLongitude($longitude);

        $entityManager->persist($event);
        $entityManager->flush();

        return $this->redirectToRoute('app_history_towns', ['town' => $town->getSlug(), 'added' => 1]);
    }
}
