<?php
/*
Template Name: bcl_viewer
*/
get_header();
?>

<style>
body{
margin: 0;
}

.container{
height:100vh;
}

.top-bar{
position:relative;
display:block;
width:100%;
padding-right:30px;
height:12vh;
border: 5.6px lightblue double;
}

.top-bar > div{
position:absolute;
top:50%;
transform: translate(-0%, -50%);
vertical-align:middle;

}

.top-bar > div *{
display:inline-block;
}

select{
width:7.8vw;
}

label > div{
width: 7.4vw !important;
height: 8vh !important;
}

.toggle-select input{
opacity: 0;
width: 0;
height: 0;
}

.toggle-button{
text-align:center;
vertical-align:middle;
color:darkgray;
}

input + .toggle-button{
height:100% !important;
background-color: #009999;
border: 4px lightgray outset;
}

input:checked + .toggle-button {
background-color: #006666 !important;
border: inset lightgray 4px;
}

.toggle-sort input{
opacity: 0;
width: 0;
height: 0;
}

.toggle-sort{
text-align:center;
vertical-align:middle;
color:darkgray;
}

.toggle-sort > input + .sort-button{
height:30px !important;
width: 30px !important;
border: 4px lightgray outset;
background-image: url("http://upload.wikimedia.org/wikipedia/commons/7/7c/Octicons-arrow-small-down.svg");
background-position:center;
background-size:contain;
background-repeat:no-repeat;
}

.toggle-sort > input:checked + .sort-button{
height:30px !important;
width: 30px !important;
border: 4px lightgray inset;
background-image: url("http://upload.wikimedia.org/wikipedia/commons/3/37/Octicons-arrow-small-up.svg");
background-position:center;
background-size:contain;
background-repeat:no-repeat;
}

.button-image{
position:absolute;
}

.main-view{
position:relative;
width:100%;
height:100%;

}

.result_bubble{
display:block !important;
height:2em;
border: 1px solid black;
border-radius:10px;
line-height:2em;
padding:0em 1em;
}

.result_bubble > *{
float:left;
}

.result_bubble:hover{
background:lightblue;

}

.result_bubble:hover .view_directive{
	opacity:1;
}

.result_title{
font-weight:bold;
}

.result_frequency{
color:red;
}

.view_directive{
display:none;
text-decoration:underline;
color:blue;
}

.hidden{
display:none;
}

</style>

<?php

		# by default, set empty search term, target and denote the first page load
	$search_term='';
	$search_target='text';
	$first_load=1;
	
		# check if there are query variables in the page load
	if(sizeof($_GET)>0){
			# rule out first page load
		$first_load = 0;
			# and read search parameters
		$search_term = $_GET['search_term'];
		$search_target = $_GET['search_target'];
	}
		# create arrays for search results and metadata,
	$search_results=[];
		# number of hits per search result,
	$result_frequencies=[];
		# and total number of results
	$number_of_results;
		
		# login to wpdb
	global $wpdb;
	$wpdb = new wpdb('username','password','database','hostname');

		# function to convert an array to a comma-separated string
			# useful for formatting SQL queries
	function arrayToCommaSeparated($arr){
			# remove and return the first element of array
		$output_string=array_shift($arr);
			# concatenate the element followed by a comma to the output string
		foreach($arr as $ar){
			$output_string=$output_string .', '.$ar;
		}
		return $output_string;
	}
?>

