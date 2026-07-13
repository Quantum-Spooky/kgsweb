<?php

use Google\Service\Drive;
use Google\Service\Drive\DriveFile;

class GoogleDrive
{
    protected Drive $drive;

    public function __construct()
    {
        $this->drive = GoogleService::drive();
    }

    /*
    |--------------------------------------------------------------------------
    | GET FILE
    |--------------------------------------------------------------------------
    */

    public function getFile(string $fileId): ?array
    {
        try {

            $file = $this->drive->files->get(
                $fileId,
                [
                    'fields' =>
                        'id,name,mimeType,modifiedTime,webViewLink,size,parents'
                ]
            );

            return $this->normalizeFile($file);

        } catch (Throwable $e) {

            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | LIST FOLDER
    |--------------------------------------------------------------------------
    */

    public function listFolder(
        string $folderId,
        bool $recursive = false
    ): array {

        $results = [];

        $pageToken = null;

        do {

            $response = $this->drive->files->listFiles([
                'q' =>
                    "'" . $folderId . "' in parents and trashed = false",

                'fields' =>
                    'nextPageToken, files(id,name,mimeType,modifiedTime,webViewLink,size)',

                'pageSize' => 1000,

                'pageToken' => $pageToken,
            ]);

            foreach ($response->getFiles() as $file) {

                $normalized = $this->normalizeFile($file);

                /*
                |--------------------------------------------------------------------------
                | RECURSIVE
                |--------------------------------------------------------------------------
                */

                if (
                    $recursive &&
                    $normalized['is_folder']
                ) {

                    $normalized['children'] =
                        $this->listFolder(
                            $normalized['id'],
                            true
                        );
                }

                $results[] = $normalized;
            }

            $pageToken = $response->getNextPageToken();

        } while ($pageToken);

        return $results;
    }

    /*
    |--------------------------------------------------------------------------
    | LIST FOLDERS ONLY
    |--------------------------------------------------------------------------
    */

    public function listFolders(
        string $folderId
    ): array {

        return array_values(
            array_filter(
                $this->listFolder($folderId),
                fn($item) => $item['is_folder']
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | LIST FILES ONLY
    |--------------------------------------------------------------------------
    */

    public function listFiles(
        string $folderId
    ): array {

        return array_values(
            array_filter(
                $this->listFolder($folderId),
                fn($item) => !$item['is_folder']
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DOWNLOAD FILE CONTENT
    |--------------------------------------------------------------------------
    */

    public function download(string $fileId): ?string
    {
        try {

            $response = $this->drive
                ->files
                ->get(
                    $fileId,
                    ['alt' => 'media']
                );

            return $response->getBody()->getContents();

        } catch (Throwable $e) {

            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORT GOOGLE DOC
    |--------------------------------------------------------------------------
    */

    public function exportGoogleDoc(
        string $fileId,
        string $mimeType = 'text/plain'
    ): ?string {

        try {

            $response = $this->drive
                ->files
                ->export(
                    $fileId,
                    $mimeType,
                    ['alt' => 'media']
                );

            return $response->getBody()->getContents();

        } catch (Throwable $e) {

            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | NORMALIZE FILE
    |--------------------------------------------------------------------------
    */

    protected function normalizeFile(
        DriveFile $file
    ): array {

        $mime = $file->getMimeType();

        return [

            'id' => $file->getId(),

            'name' => $file->getName(),

            'mime_type' => $mime,

            'modified_time' =>
                $file->getModifiedTime(),

            'web_view_link' =>
                $file->getWebViewLink(),

            'size' =>
                $file->getSize(),

            'is_folder' =>
                $mime ===
                'application/vnd.google-apps.folder',

            'is_google_doc' =>
                str_starts_with(
                    $mime,
                    'application/vnd.google-apps'
                ),
        ];
    }
}