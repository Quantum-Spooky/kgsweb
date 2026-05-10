<?php
/**
 * kgsweb2026/api/get-data.php
 * Main entry point for all dynamic data requests
 */

// 1. LOAD THE CONFIG (Crucial step!)
$base_dir = dirname(__DIR__);
$config = require $base_dir . '/config/config.php';

require_once 'class-kgs-helper.php';
require_once 'class-ticker-engine.php';
require_once 'class-calendar-engine.php';
require_once 'class-weather-engine.php';
require_once 'class-schooldistrict-engine.php';
require_once 'class-docs-engine.php';

header('Content-Type: application/json');

$type = $_GET['type'] ?? '';

switch ($type) {
   
   case 'ticker':
    try {
        $text = KGSTicker::get_tickerText();
        echo json_encode(['ticker' => $text]);
    } catch (Exception $e) {
        echo json_encode(['ticker' => 'Welcome to Kell Grade School!']);
    }
    break;
    
    case 'weather':
        echo json_encode(KGSWeather::get_weatherData());
        break;
        
    case 'calendar':
    case 'events':
        $source = $_GET['source'] ?? 'main';

        if ($source === 'board') {
            // Use dedicated board ID if it exists, otherwise use main
            $boardId = $config['calendars']['board'] ?? $config['calendars']['main'];
            
            // If they are the same ID, apply the 'Board' keyword filter
            $filter = ($boardId === $config['calendars']['main']) ? 'Board' : null;
            
            $data = KGSCalendar::get_upcomingEvents($boardId, $filter);
        } else {
            // Default to main calendar
            $data = KGSCalendar::get_upcomingEvents($config['calendars']['main']);
        }
        echo json_encode($data);
        break;
		
	case 'staff-directory':
        ob_clean();
        header('Content-Type: application/json');
        // Passing the Sheet ID from your config
        $data = KGSSchoolDistrict::get_staff_directory($config['sheets']['staff_directory']);
        echo json_encode($data);
        exit;
		
	case 'board-members':
		ob_clean();
		header('Content-Type: application/json');
		$data = KGSSchoolDistrict::get_board_members($config['sheets']['school_board_members']);
		echo json_encode($data);
		exit; // Critical line
        
	case 'tree':
		$rootId = $config['folders']['district_docs_root'];
		// Call the function that looks for the 'full_docs_tree' cache first
		$data = KGSDocs::get_full_tree_cached($rootId);
		echo json_encode($data);
		exit; 
        
    default:
        http_response_code(400);
        echo json_encode([
            'error' => 'Invalid request type',
            'requested' => $type
        ]);
        break;
}