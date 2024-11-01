<?php



class htmlSitemapItem {
  public $isCategory = false;
  public $name = '';
  public $ID = '';
  public $parentId = null;
  public $isCustom = false;
  public $url = '';
  public $bOpenInNewTab = false;
  public $post_date = null;

  function __construct($name, $isCategory = false, $id = '', $parentId = '', $isCustom = false, $object = null, $post_date = null) {
    $this->name = $name;
    $this->isCategory = $isCategory;
    $this->ID = $id;
    $this->parentId = $parentId;
    $this->isCustom = $isCustom;
    if ($isCustom && $object) {
      $object = (array)$object;
      $this->url = $object['url'];
      $this->bOpenInNewTab = $object['new_tab'];
    }
    $this->post_date = $post_date;
  }

  function splittedTest($prevPage, $ID, &$testResult) {
    $prevPage = (array)$prevPage;
    for($i = 0; $i < count($prevPage); $i++) {
      $item = $prevPage[$i];
      $item = (array)$item;
      
      if ($item['ID'] == $ID) {
        $testResult = true;
        return;
      }

      if ($item['children']) {
        $this->splittedTest($item['children'], $ID, $testResult);
      }
    }
  }

  function linedTest($prevPage, $ID) {
    $prevPage = (array)$prevPage;
    foreach($prevPage as $item) {
      $item = (array)$item;
      if ($item['ID'] == $ID) {
        return true;
      }
    }
    return false;
  }

  function continued($sitemap_instance) {
    $prevPage = $sitemap_instance->previousPage;
    if ($prevPage) {
      if ($sitemap_instance->style !== 'merge') {
        $testResult = false;
        $this->splittedTest($prevPage, $this->ID, $testResult);
        if ($testResult == true) {

          $continued_string = __('continued', 'sitemap-by-click5');
          return ' ('.$continued_string.')';
        } else {
          return '';
        }
      } else {
        return '';
      }
      
    } else {
      return '';
    }    
  }

  function display($sitemap_instance, $onlyTag = false, $displayCategories = true) {
    if ($this->isCategory && !$displayCategories) {
      return '';
    }
    else if ($this->isCategory && $displayCategories) {
      return '<h2>'.$this->name.$this->continued($sitemap_instance).'</h2>';
    } else {
      if (!$this->isCustom) {
        $bOpenInNewTab = intval(esc_attr( get_option('click5_sitemap_url_target_blanc') )) == 1;
        if (!$bOpenInNewTab) {
          if ($onlyTag) {
            
            if (strpos($this->ID, '_tax') !== false) {
              return '<a href="'.get_term_link((int)$this->ID).'">'.$this->name.$this->continued($sitemap_instance).'</a>';
            } else {
              return '<a href="'.get_permalink($this->ID).'">'.$this->name.$this->continued($sitemap_instance).'</a>';
            }

          } else {
            return '<li><a href="'.get_permalink($this->ID).'">'.$this->name.$this->continued($sitemap_instance).'</a></li>';
          }
        } else {
          if ($onlyTag) {

            if (strpos($this->ID, '_tax') !== false) {
              return '<a href="'.get_term_link((int)$this->ID).'" target="_blank" rel="nofollow">'.$this->name.$this->continued($sitemap_instance).'</a>';
            } else {
              return '<a href="'.get_permalink($this->ID).'" target="_blank" rel="nofollow">'.$this->name.$this->continued($sitemap_instance).'</a>';
            }
            
          } else {
            return '<li><a href="'.get_permalink($this->ID).'" target="_blank" rel="nofollow">'.$this->name.$this->continued($sitemap_instance).'</a></li>';
          }
        }
      } else {
        if ($this->bOpenInNewTab || intval(esc_attr( get_option('click5_sitemap_url_target_blanc') )) == 1) {
          if ($onlyTag) {
            return '<a href="'.$this->url.'" target="_blank" rel="nofollow">'.$this->name.$this->continued($sitemap_instance).'</a>';
          } else {
            return '<li><a href="'.$this->url.'" target="_blank" rel="nofollow">'.$this->name.$this->continued($sitemap_instance).'</a></li>';
          }
        } else {
          if ($onlyTag) {
            return '<a href="'.$this->url.'">'.$this->name.$this->continued($sitemap_instance).'</a>';
          } else {
            return '<li><a href="'.$this->url.'">'.$this->name.$this->continued($sitemap_instance).'</a></li>';
          }
        }
      }
    }
  }
}

