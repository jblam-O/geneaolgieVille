<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaStorage
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $projectDir,
        private readonly string $blobToken = '',
    ) {}

    /** @return array{url: string, mimeType: string, originalName: string, type: string} */
    public function store(UploadedFile $file): array
    {
        if (!$file->isValid() || $file->getSize() > 4_000_000) {
            throw new \RuntimeException('Le média doit être valide et ne pas dépasser 4 Mo.');
        }

        $mimeType = $file->getMimeType() ?? 'application/octet-stream';
        $type = str_starts_with($mimeType, 'image/') ? 'image' : (str_starts_with($mimeType, 'video/') ? 'video' : '');
        if ($type === '') {
            throw new \RuntimeException('Seules les images et vidéos sont acceptées.');
        }

        $extension = $file->guessExtension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION) ?: 'bin';
        $filename = sprintf('%s-%s.%s', date('YmdHis'), bin2hex(random_bytes(6)), strtolower($extension));
        $pathname = 'history-towns/'.$filename;

        if ($this->blobToken !== '') {
            $response = $this->httpClient->request('PUT', 'https://blob.vercel-storage.com/?pathname='.rawurlencode($pathname), [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->blobToken,
                    'x-api-version' => '12',
                    'x-vercel-blob-access' => 'public',
                    'x-add-random-suffix' => '1',
                    'x-content-type' => $mimeType,
                ],
                'body' => file_get_contents($file->getPathname()),
            ]);
            $data = $response->toArray();
            $url = (string) ($data['url'] ?? '');
            if ($url === '') {
                throw new \RuntimeException('Vercel Blob n’a pas renvoyé d’URL.');
            }
        } else {
            $directory = $this->projectDir.'/public/uploads/history-towns';
            if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new \RuntimeException('Impossible de créer le dossier local des médias.');
            }
            $file->move($directory, $filename);
            $url = '/uploads/history-towns/'.$filename;
        }

        return ['url' => $url, 'mimeType' => $mimeType, 'originalName' => $file->getClientOriginalName(), 'type' => $type];
    }
}
