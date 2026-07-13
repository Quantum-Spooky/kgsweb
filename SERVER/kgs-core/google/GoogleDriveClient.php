<?php

/**
 * GoogleDriveClient (minimal working core)
 *
 * Currently supports:
 * - Google Sheets via api_tool connector
 *
 * Future:
 * - Drive files
 * - Calendar
 * - Slides
 */

class GoogleDriveClient
{
    /**
     * Fetch rows from a Google Sheet
     *
     * Expected format:
     * A: alias
     * B: route
     */
    public function getSheetRows(string $sheetId, string $range = 'A:B'): array
    {
        if (!$sheetId) {
            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | GOOGLE SHEETS VIA CONNECTOR
        |--------------------------------------------------------------------------
        |
        | This uses the system connector layer (api_tool) to avoid direct SDK dependency.
        | */

        $response = api_tool([
            'connector' => 'google_sheets',
            'action' => 'read',
            'spreadsheet_id' => $sheetId,
            'range' => $range,
        ]);

        $rows = $response['values'] ?? [];

        if (!is_array($rows)) {
            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | NORMALIZE OUTPUT
        |--------------------------------------------------------------------------
        */

        $output = [];

        foreach ($rows as $row) {

            if (!is_array($row)) {
                continue;
            }

            $output[] = $row;
        }

        return $output;
    }

    /**
     * Convenience: map alias sheet into structured routes
     */
    public function getRouteAliasesFromSheet(string $sheetId): array
    {
        $rows = $this->getSheetRows($sheetId);

        $aliases = [];

        foreach ($rows as $row) {

            $alias = $row[0] ?? null;
            $route = $row[1] ?? null;

            if (!$alias || !$route) {
                continue;
            }

            $aliases[] = [
                'alias' => $alias,
                'route' => $route,
            ];
        }

        return $aliases;
    }
}