<?php
/**
 * kgsweb2026/api/config.php
 * Master configuration for Google API Handshake and Folder Routing.
 */

return [

	'base_url' => 'https://kellgradeschool.com/kgs2026', // No trailing slash here
    'debug_mode' => false, // Set to false when the site is "live"
    'version'    => '1.0.1', // Increment this when you make major CSS changes
	
	// GOOGLE SERVICE ACCOUNT DATA
	
    'google_auth' => [
        "type" => "service_account",
        "project_id" => "kgs-web-project",
        "private_key_id" => "315984a7bf9ab244592036164737a43429e69c3e",
		"private_key" => "-----BEGIN PRIVATE KEY-----\nMIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDGlk+jwLTnVeat\nrGcgPDja/ajmC+eZQWUcZ3ThqfloQ6RgX+a4kXSxbVTXDqG9hL/my6GTeI9pRl1K\n+zssTK+iT1Q8bMhDabPQgJAwdl8AuksFX2U6+OH6Xgys82eFr0PPseNdJyFIHG2N\nKRAzoFGPE/EsdguY6Btk75hS4XlXOSpYgVTIEtdWq38LTI9SAOY4eRC2QUeShlx8\nQpSf8Wa8oJ/fX45PZWj3f9PgD1WQ9o1Txsyk5Mv59cJgy9+rdS+L5Ui0X5cYqNpz\nVjhDDmw8Oyd8426Ou20Zvau8KX/TZJDVOEh4eNG2J3x/f5gg6xLWGw27EGT7QUqw\nIzQX4LRBAgMBAAECggEABBUi3uCjQ3J91R4TSmuMhm2CsXSHEsywLsPeeHVvav4h\ngOj1z/9vBwcCsI9kojYagRzYdfT7e0hC2pvUuwfaUD+Z4Wph2Vdw5rle5X0ofbYG\nvO35sXs8f5J7h5pzK1GXpvte64tGdRJfCvDwPCR4isTH33Bvk3mZlxMRJkqL/V/W\nXz0w5lXJ31vPpi3UpJU+G93gtDKNGYLgqZkdOtjb+oV5z5lHzxlaSuUmTI01Mg/u\nv7UqbjtQNvV/sxRDYJZbIEzhOf3tOfu7F0465dovwbWRiSpMPz65ledZyqvWZq1a\nFsOCa2PTOaveV7nWkymCTskSL2TcOZWrOr6Xxfd3cQKBgQDz2tQH/3/rulQegx9R\nN/X6YpGxoyWgrXxRYKPoYJPGoUwekoARrMu0Zi44fiTj0TU9kSE5qyJmzopQ5ytN\nwpkE3tfIJs7N1NlT0LbVx5dQhnAvog9bQOqDeACJV3pDOIjXNMIm3Q/cpnKYO41s\n9KjYpUwKkT8zOfocWfGgc8Tx0QKBgQDQelDq+NLp32coe0KRtbFX8/mfWrFJO5GC\nfzDVXipqcLk6r7ICZlxmoROgpAZ+qnAhhrEXQKIp1d0b7thtvdOQXCHK0qzy8POZ\n9i1LNuRanFRSMdwhvIZUwooFTliOPwM+Yn0CDJuz/wcIIpzbefS7ypVr+6wYskCB\ntQ2gUBFHcQKBgDmO5E9C+oG8iFBvaLv19oR0Mal5Nc94Q4i9w9J8wUS4G8x3je0e\nGTLqj2xcMf7oCaYPlIUVJNiZVcKE3g7LqOyiYNJofpXM8MM813scUlX6dY54tSE3\n8GK5t36zfDNTq9EILe+YbD8Ltq7CF76o/RWt5oX0BLPlsmhwvny99rWRAoGBAMRW\nvchdaH1bqZKFay9BF3EG08uRJTAcCrEEyl/YHEg8OyYa+6Go91KsVojOkVNPfuUE\nLdoBQ/f0cxVwfqHzycDGFAkpGjp+VF1mbEfOvYbcfckfLfsTyssen8/ZdisZCxwA\ns0xvxV/iSaQOvP0yQRtu8gRNdEmZ7oh5lAir/2nxAoGBAOvZ/B20kcF8TNWjxEpD\n8va67t8HLrnwEbwmGHuCjnMLW34BRMQc7k2GAS82olC5GThx3BE3bJlehq/gLVvK\nRkl0RsRW/Vp2o1WIKbZaPq/MAGFaw8nkuJE0qekWWwlt/1o9wm4AoqwrydJQiG+G\nZ8m1QuDPVddfAsDlE7UA5IBL\n-----END PRIVATE KEY-----\n",
        "client_email" => "kgs-web-service-account@kgs-web-project.iam.gserviceaccount.com",
        "client_id" => "118348104168435635958",
        "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
        "token_uri" => "https://oauth2.googleapis.com/token",
        "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
        "client_x509_cert_url" => "https://www.googleapis.com/robot/v1/metadata/x509/kgs-web-service-account%40kgs-web-project.iam.gserviceaccount.com"
    ],
	
	// GOOGLE DRIVE FOLDER IDS
	
	'folders' => [
		'ticker'         		=> '1g4Xsq_Yxb_Mq0OpwPUPLwzjnJHRzDocX',
		'breakfast_menu'		=> '1wK2IziGzOx8XgeDm0lEJp36k4J0N5Nd8',
		'lunch_menu'     		=> '1hJpKtrg2-8o3m2lTqXArvEDVzc-kgz7l',
		'monthly_cal'			=> '1j26-htFn1QxdEpRg2eHCVBI34rrtfIwP',	
		'academic_cal'   		=> '1Mxes5W5ZTrTOl0G1xfHEP2o-IInhZWaJ',
		'pto_feature_image'		=> '1M_gJ2tcV2z90bRtWe-c-yWtqpbsedAl1',
		'public_docs_root'		=> '1L2vOHZlPrDnvXrGVFeTZa2duilKv89IL',
		'district_docs_root'	=> '1L2vOHZlPrDnvXrGVFeTZa2duilKv89IL' // 1TQIZDXToKV5tvYNSBnZImTc-PQH-E8mo
	],
	
	// GOOGLE CALENDARS IDS
	
	'calendars' => [
		'main' 					=> 'c_35c7f773dea0cc46099f7607201bed993a0a29d94d5456aa00594ed16ffb5071@group.calendar.google.com',
		'board' 				=> null // We will handle the "board" source in get-data.php
	],

	// GOOGLE DOC FILE IDS
	
    'files' => [
        'about_kgs'     		=> '1Zirp9wWczzHzTH0O2vYeiANSKXOP3WjChViNP9F3SlI',
        'board_meeting_intro'	=> '11P9LYb0ov40zDxi22eqIVgR9r1veVEDJlOzB_N7GL0o',
		'pto_intro'				=> '1i8NOPakDDpJRZDzikGE-YR3o-1TYoHiZG3pdsHOAjKc'
    ],
	
	'sheets' => [
		'school_board_members'	=> '1VbRHAvvPYmeSxnf_4EKC05iKsB1QCrnH42EeNERfcQM',
		'staff_directory'		=> '1sGmzK73HmmlpoV8tieirMumiQWxA9N68KkeThwBwMZ4'
	],
    
    'settings' => [
        'calendar_page_url' 	=> 'https://kellgradeschool.com/calendars/'
    ],

];