class HTMLSitemapList {
  public $items = array();
  public $itemsDisplayArray = array();
  public $itemsFinalArray = array();
  public $inited = false;
  public $paged = 1;
  public $posts_per_page = 10;
  public $style = 'group';
  public $paginationMaxPage = -1;
  public $previousPage = null;
  public $previousPageCategories = null;
  public $previousPageNoTerm = null;
  public $displayTopLevel = true;
  public $groupingType = '';

  function populateChildren($item) {
    $post_children = get_children(array('post_parent' => $item->ID, 'post_status' => 'publish', 'numberposts' => -1));

    foreach($post_children as $child) {
      $this->items[] = new htmlSitemapItem($child->post_title, false, $child->ID, $item->ID, false);
      $this->populateChildren($child);
    }
  }

  function __construct() {
    $this->paginationMaxPage = -1;
    $this->posts_per_page = intval(esc_attr( get_option('click5_sitemap_html_pagination_items_per_page') ));
    if($this->posts_per_page == 0) {
      $this->posts_per_page = 100;
    }
    $this->groupingType = esc_attr(get_option('click5_sitemap_html_blog_group_by'));

    $post_types = click5_sitemap_get_post_types();
    $enabledTypesArray = array();
    $TaxenabledTypesOriginalArray = array();
    $TaxenabledTypesArray = array();

    foreach($post_types as $single_type) {
      $single_type = get_post_type_object($single_type);
      $option_name = 'click5_sitemap_display_'.$single_type->name;

      if (boolval(esc_attr( get_option($option_name) ))) {
        $enabledTypesArray[] = $single_type->name;
      }
    }

    if (boolval(esc_attr( get_option('click5_sitemap_display_cat_tax') ))) {
      $TaxenabledTypesOriginalArray[] = 'categories';
    }
  
    if (boolval(esc_attr( get_option('click5_sitemap_display_tag_tax') ))) {
      $TaxenabledTypesOriginalArray[] = 'tags';
    }

    if (boolval(esc_attr( get_option('click5_sitemap_display_authors_tax') ))) {
      $TaxenabledTypesOriginalArray[] = 'authors';
    }

    $tax_args=array(
      'public'   => true,
      '_builtin' => false
    );
    $output = 'names'; // or objects
    $operator = 'and';
    $taxonomies=get_taxonomies($tax_args,$output,$operator);
  
    if  ($taxonomies) {
      foreach ($taxonomies  as $taxitem ) {
  
        $tax_option_name = 'click5_sitemap_display_'.$taxitem;
  
        if (boolval(esc_attr( get_option($tax_option_name) ))) {
          $TaxenabledTypesArray[] = $taxitem;
        }
       }  
    }

    $_style = esc_attr( get_option('click5_sitemap_display_style') );

    if (empty($_style)) {
      $_style = 'group';
    }

    $this->style = $_style;

    if ($_style === 'group') {
      foreach($enabledTypesArray as $type) {
        $posts = get_posts(array('post_parent' => 0, 'post_type' => $type, 'post_status' => 'publish', 'numberposts' => -1));
        $label = '';
        if (intval(esc_attr(get_option('click5_sitemap_use_custom_name_'.$type)))) {
          $label = esc_attr(get_option('click5_sitemap_custom_name_text_'.$type));
        } else {
          $label = get_post_type_object($type)->label;
        }

        $this->items[] = new htmlSitemapItem($label, true, $type, null, false);

        foreach($posts as $post) {
          $this->items[] = new htmlSitemapItem($post->post_title, false, $post->ID, $type, false);
          if(isset($post)) {  
            $this->populateChildren($post);
          }
          
        }

        $customPosts = click5_sitemap_getCustomUrlsHTML($type);

        foreach($customPosts as $custom_post) {
          if ($custom_post->enabledHTML) {
            $this->items[] = new htmlSitemapItem($custom_post->title, false, $custom_post->ID, $type, true, $custom_post,$custom_post->last_mod);
          }
        }
      }


      foreach($TaxenabledTypesOriginalArray as $type) {

        if($type == 'categories'){
  
          $short_type = 'cat';
          $tax_items = get_categories();
  
        } elseif($type == 'tags'){
  
          $short_type = 'tag';
          $tax_items = get_tags();
          
        } elseif($type == 'authors'){

          $short_type = 'authors';
          $tax_items = get_users();        
        }

        $label = '';
        if (intval(esc_attr(get_option('click5_sitemap_use_custom_name_'.$short_type.'_tax')))) {
          $label = esc_attr(get_option('click5_sitemap_custom_name_text_'.$short_type.'_tax'));
        } else {
          $label = ucwords($type);
        }

        $this->items[] = new htmlSitemapItem($label, true, $type.'_tax', null, false);

        foreach($tax_items as $term) {
          if($short_type == 'authors') {
            if ( count_user_posts( $term->ID, $post_types ) >= 1 ) { 
              global $global_list;
              array_push($global_list, $term->ID);  
              
              
                $this->items[] = new htmlSitemapItem($term->data->user_login, false, $term->ID.'_'.$short_type.'_tax', $type.'_tax', false);
                if(isset($post)) { 
                $this->populateChildren($post);
              }
            }
          } else {
            
              $this->items[] = new htmlSitemapItem($term->name, false, $term->term_id.'_'.$short_type.'_tax', $type.'_tax', false);
            if(isset($post)) { 
              $this->populateChildren($post);
            }
          }
          
        }

      }



      foreach($TaxenabledTypesArray as $type) {
        $tax_items = get_terms($type);

        $original_name = get_taxonomy($type);

        $label = '';
        if (intval(esc_attr(get_option('click5_sitemap_use_custom_name_'.$type)))) {
          $label = esc_attr(get_option('click5_sitemap_custom_name_text_'.$type));
        } else {
          $label = ucwords($original_name->label);
        }

        $this->items[] = new htmlSitemapItem($label, true, $type, null, false);

        foreach($tax_items as $term) {
          $this->items[] = new htmlSitemapItem($term->name, false, $term->term_id.'_tax', $type, false);
          if(isset($post)) {  
            $this->populateChildren($post);
          }
          
        }

      }


      $customCategories = click5_sitemap_getCustomCategoriesCustomUrlsHTML();
      sort($customCategories);

      foreach($customCategories as $category) {
        $this->items[] = new htmlSitemapItem($category, true, $category, null, true);
        $customPosts = click5_sitemap_getCustomUrlsHTML($category);
        foreach($customPosts as $custom_post) {
          $this->items[] = new htmlSitemapItem($custom_post->title, false, $custom_post->ID, $category, true, $custom_post, $custom_post->last_mod);
        }
      }

    } else if ($_style === 'merge') {
      $posts = get_posts(array('post_type' => $enabledTypesArray, 'post_status' => 'publish', 'numberposts' => -1));
      foreach($posts as $post) {
        $this->items[] = new htmlSitemapItem($post->post_title, false, $post->ID, null, false);
      }

      $customUrls = click5_sitemap_getCustomUrlsHTML();
      foreach($customUrls as $custom_url) {
        if ($custom_url->enabledHTML) {
          $this->items[] = new htmlSitemapItem($custom_url->title, false, $custom_url->ID, null, true, $custom_url,$custom_url->last_mod);
        }
      }
    }

  }

