<?php
/**
 * GENERIC FILESYSTEM CACHE ENGINE
 *
 * Responsibility:
 * - Low-level JSON file cache storage
 * - Provides get/set/delete/ttl logic
 * - Handles schema versioning and atomic writes
 *
 * Rules:
 * - MUST NOT know about CMS
 * - MUST NOT know about routes
 * - MUST NOT know about page structure (meta/components)
 * - MUST remain reusable for ALL subsystems:
 *      - CMS
 *      - Google Sheets cache
 *      - menus
 *      - ticker
 *      - events
 *
 * This is INFRASTRUCTURE ONLY (not application logic).
 */


class GoogleDriveCache {

    /**
     * Schema Version
     */
	protected static int $schemaVersion = 1;

    /**
     * Base cache path
     */
    protected static string $basePath =
        ROOT_PATH . 'kgs-cache/google/';

    /**
     * Default TTL
     */
    protected static int $defaultTtl = 3600;

    /*
    |--------------------------------------------------------------------------
    | GET
    |--------------------------------------------------------------------------
    */

    public static function get(
        string $group,
        string $key,
        ?int $ttl = null
    ): ?array {

        $ttl ??= self::$defaultTtl;

        $file = self::path($group, $key);

        if (!file_exists($file)) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | TTL CHECK
        |--------------------------------------------------------------------------
        */

        if ($ttl > 0) {

            $age = time() - filemtime($file);

            if ($age > $ttl) {
                return null;
            }
        }

        $json = file_get_contents($file);

        if ($json === false) {
            return null;
        }

		$decoded = json_decode($json, true);

		if (!is_array($decoded)) {
			return null;
		}

		if (!self::isValidSchema($decoded)) {
			$repaired = self::repairOrDelete($file, $decoded, $group, $key);

			if ($repaired !== null) {
				return $repaired;
			}

			return null;
		}

        /*
        |--------------------------------------------------------------------------
        | RETURN DATA PAYLOAD
        |--------------------------------------------------------------------------
        */

        return $decoded['data'] ?? null;
    }

    /*
    |--------------------------------------------------------------------------
    | GET RAW CACHE OBJECT
    |--------------------------------------------------------------------------
    */

    public static function getRaw(
        string $group,
        string $key
    ): ?array {

        $file = self::path($group, $key);

        if (!file_exists($file)) {
            return null;
        }

        $json = file_get_contents($file);

        if ($json === false) {
            return null;
        }

		$decoded = json_decode($json, true);

		if (!is_array($decoded)) {
			return null;
		}

		if (!self::isValidSchema($decoded)) {
			return null;
		}

		return $decoded;
    }

    /*
    |--------------------------------------------------------------------------
    | SET
    |--------------------------------------------------------------------------
    */

