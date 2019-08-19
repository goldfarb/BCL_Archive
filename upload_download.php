<?php
/*
Template Name: upload_download
*/
?>

<?php
get_header();
?>


<style>
.file_menu {
	width:100%;
	display:table;
	height:10vh;
}
.tab {
	width:10vw;
	display:table-cell;
	float:left;
	text-align:center;
	border: darkgray solid 1.5px;
}
.control_sheet_row {
	display:block;
}
.cell {
	display:inline-block;
	border: #110011 solid 1.5px;
	overflow:scroll; /*ref animuson @ https://stackoverflow.com/questions/9707397/making-a-div-vertically-scrollable-using-css#9707674 */
	white-space: nowrap; /*ref JunM @ https://stackoverflow.com/questions/23235016/horizontal-scroll-of-inline-block-element#23235075 */
}
.heading {
	display:inline-block;
	border: #EEEEEE solid 1.5px;
	overflow:scroll;
	white-space: nowrap;
}
.hidden_field {
	display:none;
	white-space:pre-wrap;
}
</style>

	<!-- 
		UI Controls
	-->
<div id="menu" class="file_menu">
		<!--
			Interface for Uploading control sheet
				- selecting a .tsv file from the local disk
				- reads file into hidden input field
				- enables submit button
				- on submit, hidden field is loaded as POST variable
		-->
	<form id='' style='float:right;' method='post' action='http://bclarchive.net/upload_download/'>
			<textarea id='hidden_tsv_input' name="control_sheet_content" class='hidden_field'"> </textarea>
		<input id="file_input" type='file' onchange="fileChange()" />
		<input id="submit_input" type='submit' value='Upload' disabled/>
	</form>
	
		<!--
			Interface for Downloading control sheet
				- Javascript will parse data from DOM
				- and copy into hidden output field
				- convert to file and download
		-->
	<button id="download_button" type='button' onclick='control_sheet_download()'>Download</button>
	<textarea id='hidden_tsv_output' class='hidden_field'> </textarea>
</div>

<?php
		# borrow function from db_pusher.php
		//__DIR__.
//	require_once('db_pusher.php');
		# ref Mob @ https://stackoverflow.com/questions/8104998/how-to-call-function-of-one-php-file-from-another-php-file-and-pass-parameters-t#8105044
	//include __DIR__.'/db_pusher.php';

		#login to wpdb
	global $wpdb;
		# credentials removed from github
	$wpdb = new wpdb('username','password','database','hostname');
	
		# query existing DB table
	$control_sheet_rows = [];
	$control_sheet_query = "SELECT * FROM CONTROLSHEET";
	$control_sheet_results = $wpdb->get_results($wpdb->prepare($control_sheet_query,ARRAY_A));
	foreach($control_sheet_results as $control_sheet_result){
		$control_sheet_rows[] =  json_decode(json_encode($control_sheet_result), true);
	}
		# break content into a 2D grid
	$control_sheet_grid = [];
	foreach($control_sheet_rows as $row){
		$control_sheet_grid[] = $row;
	}

		# check if control sheet data is being submitted
	if (sizeof($_POST)>0 && $_POST['control_sheet_content'] != 'undefined'){		
		$upload_content = $_POST["control_sheet_content"];
		
			# break content into a 2D grid
		$upload_rows = explode("\n",$upload_content);
		$upload_grid = [];
		foreach($upload_rows as $row){
			$upload_grid[] = explode("\t",$row);
		}

			# remove column headings		
		unset($upload_grid[0]);
			# and reset index incrementation
				# ref https://www.dyn-web.com/php/arrays/remove.php
		$upload_grid = array_values($upload_grid);

			# check for new rows
		if(sizeof($upload_grid) > sizeof($control_sheet_grid)){
					# count number of new rows
				$new_rows = sizeof($upload_grid) - sizeof($control_sheet_grid);
					# iterate through new rows only
				for($c = 0; $c < $new_rows; $c++){
					$current_index = sizeof($control_sheet_grid) + $c;
					$current_row = $upload_grid[$current_index];
						# make sure new row is not whitespace
					if(sizeof($current_row)>1){
						# update CONTROLSHEET
						$table='CONTROLSHEET';
						$data = array(
							'ArticleId' => $current_row[0],
							'Title' => $current_row[1],
							'ReportCover' => $current_row[2],
							'Number' => $current_row[3],
							'Fiche' => $current_row[4],
							'Author' => $current_row[5],
							'Type' => $current_row[6],
							'CompleteIncomplete' => $current_row[7],
							'Observations' => $current_row[8],
							'Link' => $current_row[9],
						);
						$format = array('%d','%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s');
						$wpdb->insert($table,$data,$format);
				
						# update other TABLES
						insert_row($current_row);
					}
				}
		}
			# a counters for changes between new and existing control sheet info
		$total_discrepancies = 0;
		$discrepancy_rows = 0;
			# contrast non-new rows for discrepancies
		foreach($control_sheet_grid as $row){
				# get corresponding upload row by ID
			$index = (int)$row['ArticleId']-1;
			$upload_row = $upload_grid[$index];
			$column_index = 0;
				# variable for flagging discrepancies per row
			$mismatch = false;
				# check each column for discrepancies
			foreach($row as $column){
				if($upload_row[$column_index] != $column){
					$mismatch = true;
					$total_discrepancies += 1;
				}
				$column_index +=1;
			}
				# replace rows with discrepancies
			if($mismatch){
					# clear previous corresponding rows
				$delete_query =  "DELETE FROM ARTICLE WHERE ArticleId=" . (int)$row['ArticleId'];
				$delete_result = $wpdb->query($wpdb->prepare($delete_query,ARRAY_A));
				$delete_query =  "DELETE FROM ARTICLEAUTHOR WHERE ArticleId=" . (int)$row['ArticleId'];
				$delete_result = $wpdb->query($wpdb->prepare($delete_query,ARRAY_A));

					# and replace with uploaded row
				insert_row($row);
				$discrepancy_rows += 1;
			}
		}

			# clear existing DB table
		$clear_query = "DELETE FROM CONTROLSHEET";
		$clear_result = $wpdb->query($wpdb->prepare($clear_query,ARRAY_A));
		
			# insert current data into DB table
		$table='CONTROLSHEET';
		foreach($upload_grid as $upload_row){
			$data = array(
 				'ArticleId' => $upload_row[0],
				'Title' => $upload_row[1],
				'ReportCover' => $upload_row[2],
				'Number' => $upload_row[3],
				'Fiche' => $upload_row[4],
				'Author' => $upload_row[5],
				'Type' => $upload_row[6],
				'CompleteIncomplete' => $upload_row[7],
				'Observations' => $upload_row[8],
				'Link' => $upload_row[9],
			);		
			$format = array('%d','%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s');
			$wpdb->insert($table,$data,$format);	
		}
		
		echo $total_discrepancies . " updates made to " . $discrepancy_rows . " rows!<br/>";	
	}
