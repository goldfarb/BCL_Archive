#### reminder struhar {jr.}

#run this file inside parent directory of fichedir

#imports

#open filename map

#import filenames as array

#for each filename as fn
	#make new file '# fn.xml'
		#
		#	fill format will be
		#		<page>
		#			<article>
		#			<page_number>
		#			<width>
		#			<height>
		#			<item>
		#				<x>
		#				<y>
		#				<height>
		#				<content>
		#			<item>
		#				<x>
		#				<y>
		#				<height>
		#				<content>
		# etc...

#references found below
		
from pdfminer.layout import LAParams, LTTextBox
from pdfminer.pdfpage import PDFPage
from pdfminer.pdfinterp import PDFResourceManager
from pdfminer.pdfinterp import PDFPageInterpreter
from pdfminer.converter import PDFPageAggregator

current_row = 1;

map_file = open('map.txt','r')

lines=[line.rstrip('\n') for line in map_file]

#for each article

for line in lines:
	# copy filename from pdf document
	filename=line.replace('.pdf','.xml')	
	# open new xml file
	xfp = open('xml_data/' + filename, 'w')
	
	# open pdf file
	fp = open('fichedir/'+line, 'rb')
	
	# set pdfminer resources
	rsrcmgr = PDFResourceManager()
	laparams = LAParams()
	device = PDFPageAggregator(rsrcmgr, laparams=laparams)
	interpreter = PDFPageInterpreter(rsrcmgr, device)
	pages = PDFPage.get_pages(fp)
	
	# counter
	current_page = 1
	xfp.write('<pages>')
	for page in pages:
		# output monitor
		if(current_page==1):
			print current_row, ' ', filename
		
		# markup page-level data
		xfp.write("<page>\n")
		xfp.write("\t<article>" + str(current_row) + '</article>\n')
		xfp.write("\t<page_number>" + str(current_page) + '</page_number>\n')
		xfp.write("\t<width>" + str(page.mediabox[2]) + '</width>\n')
		xfp.write("\t<height>" + str(page.mediabox[3]) + '</height>\n')
	
		# markup text-level data
		interpreter.process_page(page)
		layout = device.get_result()
		for lobj in layout:
			if isinstance(lobj, LTTextBox):
				xfp.write("\t<item>\n")
 				x, y, text = '%.3f'%lobj.bbox[0], '%.3f'%lobj.bbox[3], lobj.get_text().encode('utf-8').rstrip("\n")
				height = '%.3f'%(lobj.bbox[3] - lobj.bbox[1])
				well_formed_text=text.replace('\n',' ').replace('\r', '').replace('&', '&amp;').replace('<','&lt;').replace('>','&gt;').replace("'",'&apos;').replace("\"",'&quot;')
				xfp.write("\t\t<x>" + x + "</x>\n")
				xfp.write("\t\t<y>" + y + "</y>\n")
 				xfp.write("\t\t<height>" + height + "</height>\n")
 				xfp.write("\t\t<content>" + well_formed_text + "</content>\n")
				xfp.write("\t</item>\n")
		xfp.write("</page>\n")
		current_page+=1
	xfp.write("</pages>")
 	xfp.close()
 	current_row+=1

# pdfminer https://github.com/euske/pdfminer/
# c/o Mark Amery https://stackoverflow.com/questions/22898145/how-to-extract-text-and-text-coordinates-from-a-pdf-file#22898159
# https://www.hacksparrow.com/python-length-or-size-of-list-tuple-array.html
# Abhranil Das @ https://stackoverflow.com/questions/8595973/truncate-to-three-decimals-in-python#8595991
# jfs @ https://stackoverflow.com/questions/3277503/how-to-read-a-file-line-by-line-into-a-list#3277516
# sorin @ https://stackoverflow.com/questions/6159900/correct-way-to-write-line-to-file#6159912
# agf @ https://stackoverflow.com/questions/9942594/unicodeencodeerror-ascii-codec-cant-encode-character-u-xa0-in-position-20#9942822

# David Grayson @ https://stackoverflow.com/questions/9519645/copying-a-file-from-one-directory-to-another-with-ruby#9520443
# YOU @ https://stackoverflow.com/questions/2491222/how-to-rename-a-file-using-python#2491232
# rslite @ https://stackoverflow.com/questions/82831/how-do-i-check-whether-a-file-exists-without-exceptions#82852
# Jmjmh @ https://stackoverflow.com/questions/7323782/python-how-to-join-entries-in-a-set-into-one-string
# cs95 @ https://stackoverflow.com/questions/46513358/finding-the-first-duplicate-of-an-array
# James @ https://stackoverflow.com/questions/16861207/xml-error-extra-content-at-the-end-of-the-document
# martincho @ https://stackoverflow.com/questions/10507230/insert-line-at-middle-of-file-with-python#10507291
# ricricucit @ https://stackoverflow.com/questions/7604436/xmlparseentityref-no-name-warnings-while-loading-xml-into-a-php-file#14318580
# potame @ https://stackoverflow.com/questions/730133/invalid-characters-in-xml
