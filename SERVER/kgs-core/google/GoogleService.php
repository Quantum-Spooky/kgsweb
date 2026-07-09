<?php
use Google\Client;
use Google\Service\Drive;
use Google\Service\Sheets;
use Google\Service\Oauth2;
use Google\Service\Calendar;

class GoogleService
{
    protected static ?Client $client = null;
    protected static ?Client $userClient = null;

    /**
     * SYSTEM CLIENT (Service Account)
     * Used by the website and worker for all backend data operations.
     */
    public static function client(): Client
    {
        if (self::$client !== null) {
            return self::$client;
        }

        $client = new Client();
        $client->setAuthConfig(config('service_account_file'));
        $client->setScopes([
            Drive::DRIVE, 
            Sheets::SPREADSHEETS, 
            Calendar::CALENDAR_READONLY
        ]);
        $client->setAccessType('offline');

        self::$client = $client;
        return $client;
    }

    /**
     * USER CLIENT (OAuth)
     * Used strictly to verify identity of staff members for the admin form.
     */
    public static function userClient(): Client
    {
        if (self::$userClient !== null) {
            return self::$userClient;
        }

        $client = new Client();
        $client->setClientId(config('oauth_client_id'));
        $client->setClientSecret(config('oauth_client_secret'));
        $client->setRedirectUri(config('base_url_absolute') . 'admin/callback.php');
        $client->addScope('profile');
        $client->addScope('email');

        self::$userClient = $client;
        return $client;
    }

    public static function drive(): Drive { return new Drive(self::client()); }
    public static function sheets(): Sheets { return new Sheets(self::client()); }
    public static function calendar(): Calendar { return new Calendar(self::client()); }
}