  function set_paged($i) {
    $this->paged = $i;
  }

  function setupOrderItem(&$orderItem) {
    $orderItem['children'] = array();
    $count_items = strlen(get_option('click5_sitemap_order_list2'));
    $_indx = 0;
    foreach($this->items as $item) {
      if ($item->ID == $orderItem['ID']) {
        global $shortcode_tags;
        $result_shortcode = do_shortcode($item->name);
        $item->name =  $result_shortcode;
        $orderItem['itemObject'] = $item;
        $orderItem['itemObjectIndx'] = $_indx;
        if($count_items > 5) {
          break;
        }
        
      }
      $_indx++;
    }
  }

  function splittedTest($prevPage, $ID, &$testResult) {
    $prevPage = (array)$prevPage;
    for($i = 0; $i < count($prevPage); $i++) {
      $item = $prevPage[$i];
      $item = (array)$item;
      
      if ($item['ID'] == $ID) {
        $testResult = true;
        return;
      }

      if ($item['children']) {
        $this->splittedTest($item['children'], $ID, $testResult);
      }
    }
  }

  function feedWithChildren(&$toplevelNode, $orderList) {
    $toplevelNode = (array)$toplevelNode;
    $count_items = strlen(get_option('click5_sitemap_order_list2'));
    foreach($orderList as $orderItem) {
      $orderItem = (array)$orderItem;

      $oI = isset($orderItem['parent']) ? $orderItem['parent'] : '';

      if($toplevelNode['ID'] !== $oI) {
        continue;
      }

      $this->setupOrderItem($orderItem);
      if($count_items < 5) {
        $this->feedWithChildren($orderItem, $orderList);
      }
      

      $toplevelNode['children'][] = $orderItem;
    }
  }

