<?php

use Google\Client;
use Google\Service\Drive;
use Google\Service\Sheets;
use Google\Service\Calendar;

class GoogleService
{
    protected static ?Client $client = null;

    /*
    |--------------------------------------------------------------------------
    | CLIENT
    |--------------------------------------------------------------------------
    */

    public static function client(): Client
    {
        if (self::$client !== null) {
            return self::$client;
        }

        $json = config_value('google_service_account_json');

        if (empty($json)) {
            throw new Exception(
                'Missing Google service account JSON.'
            );
        }

        $credentials = json_decode($json, true);

        if (!is_array($credentials)) {
            throw new Exception(
                'Invalid Google service account JSON.'
            );
        }

        $client = new Client();

        $client->setAuthConfig($credentials);

        $client->setScopes([
            Drive::DRIVE_READONLY,
            Sheets::SPREADSHEETS_READONLY,
            Calendar::CALENDAR_READONLY,
        ]);

        self::$client = $client;

        return $client;
    }

    /*
    |--------------------------------------------------------------------------
    | GOOGLE DRIVE
    |--------------------------------------------------------------------------
    */

    public static function drive(): Drive
    {
        return new Drive(
            self::client()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GOOGLE SHEETS
    |--------------------------------------------------------------------------
    */

    public static function sheets(): Sheets
    {
        return new Sheets(
            self::client()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GOOGLE CALENDAR
    |--------------------------------------------------------------------------
    */

    public static function calendar(): Calendar
    {
        return new Calendar(
            self::client()
        );
    }
}