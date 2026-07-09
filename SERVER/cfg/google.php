<?php
/**
 * cfg/google.php
 * Kell Grade School - Google Ecosystem IDs
 * 
 * PURPOSE:
 * This file acts as the "Switchboard" for all Google Drive, Docs, Sheets, 
 * and Calendar IDs.
 * 
 * ARCHITECTURAL ROLE:
 * These IDs are used by the Router and Components to fetch data from the 
 * kgs-cache system. By keeping them here, we avoid hardcoding long 
 * alphanumeric strings into our Content JSON files.
 */


/*
|--------------------------------------------------------------------------
| ESSENTIALS
|--------------------------------------------------------------------------
*/

/**
 * cfg/google.php
 * The Switchboard - Initial connection data.
 */
return [
    'service_account_file' => ROOT_PATH . 'cfg/google-service-account.json',
    
    // The "Master Key" that allows the worker to find the control panel
    'config_sheet_id' => '1zkL8AdBnHtnDOQeGLrXnjSgPET9SFEdB3RJv7DkVNpM',

    // Fallback in case sheet sync fails
    'master_root_folder_id' => '1o0X89kQL_cXEUpPOSZXcCUFz8bzCtmAp',
];