<?php
		# the following case switch formats a SQL query depending on the field being searched
	 switch ($search_target){
	 	case 'text':
	 			# an empty list for the IDs of the results
			$articleIds=[];
				# searches for occurrences of search term in page text content, returns matching Article + Page IDs
			$page_query = "SELECT ArticleId,PageText FROM PAGE WHERE PageText LIKE '%%" . $search_term . "%'";
			$page_results = $wpdb->get_results(($wpdb->prepare($page_query,ARRAY_A)));
				# tally number of results associated with each article
			foreach ($page_results as $page_result){
				$articleId = json_decode(json_encode($page_result), true)['ArticleId'];
				if(in_array($articleId,$articleIds)==FALSE){
					$articleIds[] = $articleId;
					$result_frequencies[$articleId]=1;
				}else{
					$result_frequencies[$articleId]+=1;
				}
			}
				# total number of hits across all page results
			$sum_of_frequencies = array_sum($result_frequencies);
				# query those articles associated with page results
			$article_query = "SELECT * FROM ARTICLE WHERE ArticleId IN (" . arrayToCommaSeparated($articleIds) . ")";
			$article_results = $wpdb->get_results(($wpdb->prepare($article_query,ARRAY_A)));
				# and push them into results array
			foreach ($article_results as $article_result){
				$search_results[]=json_decode(json_encode($article_result), true);
			}
			break;
			
	 	case 'authors':
	 			# an empty list for the IDs of the results
	 		$authorIds=[];
	 			# searches for matches of search term across authors' last names, returns matching Author IDs
	 		$author_query= "SELECT AuthorId FROM AUTHOR WHERE LastName LIKE '%%" . $search_term ."%'";
	 		$author_results= $wpdb->get_results(($wpdb->prepare($author_query,ARRAY_A)));
	 			# push IDs of matching authors into results array
	 		foreach ($author_results as $author_result){
				$authorIds[] = json_decode(json_encode($author_result), true)['AuthorId'];
			}
				# put together list of articles associated with author results
			$articleIds=[];
			$articleauthor_query = "SELECT ArticleId FROM ARTICLEAUTHOR WHERE AuthorId IN (" . arrayToCommaSeparated($authorIds) . ")";
			$articleauthor_results=$wpdb->get_results(($wpdb->prepare($articleauthor_query,ARRAY_A)));
	 		foreach ($articleauthor_results as $articleauthor_result){
				$articleIds[] = json_decode(json_encode($articleauthor_result), true)['ArticleId'];
			}
				# and query those articles
			$article_query = "SELECT * FROM ARTICLE WHERE ArticleId IN (" . arrayToCommaSeparated($articleIds) . ")";
			$article_results = $wpdb->get_results(($wpdb->prepare($article_query,ARRAY_A)));
			foreach ($article_results as $article_result){
				$search_results[]=json_decode(json_encode($article_result), true);
			}		
			break;
	 			# search the matches of search term in titles and returns article attributes
	 	case 'titles':
	 		$article_query = "SELECT * FROM ARTICLE WHERE Title LIKE '%%" . $search_term . "%'"
	 		$article_results = $wpdb->get_results(($wpdb->prepare($article_query,ARRAY_A)));
			foreach ($article_results as $article_result){
				$search_results[]=json_decode(json_encode($article_result), true);
			} 	
			break;
	}
		# total number of resulting articles
	$number_of_results=sizeof($search_results);
?>


	<!-- 
	DEFINE THE UI LAYOUT INCLUDING TOP BAR, NAV BUTTONS AND BROWSER/VIEW WINDOW
	 -->
