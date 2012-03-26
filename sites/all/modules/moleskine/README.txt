
Moleskine, By CouzinHub // couzin.hub@gmail.com

/////// INSTALLATION  /////////

  1. Download Moleskine from http://drupal.org/project/moleskine

  2. Unpack the downloaded file and place the moleskine folder in your Drupal installation under one of the following locations:

       sites/all/themes : making it available to all Drupal sites in a mult-site configuration

       sites/default/themes : making it available to only the default Drupal site

       sites/example.com/themes : making it available to only the example.com site if there is a sites/example.com/settings.php configuration file

  3. Log in as an administrator on your Drupal site and go to :

	Administer > Site building > Themes (admin/build/themes) 
     
	and make moleskine the default theme.

  4. From the Theme settings page (admin/build/themes) configure the Moleskine theme.


/////// Notes /////////

If you are using the TinyMCE editor, you can use the css file in the moleskine theme folder called tinymce.css to make Tinymce render the styles of the theme.

- Simply go to the TinyMCE administration page : 

/admin/settings/tinymce

- Open the profile (if multiple profiles, you will have to do this each one of them)

- In the section 'CSS', select 'Define CSS' under 'Editor CSS:'

- In the 'CSS path:' input, copy and paste this path :

/sites/all/themes/moleskine/tinymce.css

supported and tested buttons : Bold / Italic / Underline / strikethrough / text alignment / lists / blockquote / smileys / formatselect Ð font / Fullscreen (hard one... could be buggy) / Preview (same as fullscreen) / XHTML Xtras Plugin