    public static function set(
        string $group,
        string $key,
        array $data,
        array $meta = []
    ): bool {

        $file = self::path($group, $key);

        $dir = dirname($file);

        /*
        |--------------------------------------------------------------------------
        | ENSURE DIRECTORY EXISTS
        |--------------------------------------------------------------------------
        */

        if (!is_dir($dir)) {

            if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
                return false;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | CACHE PAYLOAD
        |--------------------------------------------------------------------------
        */

		$payload = [

			'_schema_version' => self::$schemaVersion,

			'_cached_at' => time(),

			'_group' => $group,

			'_key' => $key,

			'_generated' => date('c'),

			'_meta' => $meta,

			'data' => $data
		];

        /*
        |--------------------------------------------------------------------------
        | ATOMIC WRITE
        |--------------------------------------------------------------------------
        */

        $tempFile = $file . '.tmp';

        $written = file_put_contents(
            $tempFile,
            json_encode(
                $payload,
                JSON_PRETTY_PRINT |
                JSON_UNESCAPED_SLASHES
            )
        );

        if ($written === false) {
            return false;
        }

        return rename($tempFile, $file);
    }

    /*
    |--------------------------------------------------------------------------
    | EXISTS
    |--------------------------------------------------------------------------
    */

    public static function exists(
        string $group,
        string $key
    ): bool {

        return file_exists(
            self::path($group, $key)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | setRaw() (BYPASS WRAPPER, FULL CONTROL WRITE
    |--------------------------------------------------------------------------
    */
	
	public static function setRaw(string $group, string $key, array $payload): bool
	{
		$file = self::path($group, $key);

		$dir = dirname($file);

		if (!is_dir($dir)) {
			if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
				return false;
			}
		}

		if (!isset($payload['_schema_version'])) {
			$payload['_schema_version'] = self::$schemaVersion;
		}

		$data = json_encode(
			$payload,
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);

		if ($data === false) {
			return false;
		}

		$tmp = $file . '.tmp';

		if (file_put_contents($tmp, $data) === false) {
			return false;
		}

		return rename($tmp, $file);
	}


    /*
    |--------------------------------------------------------------------------
    | touch() (REFRESH TIMESTAMP ONLY)
    |--------------------------------------------------------------------------
    */
	
	public static function touch(string $group, string $key): bool
	{
		$file = self::path($group, $key);

		if (!file_exists($file)) {
			return false;
		}

		return touch($file);
	}

    /*
    |--------------------------------------------------------------------------
    | invalidateGroupPrefix() (PARTIAL DRIVE TREE CLEARING)
    |--------------------------------------------------------------------------
    */
	
	public static function invalidateGroupPrefix(string $group, string $prefix): int
	{
		$dir = self::groupPath($group);

		if (!is_dir($dir)) {
			return 0;
		}

		$count = 0;

		$files = glob($dir . '*.json');

		foreach ($files as $file) {

			$name = basename($file, '.json');

			if (str_starts_with($name, $prefix)) {

				if (unlink($file)) {
					$count++;
				}
			}
		}

		return $count;
	}

    /*
    |--------------------------------------------------------------------------
    | getMeta() (READ CACHE METADATA WITHOUT PAYLOAD)
    |--------------------------------------------------------------------------
    */
	
	public static function getMeta(string $group, string $key): ?array
	{
		$file = self::path($group, $key);

		if (!file_exists($file)) {
			return null;
		}

		$json = file_get_contents($file);

		if ($json === false) {
			return null;
		}

		$decoded = json_decode($json, true);

		if (!is_array($decoded)) {
			return null;
		}
		
		if (!self::isValidSchema($decoded)) {
			return null;
		}

		return $decoded['_meta'] ?? [
			'_cached_at'  => $decoded['_cached_at'] ?? null,
			'_generated'  => $decoded['_generated'] ?? null,
			'_group'      => $decoded['_group'] ?? $group,
			'_key'        => $decoded['_key'] ?? $key,
		];
	}


    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    public static function delete(
        string $group,
        string $key
    ): bool {

        $file = self::path($group, $key);

        if (!file_exists($file)) {
            return true;
        }

        return unlink($file);
    }

    /*
    |--------------------------------------------------------------------------
    | CLEAR GROUP
    |--------------------------------------------------------------------------
    */

    public static function clearGroup(
        string $group
    ): int {

        $dir = self::groupPath($group);

        if (!is_dir($dir)) {
            return 0;
        }

        $count = 0;

        foreach (glob($dir . '*.json') as $file) {

            if (unlink($file)) {
                $count++;
            }
        }

        return $count;
    }

    /*
    |--------------------------------------------------------------------------
    | CLEAR ALL CACHE
    |--------------------------------------------------------------------------
    */

    public static function clearAll(): int
    {
        $count = 0;

        foreach (self::groups() as $group) {
            $count += self::clearGroup($group);
        }

        return $count;
    }

    /*
    |--------------------------------------------------------------------------
    | GET AGE
    |--------------------------------------------------------------------------
    */

    public static function age(
        string $group,
        string $key
    ): ?int {

        $file = self::path($group, $key);

        if (!file_exists($file)) {
            return null;
        }

        return time() - filemtime($file);
    }

    /*
    |--------------------------------------------------------------------------
    | IS STALE
    |--------------------------------------------------------------------------
    */

    public static function isStale(
        string $group,
        string $key,
        ?int $ttl = null
    ): bool {

        $ttl ??= self::$defaultTtl;

        $age = self::age($group, $key);

        if ($age === null) {
            return true;
        }

        return $age > $ttl;
    }

    /*
    |--------------------------------------------------------------------------
    | LAST MODIFIED
    |--------------------------------------------------------------------------
    */

    public static function lastModified(
        string $group,
        string $key
    ): ?int {

        $file = self::path($group, $key);

        if (!file_exists($file)) {
            return null;
        }

        return filemtime($file);
    }

    /*
    |--------------------------------------------------------------------------
    | GET CACHE FILE PATH
    |--------------------------------------------------------------------------
    */

    public static function file(
        string $group,
        string $key
    ): string {

        return self::path($group, $key);
    }

    /*
    |--------------------------------------------------------------------------
    | ENSURE GROUP EXISTS
    |--------------------------------------------------------------------------
    */

    public static function ensureGroup(
        string $group
    ): bool {

        $dir = self::groupPath($group);

        if (is_dir($dir)) {
            return true;
        }

        return mkdir($dir, 0777, true);
    }

    /*
    |--------------------------------------------------------------------------
    | GROUP PATH
    |--------------------------------------------------------------------------
    */

    protected static function groupPath(
        string $group
    ): string {

        return self::$basePath .
            self::sanitize($group) .
            '/';
    }

    /*
    |--------------------------------------------------------------------------
    | CACHE FILE PATH
    |--------------------------------------------------------------------------
    */

    protected static function path(
        string $group,
        string $key
    ): string {

        return self::groupPath($group) .
            self::sanitize($key) .
            '.json';
    }
	
	
	/*
	|--------------------------------------------------------------------------
	| SCHEMA VALIDATION
	|--------------------------------------------------------------------------
	*/

	protected static function isValidSchema(array $decoded): bool
	{
		return ($decoded['_schema_version'] ?? 0)
			=== self::$schemaVersion;
	}

    /*
    |--------------------------------------------------------------------------
    | SANITIZE
    |--------------------------------------------------------------------------
    */

    protected static function sanitize(
        string $value
    ): string {

        return preg_replace(
            '/[^a-zA-Z0-9_\-]/',
            '',
            $value
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CACHE GROUPS
    |--------------------------------------------------------------------------
    */

    public static function groups(): array
    {
        return [
		
			'cms_pages_cache',

            'documents',

            'uploads',

            'ticker',

            'events',

            'menus',

            'sheets',

            'slides',

            'misc'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | INITIALIZE CACHE DIRECTORIES
    |--------------------------------------------------------------------------
    */

    public static function initialize(): void
    {
        foreach (self::groups() as $group) {
            self::ensureGroup($group);
        }
    }
	
	
	

	
	
	/*
	|--------------------------------------------------------------------------
	| CACHE REPAIR TOOL (PAGES GROUP)
	|--------------------------------------------------------------------------
	|
	| Scans pages cache and rebuilds invalid schema entries.
	| - Fixes missing schema version
	| - Wraps legacy raw payloads
	| - Rewrites corrupted JSON safely
	|
	*/

	public static function repairPagesCache(): array
	{
		$dir = self::groupPath('pages');

		if (!is_dir($dir)) {
			return [
				'repaired' => 0,
				'skipped' => 0,
				'errors' => 0
			];
		}

		$files = glob($dir . '*.json');

		$repaired = 0;
		$skipped  = 0;
		$errors   = 0;

		foreach ($files as $file) {

			$json = file_get_contents($file);

			if ($json === false) {
				$errors++;
				continue;
			}

			$decoded = json_decode($json, true);

			if (!is_array($decoded)) {
				$errors++;
				continue;
			}

			$needsRepair = false;

			// Case 1: schema missing or invalid
			if (($decoded['_schema_version'] ?? 0) !== self::$schemaVersion) {
				$needsRepair = true;
			}

			// Case 2: legacy format (already normalized CMS page)
			$isLegacyPage =
				isset($decoded['meta']) ||
				isset($decoded['components']);

			if ($isLegacyPage && !isset($decoded['_schema_version'])) {
				$needsRepair = true;
			}

			if (!$needsRepair) {
				$skipped++;
				continue;
			}

			/*
			|--------------------------------------------------------------------------
			| REBUILD LOGIC
			|--------------------------------------------------------------------------
			*/

			$repairedPayload = [

				'_schema_version' => self::$schemaVersion,

				'_cached_at' => $decoded['_cached_at'] ?? time(),

				'_group' => 'pages',

				'_key' => basename($file, '.json'),

				'_generated' => date('c'),

				'_meta' => $decoded['_meta'] ?? [],

				// normalize known CMS shape
				'data' => [
					'meta' => $decoded['meta'] ?? ($decoded['data']['meta'] ?? []),
					'components' => $decoded['components'] ?? ($decoded['data']['components'] ?? [])
				]
			];

			$tmp = $file . '.repair.tmp';

			$written = file_put_contents(
				$tmp,
				json_encode($repairedPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
			);

			if ($written === false) {
				$errors++;
				continue;
			}

			if (!rename($tmp, $file)) {
				$errors++;
				continue;
			}

			$repaired++;
		}

		return [
			'repaired' => $repaired,
			'skipped' => $skipped,
			'errors' => $errors
		];
	}
	
	protected static function repairOrDelete(string $file, array $decoded, string $group, string $key): ?array
	{
		// If file is totally unusable, delete it
		if (!is_array($decoded)) {
			unlink($file);
			return null;
		}

		// CASE 1: missing schema entirely → treat as legacy payload
		if (!isset($decoded['_schema_version'])) {

			$payload = [
				'_schema_version' => self::$schemaVersion,
				'_cached_at' => time(),
				'_group' => $group,
				'_key' => $key,
				'_generated' => date('c'),
				'_meta' => [],
				'data' => $decoded // assume whole file is data
			];

			file_put_contents($file, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

			return $payload['data'];
		}

		// CASE 2: wrong schema version → delete (strict mode)
		if (($decoded['_schema_version'] ?? null) !== self::$schemaVersion) {
			unlink($file);
			return null;
		}

		return null;
	}
	

		
}