?>

<!-- get database contents -->
<?php	
	$control_sheet_rows = [];
	
	$control_sheet_query = "SELECT * FROM CONTROLSHEET";
	
	$control_sheet_results = $wpdb->get_results($wpdb->prepare($control_sheet_query,ARRAY_A));
	
	foreach($control_sheet_results as $control_sheet_result){
		$control_sheet_rows[] =  json_decode(json_encode($control_sheet_result), true);
	}
	
?>

<!-- heads up bar -->
<div id="heads_up" class="control_sheet_row">
<div id="number_of_records" style='display:inline-block;'><?php echo sizeof($control_sheet_rows); ?></div>
<div style='display:inline-block'> rows of control sheet data found.</div>

<!-- display table headings -->

		<!-- ref Josh Crozier @ https://stackoverflow.com/questions/19038799/why-is-there-an-unexplainable-gap-between-these-inline-block-div-elements -->
<div class='control_sheet_row' id='headings'>
<div class='heading' style='width:5%;'>ID
</div><div class='heading' style='width:15%;'>Title
</div><div class='heading' style='width:5%;'>Report Cover
</div><div class='heading' style='width:5%;'>Number
</div><div class='heading' style='width:10%;'>Fiche
</div><div class='heading' style='width:15%;'>Author
</div><div class='heading' style='width:10%;'>Type
</div><div class='heading' style='width:10%;'>Complete/Incomplete
</div><div class='heading' style='width:10%;'>Observations
</div><div class='heading' style='width:15%;'>Link
</div>
</div>