<html>
	<body onload="parse_results(<?php echo "{$first_load}" . ",'" . $search_target . "'";?>)">
	<div class="desktop-only">
		<div class='top-bar'>
			<div>
						<!-- 
						Search Form
						 -->
				<div>&nbsp;Search&nbsp;</div>	
				
				<form action="http://bclarchive.net/browse" method='GET'>
					<input name='search_term'>
					<div>&nbsp;in&nbsp;</div>
					<select name='search_target'>
						<option value='text'>Text</option>
						<option value='authors'>Author</option>
						<option value='titles'>Title</option>
					</select>
					<input type=submit value='Search'>
				</form>
				
						<!-- 
						Filter Selection
						 -->
				<div>&nbsp;Sort by:&nbsp;</div>
			
				<label class=toggle-select>
					<input name='filter_type' type="radio" value='all' onclick='applyCritFilter("relevance")' <?php if ($search_target=='authors' or $search_target=='titles' or $search_term=='' or $first_load){echo "disabled ";} if($search_target=='text'){echo 'checked';}?>>
					<div class="toggle-button">RELEVANCE</div>
				</label><label class=toggle-select>
					<input name='filter_type' type="radio" value='title' onclick='applyCritFilter("title")' <?php if ($search_target=='authors'){echo "checked";}?>>
					<div class="toggle-button">TITLE</div>
				</label><label class=toggle-select>
					<input name='filter_type' type="radio" value='author' onclick='applyCritFilter("author")' >
					<div class="toggle-button">AUTHOR</div>
				</label><label class=toggle-select>
					<input name='filter_type' type="radio" value='fiche' onclick='applyCritFilter("fiche")' <?php if ($search_target=='titles' or $search_target=='authors' or $search_term=='' or $first_load){echo "checked";}?>>
					<div class="toggle-button">FICHE</div>
				</label>

				<label class='toggle-sort'>
					<input id='reverse_sort_button' type='checkbox' onclick='reverseSort()'>
					<div class="sort-button">
					</div>
				</label>
				
				<div id='results_monitor' style='display:inline-block;'>
					<div>&nbsp;
						<?php
							if($number_of_results>0){
								echo "{$sum_of_frequencies} results in ";
							}
						?>

						<div id='number_of_results'>
							<?php
								echo $number_of_results;
							?>
						</div>
						<div>documents.&nbsp;</div>
					</div>
				</div>
				<div class='view_directive' style='display:none;' id='back_to_results' onclick='backToResults()'>Back To Results</div> 
			</div>
		</div>
		<div class='below_bar'>
			<div class='hidden' id='frequencies_ledger'></div>
			<div class='hidden' id='authors_ledger'></div>
			<div class='hidden' id='titles_ledger'></div>
			<div class='hidden' id='fiches_ledger'></div>
		</div>
		<div class='main-view'>
			<div id="results">
				<?php
					$result_count=0;
						# unpacks search results array 
					foreach($search_results as $search_result){
						$author_ids=[];
						$articleauthor_query = "SELECT AuthorId FROM ARTICLEAUTHOR WHERE ArticleId=".$search_result['ArticleId'];
						$articleauthor_results= $wpdb->get_results(($wpdb->prepare($articleauthor_query,ARRAY_A)));
					
						foreach ($articleauthor_results as $articleauthor_result){
							$author_ids[]= json_decode(json_encode($articleauthor_result), true)['AuthorId'];
						}
						$authors=[];
						$author_query = "SELECT * FROM AUTHOR WHERE AuthorId IN (" . arrayToCommaSeparated($author_ids) . ")";
						$author_results = $wpdb->get_results(($wpdb->prepare($author_query,ARRAY_A)));
						foreach ($author_results as $author_result){
							$authors[]= json_decode(json_encode($author_result), true);
						}
								//ref Ben Roux @ https://stackoverflow.com/questions/10757671/how-to-remove-line-breaks-no-characters-from-the-string/10757755#10757755
						echo "<div id='result_" . $result_count . "' class='result_bubble' onclick=\"loadArticle('" . preg_replace('/\r|\n/','',$search_result['FileUrl']) . "','".$search_term."')\">";
						echo "<div class='result_title'>".$search_result['Title']."</div>";
						echo "<div>&nbsp&nbsp;By:&nbsp;";
						$first_author=array_shift($authors);
						echo "</div><div class='result_author'>" . $first_author['FirstName'] . $first_author['MiddleName'] .' '. $first_author['LastName'] ."</div>";
						foreach ($authors as $author){
									//later, make names links?
							echo "<div class='result_author'>, " . $author['FirstName'] . $author['MiddleName'] .' ' . $author['LastName'] ."</div>";
						}
						echo "<div class='result_fiche'>&nbsp;&nbsp;Fiche #".$search_result['Fiche']."&nbsp;</div>";
						if($search_target=='text' and $first_load==0){
						
							echo "<div>&nbsp;</div><div class='result_frequency'>".$result_frequencies[$search_result['ArticleId']]."</div><div>". "&nbsp;matches in document.</div>";
						
						}
						echo "</div>";
						$result_count++;
					}
				?>
			</div>		
		</div>
	</div>
	<div class='mobile-only'>The BCL archive does not currently support mobile browsing.  Please visit http://bclarchive.net from a desktop machine.
	</div>
<script>

