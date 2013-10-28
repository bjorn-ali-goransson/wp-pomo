<?php
/*
 * License: GPL v3 http://www.opensource.org/licenses/gpl-3.0.html
 */

$lang_dir = dirname(__FILE__) . "/../../languages/";

if(!is_dir($lang_dir)){
  mkdir($lang_dir);
}
if(!is_dir($lang_dir)){
  wp_die("Could not create directory \"languages\" in theme directory.");
}

add_action('admin_menu', function(){
	add_management_page("Translate your theme", "Translate your theme", "manage_options", "translate-your-theme", function(){
    global $lang_dir;
    
    ?>
      <div class="wrap">
        <div id="icon-tools" class="icon32"><br></div>
        <h2>Translate your theme</h2>
        <?php
          $files = get_po_files();
          
          if(count($files) > 0 && isset($_REQUEST["save_translation"]) && $_REQUEST["save_translation"] == "1"){
            $file = $_REQUEST["file"];
            $po_rows = file($lang_dir . $file);
            
            for($i = 0; $i < count($_REQUEST["po_strings"]); $i++){
              $po_string = $_REQUEST["po_strings"][$i];
              $po_translation = $_REQUEST["po_translations"][$i];
              
              $content_as_po = 'msgid "' . addcslashes(stripslashes($po_string), "\0..\37\\\"") . '"' . PHP_EOL;
              
              $found = false;
              
              // update string translation
              for($j = 0; $j < count($po_rows); $j++){
                if($po_rows[$j] == $content_as_po){
                  $po_rows[$j + 1] = 'msgstr "' . $po_translation . '"' . PHP_EOL;
                  $found = true;
                  
                  break;
                }
              }
              
              if(!$found){
                die("Couldn't update translation of \"$po_string\"; somehow, I didn't manage to find the key in the .po file.");
              }
            }
            
            $po = implode("", $po_rows);
            
            file_put_contents($lang_dir . $file, $po);
            
            generate_mo_file($file);
            
            ?>
              <p>Saving was successful!</p>
              <p><a href="tools.php?page=translate-your-theme&file=<?php echo $file ?>" class="button-primary" id="view-strings">Continue editing</a></p>
              <script>
                location.href = document.getElementById("view-strings").href;
              </script>
            <?php
          } else if(isset($_REQUEST["scan_theme"]) && $_REQUEST["scan_theme"] == "1"){
            if(isset($_REQUEST["locale"]) && $_REQUEST["locale"] != ""){
              scan_theme_files();
              generate_po_files($_REQUEST["locale"]);
              ?>
                <p>Scanning was successful! <?php if(count($domains) > 1) { echo count($domains) ?> translation files (from different "domains") were created. <?php } ?>.</p>
                <p><a href="tools.php?page=translate-your-theme" class="button-primary" id="view-strings">View strings</a></p>
                <script>
                  location.href = document.getElementById("view-strings").href;
                </script>
              <?php
            } else {
              scan_theme_files();
              generate_po_files();
              ?>
                <p>Scanning was successful!</p>
                <p><a href="tools.php?page=translate-your-theme" class="button-primary" id="view-strings">View strings</a></p>
                <script>
                  location.href = document.getElementById("view-strings").href;
                </script>
              <?php
            }
          } else {
            if(count($files) > 0){
              $chosen_file = isset($_REQUEST["file"]) ? $_REQUEST["file"] : "";
              
              $found = false;
              
              foreach($files as $file){
                if($chosen_file == $file){
                  $found = true;
                }
              }
              
              if($chosen_file == "" || !$found){
                $chosen_file = $files[0];
              }
              
              $po_file_rows = file($lang_dir . $chosen_file);
              
              if(count($files) > 1){
                ?>
                  <p>
                    <form method="get" action="tools.php" id="switch-working-file">
                      <input type="hidden" name="page" value="translate-your-theme" />
                      <select name="file" id="selected-file-for-viewing" onchange="document.getElementById('switch-working-file').submit();">
                        <?php foreach($files as $file){ ?>
                          <option <?php if($chosen_file == $file) echo "selected=\"selected\"" ?>><?php echo $file ?></option>
                        <?php } ?>
                      </select>
                      <button class="button">View</button>
                    </form>
                  </p>
                <?php
              } else {
                ?>
                  <p>&nbsp;</p>
                <?php
              }
              
              ?>
                <form method="post" action="tools.php?page=translate-your-theme">
                  <input type="hidden" name="save_translation" value="1" />
                  <input type="hidden" name="file" value="<?php echo $chosen_file ?>" />
                  
                  <table class="widefat" cellspacing="0">
                    <thead>
                      <tr>
                        <th>Theme string</th>
                        <th>Translation</th>
                      </tr>
                    </thead>
                    <tfoot>
                      <tr>
                        <th>Theme string</th>
                        <th>Translation</th>
                      </tr>
                    </tfoot>
                    <tbody>
                      <?php
                        for($i = 0; $i < count($po_file_rows); $i++){
                          if(strpos($po_file_rows[$i], "msgid") !== 0 || strpos($po_file_rows[$i], "msgid \"\"") === 0){
                            continue;
                          }
                          
                          $po_string = substr($po_file_rows[$i], strlen("msgid \""), strrpos($po_file_rows[$i], "\"") - strlen("msgid \""));
                          $po_string = stripslashes($po_string);
                          
                          $po_translation = substr($po_file_rows[$i + 1], strlen("msgstr \""), strrpos($po_file_rows[$i + 1], "\"") - strlen("msgstr \""));
                          $po_translation = stripslashes($po_translation);
                          
                          ?>
                            <tr>
                              <td>
                                <input type="text" name="po_strings[]" value="<?php echo esc_attr(htmlspecialchars($po_string)) ?>" readonly="readonly" style="width:100%;" />
                              </td>
                              <td>
                                <input type="text" name="po_translations[]" value="<?php echo esc_attr(htmlspecialchars($po_translation)) ?>" style="width:100%;" />
                              </td>
                            </tr>
                          <?php
                        }
                      ?>
                    </tbody>
                  </table>
                  
                  <p><button class="button-primary">Save</button> <a href="tools.php?page=translate-your-theme&scan_theme=1" class="button" title="Don't worry - your translations won't be changed.">Rescan theme for new/deleted strings</a></p>
                </form>
              <?php
            } else {
              ?>
                <form method="get" action="tools.php">
                  <input type="hidden" name="page" value="translate-your-theme" />
                  <input type="hidden" name="scan_theme" value="1" />
                  <p>Please start by specify the <a href="http://codex.wordpress.org/WordPress_in_Your_Language">locale</a> for which you wish to translate: <input type="text" name="locale" value="<?php echo WPLANG != '' ? WPLANG : "sv_SE" ?>" />.</p>
                  <p>Then, use the button below to scan your theme for strings to translate.</p>
                  <p><em>(Make sure you have prepared your theme by using <a href="http://codex.wordpress.org/Translating_WordPress">gettext calls</a> (like </em><code>__()</code><em> and </em><code>_e()</code><em>) to output your strings!</em></p>
                  <p><button class="button-primary">Scan theme</button></p>
                </form>
              <?php
            }
          }
        ?>
      </div>
    <?php
	});
});

function get_po_files(){
  global $lang_dir;
  
  $files = array();
  
  $dh = opendir($lang_dir);
  
  while(false !== ($file = readdir($dh))){
    if($file=="." || $file=="..") continue;
    
    if(is_dir($lang_dir . "/" . $file)){
      $dir = $file;
      
      $ddh = opendir($lang_dir . "/" . $dir);
      
      while(false !== ($file = readdir($ddh))){
        if(preg_match('#\.po$#i', $file)){
          $files[] = $dir . "/" . $file;
        }
      }
      
      closedir($ddh);
    }
  }
  
  closedir($dh);
  
  return $files;
}

function generate_mo_files(){
  $files = get_po_files();
  
  foreach($files as $file){
    generate_mo_file($file);
  }
}

function generate_mo_file($file){
  global $lang_dir;
  
  require "lib/php-mo.php";
  
  phpmo_convert($lang_dir . "/" . $file);
}

function generate_po_files($locale = false){
  global $rows, $domains, $lang_dir;
  
  $locales = array();
  
  if($locale){
    $locales[] = $locale;
  } else {
    $files = get_po_files();
    
    foreach($files as $file){
      $matches = array();
      
      // locale must be evident in filename to be detected!
      if(preg_match("@((.._)?..)\\.po$@", $file, $matches)){
        $locales[] = $matches[1];
      }
    }
  }
  
  foreach($locales as $locale){
    foreach($domains as $domain){
      if(!is_dir($lang_dir . "/" . $domain)){
        mkdir($lang_dir . $domain);
      }
      
      $filename = $domain . "/" . $locale . ".po";
      
      if(!is_file($lang_dir . $filename)){
        $po = generate_po_file($rows, $domain);
        file_put_contents($lang_dir . $filename, $po);
      } else {
        $po_rows = file($lang_dir . $filename);
        $po = merge_po_content($po_rows, $rows);
        file_put_contents($lang_dir . $filename, $po);
      }
    }
  }
}

function merge_po_content($po_rows, $rows){
  // update existing rows
  foreach($rows as $row){
    $content_as_po = $row['msgid'] . PHP_EOL;
    
    for($i = 0; $i < count($po_rows); $i++){
      if($po_rows[$i] == $content_as_po){
        // update string position
        $string_positions = get_po_string_position($row);
        
        $first_half_of_po_rows = array_slice($po_rows, 0, $i - 2); // (exclude existent string positions and content_as_po)
        $second_half_of_po_rows = array_slice($po_rows, $i); // (include content_as_po and translation)
        
        $po_rows = array_merge($first_half_of_po_rows, $string_positions, $second_half_of_po_rows);
        
        unset($first_half_of_po_rows);
        unset($second_half_of_po_rows);
        
        break;
      }
    }
  }
  
  // remove inexistent rows
  for($i = 0; $i < count($po_rows); $i++){
    if(strpos($po_rows[$i], "msgid") !== 0 || strpos($po_rows[$i], "msgid \"\"") === 0){
      continue;
    }
    
    $found = false;
    
    foreach($rows as $row){
      $content_as_po = $row['msgid'] . PHP_EOL;
      
      if($po_rows[$i] == $content_as_po){
        $found = true;
        break;
      }
    }
    
    if(!$found){
      $first_half_of_po_rows = array_slice($po_rows, 0, $i - 2); // (exclude existent string positions and content_as_po)
      $second_half_of_po_rows = array_slice($po_rows, $i + 3); // (exclude content_as_po and translation and the empty newline after them)
      
      $po_rows = array_merge($first_half_of_po_rows, $second_half_of_po_rows);
      
      unset($first_half_of_po_rows);
      unset($second_half_of_po_rows);
      
      $i = 0;
      
      // TODO: look for filename:linenumber also
    }
  }
  
  // add new rows
  foreach($rows as $row){
    $content_as_po = $row['msgid'] . PHP_EOL;
    
    $found = false;
    
    for($i = 0; $i < count($po_rows); $i++){
      if($po_rows[$i] == $content_as_po){
        $found = true;
        break;
      }
    }
    
    if(!$found){
      // update string position
      $po_rows = array_merge($po_rows, generate_po_entry($row));
    }
  }
  
  return implode($po_rows);
}

function scan_theme_files_store_results($string, $domain, $file, $line){
  global $rows, $domains;
  
  if($domain == NULL){
    $domain = "theme-localization";
  }
  
  $rows[] = array("string" => $string, "domain" => $domain, "file" => $file, "line" => $line, "msgid" => 'msgid "' . $string . '"');
  
  $domains[$domain] = $domain; // loopable and unique!
}

function scan_theme_files($dir = false, $first_run = true){
  if($first_run){
    global $rows, $domains;
    require "lib/potx.php";
    
    $rows = array();
    $domains = array();
    $dir = get_template_directory();
  }
  
  $dh = opendir($dir);
  
  while(false !== ($file = readdir($dh))){
    if($file=="." || $file=="..") continue;
    
    if(is_dir($dir . "/" . $file)){
      scan_theme_files($dir . "/" . $file, false);
    }elseif(preg_match('#\.php$#i', $file)){
      _potx_process_file($dir . "/" . $file, 0, 'scan_theme_files_store_results', '_potx_save_version', POTX_API_7);
    }
  }
  
  closedir($dh);
}

function generate_po_file($rows, $domain){
    global $wpdb;
    
    $po = "";
    $po .= '# Generated by wp-pomo' . PHP_EOL;
    $po .= 'msgid ""' . PHP_EOL;
    $po .= 'msgstr ""' . PHP_EOL;
    $po .= '"Content-Type: text/plain; charset=utf-8\n"' . PHP_EOL;
    $po .= '"Content-Transfer-Encoding: 8bit\n"' . PHP_EOL;
    $po .= '"Project-Id-Version: \n"' . PHP_EOL;
    $po .= '"POT-Creation-Date: \n"' . PHP_EOL;
    $po .= '"PO-Revision-Date: \n"' . PHP_EOL;
    $po .= '"Last-Translator: \n"' . PHP_EOL;
    $po .= '"Language-Team: \n"' . PHP_EOL;
    $po .= '"MIME-Version: 1.0\n"' . PHP_EOL;
    
    foreach($rows as $row){
      if($row["domain"] == $domain){
        $po .= implode("", generate_po_entry($row));
      }
    }
    
    return $po;
}

function generate_po_entry($row){
  $po_entry = array();
  
  $po_entry[] = PHP_EOL;
  
  $string_positions = get_po_string_position($row);
  
  $po_entry[] = $string_positions[0];
  $po_entry[] = $string_positions[1];
  
  $po_entry[] = $row["msgid"] . PHP_EOL;
  $po_entry[] = 'msgstr ""' . PHP_EOL;
  
  return $po_entry;
}

function get_po_string_position($row){
  $string_positions = array();
  
  $string_positions[] = '#: ' . $row["file"] . ":" . $row["line"] . PHP_EOL;
  $string_positions[] = '#, ' . PHP_EOL;
  
  return $string_positions;
}
