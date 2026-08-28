<?php

namespace App\DataFixtures;

use App\Entity\Town;
use App\Entity\TownEvent;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TownHistoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $towns = [
            ['Lille', 'lille', 50.6292, 3.0573, [
                [1054, '1054', 'Première mention de Lille', 'La ville apparaît dans une charte médiévale.', 'La mention d’« Isla » témoigne de l’implantation urbaine autour des bras de la Deûle.', 50.6380, 3.0630],
                [1667, '1667', 'Conquête par Louis XIV', 'Lille est prise durant la guerre de Dévolution.', 'Après le siège, Lille est rattachée au royaume de France et Vauban transforme profondément ses défenses.', 50.6406, 3.0500],
                [1670, '1670', 'Construction de la Citadelle', 'Vauban édifie la « reine des citadelles ».', 'L’ouvrage pentagonal devient une pièce majeure du dispositif défensif de la frontière du Nord.', 50.6417, 3.0440],
                [1892, '1892', 'Création de l’Institut industriel du Nord', 'Un jalon de l’enseignement supérieur technique lillois.', 'L’établissement contribue à la formation d’ingénieurs au cœur d’une métropole industrielle en plein essor.', 50.6345, 3.0488],
                [2004, '2004', 'Lille, capitale européenne de la culture', 'Une année culturelle transforme l’image de la métropole.', 'Expositions, fêtes et métamorphoses urbaines installent durablement Lille parmi les grandes destinations culturelles européennes.', 50.6370, 3.0636],
            ]],
            ['Marseille', 'marseille', 43.2965, 5.3698, [
                [-600, 'Vers 600 av. J.-C.', 'Fondation de Massalia', 'Des marins grecs fondent un comptoir sur le littoral.', 'Massalia devient un port majeur des échanges méditerranéens et diffuse l’influence grecque en Gaule.', 43.2969, 5.3698],
                [49, '49 av. J.-C.', 'Siège de Massalia', 'La cité est assiégée pendant la guerre civile romaine.', 'Après sa défaite face aux forces de César, Massalia conserve une identité commerciale forte malgré la perte d’une partie de son autonomie.', 43.2991, 5.3655],
                [1720, '1720', 'Grande peste de Marseille', 'Une épidémie frappe durement la ville et la Provence.', 'Arrivée par voie maritime, la peste bouleverse durablement la démographie et l’organisation sanitaire du port.', 43.3007, 5.3678],
                [1848, '1848', 'Ouverture du canal de Marseille', 'L’eau de la Durance arrive dans la ville.', 'Le canal améliore l’approvisionnement en eau et accompagne l’expansion urbaine et industrielle de Marseille.', 43.3494, 5.4532],
                [2013, '2013', 'Marseille-Provence, capitale européenne de la culture', 'Le territoire accueille une programmation culturelle internationale.', 'L’année est notamment marquée par l’ouverture du Mucem et la transformation du front de mer.', 43.2967, 5.3610],
            ]],
            ['Paris', 'paris', 48.8566, 2.3522, [
                [508, '508', 'Paris devient capitale de Clovis', 'Clovis installe le centre de son royaume à Paris.', 'La position de la cité sur la Seine renforce progressivement son rôle politique dans le royaume franc.', 48.8530, 2.3499],
                [1163, '1163', 'Début de Notre-Dame de Paris', 'Le chantier de la cathédrale gothique commence.', 'La construction s’étend sur près de deux siècles et devient l’un des grands symboles architecturaux de la capitale.', 48.8530, 2.3499],
                [1789, '14 juillet 1789', 'Prise de la Bastille', 'La forteresse parisienne est prise par les révolutionnaires.', 'L’événement devient un symbole majeur de la Révolution française et de la fin de l’absolutisme.', 48.8532, 2.3692],
                [1889, '1889', 'Inauguration de la tour Eiffel', 'La tour est construite pour l’Exposition universelle.', 'Conçue comme une prouesse temporaire, elle devient le monument emblématique de Paris.', 48.8584, 2.2945],
                [1944, '25 août 1944', 'Libération de Paris', 'Paris est libérée après plusieurs jours d’insurrection et de combats.', 'La reddition allemande est suivie d’un discours du général de Gaulle et d’un défilé sur les Champs-Élysées.', 48.8700, 2.3076],
            ]],
        ];

        foreach ($towns as [$name, $slug, $latitude, $longitude, $events]) {
            $town = (new Town())->setName($name)->setSlug($slug)->setLatitude($latitude)->setLongitude($longitude);
            $manager->persist($town);
            foreach ($events as [$year, $dateLabel, $title, $summary, $detail, $eventLat, $eventLng]) {
                $event = (new TownEvent())
                    ->setTown($town)->setYear($year)->setDateLabel($dateLabel)->setTitle($title)
                    ->setSummary($summary)->setDetail($detail)->setLatitude($eventLat)->setLongitude($eventLng);
                $manager->persist($event);
            }
        }
        $manager->flush();
    }
}
