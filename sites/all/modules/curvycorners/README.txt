   /*============================================================================*\  
   ||                     -= curvyCorners Drupal module =-                       ||
   ||                                                                            ||
   || README.txt                                                                 ||
   || By Jordan Starcher                                                         ||
   || Edited for Drupal 6 By Pat Teglia (CrashTest_)                             ||
   || Project Version 6.x-1.0-dev                                                ||
   || November 20, 2010                                                          ||
   || Released under the GNU license                                             ||
   ||                                                                            ||
   \*============================================================================*/

Due to licensing limitations, the JavaScript file(s) needed for curvyCorners cannot be packaged with this module.
Please follow the instructions below to install and complete the module.

1. Download curvyCorners from http://drupal.org/curvyCorners
   and extract the contents into your modules folder.

2. Download curvyCorners from http://www.curvycorners.net/downloads/
   Notice this module was designed and tested with version 2.0.4
   but may work with other versions.

3. Extract the curvyCorners zip folder and place the two .inc.js files
   inside your curvyCorners module folder.

3. Check administer > settings > Curvy Corners to see if the JavaScript
   files are correctly installed.

Some basic usage instructions are below, more detailed usage instructions
  are available at http://www.curvycorners.net/instructions/

Please support my efforts by donating. http://www.TheOverclocked.com/donate

---------------------------------------
Here is an example to apply curvyCorners to a single DIV. This is the exact code you would
  paste in the curvyCorners code box to apply curvyCorners on all pages of your site.

	window.onload = function()
	  {
	     settings = {
	          tl: { radius: 10 },
	          tr: { radius: 10 },
	          bl: { radius: 10 },
	          br: { radius: 10 },
        	  antiAlias: true,
	          autoPad: true,
          	  validTags: ["div"]
	      }

	   var divObj = document.getElementById("myDiv");
	   var cornersObj = new curvyCorners(settings, divObj);
 
	   cornersObj.applyCornersToAll();
	}



-----------------------------------------
If you need additional DIVs rounded with different settings then you will need to create
  additonal instances of the curvyCorners object for each one which require different settings.

Here is an example of different settings for two DIVs. Notice instead of using the generic
  name "settings" a more specifc identifier was choosen.

	window.onload = function()
	  {
	     header = {
	          tl: { radius: 10 },
	          tr: { radius: 10 },
	          bl: false,
	          br: false,
        	  antiAlias: true,
	          autoPad: true,
          	  validTags: ["div"]
	      }
	
	     footer = {
	          tl: false,
	          tr: false,
	          bl: { radius: 10 },
	          br: { radius: 10 },
	          antiAlias: true,
	          autoPad: true,
	          validTags: ["div"]
	      }

	   var divObj1 = document.getElementById("header");
   	   var divObj2 = document.getElementById("footer");
    
	   var cornersObj1 = new curvyCorners(header, divObj1);
   	   var cornersObj2 = new curvyCorners(footer, divObj2);

	   cornersObj1.applyCornersToAll();
	   cornersObj2.applyCornersToAll();
	}



--------------------------------------------
Here is the am example that applies curvyCorners to an entire class rather then a single DIV.

	window.onload = function()
	  {
	     settings = {
	          tl: { radius: 10 },
	          tr: { radius: 10 },
	          bl: { radius: 10 },
	          br: { radius: 10 },
        	  antiAlias: true,
	          autoPad: true,
          	  validTags: ["div"]
	      }

	   var cornersObj = new curvyCorners(settings, "myClass");
 
	   cornersObj.applyCornersToAll();
	}

--------------------------------------------
If you would like to use some jQuery syntax, it would go something like:

$(document).ready(function() {
  settings = {
    tl: { radius: 10 },
    tr: { radius: 10 },
    bl: { radius: 10 },
    br: { radius: 10 },
    antiAlias: true,
    autoPad: false,
  }

  $blocks = $('#sidebar-left .block, #page');
  $blocks.each(function() {
    varCC = new curvyCorners(settings, this);
    varCC.applyCornersToAll();
  });
});

NOTE:  I used autoPad: false, because otherwise it seems to break Drupal's javascript. (pt)
