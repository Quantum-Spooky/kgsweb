<?php

/**
 * GoogleDriveManager
 *
 * Responsibility:
 * - Fetch folder/file trees from Google Drive API
 * - Return raw structured arrays only
 *
 * Rules:
 * - MUST NOT write to cache
 * - MUST NOT know about CMS or routes
 * - Reusable for any Drive folder (documents, dining, calendar, etc.)
 *
 * Depends on: GoogleService::drive()
 *
 * Place at: kgs-core/google/GoogleDriveManager.php
 */

class GoogleDriveManager
{
    const MIME_FOLDER = 'application/vnd.google-apps.folder';

    /**
     * List a Google Drive folder, recursively by default.
     *
     * @param  string $folderId  Google Drive folder ID
     * @param  bool   $recursive Include subfolders
     * @return array  Nested array of items
     *
     * @throws Exception on Google API failure
     */
    public static function listFolder(string $folderId, bool $recursive = true): array
    {
        $service = GoogleService::drive();
        return self::fetchItems($service, $folderId, $recursive);
    }

    // -------------------------------------------------------------------------

    private static function fetchItems($service, string $folderId, bool $recursive): array
    {
        $params = [
            'q'                        => "'{$folderId}' in parents and trashed=false",
            'pageSize'                 => 1000,
            'spaces'                   => 'drive',
            'orderBy'                  => 'folder,name',
            'fields'                   => 'files(id,name,mimeType,modifiedTime,webViewLink,size)',
            'supportsAllDrives'        => true,
            'includeItemsFromAllDrives' => true,
        ];

        try {
            $results = $service->files->listFiles($params);
        } catch (\Exception $e) {
            error_log('GoogleDriveManager::fetchItems error [' . $folderId . ']: ' . $e->getMessage());
            throw $e;
        }

        $items = [];

        foreach ($results->getFiles() as $file) {

            $isFolder = ($file->getMimeType() === self::MIME_FOLDER);

            $item = [
                'id'           => $file->getId(),
                'name'         => $file->getName(),
                'type'         => $isFolder ? 'folder' : 'file',
                'mimeType'     => $file->getMimeType(),
                'modifiedTime' => $file->getModifiedTime(),
                'webViewLink'  => $file->getWebViewLink(),
                'size'         => $file->getSize(),
                'children'     => [],
            ];

            if ($isFolder && $recursive) {
                $item['children'] = self::fetchItems($service, $item['id'], true);
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Export a Google Doc to a specific format (HTML)
     * Corrected to use GoogleService::drive() to match listFolder()
     */
    public static function exportFile($fileId, $mimeType = 'text/html')
    {
        try {
            $service = GoogleService::drive();
            $response = $service->files->export($fileId, $mimeType, ['alt' => 'media']);
            return $response->getBody()->getContents();
        } catch (Exception $e) {
            throw new Exception("Google Export Error: " . $e->getMessage());
        }
    }

	/**
	 * Download a binary file (Image, PDF, etc.) from Google Drive.
	 */
	public static function downloadFile($fileId)
	{
		try {
			$service = GoogleService::drive();
			$response = $service->files->get($fileId, ['alt' => 'media']);
			return $response->getBody()->getContents();
		} catch (Exception $e) {
			throw new Exception("Google Download Error: " . $e->getMessage());
		}
	}
}