function parse_results(first_load,search_target){
			// parse DOM fields
			//visible
	var number_of_results_div=document.getElementById('number_of_results');
	var number_of_results=number_of_results_div.innerHTML;

			//hidden
	var frequencies_ledger_div = document.getElementById('frequencies_ledger');
	var authors_ledger_div = document.getElementById('authors_ledger');
	var titles_ledger_div = document.getElementById('titles_ledger');
	var fiches_ledger_div = document.getElementById('fiches_ledger');

	var result_divs=[];
	
			//list of ranked results
	var result_frequencies=[];
	var result_titles=[];
	var result_authors=[];
	var result_fiches=[];
	
			//list for sorted results
	var sorted_frequencies=[];
	var sorted_titles=[];
	var sorted_authors=[];
	var sorted_fiches=[];

	for (c=0;c<number_of_results;c++){
		result_bubble_key='result_' + String(c);
		result = document.getElementById(result_bubble_key);
		result_divs.push(result);
	}
			//make lists in for loops:
			//rank
				//ref adeneo @ https://stackoverflow.com/questions/17896746/document-getelementsbyclassname-innerhtml-always-returns-undefined#17896758
	for (r_d in result_divs){
	
		n=Number(r_d);
		var result = result_divs[r_d];
				// frequency
		if (search_target=="text" && first_load==0){
			var result_frequency = result.getElementsByClassName('result_frequency')[0].innerHTML;
			console.log(result_frequency);
			result_frequencies.push({key:n,value:result_frequency});
		}
		
				//author
		var author_divs = result.getElementsByClassName('result_author');
		var authors=[];
		for (div in author_divs){
			if (Number.isInteger(Number(div))){
				var name_sections = author_divs[div].innerHTML.replace(', ','').split(' ');
				name_sections.shift();
				var last_name = name_sections.join(' ');
				authors.push(last_name);
			}
		}
		result_authors.push({key:n,value:authors.sort()[0]});
		
				//article
		var article_title=result.getElementsByClassName('result_title')[0].innerHTML;
		result_titles.push({key:n,value:article_title});
		
				//fiche
		var article_fiche=result.getElementsByClassName('result_fiche')[0].innerHTML;
		result_fiches.push({key:n,value:article_title});
	}

			//sort
				// see js reference @ https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/sort
	sorted_frequencies = result_frequencies.sort(function(a,b){
		var valueA = Number(a.value);
		var valueB = Number(b.value);
		
		if (valueA < valueB) {
			return 1;
		}
		if (valueA > valueB) {
			return -1;
		}
		// names must be equal
		return 0;
	});
	
	sorted_authors = result_authors.sort(function(a,b){
		var valueA = a.value.toUpperCase();
		var valueB = b.value.toUpperCase();
		
		if (valueA < valueB) {
			return -1;
		}
		if (valueA > valueB) {
			return 1;
		}
		// names must be equal
		return 0;
	});
	
	sorted_titles = result_titles.sort(function(a,b){ 
		var valueA = a.value.toUpperCase();
		var valueB = b.value.toUpperCase();
		
		if (valueA < valueB) {
			return -1;
		}
		if (valueA > valueB) {
			return 1;
		}
		// names must be equal
		return 0;
	});
	
	sorted_fiches=result_fiches;
	
			// transfer sorted key:value pairs to ledger fields
			//frequencies
	frequencies_string='';
	for (line in sorted_frequencies){
		if(Number(line)<sorted_frequencies.length-1){
			frequencies_string += sorted_frequencies[line].key +',';
		}else{
			frequencies_string += sorted_frequencies[line].key;
		}
	}
			//titles
	titles_string='';
	for (line in sorted_titles){
		if(Number(line)<sorted_titles.length-1){
			titles_string += sorted_titles[line].key +',';
		}else{
			titles_string += sorted_titles[line].key;
		}
	}
			//authors
	authors_string='';
	for (line in sorted_authors){
		if(Number(line)<sorted_authors.length-1){
			authors_string += sorted_authors[line].key +',';
		}else{
			authors_string += sorted_authors[line].key;
		}
	}
	
	fiches_string='';
	for (line in sorted_fiches){
		if(Number(line)<sorted_fiches.length-1){
			fiches_string += sorted_fiches[line].key +',';
		}else{
			fiches_string += sorted_fiches[line].key;
		}
	}

	frequencies_ledger_div.innerHTML=frequencies_string;
	authors_ledger_div.innerHTML=authors_string;
	titles_ledger_div.innerHTML=titles_string;
	fiches_ledger_div.innerHTML=fiches_string;
	
	if(search_target=='text' && first_load==0){
		applyCritFilter('relevance');
	}
}

