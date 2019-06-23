# run this ruby script inside fiche root

require 'fileutils'

path = Dir.pwd()
dir = Dir.new(path)

subfolders = Array.new

dir.each do |d|
	subfolders.push(d)
end

#move files to root dir
subfolders.each do |s|
	if File.directory?(s) && s != ".." && s != "."
		files = Dir.children(s)
		files.each do |f|
			if f != '_DS_Store' && f != '.DS_Store' && File.file?(s+"/"+f)
				FileUtils.copy(s+"/"+f,".")
			end
		end
	end
end
			
#delete subfolders recursively
subfolders.each do |s|
	if File.directory?(s) && s != ".." && s != "."
		FileUtils.rm_r(s)
	end
end

#fix whitespace in filenames

filelist = Array.new
dir = Dir.new(path)

dir.each do |f|
	filelist.push(f)
end

filelist.each do |fn|
	if fn.include?(" ") && File.file?(fn)
		File.rename(fn,fn.gsub(" ",""))
	end
end


#fix duplicate periods

filelist = Array.new
dir = Dir.new(path)

dir.each do |f|
	filelist.push(f)
end

filelist.each do |fn|
	if fn.include?("..") && File.file?(fn)
		File.rename(fn,fn.gsub("..",".")
	end
end

#interpolate fiche numbers into 3 digts

filelist = Array.new
dir = Dir.new(path)

dir.each do |f|
	filelist.push(f)
end

filelist.each do |fn|
	if (fn != "." && fn != "..")
		substrings = fn.split("_")
		fiche = substrings[0].downcase
		number = fiche.gsub("fiche","")
		new_number=''
		integer = number.to_i

		if integer > 0
			new_number = format("%03d",integer)
		else
			new_number = number
		end

		substrings[0] = "fiche" + new_number
		new_fn = substrings.join("_")
	
		File.rename(fn,new_fn)
	end
end
