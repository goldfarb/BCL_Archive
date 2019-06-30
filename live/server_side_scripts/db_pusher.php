<?php
/*
Template Name: db_pusher
*/
?>

<!-- 
/xml_data
/fichedir
 -->

<?php
		//xmc @ https://stackoverflow.com/questions/10527272/max-execution-time-alternative#10527424
		//Youichi Okada  @ https://stackoverflow.com/questions/21508592/adding-a-new-wpdb-class-in-wordpress#21510096
		//^ very helpful
		//Uberfuzzy @ https://stackoverflow.com/questions/18119133/what-does-it-mean-include-path-usr-share-pear-usr-share-php
		//Vic Seedoubleyew @ https://stackoverflow.com/questions/36577020/php-failed-to-open-stream-no-such-file-or-directory#36577021
		require_once(__DIR__.'/../../../wp-load.php');
	
		//Michiel Pater @ https://stackoverflow.com/questions/5164930/fatal-error-maximum-execution-time-of-30-seconds-exceeded#5164954
		//ini_set('max_execution_time', 600); //300 seconds = 5 minutes


		# login to wpdb	
	global $wpdb;
	$wpdb = new wpdb('username','password','database','hostname');

		# create an array for CONTROLSHEET table data
	$control_sheet_rows = [];
	
		# query CONTROLSHEET table data
	$control_sheet_query = "SELECT * FROM CONTROLSHEET";
	
	$control_sheet_results = $wpdb->get_results($wpdb->prepare($control_sheet_query,ARRAY_A));
	
	foreach($control_sheet_results as $control_sheet_result){
		$control_sheet_rows[] =  json_decode(json_encode($control_sheet_result), true);
	}
	
		# counters will be used for for assigning column IDs in WORD and PAGE tables
			# [as the script iterates through the articles]
	$total_pages=0;
	$total_words=0;
	
		# iterate through every article
	foreach($control_sheet_rows as $control_sheet_row){
			
			# insert rows to ARTICLE, AUTHOR, and ARTICLEAUTHOR
		insert_row($control_sheet_row);
		
		////////text parser/////////
			# these variables will be used to look up the associated XML files
		$article_id = $control_sheet_row['ArticleId'];
		$article_file_url= $control_sheet_row['Link'];
		
		//get article url
			// ref Jan @ https://stackoverflow.com/questions/34481697/filesize-stat-failed-for-specific-path-php
		$filename= __DIR__ . '/../../../xml_data/' . trim(str_replace('.pdf','',$article_file_url)).'.xml';
		echo $filename;
		$xml_file=fopen($filename,'r') or die("Unable to open file!");
		$xml_content = fread($xml_file,filesize($filename));
		fclose($xml_file);


		$xml= simplexml_load_string($xml_content) or die("Error: Cannot create object");

		$xml_pages = $xml->page;
		$number_of_pages = sizeof($xml_pages);
	
		foreach($xml_pages as $xml_page){
			$total_pages++;
			$xml_article=$xml_page->article;
			$xml_page_number=$xml_page->page_number;
			$xml_page_width=$xml_page->width;
			$xml_page_height=$xml_page->height;
		
			$page_text='';

			$xml_items=$xml_page->item;

			foreach($xml_items as $xml_item){
				$total_words++;
				$item_x=$xml_item->x;
				$item_y=$xml_item->y;
				$item_height=$xml_item->height;
				$item_content=$xml_item->content;
			
				$page_text=$page_text . $item_content;
				//format db entry
				$table='WORD';
				$data=array('WordId'=>$total_words, 'WordX'=>$item_x, 'WordY'=>$item_y, 'WordHeight'=>$item_height, 'WordContent'=>$item_content, 'PageId'=>$total_pages);
				$format = array('%d','%f','%f','%f','%s','%d');
				$wpdb->insert($table,$data,$format);	
			}
		
			//format db entry
			$table='PAGE';
			$data=array('PageId'=>$total_pages, 'PageWidth'=>$xml_page_width, 'PageHeight'=>$xml_page_height, 'CorrectionStatus'=>"NONE", 'ArticleId'=>$article_id, 'PageNumber'=>$xml_page_number, 'PageText'=>$page_text);
			$format = array('%d','%f','%f','%s','%d','%d','%s');
			$wpdb->insert($table,$data,$format);	
		}
		
	}

# php insert function here