function applyCritFilter(criterion){

			//uncheck reverse button
	var reverse_button = document.getElementById('reverse_sort_button');
	reverse_button.checked = false;

	var number_of_results_div=document.getElementById('number_of_results');
	var number_of_results=number_of_results_div.innerHTML;
	
	var results_div=document.getElementById('results');
	var sorted_divs=[];
	
	var frequencies_ledger_div = document.getElementById('frequencies_ledger');
	var authors_ledger_div = document.getElementById('authors_ledger');
	var titles_ledger_div = document.getElementById('titles_ledger');
	var fiches_ledger_div = document.getElementById('fiches_ledger');
	
	var frequencies_ledger = frequencies_ledger_div.innerHTML.split(',');
	var authors_ledger = authors_ledger_div.innerHTML.split(',');
	var titles_ledger = titles_ledger_div.innerHTML.split(',');
	var fiches_ledger = fiches_ledger_div.innerHTML.split(',');

			//transfer result_bubbles into display bubbles according to maps
	switch (criterion){
		case 'relevance':
			for (index in frequencies_ledger){
				div_id='result_'+Number(frequencies_ledger[index]);
				div = document.getElementById(div_id);
				sorted_divs.push(div);
			}
		break;
		case 'title':
			for (index in titles_ledger){
				div_id='result_'+Number(titles_ledger[index]);
				div = document.getElementById(div_id);
				sorted_divs.push(div);
			}
		break;
		case 'author':
			for (index in authors_ledger){
				div_id='result_'+Number(authors_ledger[index]);
				div = document.getElementById(div_id);
				sorted_divs.push(div);
			}
		break;
		case 'fiche':
			for (index in fiches_ledger){
				div_id='result_'+Number(fiches_ledger[index]);
				div = document.getElementById(div_id);
				sorted_divs.push(div);
			}
		break;
		
	}
	results_div.innerHTML='';
	for (div in sorted_divs){
		results_div.appendChild(sorted_divs[div]);
	}
		//applyResultsPageFilter(?)
}

function applyResultsPageFilter(page_number){

		// Filter N results per page

}

function reverseSort(){
				//ref Neil @ https://stackoverflow.com/questions/12539361/how-to-reverse-the-ordering-of-list-items-in-an-unordered-list#12539391
	var results_div=document.getElementById('results');
	var results_children=results_div.children;
	var c=results_children.length;
	while(c--){
		if(Number.isInteger(c)){
			results_div.appendChild(results_children[c]);
		}
	}
			//applyResultsPageFilter(?
}


		//display functions
function loadArticle(article_url,search_query){
	results_monitor_div = document.getElementById("results_monitor");
	results_monitor_div.style.display = 'none';
	
			//hide results_div
	var results_div=document.getElementById('results');
	results_div.style.display='none';
	view_directive_div = document.getElementsByClassName('view_directive')[0];
	view_directive_div.style.display='inline';
	
				//ref https://blog.oio.de/2014/04/11/integrating-pdf-js-pdf-viewer-web-application/
	var pdf_iframe= document.createElement("iframe");
	pdf_iframe.id = 'pdf_iframe';
	
	if (search_query==''){
		pdf_iframe.src  = "http://bclarchive.net/fichedir/" + article_url;
	}else{
		pdf_iframe.src  = "http://bclarchive.net/fichedir/" + article_url + "#search=" + search_query; //bf @ https://github.com/mozilla/pdf.js/issues/1875
	}
	pdf_iframe.width='100%';
	pdf_iframe.height='1000em';

			//now add back to results div
	main_view_div=document.getElementsByClassName('main-view')[0];
	main_view_div.appendChild(pdf_iframe);
	
}

function backToResults(){
	pdf_iframe = document.getElementById('pdf_iframe');
	results_monitor_div = document.getElementById("results_monitor");
	view_directive_div = document.getElementsByClassName('view_directive')[0];
	view_directive_div.style.display='none';
	results_monitor_div.style.display='inline';
	var results_div=document.getElementById('results');
	results_div.style.display='block';
	
	main_view_div=document.getElementsByClassName('main-view')[0];
	
	main_view_div.removeChild(pdf_iframe);
}

</script>

	</body>
</html>


<?php
 get_footer();
?>