  function setupPaginationMerged(&$arr) {
    if ($this->posts_per_page == 0) {
      $this->paginationMaxPage = -1;
      return;
    }

    $offset = ($this->paged - 1) * $this->posts_per_page;
    $start = max($offset, 0);
    $end = $start + $this->posts_per_page;

    $this->paginationMaxPage = ceil(count($arr) / $this->posts_per_page);

    $arr_copy = $arr;
    $arr = array_slice($arr, $start, $this->posts_per_page, false);

    if ($this->paged > 1) {
      $prev_offset = ($this->paged - 2) * $this->posts_per_page;
      $prev_start = max($prev_offset, 0);
      $this->previousPage = array_slice($arr_copy, $prev_start, $this->posts_per_page, false);
    }
  }

  function getCountArrayRecursive($arr, &$size = 0) {
    $arr = (array)$arr;
    $size += count($arr);
    foreach($arr as $item) {
      $item = (array)$item;
      if ($item['children']) {
        $this->getCountArrayRecursive($item['children'], $size);
      }
    }
    return $size;
  }

  function iterateArrayRecursive($arr, $start, $end, &$output, &$it = 0) {
    $arr = (array)$arr;

    $size = '';
    foreach($arr as $item) {
      $item = (array)$item;
      if(count($output)+1 >= $end){
        if(strpos($item['ID'],"_") !== false)
          $it++;
      }
      //here is the place to add
      if ($start <= $it && $it < $end) {
        $itemCopy = $item;
        unset($itemCopy['children']);
   
        $output[] = $itemCopy;
      }

     
      $it += 1;
      if ($item['children']) {
        $this->iterateArrayRecursive($item['children'], $start, $end, $output, $it);
      }
      
    }
    
    return $size;
  }

  function flatArrayTo($source, &$output) {
    $source = (array)$source;
    
    foreach($source as $item) {
      $item = (array)$item;
      $itemCopy = $item;
      unset($itemCopy['children']);
      $output[] = $itemCopy;

      if ($item['children']) {
        $this->flatArrayTo($item['children'], $output);
      }
    }
  }

  function getRootParentID($item, $array, &$arrayInbetween) {
    $item = (array)$item;
    if (!isset($item['parent'])) {
      //return $item['ID'];
    } else {
      $parentId = $item['parent'];
      foreach($array as $compare_as_potential_parent) {
        if ($compare_as_potential_parent['ID'] == $parentId) {
          $arrayInbetween[] = $parentId;
          $this->getRootParentID($compare_as_potential_parent, $array, $arrayInbetween);
        }
      }
    }
  }

  function getItemByID($ID, $array) {
    foreach($array as $item) {
      $item = (array)$item;
      if ($item['ID'] == $ID) {
        return $item;
      }
    }
    return null;
  }

  function reassignToParent($pageItems, $copyTopLevel, $arrayChildren) {
    $flatSourceArray = array();

    $this->flatArrayTo($copyTopLevel, $flatSourceArray);
    $this->flatArrayTo($arrayChildren, $flatSourceArray);

    $finalArray = array();
    $rootsArray = array();
    $parentArrays = array();

    foreach($pageItems as $pageItem) {
      $parentArray = array();
      $this->getRootParentID($pageItem, $flatSourceArray, $parentArray);
      $parentArrays[$pageItem['ID']] = $parentArray;

      foreach(array_reverse($parentArray) as $parent) {
        $finalArray[] = $parent;
      }
      $finalArray[] = $pageItem['ID'];
    }

    $finalArray = array_unique($finalArray);

    return $finalArray;
  }

  function prepareArrayPages(&$arrayPages, $copyTopLevel, $arrayChildren, $currentPage, $itemsPerPage, $maxPage) {
    $arrayPages = array();
    for($i = 0; $i <= $maxPage; $i++) {
      $arrayPages[] = array();
    }

    for($i = 0 ; $i < count($arrayPages); $i++) {
      $arrayPage = array();
      $start = $i * $itemsPerPage;
      $end = $start + $itemsPerPage;
      $it = 1;
      if(count($arrayPages[$i]) < $itemsPerPage){
        $it = 0;
      }
        
      $this->iterateArrayRecursive($arrayChildren, $start, $end, $arrayPage, $it);
      $arrayPages[$i] = $arrayPage;
    }

    
    return $this->reassignToParent($arrayPages[$currentPage], $copyTopLevel, $arrayChildren);
  }

