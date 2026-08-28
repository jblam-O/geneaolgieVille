<?php

namespace App\Controller;

use App\Entity\Town;
use App\Entity\TownEvent;
use App\Entity\TownEventMedia;
use App\Repository\TownRepository;
use App\Service\MediaStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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

    #[Route('/historyTowns/events', name: 'app_history_towns_add', methods: ['POST'])]
    public function add(
        Request $request,
        TownRepository $townRepository,
        EntityManagerInterface $entityManager,
        MediaStorage $mediaStorage,
    ): Response {
        if (!$this->isCsrfTokenValid('add-town-event', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton de formulaire invalide.');
        }

        $town = $townRepository->findOneBy(['slug' => $request->request->getString('town')]);
        $title = trim($request->request->getString('title'));
        $summary = trim($request->request->getString('summary'));
        $detail = trim($request->request->getString('detail'));
        $year = $request->request->getInt('year');
        if (!$town || $title === '' || $summary === '' || $detail === '' || $year < -4000 || $year > 2100) {
            throw $this->createNotFoundException('Les informations de l’événement sont incomplètes.');
        }

        $event = (new TownEvent())
            ->setTown($town)->setYear($year)
            ->setDateLabel(trim($request->request->getString('dateLabel')) ?: (string) $year)
            ->setTitle(mb_substr($title, 0, 180))->setSummary($summary)->setDetail($detail)
            ->setLatitude($request->request->get('latitude') !== '' ? $request->request->getFloat('latitude') : $town->getLatitude())
            ->setLongitude($request->request->get('longitude') !== '' ? $request->request->getFloat('longitude') : $town->getLongitude());

        $files = $request->files->all('media');
        foreach (array_slice($files, 0, 4) as $file) {
            if (!$file instanceof UploadedFile || $file->getClientOriginalName() === '') { continue; }
            $stored = $mediaStorage->store($file);
            $event->addMedia((new TownEventMedia())->setType($stored['type'])->setUrl($stored['url'])
                ->setOriginalName($stored['originalName'])->setMimeType($stored['mimeType']));
        }

        $entityManager->persist($event);
        $entityManager->flush();

        return $this->redirectToRoute('app_history_towns', ['town' => $town->getSlug(), 'added' => 1]);
    }
}