<!--print out database contents to DOM -->
<?php
	$column=0;
	$row=0;
	foreach($control_sheet_rows as $control_sheet_row){
		echo "<div class='control_sheet_row'>";
		foreach($control_sheet_row as $control_sheet_item){
			echo "<div class='cell' style='width:";
			switch  ($column){
				case 0:
					echo "5%;' id='cell_" . $row . '_' . $column ."'>" . $control_sheet_item . "</div>";
					$column++;
					break;
				case 1:
					echo "15%;' id='cell_" . $row . '_' . $column ."'>" . $control_sheet_item . "</div>";
					$column++;
					break;
				case 2:
					echo "5%;' id='cell_" . $row . '_' . $column ."'>" . $control_sheet_item . "</div>";
					$column++;
					break;
				case 3:
					echo "5%;' id='cell_" . $row . '_' . $column ."'>" . $control_sheet_item . "</div>";
					$column++;
					break;
				case 4:
					echo "10%;' id='cell_" . $row . '_' . $column ."'>" . $control_sheet_item . "</div>";
					$column++;
					break;
				case 5:
					echo "15%;' id='cell_" . $row . '_' . $column ."'>" . $control_sheet_item . "</div>";
					$column++;
					break;
				case 6:
					echo "10%;' id='cell_" . $row . '_' . $column ."'>" . $control_sheet_item . "</div>";
					$column++;
					break;
				case 7:
					echo "10%;' id='cell_" . $row . '_' . $column ."'>" . $control_sheet_item . "</div>";
					$column++;
					break;
				case 8:
					echo "10%;' id='cell_" . $row . '_' . $column ."'>" . $control_sheet_item . "</div>";
					$column++;
					break;
				case 9:
					echo "15%;' id='cell_" . $row . '_' . $column ."'>" . $control_sheet_item . "</div>";
					$column=0;
					break;
				default:
					echo "0vw;'></div>";
					break;
			}
		}
		$row++;
		echo "</div>";
	}
	
?>

<div id="content" class="site-content">
<div class="container">

<?php get_template_part( 'template-parts/sidebars/sidebar', 'breadcrumbs' ); ?>

<div class="row">
		<div class="col-md-12">
		<main id="main" class="site-main">

			<?php
			while ( have_posts() ) : the_post();

				get_template_part( 'template-parts/page/content', 'page' );

				// If comments are open or we have at least one comment, load up the comment template.
				if ( comments_open() || get_comments_number() ) :
					comments_template();
				endif;

			endwhile; // End of the loop.
			?>

		</main>
	</div>
</div>
</div>

<?php
get_footer();
?>

<script>
		// referenced copy @ https://stackoverflow.com/questions/8645369/how-do-i-get-the-file-content-from-a-form#8645576
	// detects when a new .tsv file has been selected by the user
function fileChange(){
		// select the elements involved in uploading the file content
	var hidden_field = document.getElementById('hidden_tsv_input');
	var file  = document.getElementById('file_input').files[0];
	var submit = document.getElementById('submit_input');
		// read the contents of the file into hidden field
	var reader = new FileReader();
	reader.readAsText(file);
	reader.onload=function(){
		hidden_field.innerHTML=reader.result;
		console.log(hidden_field.innerHTML);
	};
			//ref hudolejev @ https://stackoverflow.com/questions/2874688/how-to-disable-an-input-type-text#2874745
		// enable submission of the input
	submit.disabled=false;
}

function control_sheet_download(){
		// define a name for the downloaded file
	var filename = 'bcl_control_sheet.tsv';
		// prepare the hidden div for tsv formatted data
	var tsv_div = document.getElementById('hidden_tsv_output');
	tsv_div.innerHTML = "ID\tTitle\tReport Cover\tNumber\tFiche\tAuthor\tType\tComplete/Incomplete\tObservations\tLink\n";
	
		// iterate through DOM, populate tsv data into the hidden div
	var number_of_records = Number(document.getElementById('number_of_records').innerHTML);
	for (var i=0;i<number_of_records;i++){
		for (var j=0;j<10;j++){
			var cell_id = "cell_" + i + "_" + j;
			var cell_div = document.getElementById(cell_id);
			tsv_div.innerHTML += cell_div.innerHTML;
			if(j<9){
				tsv_div.innerHTML += "\t";
			}
		}
	}
		// put the hidden div content into a file and download
	var tsv_content = tsv_div.innerHTML;
	var tsv_blob = new Blob([tsv_content], {type:'text/plain'});
	var download_link = document.createElement('a');
	download_link.download = filename;
	download_link.href = window.URL.createObjectURL(tsv_blob);
	document.body.appendChild(download_link);
	download_link.click();
	
}
</script>

<?php

// copied from db_pusher.php
function insert_row($row){
		# reference global variable from inside function
	global $wpdb;

		# register variables into PHP
	//read article id
	$article_id = $row['ArticleId'];
	//read authors
	$article_author = $row['Author'];
	//read article
	$article_title = $row['Title'];
	//read report cover
	$article_report_cover = $row['ReportCover'];
	//read number
	$article_number = $row['Number'];
	//read fiche
	$article_fiche = $row['Fiche'];
	//read type
	$article_type = $row['Type'];
	//read file
	$article_file_url= $row['Link'];

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