Wp-Pomo
===============

Generate .PO and .MO files for your theme just by using the Wordpress admin interface. Uses PotX, some code from WPML (when they were on Wordpress plugin database), and php.mo from josscrowcroft.

Drop the lib folder in your theme, and go `require 'lib/wp-pomo/wp-pomo.php';` from your functions.php. Now there should be a new item under Tools/Translate your theme.

If you wanna see this as an independent plugin, please clone this repo; I have no time for it.

To load your language files, try using the following code in your functions.php:

`
function theme_init(){
  $lang_dir = dirname(__FILE__) . "/languages/";

  $dh = opendir($lang_dir);
  
  while(false !== ($file = readdir($dh))){
    if($file=="." || $file=="..") continue;
    
    if(is_dir($lang_dir . "/" . $file)){
      $dir = $file;
      
      $ddh = opendir($lang_dir . $dir);
      while(false !== ($file = readdir($ddh))){
        if(preg_match('#\.mo$#i', $file)){
          load_theme_textdomain($dir == "theme-localization" ? "default" : $dir, get_template_directory() . "/languages/" . $dir);
        }
      }
      
      closedir($ddh);
    }
  }

  closedir($dh);
}

add_action ('init', 'theme_init');
`