function insert_row($control_sheet_row){
		# login again just in case function is called from another script
	global $wpdb;
	$wpdb = new wpdb('username','password','database','hostname');

		# register variables into PHP
	//read article id
	$article_id = $control_sheet_row['ArticleId'];
	//read authors
	$article_author = $control_sheet_row['Author'];
	//read article
	$article_title = $control_sheet_row['Title'];
	//read report cover
	$article_report_cover = $control_sheet_row['ReportCover'];
	//read number
	$article_number = $control_sheet_row['Number'];
	//read fiche
	$article_fiche = $control_sheet_row['Fiche'];
	//read type
	$article_type = $control_sheet_row['Type'];
	//read file
	$article_file_url= $control_sheet_row['Link'];

		# parse Fiche column, including possible range		
	$fiche_numbers = explode(" ",$article_fiche)[1];
	$fiche_number = explode("-",$fiche_numbers)[0];
	$fiche_span = sizeof(explode("-",$fiche_numbers));


	////////register author(s) for DB insertion////////
	
		# create an array for (multiple) author(s)
	$authors = explode("&", $article_author);

		# get the highest current authorID by counting existing DB entries
	$author_query = "SELECT * FROM AUTHOR";
	$author_results = $wpdb->get_results($wpdb->prepare($author_query, ARRAY_A));
	$high_author_id = sizeof($author_results);

		# create holder for new authorIDs
	$authorIDs = [];

		# break down each author name into parts
	foreach($authors as $author){
			#create empty variables for each AUTHOR column
		$author_first_name='';
		$author_middle_name='';
		$author_last_name='';
			
			# detect whether author has a name by checking for commas
		if (strpos($author,',')){
				# name into last and first components
			$author_last_first = explode(",", $author);
			$author_last_name = trim($author_last_first[0]);
				# may be one or two initials
			$author_initials = trim($author_last_first[1]);		
					//ref codaddict @ https://stackoverflow.com/questions/2497970/how-to-check-how-many-times-something-occurs-in-a-string-in-php
				# detect either one or two initials
			if (substr_count($author_initials,'.')>1){
				$author_first_middle = explode(".", $author_initials);
				$author_first_name = trim($author_first_middle[0]);
				$author_middle_name = trim($author_first_middle[1]);
			}else{
				$author_first_name = trim(str_replace('.','',$author_initials));
			}
			# if author is not a name (eg. et al, BCL STAFF)
		}else{
			$author_last_name=trim($author);
		}
		
			# check whether author name already exists in database
		$author_contrast_query = "SELECT * FROM AUTHOR WHERE LastName = '{$author_last_name}' AND FirstName ='{$author_first_name}'";
		$author_contrast_results  = $wpdb->get_results($wpdb->prepare($author_contrast_query, ARRAY_A));

			# in case duplicates are found, reuse their IDs for current author
		if (sizeof($author_contrast_results) > 0){
			foreach($author_contrast_results as $author_contrast_result){
				$existing_id = json_decode(json_encode($author_contrast_result), true)['AuthorId'];
				$authorIDs[] = $existing_id;
			}
			# otherwise, increment a new author ID for the current author
		}else{
			$high_author_id += 1;
			
				# and insert the new author information into the database
			$table = 'AUTHOR';
			$data = array('AuthorId'=>$high_author_id,
							'FirstName'=>$author_first_name,
							'MiddleName'=>$author_middle_name,
							'LastName'=>$author_last_name);
			$format = array('%d','%s','%s','%s');
	
			$wpdb->insert($table,$data,$format);
				# adding the new ID to the current list
			$authorIDs[] = $high_author_id;			
		}
	}

		# finally, make new rows for each current author in relation to the current article!
	foreach ($authorIDs as $authorID){
		$table = 'ARTICLEAUTHOR';
		$data = array('ArticleId'=>$article_id, 'AuthorId'=>$authorID);
		$format = array('%d','%d');
		$wpdb->insert($table,$data,$format);
	}
	
		////////insert article////////	
	$table = 'ARTICLE';
	$data = array('ArticleId'=>$article_id,
					'Title'=>$article_title,
					'ReportCover'=>$article_report_cover,
					'Number'=>$article_number,
					'Fiche'=>$fiche_number,
					'Fiches'=>$fiche_span,
					'Type'=>$article_type,
					'FileUrl'=>$article_file_url
					);
	$format = array('%d','%s', '%f', '%s', '%d', '%d', '%s', '%s', '%d', '%d', '%d');
	$wpdb->insert($table,$data,$format);
	
}

?>