  function dropNotIncluded($arr, $include, &$new_arr) {
    foreach($arr as $item_arr) {
      $item_arr = (array)$item_arr;
      if(!in_array($item_arr['ID'], $include)) {
        continue;
      }
      
      if ($item_arr['children']) {
        $newChildren = array();
        $this->dropNotIncluded($item_arr['children'], $include, $newChildren);
        $item_arr['children'] = $newChildren;
      }

      $new_arr[] = $item_arr;
    }
  }

  function setupPaginationSplitted(&$arr, $copyTopLevel, $arrayChildren) {
    if ($this->posts_per_page == 0) {
      $this->paginationMaxPage = -1;
      return;
    }

    $_childrenSizeBuff = 0;
    $itemsCount = $this->getCountArrayRecursive($arrayChildren, $_childrenSizeBuff);
    $this->paginationMaxPage = ceil($itemsCount / $this->posts_per_page);

    $currentPage = max($this->paged - 1, 0);
    $itemsPerPage = $this->posts_per_page;
    $maxPage = max(($this->paginationMaxPage - 1), 0);

    $arrayPages = array();
    $allowedIDs = $this->prepareArrayPages($arrayPages, $copyTopLevel, $arrayChildren, $currentPage, $itemsPerPage, $maxPage);
    if(count($arrayPages[count($arrayPages)-1]) == 0 && $this->paginationMaxPage > 0)
      $this->paginationMaxPage--;
    $new_arr = array();
    $arr_copy = $arr;
    $this->dropNotIncluded($arr, $allowedIDs, $new_arr);
    $arr = $new_arr;

    if ($currentPage > 0) {
      $prev_arrayPages = array();
      $prev_allowedIDs = $this->prepareArrayPages($prev_arrayPages, $copyTopLevel, $arrayChildren, ($currentPage - 1), $itemsPerPage, $maxPage);
      $prev_new_arr = array();
      $this->dropNotIncluded($arr_copy, $prev_allowedIDs, $prev_new_arr);
      $this->previousPage = $prev_new_arr;
    }
  }

  function setupDisplayOrder() {
    $s1 = get_option('click5_sitemap_order_list');
    $s2 = get_option('click5_sitemap_order_list2');
    $s3 = get_option('click5_sitemap_order_list3');
    $s4 = get_option('click5_sitemap_order_list4');

    $total  = $s1 . $s2 . $s3 . $s4;
    $orderList = (array) json_decode($total);

    if ($this->style == 'group') {
      //first add categories / or pages without parent
      foreach($orderList as $orderItem) {
        $orderItem = (array)$orderItem;

        if(isset($orderItem['parent'])) {
          continue;
        }

        if ($orderItem['is_category'] !== true) {
          continue;
        }

        $this->setupOrderItem($orderItem);
        $this->itemsDisplayArray[] = $orderItem;
      }

      //setup children
      $copyTopLevel = array();
      $arrayChildren = array();
      foreach($this->itemsDisplayArray as &$toplevelNode) {
        $copyTopLevel[] = $toplevelNode;
        $this->feedWithChildren($toplevelNode, $orderList);

        foreach($toplevelNode['children'] as $childNode) {
          $blacklistArray = json_decode(get_option('click5_sitemap_blacklisted_array'));
          $blacklist = array_column($blacklistArray ? $blacklistArray : array(), 'ID');
          $childID = 0;
          if(isset($childNode['parent'])){
            if($childNode['parent'] == "authors_tax"){
              $childID = "7777".intval($childNode['ID']);
            }else if($childNode['parent'] == "categories_tax"){
              $childID = "9999".intval($childNode['ID']);
            }else if($childNode['parent'] == "tag_tax"){
              $childID = "8888".intval($childNode['ID']);
            }else{
              $childID = intval($childNode['ID']);
            }
          }
          if(!in_array($childID,$blacklist))
            $arrayChildren[] = $childNode;
        }
      }

      global $g_arrayChildren;
      $g_arrayChildren = $arrayChildren;

      $this->displayTopLevel = count($copyTopLevel) > 0;

      $this->setupPaginationSplitted($this->itemsDisplayArray, $copyTopLevel, $arrayChildren);

    } else {
      usort($orderList, function($a, $b) {return $a->order - $b->order;});

      foreach($orderList as $orderItem) {
        if ($orderItem->is_category) {
          continue;
        }

        $orderItem = (array)$orderItem;

        $_indx = 0;
        foreach($this->items as $item) {
          if ($item->ID == $orderItem['ID']) {
            $orderItem['itemObject'] = $item;
            $orderItem['itemObjectIndx'] = $_indx;
          }
          $_indx++;
        }

        $this->itemsDisplayArray[] = $orderItem;
      }

      $this->setupPaginationMerged($this->itemsDisplayArray);
    }
  }

