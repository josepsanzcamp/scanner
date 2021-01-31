# scanner
Software to automate the scanner task for my brother DS620

# Description
The main idea of this script is to automate the scanner task by providing an infinite loop that try to scan images until you break the loop by pressing the CTRL+C keys, currently it's intended to work with my Brother DS620 scanner using the follow external programs:

- scanimage => used to obtain the images from the scanner
- convert => to crop the image and to create the first version of pdf
- gs => to minify the pdf
- killall => to stop the old geeqie process
- geeqie => to show the current scanned image

# How to use
To execute the program, try to execute the scanner.php by "php scanner.php", when you want to end the scanner task, you only need to press the CTRL+C keys to break the main loop.

