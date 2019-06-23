<?php
/*
Template Name: upload_download
*/
?>

<?php
get_header();
?>

<style>
.file_menu
{
	width:100%;
	display:table;
	height:10vh;
}

.tab
{
	width:10vw;
	display:table-cell;
	float:left;
	text-align:center;
	border: darkgray solid 3px;
}

.control_sheet_row
{
	display:block;
	
}

.cell
{
	display:inline-block;
	border: #110011 solid 3px;
	/*ref animuson @ https://stackoverflow.com/questions/9707397/making-a-div-vertically-scrollable-using-css#9707674 */
	overflow:scroll;
	/*ref JunM @ https://stackoverflow.com/questions/23235016/horizontal-scroll-of-inline-block-element#23235075 */
	white-space: nowrap;
}

.heading
{
	display:inline-block;
	border: #EEEEEE solid 3px;
	overflow:scroll;
	white-space: nowrap;
}
</style>

<!-- setup functions -->
<?php
#login to wpdb
global $wpdb;
$wpdb = new wpdb('650270_edmund','Ned10583!','650270_bcl_documents','mariadb-003.wc2.phx1.stabletransit.com');

$number_of_records = 0;
?>

<div id="menu" class="file_menu">
	<div id="control_sheet_tab" class="tab"> Control Sheet </div>
	<div id="attachments_tab" class="tab"> Attachments </div>
	<form id='' style='float:right;' method='post' action='http://bclarchive.net/upload_download/'>
			<textarea id='hidden_field' name="control_sheet_content" style="display:none;white-space:pre-wrap;"> </textarea>
		<input id="file_input" type='file' onchange="fileChange()" />
		<input id="submit_input" type='submit' value='Upload' disabled/>
		<button id="download_button" type='button' onclick='control_sheet_download()'>Download</button>
	</form>
</div>

<!--hidden div for tsv formatted table to be downloaded from--> 
<textarea id='hidden_tsv' style='display:none;white-space:pre-wrap;'> </textarea>


<!--unpack POST variable into database-->
<?php
	if (sizeof($_POST)>0 && $_POST['control_sheet_content'] != 'undefined'){		
		$control_sheet_content = $_POST["control_sheet_content"];

		$content_lines = explode("\n",$control_sheet_content);

		$content_grid = [];

		foreach($content_lines as $line){
			$content_grid[] = explode("\t",$line);
		}
		
		//remove first row
		unset($content_grid[0]);

		//clear existing database table
		$clear_query = "DELETE FROM CONTROLSHEET";
		$clear_result = $wpdb->query($wpdb->prepare($clear_query,ARRAY_A));
		
		//insert to database procedurally
		$table='CONTROLSHEET';
		
		foreach($content_grid as $content_row){
		
			$data = array(
 				'ArticleId' => $content_row[0],
				'Title' => $content_row[1],
				'ReportCover' => $content_row[2],
				'Number' => $content_row[3],
				'Fiche' => $content_row[4],
				'Author' => $content_row[5],
				'Type' => $content_row[6],
				'CompleteIncomplete' => $content_row[7],
				'Observations' => $content_row[8],
				'Link' => $content_row[9],
			);
			
			$format = array('%d','%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s');
			
			$wpdb->insert($table,$data,$format);
		
		}	
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
<!-- procedurally generate Divs for control sheet rows -->


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
function fileChange(){
	console.log('the file was changed');
	var hidden_field = document.getElementById('hidden_field');
	var file  = document.getElementById('file_input').files[0];
	var submit = document.getElementById('submit_input');
	var reader = new FileReader();
	
	reader.readAsText(file);
	reader.onload=function(){
		hidden_field.innerHTML=reader.result;
		console.log(hidden_field.innerHTML);
	};
	
	//ref hudolejev @ https://stackoverflow.com/questions/2874688/how-to-disable-an-input-type-text#2874745
	submit.disabled=false;
	
}

function control_sheet_download(){
	var filename = 'bcl_control_sheet.tsv';

	var tsv_div = document.getElementById('hidden_tsv');
	tsv_div.innerHTML = "ID\tTitle\tReport Cover\tNumber\tFiche\tAuthor\tType\tComplete/Incomplete\tObservations\tLink\n";
	
	//iterate through DOM, populate tsv data
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
		//tsv_div.innerHTML += "";
	}
	
	
	var tsv_content = tsv_div.innerHTML;
	var tsv_blob = new Blob([tsv_content], {type:'text/plain'});
	
	var download_link = document.createElement('a');
	download_link.download = filename;
	download_link.href = window.URL.createObjectURL(tsv_blob);
	document.body.appendChild(download_link);
	download_link.click();
	//window.URL.revokeObjectURL(url);
	
}
</script>