  function filterOutBlacklisted() {
    $blacklistArray = json_decode(get_option('click5_sitemap_blacklisted_array'));
    $blacklist = array_column($blacklistArray ? $blacklistArray : array(), 'ID');
    $categories = get_categories();
    $users = get_users();
    $tag_list_array = get_tags();
    $buffer = $this->items;
    $this->items = array();
    foreach($buffer as $item) {
      if(strpos($item->parentId, 'categories') !== false) {
        foreach($categories as $cat_item) {
          if($cat_item->name == $item->name) {
            $cat_id = "9999" . $cat_item->cat_ID;
            if (in_array($cat_id, $blacklist)) {
              continue;
            }
            $this->items[] = $item;
          }
        } 

      } else if(strpos($item->parentId, 'authors') !== false) {
        foreach($users as $user_item) {
          if($user_item->data->display_name == $item->name) {
            $auth_id = "8888" . $user_item->ID;
            if (in_array($auth_id, $blacklist)) {
              continue;
            }
            $this->items[] = $item;
          }
        } 

      } else if(strpos($item->parentId, 'tags') !== false) {
        foreach($tag_list_array as $tag_item) {
          if($tag_item->name == $item->name) {
            $tag_id = "7777" . $tag_item->term_id;
            if (in_array($tag_id, $blacklist)) {
              continue;
            }
            $this->items[] = $item;
          }
        } 

      } else {
        $cpt_args = array(
          'public'   => true,
          '_builtin' => false,
        );
        $cpt_output = 'names';
        $cpt_operator = 'and';
        $cpt_types = get_taxonomies( $cpt_args, $cpt_output, $cpt_operator ); 
        $taxonomies_array = array();
        foreach($cpt_types as $cpt_item) {
          array_push($taxonomies_array, $cpt_item);
        }

        if(in_array($item->parentId, $taxonomies_array)) {
          $id_taxonomy = explode("_", $item->ID);
          if (in_array("5555" . $id_taxonomy[0], $blacklist)) {
            continue;
          }
          $this->items[] = $item;
        } else {
          if (in_array($item->ID, $blacklist)) {
            continue;
          }
          $this->items[] = $item;
        }
      }
    }
  }

  function displayGroup($item, $postType) {
    $url = '';
    $prependTitle = '';

    if ($this->groupingType == 'archives') {
      $prependTitle = '<strong>Archive:</strong>&nbsp;';
    } else if ($this->groupingType == 'tags') {
      $prependTitle = '<strong>Tag:</strong>&nbsp;';
    } else if ($this->groupingType == 'categories') {
      $prependTitle = '<strong>Category:</strong>&nbsp;';
    }

    $mainName = '';

    $result = '';

    $item = (array)$item;
    $_buffer = explode('__', $item['ID']);
    $term_id = $_buffer[1];
    if ($this->groupingType != 'archives') {

      $cat_term = 'category';
      $tag_term = 'post_tag';

      if ( class_exists( 'WooCommerce' ) ) {

        if(strpos($_buffer[0], 'product') !== false){
          $cat_term = 'product_cat';
          $tag_term = 'product_tag';
        }
        
      }

      $termObj = get_term($term_id);
      if ($termObj) {
        $mainName = $termObj->name;
        $url = get_term_link(intval($term_id), $this->groupingType == 'tags' ? $tag_term : $cat_term);
        
        if (!empty($url) && !is_wp_error($url)) {
          $result = $prependTitle.'<span><a href="'.$url.'" title="'.$mainName.'">'.$mainName.'</a></span>';
        } else {
          $result = $prependTitle.'<span>'.$mainName.'</span>';
        }
      }
    } else {
      //TODO
      //print_r($item);
      $_buffer = explode('__', $item['ID']);
      $date = strtotime('01-'.$_buffer[1]);
      $mainName = date("F Y", $date);
      
     
      $url = $this->getArchiveURL($_buffer[1], $postType);


      if (!empty($url) && !is_wp_error($url)) {
        $result = $prependTitle.'<span><a href="'.$url.'" title="'.$mainName.'">'.$mainName.'</a></span>';
      } else {
        $result = $prependTitle.'<span>'.$mainName.'</span>';
      }
    }

    
      $prevPage = $this->previousPage;
      if ($prevPage) {
        if ($this->style !== 'merge') {
          $testResult = false;
          $this->splittedTest($prevPage, $item['ID'], $testResult);
          if ($testResult == true) {
            $result .= ' (continued)';
          }
        }
      }

    return $result;
  }


