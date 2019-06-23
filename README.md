# BCL_Archive
Home to the back-end of bclarchive.net, presently browsable at http://bclarchive.net/browse/.

## CONTENTS
* [Introduction](#introduction)
* [Control Sheet](#control-sheet)
* [Database Architecture](#database-architecture)
* [Helper Scripts](#helper-scripts)
* [Hosted Content](#hosted-content)
* [Active Scripts](#active-scripts)

# Introduction
The BCL Archive comprises a wealth of academic research published by the Biological Computation Lab at Univ. of Illinois, Urbana-Champagne, since its inception in 1958 through nearly two-decades up to 1976. Encompassing a unique cross-section of subject matter spanning computer science, biology and philosophy, the archive provides a sensational glimpse into the breathtaking atmosphere of the BCL at the dawn of the information age.

The history of this project begins with the doctorate research of Prof. Peter Asaro, which included the work of retrieving from microfische and digitally scanning the complete published catalog the BCL. Asaro then hired student research assistants at the New School to coordinate the production of a digital archive housed to be housed at bclarchive.net.  The first generation of student workers took up the task of converting the data from digital images to PDF documents and recovering the texts using OCR (optical character recognition).  The subsequent phases of production are herein explained in depth.

This documentation mainly details the archive's back-end, being a custom Wordpress theme; the code, however, is largely platform-independent, and comprises mainly Javascript, PHP, SQL, and HTML.  Python and Ruby scripts were used for validation and standardization and, along with various procedural artifacts, may be found in the **proc** directory of the this repository.   All other scripts (ie. those used on the server) may be found in the **live** directory.  Future generations of research assistants and/or interested students should feel free to fork this repository and examine/alter the code for themselves, and kindly note that **all web sources, including stackexchange posts, have been cited**. The following is an overarching explication of production by the lead developer from 2/2018-6/2019, Edmund Eisenberg.

# Control Sheet

The first benchmark of compiling the database was the creation of a master control sheet linking together all relevant details pertaining to the archive's production, including article titles, authors, file locations, fiche locations and more. This work was spearheaded by Sofia, who manually assembled all of the useful metadata in the publications into a single spreadsheet.  Through conversations with the development team, she also implemented stringent standards of formatting author names, file paths, and duplicate/multiple quantities in order to ensure flawless migration to a MySQL database.  These conventions should be maintained in subsequent alterations to the Control Sheet, and are as follows:

- Author names must be formatted "Last, F. (M.)"
- Multiple author names should be separated by "&" exclusively ie. never by commas
- Fiche number must be in 3 digit notation
- Multiple fiches should be indicated by a range, eg. FICHE 004-006
- File paths should follow the pattern, "fiche###_number_reportcover_authors_title.pdf"

# Database Architecture

Foundational to the succesful organization of the large body of primary-source documents was a strong planning convention in the database schema.  This convention was learned in the Lynda.com course "Programming Foundations: Databases with Simon Allardice".  The premise of this schema convention is relational, low-redundancy and purposeful design based on "one-to-many" and "many-to-many" ID relationships.  [An early draft](proc/bcla_early_schema.jpg) of the schema proved essential to achieving the data-sanity and robustness required for implementing a database of the incumbent magnitude, and the current schema is depicted by this image:

![Schematizing the database was the essential first step of building the BCL archive](proc/bcla_final_schema.jpg "The preliminary schema used in planning the layout of the database")

The use of a linking table between ARTICLE and AUTHOR tables reflects the "many-to-many" relationship among these two tables, whereas the remaining tables feature "one-to-many" relationships or, in the special* case of the CONTROLSHEET table, a "one-to-one" relationship.

*Usually, "one-to-one" relationships are discouraged in relational databases, by the reasoning that there is no need for more than one table.  However, slight formatting differences between human and machine-readable versions of the same data are license for a "one-to-one" relationship between the ARTICLE and CONTROLSHEET tables.

# Helper Scripts

Although the database was at this point "clear for takeoff", there were several hurdles remaining surrounding the documents:
- The articles were, up till then, organized in directories according to their fiche, but not necessarily in the order of the control sheet.  Furthermore, with the [file name standardization](#control-sheet), fiche directories had become redundant altogether.  It would thus be necessary to substitute this structure for a single, "flat" directory containing all of the articles.
- The file paths used in the original control sheet were URLs to the documents in Google Drive, not the local paths.  Because Google uses hashes in the URLs, there was no direct relationship between these links and the actual file names.  The control sheet would have to be copiously updated to include these file names and there was no clear way of approaching this task.
- Last but not least, there was the formidable problem of OCR inaccuracies found to some degree in nearly all of he PDFs.  This issue should at least be structurally addressed if not fixed altogether.

These issues, for the most part, were tackled programmatically.  Here are the methods used:

#### ficheflatten.rb
To create a flat directory structure, a [ruby script](proc/ficheflatten.rb) was run at the root of the fiche directories. This script not only indexed, consolidated, and cleaned up the documents within, but also corrected various file name inconsistencies such as duplicate  punctuation, invalid whitespace, and improper numerical formatting.

#### filename_matcher.rb
To update the correct URL links in the control sheet, a [pattern matching ruby script](proc/url_nn_map.rb) was utilized.  This algorithm compared article titles from the Control Sheet to document filenames by evaluating the sequential correspondence between all possible combinations therein using a weighting ledger.  Using this approach, the estimation of correspondence was sufficiently accurate and highly preferable to manual correction.

#### text_scraper.py
The problem of ubiquitous inaccuracies in the OCR remains an issue, but [python](proc/text_scraper.py) was used to exhaustively mine the article texts into XML format.  This contributed significantly towards establishing command over this considerable problem, and there have been attempts at developing a browser-based OCR-correction-interface.  At the time of writing, however, no such interface has been effectively integrated into the archive/database.  This task could be a potential "next step" for future generations of research assistants.	

The output of the processes aforementioned is the cumulative hosted content as summarized in the following section.

# Hosted Content

### PDFs
As [discussed](#ficheflattenrb) in the previous section, the whole of the publications in PDF format were consigned to a single, flat directory with a homogeneous naming convention.  The resulting directory 'fichedir' was then uploaded to the root of bclarchive.net.  Individual PDFs may be accessed by the following URL scheme: http://bclarchive.net/fichedir/filename.pdf

### XML files
In order to push the collected text data mined from the PDFs to the database, the output of [text_scraper.py](#text_scraperpy) was uploaded to the root of the server as well.  The resulting XML files were aggregated in a directory named 'xml_data'.  The structure of such files is as follows:

```
<pages>
	<page>
		<article> [article_id] </article>
		<page_number> [page_number] </page_number>
		<width> [page_width_px] </width>
		<height> [page_height_px] </height>
		<item>
			<x> [x_coordinate_px] </x>
			<y> [y_coordinate_px] </y>
			<height> [line_height_px] </height>
			<content> [text_content] </content>
		</item>
		<!--
		... items for each text object in page
		-->
	</page>
	<!--
	... additional pages in document
	-->
</pages>			
		
```
It should be noted that, due to rigorous encoding standards of XML, extensive validation of the text was prerequisite and, though not explicitly documented, this is comprehensively referenced by citations included in text_scraper.py.  

# Active Scripts

In this section, a comprehensive account will be given of the scripts that are actively deployed on the server.  This includes references to peripheral content as well as the order in which scripts were deployed.

[finish commenting db pusher]

####upload_download.php

The purpose of upload_download.php is to have a page where the control spreadsheet may be downloaded, modified, and then re-uploaded.

////left to finish:
on reupload, contrast each row with existing,

if changed, borrow parser functions from db_pusher
and update each row




Actively-deployed scripts may be found in the **live** directory of this repository.  It's contents may be divided into two distinct categories: **Wordpress Template Files** and **Server-Side Scripts**.  This distinction is made on the basis of how a script is executed, ie. via a web-server or a local user, by the reaon

### Wordpress Template Files

The first type of script are Wordpress template files, which contain the PHP, HTML, CSS, and Javascript that make up the public-facing website.  These 'template files' are essentially no different than any other PHP webpage, that is, they are accessed by URL and executed by a webserver before being served to the browser.

#### upload_download.php

  

#### bcl_viewer.php

### Server-Side Scripts

Server-side Scripts, contrasted with Wordpress Templates, are not publicly-accessible, as they require an execution timeframe greater than is allowed by public-oriented security restrictions.  Instead, they must be run locally from the server; this may be done by scheduling a cron job in the host control panel. 

#### db_pusher.php

## proc
In the **proc** directory, we find helper scripts and procedural artifacts left over from the duration of the archiving process (see **timeline** for details) which have been retained for educational and reproducibility purposes.

<!-- 
### Collation and OCR - Yelena w Sofia, Sara
The first step was the collation of a burgeoning repository of scanned TIFF images into organized PDF documents corresponding to all publications and their Fiche locations. 

### Control Sheet Population – Sofia

### flat document organization - Sofia + Ned
####	renaming
####	flattening
####	mapping to control column
####	uploading

### Website Foundations – Ned
####	schematization
####	control sheet upload +download
####	document parsing & validation
####	db compilation

### Front End
####	Search bar => queries db
####	results filter=>sorts results (!fill div arrays)
####		hidden ledgers
####	reverse sort
####	article display


List of procedures

Python
Ruby
upload_download.php
db_pusher.php

Templates

Server-side Scripts

Other Scripts
 -->
