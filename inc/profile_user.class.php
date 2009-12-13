<?php
/*
 * @version $Id: profile.class.php 9663 2009-12-13 11:38:45Z yllen $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */


if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Profile_User class
class Profile_User extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_profiles_users';
   public $type = 'Profile_User';

   //TODO : right management

   /**
    * Show rights of a user
    *
    * @param $target where to go on action
    * @param $user object
    **/
   static function showForUser(User $user) {
      global $DB,$CFG_GLPI, $LANG;

      $ID = $user->getField('id');
      if (!$user->can($ID,'r')) {
         return false;
      }

      $canedit = $user->can($ID,'w');

      $strict_entities = getUserEntities($ID,false);
      if (!haveAccessToOneOfEntities($strict_entities) && !isViewAllEntities()) {
         $canedit = false;
      }

      $canshowentity = haveRight("entity","r");
      $rand=mt_rand();
      echo "<form name='entityuser_form$rand' id='entityuser_form$rand' method='post' action='";
      echo getItemTypeFormURL(__CLASS__)."'>";

      if ($canedit) {
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='4'>".$LANG['entity'][3]."</tr>";

         echo "<tr class='tab_bg_2'><td class='center'>";
         echo "<input type='hidden' name='users_id' value='$ID'>";
         Dropdown::dropdownValue("glpi_entities","entities_id",0,1,$_SESSION['glpiactiveentities']);
         echo "</td><td class='center'>".$LANG['profiles'][22]."&nbsp;: ";
         dropdownUnderProfiles("profiles_id");
         echo "</td><td class='center'>".$LANG['profiles'][28]."&nbsp;: ";
         Dropdown::showYesNo("is_recursive",0);
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value='".$LANG['buttons'][8]."' class='submit'>";
         echo "</td></tr>";

         echo "</table></div><br>";
      }

      echo "<div class='center'><table class='tab_cadre_fixehov'>";
      echo "<tr><th colspan='2'>".$LANG['Menu'][37]."</th>";
      echo "<th>".$LANG['profiles'][22]." (D=".$LANG['profiles'][29].", R=".$LANG['profiles'][28].")";
      echo "</th></tr>";

      $query = "SELECT DISTINCT `glpi_profiles_users`.`id` AS linkID, `glpi_profiles`.`id`,
                       `glpi_profiles`.`name`, `glpi_profiles_users`.`is_recursive`,
                       `glpi_profiles_users`.`is_dynamic`, `glpi_entities`.`completename`,
                       `glpi_profiles_users`.`entities_id`
                FROM `glpi_profiles_users`
                LEFT JOIN `glpi_profiles` ON (`glpi_profiles_users`.`profiles_id` = `glpi_profiles`.`id`)
                LEFT JOIN `glpi_entities` ON (`glpi_profiles_users`.`entities_id` = `glpi_entities`.`id`)
                WHERE `glpi_profiles_users`.`users_id` = '$ID'
                ORDER BY `glpi_profiles`.`name`, `glpi_entities`.`completename`";
      $result=$DB->query($query);

      if ($DB->numrows($result) >0) {
         while ($data = $DB->fetch_array($result)) {
            echo "<tr class='tab_bg_1'>";
            echo "<td width='10'>";
            if ($canedit && in_array($data["entities_id"], $_SESSION['glpiactiveentities'])) {
               echo "<input type='checkbox' name='item[".$data["linkID"]."]' value='1'>";
            } else {
               echo "&nbsp;";
            }
            echo "</td>";

            if ($data["entities_id"] == 0) {
               $data["completename"] = $LANG['entity'][2];
            }
            echo "<td>";
            if ($canshowentity) {
               echo "<a href='".getItemTypeFormURL('Entity')."?id=".$data["entities_id"]."'>";
            }
            echo $data["completename"].($_SESSION["glpiis_ids_visible"]?" (".$data["entities_id"].")":"");
            if ($canshowentity) {
               echo "</a>";
            }
            echo "</td>";
            echo "<td>".$data["name"];
            if ($data["is_dynamic"] || $data["is_recursive"]) {
               echo "<strong>&nbsp;(";
               if ($data["is_dynamic"]) {
                  echo "D";
               }
               if ($data["is_dynamic"] && $data["is_recursive"]) {
                  echo ", ";
               }
               if ($data["is_recursive"]) {
                  echo "R";
               }
               echo ")</strong>";
            }
            echo "</td>";
         }
         echo "</tr>";
      }
      echo "</table></div>";

      if ($canedit) {
         openArrowMassive("entityuser_form$rand",true);
         closeArrowMassive('delete', $LANG['buttons'][6]);
      }
      echo "</form>";
   }

   /**
    * Show users of an entity
    *
    * @param $target string : where to go on action
    * @param $ID integer : entity ID
    */
   static function showForEntity(Entity $entity) {
      global $DB,$CFG_GLPI, $LANG;


      $ID = $entity->getField('id');
      if (!$entity->can($ID,"r")) {
         return false;
      }

      $canedit = $entity->can($ID,"w");
      $canshowuser=haveRight("user","r");
      $nb_per_line=3;
      $rand=mt_rand();
      if ($canedit) {
         echo "<form name='entityuser_form$rand' id='entityuser_form$rand' method='post' action='";
         echo getItemTypeFormURL(__CLASS__)."'>";
         $headerspan=$nb_per_line*2;
      } else {
         $headerspan=$nb_per_line;
      }

      if ($canedit) {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='5'>".$LANG['setup'][603]."</tr>";
         echo "<tr><td class='tab_bg_2 center'>";
         echo "<input type='hidden' name='entities_id' value='$ID'>";
         User::dropdownAllUsers("users_id",0,1);
         echo "</td><td class='tab_bg_2 center'>";
         echo $LANG['profiles'][22]."&nbsp;:&nbsp;";
         dropdownUnderProfiles("profiles_id");
         echo "</td><td class='tab_bg_2 center'>";
         echo $LANG['profiles'][28]."&nbsp;:&nbsp;";
         Dropdown::showYesNo("is_recursive",0);
         echo "</td><td class='tab_bg_2 center'>";
         echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'>";
         echo "</td></tr>";
         echo "</table><br>";
      }
      echo "<table class='tab_cadre_fixehov'><tr><th colspan='$headerspan'>".
             $LANG['Menu'][14]." (D=".$LANG['profiles'][29].", R=".$LANG['profiles'][28].")</th></tr>";

      $query="SELECT DISTINCT `glpi_profiles`.`id`, `glpi_profiles`.`name`
              FROM `glpi_profiles_users`
              LEFT JOIN `glpi_profiles` ON (`glpi_profiles_users`.`profiles_id` = `glpi_profiles`.`id`)
              LEFT JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_profiles_users`.`users_id`)
              WHERE `glpi_profiles_users`.`entities_id`='$ID'
                    AND `glpi_users`.`is_deleted`='0'";

      $result=$DB->query($query);
      if ($DB->numrows($result)>0) {
         while ($data=$DB->fetch_array($result)) {
            echo "<tr><th colspan='$headerspan'>".$data["name"]."</th></tr>";

            $query="SELECT `glpi_users`.*, `glpi_profiles_users`.`id` AS linkID,
                           `glpi_profiles_users`.`is_recursive`, `glpi_profiles_users`.`is_dynamic`
                    FROM `glpi_profiles_users`
                    LEFT JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_profiles_users`.`users_id`)
                    WHERE `glpi_profiles_users`.`entities_id`='$ID'
                          AND `glpi_users`.`is_deleted`='0'
                          AND `glpi_profiles_users`.`profiles_id`='".$data['id']."'
                    ORDER BY `glpi_profiles_users`.`profiles_id`, `glpi_users`.`name`,
                             `glpi_users`.`realname`, `glpi_users`.`firstname`";
            $result2=$DB->query($query);
            if ($DB->numrows($result2)>0) {
               $i=0;
               while ($data2=$DB->fetch_array($result2)) {
                  if ($i%$nb_per_line==0) {
                     if ($i!=0) {
                        echo "</tr>";
                     }
                     echo "<tr class='tab_bg_1'>";
                  }
                  if ($canedit) {
                     echo "<td width='10'>";
                     echo "<input type='checkbox' name='item[".$data2["linkID"]."]' value='1'>";
                     echo "</td>";
                  }
                  echo "<td>";

                  echo formatUserName($data2["id"],$data2["name"],$data2["realname"],
                                      $data2["firstname"],$canshowuser);
                  if ($data2["is_dynamic"] || $data2["is_recursive"]) {
                     echo "<strong>&nbsp;(";
                     if ($data2["is_dynamic"]) {
                        echo "D";
                     }
                     if ($data2["is_dynamic"] && $data2["is_recursive"]) {
                        echo ", ";
                     }
                     if ($data2["is_recursive"]) {
                        echo "R";
                     }
                     echo ")</strong>";
                  }
                  echo "</td>";
                  $i++;
               }
               while ($i%$nb_per_line!=0) {
                  echo "<td>&nbsp;</td>";
                  if ($canedit) {
                     echo "<td>&nbsp;</td>";
                  }
                  $i++;
               }
               echo "</tr>";
            } else {
               echo "<tr colspan='$headerspan'>".$LANG['common'][54]."</tr>";
            }
         }
      }
      echo "</table>";

      if ($canedit) {
         openArrowMassive("entityuser_form$rand", true);
         closeArrowMassive('delete', $LANG['buttons'][6]);
         echo "</form>";
      }
   }

   /**
    * Show the User having a profile, in allowed Entity
    *
    * @param $prof object
    */
   static function showForProfile(Profile $prof) {
      global $DB,$LANG,$CFG_GLPI;

      $ID = $prof->fields['id'];
      $canedit=haveRight("user","w");
      if (!$prof->can($ID,'r')) {
         return false;
      }

      echo "<table class='tab_cadre_fixe'><tr>";
      echo "<th>".$LANG['profiles'][22]." :&nbsp;&nbsp;&nbsp;&nbsp;".$prof->fields["name"]."</th></tr>\n";
      echo "<tr><th colspan='2'>".$LANG['Menu'][14]." (D=".$LANG['profiles'][29].", R=".
                 $LANG['profiles'][28].")</th></tr>";
      echo "</table>\n";

      $query = "SELECT `glpi_users`.*, `glpi_profiles_users`.`entities_id` AS entity,
                     `glpi_profiles_users`.`id` AS linkID, `glpi_profiles_users`.`is_dynamic`,
                     `glpi_profiles_users`.`is_recursive`
                FROM `glpi_profiles_users`
                LEFT JOIN `glpi_entities` ON (`glpi_entities`.`id`=`glpi_profiles_users`.`entities_id`)
                LEFT JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_profiles_users`.`users_id`)
                WHERE `glpi_profiles_users`.`profiles_id` = '$ID'
                      AND `glpi_users`.`is_deleted` = '0' ".
                      getEntitiesRestrictRequest("AND","glpi_profiles_users")."
                ORDER BY `glpi_entities`.`completename`";

      echo "<table class='tab_cadre_fixe'>";

      $i=0;
      $nb_per_line=3;
      $rand=mt_rand(); // Just to avoid IDE warning

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)!=0) {
            $temp=-1;
            while ($data=$DB->fetch_array($result)) {
               if ($data["entity"]!=$temp) {
                  while ($i%$nb_per_line!=0) {
                     if ($canedit) {
                        echo "<td width='10'>&nbsp;</td>";
                     }
                     echo "<td class='tab_bg_1'>&nbsp;</td>\n";
                     $i++;
                  }
                  if ($i!=0) {
                     echo "</table>";
                     if ($canedit) {
                        openArrowMassive("profileuser_form".$rand."_$temp", true);
                        Dropdown::dropdownValue("glpi_entities","entities_id",0,1,$_SESSION['glpiactiveentities']);
                        echo "&nbsp;<input type='submit' name='moveentity' value=\"".
                              $LANG['buttons'][20]."\" class='submit'>&nbsp;";
                        closeArrowMassive('delete', $LANG['buttons'][6]);
                     }
                     echo "</div></form></td></tr>\n";
                  }

                  // New entity
                  $i=0;
                  $temp=$data["entity"];
                  $rand=mt_rand();
                  echo "<tr class='tab_bg_2'>";
                  echo "<td class='left'>";
                  echo "<a href=\"javascript:showHideDiv('entity$temp$rand','imgcat$temp', '".
                         GLPI_ROOT."/pics/folder.png','".GLPI_ROOT."/pics/folder-open.png');\">";
                  echo "<img alt='' name='imgcat$temp' src=\"".GLPI_ROOT."/pics/folder.png\">&nbsp;";
                  echo "<strong>".Dropdown::getDropdownName('glpi_entities',$data["entity"])."</strong>";
                  echo "</a>";
                  echo "</td></tr>\n";

                  echo "<tr><td>";
                  echo "<form name='profileuser_form".$rand."_$temp' id='profileuser_form".$rand.
                         "_$temp' method='post' action='".getItemTypeFormURL(__CLASS__)."'>";
                  echo "<div class='center' id='entity$temp$rand' style='display:none;'>\n";
                  echo "<table class='tab_cadre_fixe'>\n";
               }
               if ($i%$nb_per_line==0) {
                  if ($i!=0) {
                     echo "</tr>\n";
                  }
                  echo "<tr class='tab_bg_1'>\n";
                  $i=0;
               }
               if ($canedit) {
                  echo "<td width='10'>";
                  $sel="";
                  if (isset($_GET["select"]) && $_GET["select"]=="all") {
                     $sel="checked";
                  }
                  echo "<input type='checkbox' name='item[".$data["linkID"]."]' value='1' $sel>";
                  echo "</td>";
               }
               echo "<td class='tab_bg_1'>".formatUserName($data["id"],$data["name"],$data["realname"],
                                                           $data["firstname"],1);

               if ($data["is_dynamic"] || $data["is_recursive"]) {
                  echo "<strong>&nbsp;(";
                  if ($data["is_dynamic"]) {
                     echo "D";
                  }
                  if ($data["is_dynamic"] && $data["is_recursive"]) {
                     echo ", ";
                  }
                  if ($data["is_recursive"]) {
                     echo "R";
                  }
                  echo ")</strong>";
               }
               echo "</td>\n";
               $i++;
            }
            if ($i%$nb_per_line!=0) {
               while ($i%$nb_per_line!=0) {
                  if ($canedit) {
                     echo "<td width='10'>&nbsp;</td>";
                  }
                  echo "<td class='tab_bg_1'>------</td>";
                  $i++;
               }
            }
            if ($i!=0) {
               echo "</table>\n";
               if ($canedit) {
                  openArrowMassive("profileuser_form".$rand."_$temp", true);
                  Dropdown::dropdownValue("glpi_entities","entities_id",0,1,$_SESSION['glpiactiveentities']);
                  echo "&nbsp;<input type='submit' name='moveentity' value=\"".
                               $LANG['buttons'][20]."\" class='submit'>&nbsp;";
                  closeArrowMassive('delete', $LANG['buttons'][6]);
               }
               echo "</div></form></td></tr>\n";
            }
         } else {
            echo "<tr><td class='tab_bg_1 center'>".$LANG['profiles'][33]."</td></tr>\n";
         }
      }
      echo "</table>\n";
   }
}
?>