  function displaySubchilds(&$html, $item, $postType = null) {
    global $global_list;
    $item = (array)$item;
    $item['children'] = (array)$item['children'];
    if (count($item['children'])) {

          $column_option = esc_attr(get_option('click5_sitemap_display_columns'));

          $columnMode = false;
          $columns = 1;
          $subpages = false;
          $add_subpages_class = '';
          $parentStart = ''; 
          $parentEnd = ''; 

          if(!empty($column_option) && $column_option != '1'){
            $columnMode = true;
            $columns = $column_option;
          }
          
          if($columnMode){
            foreach($item['children'] as $subchild) {
              $subchild = (array)$subchild;
              if (isset($subchild['itemObject'])) {
                if(is_int($subchild['itemObject']->parentId)){
                  $subpages = true;
                  $add_subpages_class = 'child';
                  $parentStart = '<ul>'; 
                  $parentEnd = '</ul>'; 
                }
              } 
            }
          }

          $html .= $subpages ? $parentStart : '<ul class="sub c5_col'.$columns.'">';
          $marker = 1;
            foreach($item['children'] as $subchild) {
              $subchild = (array)$subchild;
              if (function_exists('str_contains'))
              {
                $check_str = str_contains($subchild["ID"], "g_");
              }
              else
              {
                if (strpos($subchild["ID"], "g_") !== false) {   
                  $check_str = true;
                } else {
                  $check_str = false;
                }
              }
              if( $subchild["parent"] == "authors_tax") {
                $id_li = "c5_sitemap_author_" . $marker;
              }  else if( $subchild["parent"] == "categories_tax") {
                $cat_string = explode('_',  $subchild["ID"]);
                $id_li = "c5_sitemap_cat_" . $cat_string[0];
              } else if( $subchild["parent"] == "tags_tax") {
                $tag_string = explode('_',  $subchild["ID"]);
                $id_li = "c5_sitemap_tag_" . $tag_string[0];
              } else if($check_str) {
                $cat_string = explode('__',  $subchild["ID"]);
                $id_li = "c5_sitemap_group_cat_" . $cat_string[1];
              } else { 
                $id_li = "c5_sitemap_" . $subchild["ID"];
              }
              if (isset($subchild['itemObject'])) {

                $html .= '<li id=' . $id_li . ' class="c5_child '.$add_subpages_class.'">'.$subchild['itemObject']->display($this, true);
                  $this->displaySubchilds($html, $subchild, $postType);
                $html .= '</li>';


              } else if(strpos($subchild['ID'], 'g_') !== false) {
                if ($subchild['children']) {
                  if (count($subchild['children'])) {
                    $html .= '<li id=' . $id_li . ' class="c5_parent">'.$this->displayGroup($subchild, $postType);
                    $this->displaySubchilds($html, $subchild, $postType);
                    $html .= '</li>';
                  }
                }
              } else if($postType == "authors_tax") {
                $url_link = get_author_posts_url($global_list[0]);
                $data_user = get_userdata($global_list[0]);
                $blacklistArray = json_decode(get_option('click5_sitemap_blacklisted_array'));
                $blacklist = array_column($blacklistArray ? $blacklistArray : array(), 'ID');
                if (!in_array("8888" . $data_user->ID, $blacklist)) { 
                  $html .= '<li id=' . $id_li . ' class="c5_child '.$add_subpages_class.'">';
                  $html .= '<a href="'.$url_link.'">'.$data_user->data->user_login.'</a>';
                  $html .= '</li>';
                }
                array_shift($global_list);
              }
              if( $subchild["parent"] == "authors_tax") {
                $marker++;
              }
            }
          $html .= $subpages ? $parentEnd : '</ul>';
    }
  }


