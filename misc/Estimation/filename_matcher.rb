# titles[]
# 
# urls[]
# 
# map[]
# 
# for each title
# 	array characters[]=title.chars
# 	dict results[]=dict[length_of_urls]
# 	
# 	n=0
# 	for each url:
# 		for each character
# 			if url contains character
# 				results[n]++;
# 		n++
# 			
# 	highest_result=results.sort[0];
# 	
# 	map[title]=urls[highest_result];
# 		



# exectute inside directory containing titles.txt, urls.txt broken by line
# http://vicfriedman.github.io/blog/2015/09/01/everything-you-need-to-know-about-working-with-text-files-in-ruby/

# read the data
title_file = File.open('titles.txt')
url_file = File.open('urls.txt')
title_file_content = title_file.read
url_file_content = url_file.read

#parse the lines of data
titles=title_file_content.split("\n")
urls=url_file_content.split("\n")

# create empty map key
map = Hash.new


#cross reference characters of titles X urls

#for each title
titles.each{ |title|

	#remove whitespace
	t= title.split(' ').join

	#set 'highest' counter
	highest_url=''
	highest_count=-100 #arbitrary, low number starting point

	#create an array of title's characters
	title_chars=t.chars
	
	#create empty hash for results versus each url
	results = Hash.new
	
	#for each url
	urls.each{ |u|
		#only use title portion of url
		u_t=u.split('_').last
	
	
		#split the url into individual characters
		url_chars = u_t.chars
		#create a new result entry for the specific url
		results[u]=0;
		
		#convolve sequence order as a weight factor
		index_weight=0
		
		# for each character in the title
		title_chars.each{ |t_c|
			# find the character in the url, and delete if exists to prevent duplicates
			index= url_chars.find_index(t_c)
			
			if index != nil
				#weight: index
				index_weight+=index
				# results +=1
				results[u]+=5
				# delete if exists to prevent duplicates
				url_chars.delete_at(index)
			end
		}
		
		#subtract total index weight
		results[u]-=index_weight
		
		if results[u]>highest_count
			highest_count=results[u]
			highest_url=u
		end
	}
	
	map[title]= highest_url
}

File.open("map.txt",'w') do |file|
	titles.each{ |t|
		file.write(map[t]+"\n")
	}
	file.close
end

print map