  function getArchiveURL($categoryID, $postTypeName) {
    $categoryMonthYearArray = explode('-', $categoryID);
    if (count($categoryMonthYearArray) == 2) {
      return '/?post_type='.$postTypeName.'?year='.$categoryMonthYearArray[1].'&monthnum='.$categoryMonthYearArray[0];
    } else {
      return '';
    }
  }

  function display() {
    $this->filterOutBlacklisted();
    $this->setupDisplayOrder();

    $counter = 0;

    $style = $this->style;

    $html = '';

    $html .= '<div class="sitemap_by_click5 v'.str_replace('.', '', CLICK5_SITEMAP_VERSION).' ddsg-wrapper" id="c5_sitemap_wrapper">';

    if($style == 'merge') {
      $html .= '<ul class="main_list">';
    }

    $displayRecords = array();
    $finalPageRecords = array();

    $finalPageRecords = $this->itemsDisplayArray;


    $groupingType = esc_attr(get_option('click5_sitemap_html_blog_group_by'));

    $finalPageRecords = click5_sort_array($finalPageRecords,esc_attr( get_option('click5_sitemap_html_blog_sort_by')),$style,esc_attr( get_option('click5_sitemap_html_blog_order_by')));
    if ($style !== 'merge') {
    foreach($finalPageRecords as $item) {
        if(isset($item['itemObject'])){
          $html .= $item['itemObject']->display($this, false, $this->displayTopLevel);
          $this->displaySubchilds($html, $item, $item['ID']);
        }
        
      }
    } else {
      foreach($finalPageRecords as $item) {
        if(isset($item['itemObject'])){
          $html .= $item['itemObject']->display($this, false, $this->displayTopLevel);
        }
      }
    }

    if($style == 'merge') {
      $html .= '</ul>';
    }

    $html .= '</div>';

    $maxPage = $this->paginationMaxPage;

    if ($maxPage > 1 && $this->posts_per_page > 0) {
      if(get_option('permalink_structure')) { 
        $format_first = "/page/1";
      } else {
        global $post;
        $format_first = "/?page_id=".$post->ID."/page=1";
      }
      //display pagination
      global $wp;
      $cur_url = home_url( $wp->request );
      $cur_url = preg_replace('/\/page\/\d+/', '', $cur_url);
      $html .= '<div class="sitemap-by-click5_pagination pagination">';
      $html .= '<div class="nav">';
      if ($this->paged > 1) {
        if(get_option('permalink_structure')) { 
          $format = "/page/";
        } else {
          global $post;
          $format = "/?page_id=".$post->ID."/page=";
        }
        $html .= '<a class="pagination-item" href="'.$cur_url.$format_first.'"> « '.__('First', 'sitemap-by-click5').' </a>';
        $html .= '<a class="pagination-item" href="'.$cur_url.$format.max(1, $this->paged - 1).'"> « </a>';
      } else if ($this->paged == 1) {
        $html .= '<strong class="on">1</strong>';
      }

      $offset = 3;

      $start = max($this->paged - $offset, 2);
      $end = min($this->paged + $offset, $maxPage);

      for($i = $start; $i <= $end; $i++) {
        if ($i == $this->paged) {
          //set current item class
          $html .= '<strong class="on">'.$i.'</strong>';
        } else {
          if(get_option('permalink_structure')) { 
            $format = "/page/";
          } else {
            global $post;
            $format = "/?page_id=".$post->ID."/page=";
          }
          $html .= '<a class="pagination-item" href="'.$cur_url.$format.$i.'">'.$i.'</a>';
        }
      }
      if ($this->paged < $maxPage && $this->paged != $maxPage) {
        if(get_option('permalink_structure')) { 
          $format = "/page/";
        } else {
          global $post;
          $format = "/?page_id=".$post->ID."/page=";
        }
        $html .= '<a class="pagination-item" href="'.$cur_url.$format.($this->paged + 1).'"> » </a>';
        $html .= '<a class="pagination-item" href="'.$cur_url.$format.$maxPage.'"> '.__('Last', 'sitemap-by-click5').' » </a>';
      }
      $html .= '</div></div>';
    }

    return $html;
  }
}

?>