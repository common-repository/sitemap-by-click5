<?php

function click5_sitemap_send_notification($message, $type) {
  return array('notification' => true, 'message' => $message, 'type' => $type);
}

function click5_sitemap_get_post_types($at_least = 1) {
  $post_types = get_post_types(array('public' => true), 'names');
  $result_post_types = array();

  foreach($post_types as $post_type) {
    if($post_type != "elementor_library")
    {
      $posts = get_posts(array('post_type' => $post_type, 'post_status' => 'publish', 'numberposts' => $at_least));
      if (count($posts) == $at_least) {
        $result_post_types[] = $post_type;
      }
    }
  }

  return $result_post_types;
}

function click5_sitemap_getCustomUrlsHTML($type = null) {
  $result = array();

  $custom_urls_list = (array)json_decode(get_option('click5_sitemap_urls_list'));

  foreach($custom_urls_list as $url_item) {
    $url_item = $url_item;
    if ($type && $url_item->category->name !== $type) {
      continue;
    }
    if ($url_item->enabledHTML) {
      $result[] = $url_item;
    }
  }

  return $result;
}

function click5_sitemap_getCustomCategoriesCustomUrlsHTML() {
  $result = array();

  $custom_urls_list = (array)json_decode(get_option('click5_sitemap_urls_list'));

  foreach($custom_urls_list as $url_item) {
    $url_item = $url_item;
    if ($url_item->enabledHTML && $url_item->category->use_custom && !in_array($url_item->category->name, $result)) {
      $result[] = $url_item->category->name;
    }
  }

  return $result;
}

function click5_sitemap_getCustomUrlsXML($type = null) {
  $result = array();

  $custom_urls_list = (array)json_decode(get_option('click5_sitemap_urls_list'));

  foreach($custom_urls_list as $url_item) {
    $url_item = $url_item;
    if ($type && $url_item->category->name !== $type) {
      continue;
    }

    if ($url_item->enabledXML) {
      if(isset($url_item->url))
      {
        if (function_exists('str_contains'))
        {
          if(str_contains($url_item->url, 'http') == false)
          {
            $url_item->url = esc_url(site_url(). $url_item->url);
          }
        }
        else
        {
          if (strpos($url_item->url, 'http') == false) {
           
            $url_item->url = esc_url(site_url(). $url_item->url);
          }
        }
      }
      $result[] = $url_item;
    }
  }

  return $result;
}

function click5_sitemap_getCustomCategoriesCustomUrlsXML() {
  $result = array();

  $custom_urls_list = (array)json_decode(get_option('click5_sitemap_urls_list'));

  foreach($custom_urls_list as $url_item) {
    $url_item = $url_item;

    if ($url_item->enabledXML && $url_item->category->use_custom && !in_array($url_item->category->name, $result)) {
      $result[] = $url_item->category->name;
    }
  }

  return $result;
}


function click5_sitemap_sort_default_order(&$arr, $compareDates = true) {
  usort($arr, function($a, $b) {
    return strcmp(strtolower($a['title']), strtolower($b['title']));
  });
}

/**
 *
 * Sort array to display
 * 
 * @param    array $arr  Array object to sort
 * @param    string $sortBy  Sort by alphabetically/date
 * @param    string $style  Style merge/group
 * @param    string $sortOrder  Order by ASC/DESC
 * @return      array
 *
 */

function click5_sort_array( $arr, $sortBy, $style, $sortOrder ){

  if(esc_attr(get_option("click5_sitemap_custom_sorting")) == 1)
    return $arr;

  if($style == "merge"){
    if($sortBy == "alphabetically"){
      usort($arr, function($a, $b) {
        return strcmp(strtolower($a['itemObject']->name), strtolower($b['itemObject']->name));
      });   
    }else if($sortBy == "date"){
      usort($arr, function($a, $b) {
        $postDataA = get_post($a["ID"]);
        $postDataB = get_post($b["ID"]);

        if(!is_null($postDataA))
          $postDateA = strtotime($postDataA->post_date);
        else
          $postDateA = strtotime($a['itemObject']->post_date);
              
        if(!is_null($postDataB))
          $postDateB = strtotime($postDataB->post_date);
        else
          $postDateB = strtotime($b['itemObject']->post_date);

        return (int)strcmp(strtolower($postDateA), strtolower($postDateB));
      });
    }

    if($sortOrder == "DESC"){
      $arr = array_reverse($arr);
    }
    
  }else if($style == "group"){
    if($sortBy == "alphabetically"){
      foreach($arr as $index => $post){
        $sortedArray = array();
        $arrayToSort = $post['children'];
        if(count($arrayToSort[0]['children']) == 0 && (strpos($post['ID'],"cat") === false && strpos($post['ID'],"tag") === false && strpos($post['ID'],"tax") === false && (isset($post['isCustomURL']) && $post['isCustomURL'] === true))){
          usort($arrayToSort, function($a, $b) {
            return (int)strcmp(strtolower($a['itemObject']->name), strtolower($b['itemObject']->name));
          }); 

          if($sortOrder == "DESC"){
            $arrayToSort = array_reverse($arrayToSort);
          }

          $sortedArray = $arrayToSort;
          $arr[$index]['children'] = $sortedArray;

        }else if(count($arrayToSort[0]['children']) > 0 && (strpos($post['ID'],"cat") === false && strpos($post['ID'],"tag") === false && strpos($post['ID'],"tax") === false && (isset($post['isCustomURL']) && $post['isCustomURL'] === true))){
          foreach($arrayToSort as $childrenIndex => $postChildren){
            $sortedArray = array();
            usort($postChildren['children'], function($a, $b) {
              return (int)strcmp(strtolower($a['itemObject']->name), strtolower($b['itemObject']->name));
            });

            if($sortOrder == "DESC"){
              $arrayToSort[$childrenIndex]['children'] = array_reverse($postChildren['children']);
            }
            
            $sortedArray = $arrayToSort;
            $arr[$index]['children'] = $sortedArray;
          }
        }
      }
    }else if($sortBy == "date"){
      foreach($arr as $index => $post){
        $sortedArray = array();
        $arrayToSort = $post['children'];
        if(count($arrayToSort[0]['children']) == 0 && (strpos($post['ID'],"cat") === false && strpos($post['ID'],"tag") === false && strpos($post['ID'],"tax") === false )){
          usort($arrayToSort, function($a, $b) {
            $postDataA = get_post($a["ID"]);
            $postDataB = get_post($b["ID"]);

            if(!is_null($postDataA))
                $postDateA = strtotime($postDataA->post_date);
              else
                $postDateA = strtotime($a['itemObject']->post_date);
              
              if(!is_null($postDataB))
                $postDateB = strtotime($postDataB->post_date);
              else
                $postDateB = strtotime($b['itemObject']->post_date);
            return (int)strcmp(strtolower($postDateA), strtolower($postDateB));
          }); 

          if($sortOrder == "DESC"){
            $arrayToSort = array_reverse($arrayToSort);
          }

          $sortedArray = $arrayToSort;
          $arr[$index]['children'] = $sortedArray;

        }else if(count($arrayToSort[0]['children']) > 0 && (strpos($post['ID'],"cat") === false && strpos($post['ID'],"tag") === false && strpos($post['ID'],"tax") === false )){
          foreach($arrayToSort as $childrenIndex => $postChildren){
            $sortedArray = array();
            usort($postChildren['children'], function($a, $b) {
              $postDataA = get_post($a["ID"]);
              $postDataB = get_post($b["ID"]);
  
              if(!is_null($postDataA))
                $postDateA = strtotime($postDataA->post_date);
              else
                $postDateA = strtotime($a['itemObject']->post_date);
              
              if(!is_null($postDataB))
                $postDateB = strtotime($postDataB->post_date);
              else
                $postDateB = strtotime($b['itemObject']->post_date);
              return (int)strcmp(strtolower($postDateA), strtolower($postDateB));
            });

            if($sortOrder == "DESC"){
              $postChildren['children'] = array_reverse($postChildren['children']);
            }
            
            $arrayToSort[$childrenIndex] = $postChildren;
            $sortedArray = $arrayToSort;
            $arr[$index]['children'] = $sortedArray;
          }
        }
      }
    }
  }

  return $arr;
}

function click5_sort_nested_array( $arr, $sortBy, $style, $sortOrder ){

  if(esc_attr(get_option("click5_sitemap_custom_sorting")) == 1)
    return $arr;

  if($style == "merge"){
    if($sortBy == "alphabetically"){
      usort($arr, function($a, $b) {
        $postDataA = get_post($a["ID"]);
        $postDataB = get_post($b["ID"]);
        if(strpos($a["ID"],"c_") !== false){
          $postDataA = $a['title'];
        }else{
          $postDataA = $postDataA->post_title;
        }

        if(strpos($b["ID"],"c_") !== false){
          $postDataB = $b['title'];
        }else{
          $postDataB = $postDataB->post_title;
        }

        return (int)strcmp(strtolower($postDataA), strtolower($postDataB));
      });   
    }else if($sortBy == "date"){
      usort($arr, function($a, $b) {
        $postDataA = get_post($a["ID"]);
        $postDataB = get_post($b["ID"]);

        if(!is_null($postDataA))
          $postDateA = strtotime($postDataA->post_date);
        else
          $postDateA = strtotime($a['post_date']);
              
        if(!is_null($postDataB))
          $postDateB = strtotime($postDataB->post_date);
        else
          $postDateB = strtotime($b['post_date']);

        return (int)strcmp(strtolower($postDateA), strtolower($postDateB));
      });
    }

    if($sortOrder == "DESC"){
      $arr = array_reverse($arr);
    }
    
  }else if($style == "group"){
    if($sortBy == "alphabetically"){
      foreach($arr as $index => $post){
        $sortedArray = array();
        $arrayToSort = $post['children'];
        if(count($arrayToSort) == 0){
          if(strpos($post['ID'],"cat") === false && strpos($post['ID'],"tag") === false && strpos($post['ID'],"tax") === false && (isset($post['isCustomURL']) && $post['isCustomURL'] === true)){
            usort($arrayToSort, function($a, $b) {
              $a = get_post($a['ID']);
              $b = get_post($b['ID']);
              return (int)strcmp(strtolower($a->post_title), strtolower($b->post_title));
            }); 

            if($sortOrder == "DESC"){
              $arrayToSort = array_reverse($arrayToSort);
            }
          }else{
            usort($arrayToSort, function($a, $b) {
              return (int)strcmp(strtolower($a['title']), strtolower($b['title']));
            }); 

            if($sortOrder == "DESC"){
              $arrayToSort = array_reverse($arrayToSort);
            }
          }

          $sortedArray = $arrayToSort;
          $arr[$index]['children'] = $sortedArray;
        }else if(count($arrayToSort) > 0){
          foreach($arrayToSort as $childrenIndex => $postChildren){
            $sortedArray = array();
            if(strpos($post['ID'],"cat") === false && strpos($post['ID'],"tag") === false && strpos($post['ID'],"tax") === false && (isset($post['isCustomURL']) && $post['isCustomURL'] === true)){
              if(isset($postChildren['children'])){
                usort($postChildren['children'], function($a, $b) {
                  return (int)strcmp(strtolower($a['title']), strtolower($b['title']));
                });
              }else{
                usort($arrayToSort, function($a, $b) {
                  return (int)strcmp(strtolower($a['title']), strtolower($b['title']));
                });
              }
            }else{
              if(isset($postChildren['children'])){
                usort($postChildren['children'], function($a, $b) {
                  return (int)strcmp(strtolower($a['title']), strtolower($b['title']));
                });
              }else{
                usort($arrayToSort, function($a, $b) {
                  return (int)strcmp(strtolower($a['title']), strtolower($b['title']));
                });
              }
            }

            if($sortOrder == "DESC"){
              if(isset($postChildren['children'])){
                $arrayToSort[$childrenIndex]['children'] = array_reverse($postChildren['children']);
              }else{
                $arrayToSort = array_reverse($arrayToSort);
              }
              
            }
            
            $sortedArray = $arrayToSort;
            $arr[$index]['children'] = $sortedArray;
          }
        }
      }
    }else if($sortBy == "date"){
      foreach($arr as $index => $post){
        $sortedArray = array();
        $arrayToSort = $post['children'];
        if(count($arrayToSort) == 0 && (strpos($post['ID'],"cat") === false && strpos($post['ID'],"tag") === false && strpos($post['ID'],"tax") === false)){
          if(isset($postChildren['children'])){
            usort($postChildren['children'], function($a, $b) {
              $postDataA = get_post($a["ID"]);
              $postDataB = get_post($b["ID"]);
              
              if(!is_null($postDataA))
                $postDateA = strtotime($postDataA->post_date);
              else
                $postDateA = strtotime($a['post_date']);
              
              if(!is_null($postDataB))
                $postDateB = strtotime($postDataB->post_date);
              else
                $postDateB = strtotime($b['post_date']);
              return (int)strcmp(strtolower($postDateA), strtolower($postDateB));
            }); 
          }else{
            usort($arrayToSort, function($a, $b) {
              $postDataA = get_post($a["ID"]);
              $postDataB = get_post($b["ID"]);
              
              if(!is_null($postDataA))
                $postDateA = strtotime($postDataA->post_date);
              else
                $postDateA = strtotime($a['post_date']);
              
              if(!is_null($postDataB))
                $postDateB = strtotime($postDataB->post_date);
              else
                $postDateB = strtotime($b['post_date']);
                
              return (int)strcmp(strtolower($postDateA), strtolower($postDateB));
            }); 
          }

          if($sortOrder == "DESC"){
            $arrayToSort = array_reverse($arrayToSort);
          }

          $sortedArray = $arrayToSort;
          $arr[$index]['children'] = $sortedArray;

        }else if(count($arrayToSort) > 0 && (strpos($post['ID'],"cat") === false && strpos($post['ID'],"tag") === false && strpos($post['ID'],"tax") === false)){
          foreach($arrayToSort as $childrenIndex => $postChildren){
            $sortedArray = array();
            if(isset($postChildren['children'])){
              if($sortOrder == "ASC" && isset($arrayToSort[$childrenIndex]['children'])){
                usort($arrayToSort[$childrenIndex]['children'], function($a, $b) {
                  $postDataA = get_post($a["ID"]);
                  $postDataB = get_post($b["ID"]);
      
                  if(!is_null($postDataA))
                    $postDateA = strtotime($postDataA->post_date);
                  else
                    $postDateA = strtotime($a['post_date']);
                  
                  if(!is_null($postDataB))
                    $postDateB = strtotime($postDataB->post_date);
                  else
                    $postDateB = strtotime($b['post_date']);
                  
                  return (int)strcmp(strtolower($postDateA), strtolower($postDateB));
                }); 
              }else{
                usort($postChildren['children'], function($a, $b) {
                  $postDataA = get_post($a["ID"]);
                  $postDataB = get_post($b["ID"]);
      
                    if(!is_null($postDataA))
                      $postDateA = strtotime($postDataA->post_date);
                    else
                      $postDateA = strtotime($a['post_date']);
                    
                    if(!is_null($postDataB))
                      $postDateB = strtotime($postDataB->post_date);
                    else
                      $postDateB = strtotime($b['post_date']);
                  return (int)strcmp(strtolower($postDateA), strtolower($postDateB));
                }); 
              }
              
            }else{
              usort($arrayToSort, function($a, $b) {
                $postDataA = get_post($a["ID"]);
                $postDataB = get_post($b["ID"]);
    
                if(!is_null($postDataA))
                  $postDateA = strtotime($postDataA->post_date);
                else
                  $postDateA = strtotime($a['post_date']);
              
                if(!is_null($postDataB))
                  $postDateB = strtotime($postDataB->post_date);
                else
                  $postDateB = strtotime($b['post_date']);
                return (int)strcmp(strtolower($postDateA), strtolower($postDateB));
              }); 
            }

            if($sortOrder == "DESC"){
              if(isset($postChildren['children'])){
                $arrayToSort[$childrenIndex]['children'] = array_reverse($postChildren['children']);
              }else{
                $arrayToSort = array_reverse($arrayToSort);
              }
              
            }
            $sortedArray = $arrayToSort;
            $arr[$index]['children'] = $sortedArray;
          }
        }
      }
    }
  }

  return $arr;
}

function click5_sitemap_sort_default_date(&$arr, $compareDates = true) {
  usort($arr, function($a, $b) {
    $_buffA = explode('__', $a['ID']);
    $_buffB = explode('__', $b['ID']);

    if (!$_buffA[1] || !$_buffB[1]) {
      return 0;
    } else {
      return strtotime('01-'.$_buffB[1]) - strtotime('01-'.$_buffA[1]);
    }
  });
}



function click5_sitemap_get_order_list_populate_subchildren($blacklist, &$item) {
  $posts = get_children(array('post_parent' => $item['ID'], 'post_status' => 'publish', 'numberposts' => -1));
  if (count($posts)) {
   $item['children'] = array();

    foreach($posts as $post) {
      if (in_array($post->ID, $blacklist)) {
        continue;
      }

      $newChild = array('ID' => $post->ID, 'title' => $post->post_title);
      click5_sitemap_get_order_list_populate_subchildren($blacklist, $newChild);
      $item['children'][] = $newChild;
    }

    if (count($item['children'])) {
      click5_sitemap_sort_default_order($item['children']);
    }
  }
}

function click5_sitemap_get_safe_term($id, $term) {
  $page = get_post($id);
  if($id==2596)
  {
    $test=";;";
  }
  if (!$page) {
    return null;
  }

  $new_term = $term;

  if ( class_exists( 'WooCommerce' ) ) {
    if($page->post_type == 'product' && $term == 'category'){
      $new_term = 'product_cat';
    } elseif($page->post_type == 'product' && $term == 'post_tag'){
      $new_term = 'product_tag';
    }
  }

  $termArr = get_the_terms($id, $new_term);

  if (!$termArr) {
    return null;
  }

  if (!$termArr[0]) {
    return null;
  }

  if(isset(get_post_meta($id)['_yoast_wpseo_primary_category'])){
    if(get_post_meta($id)['_yoast_wpseo_primary_category'][0]!="")
    {
      $primaryCategory = get_category(get_post_meta($id)['_yoast_wpseo_primary_category'][0]);
      if(is_null($primaryCategory))
      {
        return array('term_id' => $termArr[0]->term_id, 'name' => $termArr[0]->name);
      }
      else
      {
        return array('term_id' => $primaryCategory->term_id, 'name' => $primaryCategory->name);
      }
    }
    else
      return array('term_id' => $termArr[0]->term_id, 'name' => $termArr[0]->name);
  }


  return array('term_id' => $termArr[0]->term_id, 'name' => $termArr[0]->name);
}

function click5_sitemap_remap_to_groups_single(&$arrayToplevel, &$noTerm, $childToplevel, $groupingType, $postType) {
  $childToplevel = (array)$childToplevel;
  if ($groupingType !== 'archives') {
    $current_page_term = click5_sitemap_get_safe_term($childToplevel['ID'], $groupingType == 'categories' ? 'category' : 'post_tag');
    if ($current_page_term == null) {
      $noTerm[] = $childToplevel;
    } else {
      $bItemHasBeenAssigned = false;
      foreach($arrayToplevel as &$potentialParent) {
        if ($potentialParent['ID'] == 'g_'.$postType.'__'.$current_page_term['term_id']) {
          $potentialParent['children'][] = $childToplevel;
          $bItemHasBeenAssigned = true;
        }
      }
      if(!$bItemHasBeenAssigned) {
        $arrayToplevel[] = array(
          'ID' => 'g_'.$postType.'__'.$current_page_term['term_id'],
          'title' => $current_page_term['name'],
          'children' => array($childToplevel)
        );
      }
    }
  } else {
    //archives here
    $monthName = get_the_date('F Y', $childToplevel['ID']);
    $monthId = get_the_date('m-Y', $childToplevel['ID']);

    if (!empty($monthName) && !empty($monthId)) {
      $bItemHasBeenAssigned = false;
        foreach($arrayToplevel as &$potentialParent) {
          if ($potentialParent['ID'] == 'g_'.$postType.'__'.$monthId) {
            $potentialParent['children'][] = $childToplevel;
            $bItemHasBeenAssigned = true;
          }
        }
        if(!$bItemHasBeenAssigned) {
          $arrayToplevel[] = array(
            'ID' => 'g_'.$postType.'__'.$monthId,
            'title' => $monthName,
            'children' => array($childToplevel)
          );
        }
    } else {
      $noTerm[] = $childToplevel;
    }
  }
}

function click5_sitemap_get_order_list($_style = 'group') {
  $orderList = array();

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


  $blacklistArray = json_decode(get_option('click5_sitemap_blacklisted_array'));
  $blacklist = array_column($blacklistArray ? $blacklistArray : array(), 'ID');
  $groupingType = esc_attr(get_option('click5_sitemap_html_blog_group_by'));
  if ($_style === 'group') {


    foreach($enabledTypesArray as $type) {
      $typeObject = array();
      $typeObject['ID'] = $type;
      if (intval(esc_attr(get_option('click5_sitemap_use_custom_name_'.$type)))) {
        $typeObject['title'] = esc_attr(get_option('click5_sitemap_custom_name_text_'.$type));
      } else {
        $typeObject['title'] = get_post_type_object($type)->label;
      }
      $typeObject['children'] = array();

      $posts = get_posts(array('post_parent' => 0, 'post_type' => $type, 'post_status' => 'publish', 'numberposts' => -1));

      foreach($posts as $post) {
        if (in_array($post->ID, $blacklist)) {
          continue;
        }
        if($post->ID == 5550)
        {
          $test = "111";
        }
        $newChild = array('ID' => $post->ID, 'title' => $post->post_title);
        click5_sitemap_get_order_list_populate_subchildren($blacklist, $newChild);
        $typeObject['children'][] = $newChild;
      }

      $customLinks = click5_sitemap_getCustomUrlsHTML($type);

      foreach($customLinks as $customLink) {
        $typeObject['children'][] = array('ID' => $customLink->ID, 'title' => $customLink->title);
      }

      click5_sitemap_sort_default_order($typeObject['children']);

      $postTypeGroupingEnabled = boolval(esc_attr(get_option('click5_sitemap_html_blog_treat_'.$type)));

      if ($groupingType !== 'disabled' && $postTypeGroupingEnabled) {
        $newChildrenToplevel = array();
        $noTerm = array();
        foreach($typeObject['children'] as $childToplevel) {         
          click5_sitemap_remap_to_groups_single($newChildrenToplevel, $noTerm, $childToplevel, $groupingType, $type);
        }

        if ($groupingType !== 'archives') {
          click5_sitemap_sort_default_order($newChildrenToplevel);
          click5_sitemap_sort_default_order($noTerm);
        } else {
          click5_sitemap_sort_default_date($newChildrenToplevel);
          click5_sitemap_sort_default_order($noTerm);
        }

        $typeObject['children'] = array();

        foreach($newChildrenToplevel as $grouped) {
          $typeObject['children'][] = $grouped;
        }

        foreach($noTerm as $not_grouped) {
          $typeObject['children'][] = $not_grouped;
        }
      }

      $orderList[] = $typeObject;

      click5_sitemap_sort_default_order($orderList);
    }


    foreach($TaxenabledTypesOriginalArray as $type) {
      $typeObject = array();
      $typeObject['ID'] = $type . '_tax';

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

      if (intval(esc_attr(get_option('click5_sitemap_use_custom_name_'.$short_type.'_tax')))) {
        $typeObject['title'] = esc_attr(get_option('click5_sitemap_custom_name_text_'.$short_type.'_tax'));
      } else {
        $typeObject['title'] = ucwords($type);
      }
      $typeObject['children'] = array();


      foreach ( $tax_items as $term) {
        if($short_type == 'authors') {
          if ( count_user_posts( $term->ID, $post_types ) >= 1 ) { 
            $newChild = array('ID' => $term->term_id.'_'.$short_type.'_tax', 'title' => $term->data->user_login);
            $typeObject['children'][] = $newChild;
          }
        } else {
          $newChild = array('ID' => $term->term_id.'_'.$short_type.'_tax', 'title' => $term->name);
          $typeObject['children'][] = $newChild;
        }
        
        
      }

      $orderList[] = $typeObject;
      click5_sitemap_sort_default_order($orderList);

    }


    foreach($TaxenabledTypesArray as $type) {
      $typeObject = array();
      $typeObject['ID'] = $type;

      $original_name = get_taxonomy($type); 

      if (intval(esc_attr(get_option('click5_sitemap_use_custom_name_'.$type)))) {
        $typeObject['title'] = esc_attr(get_option('click5_sitemap_custom_name_text_'.$type));
      } else {
        $typeObject['title'] = ucwords($original_name->label);
      }
      $typeObject['children'] = array();


      $tax_items = get_terms($type);

      foreach ( $tax_items as $term) {

        $newChild = array('ID' => $term->term_id.'_tax', 'title' => $term->name);
        $typeObject['children'][] = $newChild;
        
      }

      $orderList[] = $typeObject;

      click5_sitemap_sort_default_order($orderList);

    }


    $customCategories = click5_sitemap_getCustomCategoriesCustomUrlsHTML();
    foreach($customCategories as $category) {
      $typeObject = array();
      $typeObject['ID'] = $category;
      $typeObject['title'] = $category;
      $typeObject['children'] = array();
      $customPosts = click5_sitemap_getCustomUrlsHTML($category);

      foreach($customPosts as $custompost) {
        $typeObject['isCustomURL'] = true;
        $typeObject['children'][] = array('ID' => $custompost->ID, 'title' => $custompost->title,'isCustomURL' => true, "post_date" => $custompost->last_mod);
      }

      click5_sitemap_sort_default_order($typeObject['children']);

      $orderList[] = $typeObject;
    }

  } else if ($_style === 'merge') {
    $posts = get_posts(array('post_type' => $enabledTypesArray, 'post_status' => 'publish', 'numberposts' => -1));
    $customPosts = click5_sitemap_getCustomUrlsHTML();
    foreach($posts as $post) {
      if (in_array($post->ID, $blacklist)) {
        continue;
      }
      $orderList[] = array('ID' => $post->ID, 'title' => $post->post_title);
    }
    foreach($customPosts as $post) {
      $orderList[] = array('ID' => $post->ID, 'title' => $post->title, "post_date" => $post->last_mod);
    }

    click5_sitemap_sort_default_order($orderList, false);
  }

  click5_set_old_order();

  click5_sitemap_sort_default_order($orderList);
  return $orderList;
}

function click5_sitemap_HTML_get_nestedElements(){
  return json_decode(get_option('click5_sitemap_order_list_nested'));
}

//recursive function
function click5_sitemap_HTML_display_children_sublist(&$html, $item) {
  $_style = esc_attr(get_option('click5_sitemap_display_style'));
  if(isset($item['children'])){
    $item['children'] = (array)$item['children'];
    if(count($item['children'])) {
      $html .= '<ol class="dd-list">';

      $nestedElements = click5_sitemap_HTML_get_nestedElements();
      $itemArray = array($item);
      
      foreach($item['children'] as $child) {
        
        if (!empty($nestedElements)) {
          
          $isInArray = false;
          foreach($nestedElements as $element){ 
            if($element->element == $child['ID']){
              $isInArray = true;
            }
          }

          $isParentInArray = false;
          foreach($nestedElements as $element){ 
            if($element->parent == $child['ID'] && $element->toOriginalNested == false){
              $isParentInArray = true;
            }
          }

          if(!$isInArray){
            $html .= '<li class="dd-item sub-item" data-value="'.esc_attr($child['ID']).'" name="'.esc_attr($item['ID']).'"><div class="dd-handle xq6">'.esc_attr($child['title']).'</div>';
            click5_sitemap_HTML_display_children_sublist($html, $child);
          }
          
          if($isParentInArray){
            $html .= '<ol class="dd-list">';
            foreach($nestedElements as $value){ 
              if($value->parent == $child['ID']){

                if($value->original_parent){
                  $custom_class = 'original-nested';
                } else {
                  $custom_class = 'custom-nested';
                }

                $html .= '<li class="dd-item sub-item '.$custom_class.'" data-value="'.esc_attr($value->element).'"><div class="dd-handle xq5">'.esc_attr($value->title).'</div></li>';
              }
            }
            $html .= '</ol>';
          }

        } else {
          $html .= '<li class="dd-item sub-item" data-value="'.esc_attr($child['ID']).'" name="'.esc_attr($item['ID']).'"><div class="dd-handle xq4">'.esc_attr($child['title']).'</div>';
          click5_sitemap_HTML_display_children_sublist($html, $child);
        }

        $html .= '</li>';
      }

      if (!empty($nestedElements)) {
        $isMainParentInArray = false;
        foreach($nestedElements as $element){ 
          if($element->parent == $item['ID']){
            $isMainParentInArray = true;
          }
        }

        if($isMainParentInArray){
          foreach($nestedElements as $value){ 
            if($value->parent == $item['ID']){

              if($value->original_parent){
                $custom_class = 'original-nested';
              } else {
                $custom_class = 'custom-nested';
              }

              $html .= '<li class="dd-item sub-item '.$custom_class.'" data-value="'.esc_attr($value->element).'"><div class="dd-handle xq3">'.esc_attr($value->title).'</div></li>';
            }
          }
        }
      }


      $html .= '</ol>';

    }
  }
}

function click5_sortByOrder($a, $b) {
  return (int)($a->order > $b->order);
}

function click5_set_old_order() {

  $styleSett = esc_attr( get_option('click5_sitemap_display_style') );
  $_style = !empty($styleSett) ? $styleSett : 'group';

  if ($_style === 'group') {

    if(get_option('click5_sitemap_order_list_old') && get_option('click5_sitemap_order_list')){
    $get_order_old = json_decode(get_option('click5_sitemap_order_list_old'));
    $get_order_new = json_decode(get_option('click5_sitemap_order_list'));

    foreach($get_order_old as $value){ 
      foreach($get_order_new as $value_new){ 
        if($value->ID == $value_new->ID){

          $value_new->order = $value->order;
          
          if(isset($value->parent))
          {
            if($value->parent != false && !empty($value_new->parent)){
              $value->parent = $value_new->parent;
            }else if($value->parent != false && empty($value_new->parent)){
              $value_new->parent = $value->parent;
            }
          }

        }
      }
    }

    usort($get_order_new, 'click5_sortByOrder');

    return update_option('click5_sitemap_order_list', json_encode($get_order_new));

    }
  }
}

function click5_sitemap_HTML_sitemap_display_order_list_and_nested() {
  $result_array = array();
  $html = '';
  $styleSett = esc_attr( get_option('click5_sitemap_display_style') );
  $_style = !empty($styleSett) ? $styleSett : 'group';

  click5_set_old_order();

  $arrayOrderList = click5_sitemap_get_order_list($_style);
  $arrayOrderList = click5_sort_nested_array($arrayOrderList,esc_attr( get_option('click5_sitemap_html_blog_sort_by')),$_style,esc_attr( get_option('click5_sitemap_html_blog_order_by')));
  

  if ($_style === 'group') {
    foreach($arrayOrderList as $orderItem) {
      $html .= '<li class="dd-item group" data-value="'.esc_attr($orderItem['ID']).'"><div class="dd-handle xq2">'.esc_attr($orderItem['title']).'</div>';
      click5_sitemap_HTML_display_children_sublist($html, $orderItem);
    }
  } else if ($_style === 'merge') {
    foreach($arrayOrderList as $orderItem) {
      $html .= '<li class="dd-item" data-value="'.esc_attr($orderItem['ID']).'"><div class="dd-handle xq1">'.esc_attr($orderItem['title']).'<div></li>';
    }
  }
$result_array[0] = $html;
$result_array[1] = json_decode(get_option('click5_sitemap_order_list_nested'));
$result_array[2] = (array) json_decode(get_option('click5_sitemap_order_list'));
error_log($html,3,"my-errors2.log");
return $result_array;

}

function click5_sitemap_HTML_sitemap_display_order_list_and_nested_count() {
  $_style = "merge";
  click5_set_old_order();
  $arrayOrderList = click5_sitemap_get_order_list($_style);

  return count($arrayOrderList);
}


function click5_sitemap_HTML_sitemap_display_order_list() {

    $html = '';
    $styleSett = esc_attr( get_option('click5_sitemap_display_style') );
    $_style = !empty($styleSett) ? $styleSett : 'group';

    click5_set_old_order();
  
    $arrayOrderList = click5_sitemap_get_order_list($_style);
    
    $arrayOrderList = array_reverse($arrayOrderList);    


    if ($_style === 'group') {
      foreach($arrayOrderList as $orderItem) {
        $html .= '<li class="dd-item group" data-value="'.esc_attr($orderItem['ID']).'"><div class="dd-handle xq2">'.esc_attr($orderItem['title']).'</div>';
        click5_sitemap_HTML_display_children_sublist($html, $orderItem);
      }
    } else if ($_style === 'merge') {
      foreach($arrayOrderList as $orderItem) {
        $html .= '<li class="dd-item" data-value="'.esc_attr($orderItem['ID']).'"><div class="dd-handle xq1">'.esc_attr($orderItem['title']).'<div></li>';
      }
    }

  return $html;
  
}
require('HTMLsitemap.php');

function click5_sitemap_display_sitemap() {

  $version = CLICK5_SITEMAP_DEV_MODE ? time() : CLICK5_SITEMAP_VERSION;

  wp_enqueue_style( 'click5_sitemap_css_front', plugins_url('/css/front/index.css', __FILE__), array(), $version );

  $HTMLSitemap = new HTMLSitemapList();
  $paged = 1;
  if ( get_query_var('paged') ) {
      $paged = get_query_var('paged');
  } elseif ( get_query_var('page') ) { // 'page' is used instead of 'paged' on Static Front Page
      $paged = get_query_var('page');
  } else {
      $paged = 1;
  }
  if(!get_option('permalink_structure')) {
    $lin = $_GET;
    if(strpos($lin['page_id'], 'page=') !== false){ 
      $paged = explode("page=",$lin['page_id'])[1];
    }
  }

  $HTMLSitemap->set_paged($paged);
  return $HTMLSitemap->display();
}

add_shortcode( 'click5_sitemap', 'click5_sitemap_display_sitemap' );

function click5_sitemap_checkIsAValidDate($myDateString){
    return (bool)strtotime($myDateString);
}


function click5_sitemap_generate_CreateXML($name, $items, $custom_items = null) {
  $blacklistSeoIDArray = json_decode(get_option('click5_sitemap_seo_blacklisted_array'));
  $blacklistSeoID = array_column($blacklistSeoIDArray ? $blacklistSeoIDArray : array(), 'ID');

  $file_xml = '<?xml version="1.0" encoding="UTF-8"?>';
  $file_xml .= '<?xml-stylesheet type="text/xsl" href="'.esc_url(plugins_url('', __FILE__)).'/css/front/template.xsl" ?>';

  $file_xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
  $lastmod = date('Y-m-d', strtotime('09/14/2000'));

  foreach($items as $page) {
    if(in_array($page->ID, $blacklistSeoID)) {
      continue;
    }

    $permalink = get_permalink($page->ID);

    $file_xml .= '<url>';

    $page_mod = date('Y-m-d', strtotime($page->post_modified));
    if ($page_mod > $lastmod) {
      $lastmod = $page_mod;
    }
    

    $file_xml .= '<loc>'.esc_url($permalink).'</loc>';
    $file_xml .= '<lastmod>'.esc_attr($page_mod).'</lastmod>';

    $file_xml .= '</url>';
  }

  if ($custom_items) {
    foreach($custom_items as $custom_page) {
      $file_xml .= '<url>';
      $file_xml .= '<loc>'.esc_url($custom_page->url).'</loc>';
        if (click5_sitemap_checkIsAValidDate($custom_page->last_mod)) {
          $file_xml .= '<lastmod>'.date('Y-m-d', strtotime(esc_attr($custom_page->last_mod))).'</lastmod>';
          $modDate = date('Y-m-d', strtotime($custom_page->last_mod));
          if ($modDate> $lastmod) {
            $lastmod = $modDate;
          }
        }
      
      $file_xml .= '</url>';
    }
  }
  $file_xml .= '</urlset>';
  clearstatcache();
  if (file_exists(ABSPATH.'/'.$name)) {
    unlink(ABSPATH.'/'.$name);
  }

  click5_ping_sitemap_to_google(site_url().'/'.$name, 'post_'.$name);

  if (file_put_contents(ABSPATH.'/'.basename($name), $file_xml)) {
    return array('url' => esc_url(site_url().'/'.basename($name)), 'lastmod' => $lastmod);
  } else {
    return false;
  }
}

function click5_sitemap_generate_video_list($name) {
  $result_array = array();
  $marker = 0;
  $file_xml_header = '<?xml version="1.0" encoding="UTF-8"?>';
  $file_xml_header .= '<?xml-stylesheet type="text/xsl" href="'.esc_url(plugins_url('', __FILE__)).'/css/front/template.xsl" ?>';
  $file_xml_header .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">';
  $file_xml = $file_xml_header;
  
  $lastmod = date('Y-m-d', strtotime('09/14/2000'));
  
  global $wpdb;
  $results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}posts WHERE post_status='publish' and (post_type != 'attachment' and post_type != 'acf-field' and post_type != 'acf-field-group' and post_type != 'revision' 
  and post_type != 'acf' and post_type!='nav_menu_item' and post_type != 'oembed_cache' and post_type !='wpcf7_contact_form' and post_type !='wphb_minify_group') order by id desc", OBJECT );

  $split_counter = 0;
  $split_counter_total = 0;
  $video_index = 0;
  foreach($results as $p_item) 
  {
    $cont = $p_item->post_content;
    $url_exist = false;

    
    $link_post = get_permalink($p_item->ID);
    if (function_exists('str_contains'))
    {
      if(str_contains($link_post, '?p=') || str_contains($link_post, '&p=') )
      {
        continue;
      }
    }
    else
    {
      if (strpos($link_post, '?p=') !== false || strpos($link_post, '&p=') !== false) {
       
        continue;
      }
    }
    
    $regexFB = '/videos%2F.*\b(2F)\b/i';
    $str =$p_item->post_content;

    $post_current = get_post($p_item->ID);

    preg_match_all('~\S*\bwww\.youtube\.com\S*~', $p_item->post_content, $matches);
    preg_match_all('/\/\/(?:www\.)?vimeo.com\/([0-9a-z\-_]+)/i', $p_item->post_content, $matches1);    
    preg_match($regexFB, $str, $matches2, PREG_OFFSET_CAPTURE, 0);

    if(isset($matches))
    {
      if(count($matches[0])>0)
      {
        $link_post = get_permalink($p_item->ID);
        $marker++; 
        $split_counter +=1;     
        $file_xml .= '<url>';
        $file_xml .= '<loc>'.esc_url($link_post).'</loc>';
        $time_mod = explode(" ", $p_item->post_modified);
        $file_xml .= '<lastmod>'. $time_mod[0] .'</lastmod>';
        $url_exist = true;
        if($time_mod[0] > $lastmod)
        {
          $lastmod = $time_mod[0];
        }
        $content_array = explode(' ', $p_item->post_content);

          $ind = 0;
          $length = count($matches[0]);
          while(true)
          {
              if($ind<$length)
              {
                if(function_exists("str_contains"))
                {
                  if(str_contains($matches[0][$ind], "data-src-cmplz"))
                  {
                    $ind = $ind +1;
                    continue;
                  }
                }
                $matches[0][$ind] = str_replace('src=', '', $matches[0][$ind]);
                $matches[0][$ind] = str_replace('"', '', $matches[0][$ind]);            
                $matches[0][$ind] = str_replace('embed/', 'watch!v=', $matches[0][$ind]);
                $matches[0][$ind] = str_replace('[embed]', '', $matches[0][$ind]);
                $matches[0][$ind] = str_replace('[/embed]', '', $matches[0][$ind]);
                $matches[0][$ind] = str_replace('watch?v=', 'watch!v=', $matches[0][$ind]);
                if(function_exists("str_contains"))
                {
                  if(str_contains($matches[0][$ind], "?"))
                  {
                    $matches[0][$ind] = substr($matches[0][$ind], 0, strpos($matches[0][$ind], "?"));
                  }
                }
                $matches[0][$ind] = str_replace('watch!v=', 'watch?v=', $matches[0][$ind]);

                $url_components = parse_url($matches[0][$ind]);
 
                // Use parse_str() function to parse the
                // string passed via URL
                parse_str($url_components['query'], $params);
                $matches[0][$ind] = 'https://www.youtube.com/watch?v='. $params['v'];
                $matches[0][$ind] = str_replace('?rel=0', '', $matches[0][$ind]);
               
                $youtube = "https://www.youtube.com/oembed?url=". $matches[0][$ind] ."&format=json";


                $result = wp_remote_get( $youtube, array());

                if(isset($result) && !is_wp_error($result))
                {
                  if(is_object($result))
                    $result = (array)json_decode(json_encode($result),true);
                  if($result['response']['code']==200)
                  {
                    $youtube_content =  json_decode($result['body'], true);
                    
                    parse_str( parse_url( $matches[0][$ind], PHP_URL_QUERY ), $my_array_of_vars );
                    $image = "https://img.youtube.com/vi/". $my_array_of_vars['v'] ."/default.jpg";
                    $file_xml .= '<video:video>';
                    $file_xml .= "<video:thumbnail_loc>" .$image."</video:thumbnail_loc>";

                    $title_vid = $youtube_content["title"];
                    $title_vid = str_replace('&', '&#38;', $title_vid);
                    $file_xml .= '<video:title>'.$title_vid.'</video:title>';
                    $file_xml .= '<video:description>'.$title_vid.'</video:description>';
    
                    $file_xml .= '<video:player_loc>'.$matches[0][$ind].'</video:player_loc>';
                    $file_xml .= '</video:video>';
                    
                  }
                } 
                $ind = $ind +1;              
              }
              else
              {
                break;
              }
          }
          //$file_xml .= '</url>';
      }
    }
    if(isset($matches1))
    {
      if(count($matches1[1])>0)
      {  
        $link_post = get_permalink($p_item->ID);
        $marker++;
        if($url_exist == false)
        {
          $split_counter +=1;
          $file_xml .= '<url>';
          $file_xml .= '<loc>'.esc_url($link_post).'</loc>';
          $time_mod = explode(" ", $p_item->post_modified);
          $file_xml .= '<lastmod>'. $time_mod[0] .'</lastmod>';
          $url_exist = true;
        }
        if($time_mod[0] > $lastmod)
        {
          $lastmod = $time_mod[0];
        }
        $content_array = explode(' ', $p_item->post_content);

          $ind = 0;
          $length = count($matches1[1]);
          while(true)
          {
              if($ind<$length)
              {
                $vimeo = "https://vimeo.com/api/oembed.json?url=https://vimeo.com/" . $matches1[1][0];


                $result = wp_remote_get( $vimeo, array());

                if(isset($result))
                {
                  if($result['response']['code']==200)
                  {

                    $data_player =  json_decode($result['body'], true);
                    $file_xml .= '<video:video>';            
                   
                   
                    $image = $data_player["thumbnail_url"];
                    $file_xml .= "<video:thumbnail_loc>" .$image."</video:thumbnail_loc>";
                    $title_vid = $data_player["title"];
                    $title_vid = str_replace('&', '&#38;', $title_vid);
                    $file_xml .= '<video:title>'.$title_vid.'</video:title>';
    
                    $file_xml .= '<video:player_loc>'.'https://vimeo.com/' . $matches1[1][$ind].'</video:player_loc>';
                    $file_xml .= '</video:video>';
                    
                  }
                }
                $ind = $ind +1;
                
              }
              else
              {
                break;
              }
          }
      } 
    }
    if(isset($matches2) && isset($matches2[0]))
    {
      if(count($matches2[0])>0)
      {  
        $link_post = get_permalink($p_item->ID);
        $marker++;
        if($url_exist == false)
        {
          $split_counter +=1;
          $file_xml .= '<url>';
          $file_xml .= '<loc>'.esc_url($link_post).'</loc>';
          $time_mod = explode(" ", $p_item->post_modified);
          $file_xml .= '<lastmod>'. $time_mod[0] .'</lastmod>';
        }
        $url_exist = true;
        if($time_mod[0] > $lastmod)
        {
          $lastmod = $time_mod[0];
        }

          $ind = 0;
          $length = count($matches2[0]);
          while(true)
          {
              if($ind<$length)
              {           
                $matches2[0][$ind] = str_replace('videos', '', $matches2[0][$ind]);
                $matches2[0][$ind] = str_replace('%2F', '', $matches2[0][$ind]);

                $file_xml .= '<video:video>'; 
                $file_xml .= '<video:player_loc>'. 'https://www.facebook.com/watch/?v='. $matches2[0][$ind].'</video:player_loc>';
                $file_xml .= '</video:video>';
                $ind = $ind +1;
                
              }
              else
              {
                break;
              }
          }
      }
    }

    if ( class_exists('ACF') )
    {       
     $link_post = get_permalink($p_item->ID);
     $acf_fields = get_fields($p_item->ID);

     if(is_array($acf_fields))
     {
      $c5_xml_length = strlen( $file_xml);
      global $video_xml;
      global $gUrl_exsist;
      global $gMarker;
      $video_xml = "";
      $video_xml = $file_xml;
      $gUrl_exsist = $url_exist;
      $gMarker = $marker;
      $split_counter +=1;
      if(isset ($acf_fields))
      {
      if(!empty($acf_fields['videos']))
      {
        if(is_array($acf_fields['videos']))
        {
      if(count($acf_fields['videos'])>0)
      {
        if($time_mod[0] > $lastmod)
        {
          $lastmod = $time_mod[0];
        }

        foreach ($acf_fields['videos'] as $acf_video)
        {

          $yt_link = "";
          if(!empty($acf_video['yt_link'])){
            $yt_link = $acf_video['yt_link'];
          }
          else if(!empty($acf_video['yt'])){
            $yt_link = $acf_video['yt'];
          }
          else if(!empty($acf_video['video_link'])){
            $yt_link = $acf_video['video_link'];
          }
          else if(!empty($acf_fields['video_url'])){
            $yt_link = $acf_fields['video_url'];
          }          
          else if(!empty($acf_video['video_file'])){
            $yt_link = $acf_video['video_file'];
          }
          else if(!empty($acf_video['link'])){
            $yt_link = $acf_video['link'];
          }
          else
          {
            if(!empty($acf_video['video']))
            {
              $yt_link = $acf_video['video'];
            }
          }
          $yt_link = esc_url($yt_link);

          if(str_contains($yt_link, ".mp4"))
          {
            $marker++;
            if($url_exist == false)
            {
              $split_counter +=1;
              $file_xml .= '<url>';
              $file_xml .= '<loc>'.esc_url($link_post).'</loc>';
              $time_mod = explode(" ", $p_item->post_modified);
              $file_xml .= '<lastmod>'. $time_mod[0] .'</lastmod>';
              $url_exist = true;
              $gUrl_exsist = true;
            }
            $image = $acf_video['thumb']['url'];
            $file_xml .= '<video:video>';
            $file_xml .= "<video:thumbnail_loc>" .$image."</video:thumbnail_loc>";

            $title_vid = $acf_video['title'];
            $title_vid = str_replace('&', '&#38;', $title_vid);
            $file_xml .= '<video:title>'.$title_vid.'</video:title>';
            $file_xml .= '<video:description>'.$title_vid.'</video:description>';

            $yt_link = site_url() . $yt_link;
            $file_xml .= '<video:player_loc>'.$yt_link.'</video:player_loc>';
            $file_xml .= '</video:video>';
            
          }
          else
          {
            $youtube = "https://www.youtube.com/oembed?url=". $yt_link ."&format=json";
            $result = wp_remote_get( $youtube, array());

              if(isset($result))
              {
                if($result['response']['code']==200)
                {
                  $marker++;
                  if($url_exist == false)
                  {
                    $split_counter +=1;
                    $file_xml .= '<url>';
                    $file_xml .= '<loc>'.esc_url($link_post).'</loc>';
                    $time_mod = explode(" ", $p_item->post_modified);
                    $file_xml .= '<lastmod>'. $time_mod[0] .'</lastmod>';
                    $url_exist = true;
                    $gUrl_exsist = true;
                  }
                  $youtube_content =  json_decode($result['body'], true);
                    
                  parse_str( parse_url( $yt_link, PHP_URL_QUERY ), $my_array_of_vars );
                  $image = "https://img.youtube.com/vi/". $my_array_of_vars['v'] ."/default.jpg";
                  $file_xml .= '<video:video>';
                  $file_xml .= "<video:thumbnail_loc>" .$image."</video:thumbnail_loc>";

                  $title_vid = $youtube_content["title"];
                  $title_vid = str_replace('&', '&#38;', $title_vid);
                  $file_xml .= '<video:title>'.$title_vid.'</video:title>';
                  $file_xml .= '<video:description>'.$title_vid.'</video:description>';
      
                  $file_xml .= '<video:player_loc>'.$yt_link.'</video:player_loc>';
                  $file_xml .= '</video:video>';
                      
                  }
                }
            } 
          }
      }
    }
    }
    if(!empty($acf_fields['videos_bottom']))
      {
      if(count($acf_fields['videos_bottom'])>0)
      {
        $marker++;
        if($url_exist == false)
        {
          $split_counter +=1;
          $file_xml .= '<url>';
          $file_xml .= '<loc>'.esc_url($link_post).'</loc>';
          $time_mod = explode(" ", $p_item->post_modified);
          $file_xml .= '<lastmod>'. $time_mod[0] .'</lastmod>';
          $url_exist = true;
          $gUrl_exsist = true;
        }
        if($time_mod[0] > $lastmod)
        {
          $lastmod = $time_mod[0];
        }

        foreach ($acf_fields['videos_bottom'] as $acf_video)
        {

          $yt_link = "";
          if(!empty($acf_video['yt'])){
            $yt_link = $acf_video['yt'];
          }
          else if(!empty($acf_video['video_link'])){
            $yt_link = $acf_video['video_link'];
          }
          else if(!empty($acf_fields['video_url'])){
            $yt_link = $acf_fields['video_url'];
          }
          else if(!empty($acf_fields['video_file'])){
            $yt_link = $acf_fields['video_file'];
          }
          else if(!empty($acf_video['link'])){
            $yt_link = $acf_video['link'];
          }
          else
          {
            if(!empty($acf_video['video']))
            {
              $yt_link = $acf_video['video'];
            }
          }
          $yt_link = esc_url($yt_link);
          $youtube = "https://www.youtube.com/oembed?url=". $yt_link ."&format=json";
          $result = wp_remote_get( $youtube, array());

            if(isset($result))
            {
              if($result['response']['code']==200)
              {
                $youtube_content =  json_decode($result['body'], true);
                  
                parse_str( parse_url( $yt_link, PHP_URL_QUERY ), $my_array_of_vars );
                $image = "https://img.youtube.com/vi/". $my_array_of_vars['v'] ."/default.jpg";
                $file_xml .= '<video:video>';
                $file_xml .= "<video:thumbnail_loc>" .$image."</video:thumbnail_loc>";

                $title_vid = $youtube_content["title"];
                $title_vid = str_replace('&', '&#38;', $title_vid);
                $file_xml .= '<video:title>'.$title_vid.'</video:title>';
                $file_xml .= '<video:description>'.$title_vid.'</video:description>';
    
                $file_xml .= '<video:player_loc>'.$yt_link.'</video:player_loc>';
                $file_xml .= '</video:video>';
                    
                }
              } 
          }
      }
    }
    if(!empty($acf_fields['featured_showreel']))
    {
      if(count($acf_fields['featured_showreel'])>0)
      {
        $marker++;
        if($url_exist == false)
        {
          $split_counter +=1;
          $file_xml .= '<url>';
          $file_xml .= '<loc>'.esc_url($link_post).'</loc>';
          $time_mod = explode(" ", $p_item->post_modified);
          $file_xml .= '<lastmod>'. $time_mod[0] .'</lastmod>';
          $url_exist = true;
          $gUrl_exsist = true;
        }
        if($time_mod[0] > $lastmod)
        {
          $lastmod = $time_mod[0];
        }

        foreach ($acf_fields['featured_showreel'] as $acf_video)
        {
          $acf_video = esc_url($acf_video);
          $youtube = "https://www.youtube.com/oembed?url=". $acf_video ."&format=json";
          $result = wp_remote_get( $youtube, array());

            if(isset($result))
            {
              if($result['response']['code']==200)
              {
                $youtube_content =  json_decode($result['body'], true);
                  
                parse_str( parse_url( $acf_video, PHP_URL_QUERY ), $my_array_of_vars );
                $image = "https://img.youtube.com/vi/". $my_array_of_vars['v'] ."/default.jpg";
                $file_xml .= '<video:video>';
                $file_xml .= "<video:thumbnail_loc>" .$image."</video:thumbnail_loc>";

                $title_vid = $youtube_content["title"];
                $title_vid = str_replace('&', '&#38;', $title_vid);
                $file_xml .= '<video:title>'.$title_vid.'</video:title>';
                $file_xml .= '<video:description>'.$title_vid.'</video:description>';
    
                $file_xml .= '<video:player_loc>'.$acf_video.'</video:player_loc>';
                $file_xml .= '</video:video>';
                    
                }
              } 
          }
      }
    }
    if(!empty($acf_fields['showreels']))
    {
      if(count($acf_fields['showreels'])>0)
      {
        $marker++;
        if($url_exist == false)
        {
          $split_counter +=1;
          $file_xml .= '<url>';
          $file_xml .= '<loc>'.esc_url($link_post).'</loc>';
          $time_mod = explode(" ", $p_item->post_modified);
          $file_xml .= '<lastmod>'. $time_mod[0] .'</lastmod>';
          $url_exist = true;
          $gUrl_exsist = true;
        }
        if($time_mod[0] > $lastmod)
        {
          $lastmod = $time_mod[0];
        }

        foreach ($acf_fields['showreels'] as $acf_video)
        {
          $$acf_video['video_link'] = esc_url($$acf_video['video_link']);
          $youtube = "https://www.youtube.com/oembed?url=". $acf_video['video_link'] ."&format=json";
          $result = wp_remote_get( $youtube, array());

            if(isset($result))
            {
              if($result['response']['code']==200)
              {
                $youtube_content =  json_decode($result['body'], true);
                  
                parse_str( parse_url( $acf_video['video_link'], PHP_URL_QUERY ), $my_array_of_vars );
                $image = "https://img.youtube.com/vi/". $my_array_of_vars['v'] ."/default.jpg";
                $file_xml .= '<video:video>';
                $file_xml .= "<video:thumbnail_loc>" .$image."</video:thumbnail_loc>";

                $title_vid = $youtube_content["title"];
                $title_vid = str_replace('&', '&#38;', $title_vid);
                $file_xml .= '<video:title>'.$title_vid.'</video:title>';
                $file_xml .= '<video:description>'.$title_vid.'</video:description>';
    
                $file_xml .= '<video:player_loc>'.$acf_video['video_link'].'</video:player_loc>';
                $file_xml .= '</video:video>';
                    
                }
              } 
          }
      }
    }    
      else
      {
        $yt_link = "";     
        if(!empty($acf_fields['yt'])){
            $yt_link = $acf_fields['yt'];
          }
          else if(!empty($acf_fields['yt_link'])){
            $yt_link = $acf_fields['yt_link'];
          }
          else if(!empty($acf_fields['video_link'])){
            $yt_link = $acf_fields['video_link'];
          }
          else if(!empty($acf_fields['video_url'])){
            $yt_link = $acf_fields['video_url'];
          }
          else if(!empty($acf_fields['video_file'])){
            $yt_link = $acf_fields['video_file'];
          }
          else
          {
            if(!empty($acf_fields['video'])){
              $yt_link = $acf_fields['video'];
            }
          }
          
          if($yt_link != "")
          {
            $yt_link = esc_url($yt_link);
            $marker++;
        if($url_exist == false)
        {
          $split_counter +=1;
          $file_xml .= '<url>';
          $file_xml .= '<loc>'.esc_url($link_post).'</loc>';
          $time_mod = explode(" ", $p_item->post_modified);
          $file_xml .= '<lastmod>'. $time_mod[0] .'</lastmod>';
          $url_exist = true;
          $gUrl_exsist = true;
        }
        if($time_mod[0] > $lastmod)
        {
          $lastmod = $time_mod[0];
        }
          $youtube = "https://www.youtube.com/oembed?url=". $yt_link ."&format=json";
          $result = wp_remote_get( $youtube, array());

            if(isset($result))
            {
              if($result['response']['code']==200)
              {
                $youtube_content =  json_decode($result['body'], true);
                  
                parse_str( parse_url( $yt_link, PHP_URL_QUERY ), $my_array_of_vars );
                $image = "https://img.youtube.com/vi/". $my_array_of_vars['v'] ."/default.jpg";
                $file_xml .= '<video:video>';
                $file_xml .= "<video:thumbnail_loc>" .$image."</video:thumbnail_loc>";

                $title_vid = $youtube_content["title"];
                $title_vid = str_replace('&', '&#38;', $title_vid);
                $file_xml .= '<video:title>'.$title_vid.'</video:title>';
                $file_xml .= '<video:description>'.$title_vid.'</video:description>';
    
                $file_xml .= '<video:player_loc>'.$yt_link.'</video:player_loc>';
                $file_xml .= '</video:video>';
                    
                }
              }
            }
      }
  }
     
      $url_exist = $gUrl_exsist;
      $marker = $gMarker;
      if($c5_xml_length <strlen( $file_xml) )
      {
        $url_exist = true;
        $marker++;
      }
     }
     else
     {
      if (strpos($acf_fields, 'youtube') !== false || strpos($acf_fields, 'vimeo') !== false)
      {
        preg_match_all('~\S*\bwww\.youtube\.com\S*~', $acf_fields, $matches);
        if(count($matches[0])>0)
        {
          $link_post = get_permalink($p_item->ID);
          if($url_exist == false)
          {
            $marker++;
            $url_exist = true;
            $split_counter +=1;
            $file_xml .= '<url>';
            $file_xml .= '<loc>'.esc_url($link_post).'</loc>';
            $time_mod = explode(" ", $p_item->post_modified);
            $file_xml .= '<lastmod>'. $time_mod[0] .'</lastmod>';
            if($time_mod[0] > $lastmod)
            {
              $lastmod = $time_mod[0];
            }
          }
      
          $marker++;
            $ind = 0;
            $length = count($matches[0]);                            
          
            while(true)
            {
                if($ind<$length)
                {
                  $matches[0][$ind] = str_replace('src=', '', $matches[0][$ind]);
                  $matches[0][$ind] = str_replace('"', '', $matches[0][$ind]);            
                  $matches[0][$ind] = str_replace('embed/', 'watch?v=', $matches[0][$ind]);
                  $matches[0][$ind] = str_replace('[embed]', '', $matches[0][$ind]);
                  $matches[0][$ind] = str_replace('[/embed]', '', $matches[0][$ind]);
                  
                  $youtube = "https://www.youtube.com/oembed?url=". $matches[0][$ind] ."&format=json";


                  $result = wp_remote_get( $youtube, array());

                  if(isset($result))
                  {
                    if($result['response']['code']==200)
                    {
                      $youtube_content =  json_decode($result['body'], true);
                      if(isset($youtube_content))
                      {
                        $title_vid = $youtube_content["title"];
                        $title_vid = str_replace('&', '&#38;', $title_vid);
                      }
                      parse_str( parse_url( $matches[0][$ind], PHP_URL_QUERY ), $my_array_of_vars );
                      $image = "https://img.youtube.com/vi/". $my_array_of_vars['v'] ."/default.jpg";
                      $file_xml .= '<video:video>';
                      $file_xml .= "<video:thumbnail_loc>" .$image."</video:thumbnail_loc>";

                      $file_xml .= '<video:title>'.$title_vid.'</video:title>';
                      $file_xml .= '<video:description>'.$title_vid.'</video:description>';
      
                      $file_xml .= '<video:player_loc>'.$matches[0][$ind].'</video:player_loc>';
                      $file_xml .= '</video:video>';
                      $ind = $ind +1;
                    }
                  }  
    
                  
                  
                }
                else
                {
                  break;
                }
            }
        }
      }

     }
    }
    if($url_exist == true)
    {
      $marker++;
      $file_xml .= '</url>';
    }
    if($split_counter == 1000)
        {
          $split_counter_total += $split_counter;
          $split_counter = 0;
          if($marker>0){
            $file_xml .= '</urlset>';
      
            $video_index +=1;
            $name = "sitemap-video-".$video_index.".xml";

            clearstatcache();
            if (file_exists(ABSPATH.'/'.$name)) {
                unlink(ABSPATH.'/'.$name);
            }
      
            click5_ping_sitemap_to_google(site_url().'/'.$name, 'videos');
      

            if (file_put_contents(ABSPATH.'/'.basename($name), $file_xml)) {
              array_push($result_array, array('url' => esc_url(site_url().'/'.basename($name)), 'lastmod' => $lastmod));
            } else {
                return false;
            }
            $file_xml = $file_xml_header;
        } 
        }

  }

  

  if($marker > 0){
      $file_xml .= '</urlset>';
      if($video_index>0)
      {
        $video_index +=1;
        $name = "sitemap-video-".$video_index.".xml";
      }
      clearstatcache();
      if (file_exists(ABSPATH.'/'.$name)) {
          unlink(ABSPATH.'/'.$name);
      }

      click5_ping_sitemap_to_google(site_url().'/'.$name, 'videos');

      if (file_put_contents(ABSPATH.'/'.basename($name), $file_xml)) {
          array_push($result_array, array('url' => esc_url(site_url().'/'.basename($name)), 'lastmod' => $lastmod));
      } else {
          return false;
      }
  } else {
      return false;
  }
  return $result_array;
}


function click5_sitemap_generate_image_list($name) {
  $marker = 0;
  $result_array = array();
  $file_xml_header = '<?xml version="1.0" encoding="UTF-8"?>';
  $file_xml_header .= '<?xml-stylesheet type="text/xsl" href="'.esc_url(plugins_url('', __FILE__)).'/css/front/template.xsl" ?>';
  $file_xml_header .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

  $file_xml = $file_xml_header;
  $lastmod = date('Y-m-d', strtotime('09/14/2000'));


  global $wpdb;
  $ids_vid = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}posts WHERE post_status='publish' and (post_type != 'attachment' and post_type != 'acf-field' and post_type != 'acf-field-group' and post_type != 'revision' 
  and post_type != 'acf' and post_type!='nav_menu_item' and post_type != 'oembed_cache' and post_type !='wpcf7_contact_form' and post_type !='wphb_minify_group') order by id desc", OBJECT );
  
  //$ids_vid = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}posts WHERE id =33297 and post_status='publish' and (post_type != 'attachment' and post_type != 'acf-field' and post_type != 'acf-field-group' and post_type != 'revision' 
 // and post_type != 'acf' and post_type!='nav_menu_item' and post_type != 'oembed_cache' and post_type !='wpcf7_contact_form' and post_type !='wphb_minify_group') order by id desc", OBJECT );
  //33297
  $split_counter = 0;
  $split_counter_total = 0;
  $image_index = 0;
  foreach($ids_vid as $p_item) 
  {

        $current_domain = get_site_url();
        $link_post = get_permalink($p_item->ID);

        if (function_exists('str_contains'))
        {
          if(str_contains($link_post, '?p='))
          {
            continue;
          }
        }
        else
        {
          if (strpos($link_post, '?p=') !== false) {
           
            continue;
          }
        }
        
        $time_mod = explode(" ", $p_item->post_modified);
        $url_exist = false;
        if($time_mod[0] > $lastmod)
        {
          $lastmod = $time_mod[0];
        }
        $pattern = '@src="([^ ]+(\.gif|\.jpg|\.jpeg|\.png))"@';
        
        preg_match_all($pattern, $p_item->post_content, $matches);

        if(count($matches[1])>0){
          $split_counter +=1;
          $url_exist  = true;
          $marker++;
          $file_xml .= '<url>';
          $file_xml .= '<loc>'.esc_url($link_post).'</loc>';
          $file_xml .= '<lastmod>'. $time_mod[0] .'</lastmod>';

          $ind = 0;
          $length = count($matches[1]);

          while(true)
          {
            if($ind<$length)
            {
              if(!strpos($matches[1][$ind],"src=")!==false)
              {
               
                $file_xml .= '<image:image>';  
                if (function_exists('str_contains'))
                {             
                  if(str_contains($matches[1][$ind], $current_domain) == false)
                  {
                    $file_xml .= '<image:loc>'.$current_domain .$matches[1][$ind].'</image:loc>';
                  }
                  else
                  {
                    $file_xml .= '<image:loc>'.$matches[1][$ind].'</image:loc>';
                  }
                }
                else
                {
                  if(strpos($matches[1][$ind], $current_domain) == false)
                  {
                    $file_xml .= '<image:loc>'.$current_domain .$matches[1][$ind].'</image:loc>';
                  }
                  else
                  {
                    $file_xml .= '<image:loc>'.$matches[1][$ind].'</image:loc>';
                  }
                }
                $file_xml .= '</image:image>';
              }   
              $ind = $ind +1;
            }
            else
            {
              break;
            }
          }
       }

       
        $res = explode("ids=\"", $p_item->post_content);
        $res = str_replace("\"]", "", $res);
        if(count($res)>1)
        {
          $gallery_ids = explode(",", $res[1]);
          if(count($gallery_ids)>0)
          {
            foreach($gallery_ids as $val ){
            $post_item = get_post($val);
            
            if(isset($post_item))
            {
            if(strpos($post_item->guid, ".jpg") !== false || strpos($post_item->guid, '.png') !== false || strpos($post_item->guid, '.jpeg') !== false){
              if($url_exist == false)
              {
                $split_counter +=1;
                $file_xml .= '<url>';
                $file_xml .= '<loc>'.esc_url($link_post).'</loc>';
                $time_mod = explode(" ", $p_item->post_modified);
                $file_xml .= '<lastmod>'. $time_mod[0] .'</lastmod>';
                $url_exist  = true;
                $marker++;
              }
              if($p_item->post_modified > $lastmod)
              {
                $lastmod = date('Y-m-d', strtotime($p_item->post_modified));
              }
              $link_post1 = wp_get_attachment_url($post_item->ID);
              $file_xml .= '<image:image>';
              $file_xml .= '<image:loc>'.$link_post1.'</image:loc>';
              $file_xml .= '</image:image>';
              $url_exist = true;
              }
            }
          }
          }
        }

       //get post meta to find thumbinal image       
       $meta_list = get_post_meta($p_item->ID, '', true);

       foreach($meta_list as $key=>$val){
         foreach($val as $vals){
             if (strpos($key, '_thumbnail_id') !== false){
              $post_item = get_post($val[0]);
              if(isset($post_item))
              {
              if(strpos($post_item->guid, ".jpg") !== false || strpos($post_item->guid, '.png') !== false || strpos($post_item->guid, '.jpeg') !== false){
                if($url_exist == false)
                {
                  $split_counter +=1;
                  $file_xml .= '<url>';
                  $file_xml .= '<loc>'.esc_url($link_post).'</loc>';
                  $time_mod = explode(" ", $p_item->post_modified);
                  $file_xml .= '<lastmod>'. $time_mod[0] .'</lastmod>';
                  $url_exist  = true;
                  $marker++;
                }
                if($p_item->post_modified > $lastmod)
                {
                  $lastmod = date('Y-m-d', strtotime($p_item->post_modified));
                }
                $link_post1 = wp_get_attachment_url($post_item->ID);
                $file_xml .= '<image:image>';
                $file_xml .= '<image:loc>'.$link_post1.'</image:loc>';
                $file_xml .= '</image:image>';
                $url_exist = true;
              }
            }
               break;
             }
         }
       }

       if ( class_exists('ACF') )
       {       
        $acf_fields = get_fields($p_item->ID);
        if(is_array($acf_fields))
        {
          $c5_xml_length = strlen( $file_xml);
          $file_xml = click5_get_acf_image_recursive($acf_fields, $p_item, $link_post, $url_exist, $marker, $lastmod, $file_xml);
          if($c5_xml_length <strlen( $file_xml) )
          {
           $url_exist = true;
           $split_counter +=1;
           $file_xml .= click5_get_acf_images($acf_fields, $p_item, $link_post, $url_exist, $marker, $lastmod);
          }
        }
       }
        if($url_exist == true)
        {
          $file_xml .= '</url>';
        }

        if($split_counter == 1000)
        {
          $split_counter_total += $split_counter;
          $split_counter = 0;
          if($marker>0){
            $file_xml .= '</urlset>';
      
            $image_index +=1;
            $name = "sitemap-image-".$image_index.".xml";

            clearstatcache();
            if (file_exists(ABSPATH.'/'.$name)) {
                unlink(ABSPATH.'/'.$name);
            }
      
            click5_ping_sitemap_to_google(site_url().'/'.$name, 'categories');
      

            if (file_put_contents(ABSPATH.'/'.basename($name), $file_xml)) {
              array_push($result_array, array('url' => esc_url(site_url().'/'.basename($name)), 'lastmod' => $lastmod));
            } else {
                return false;
            }
            $file_xml = $file_xml_header;
        } 
        }
  }

  

  if($marker>0){
      $file_xml .= '</urlset>';

      if($image_index>0)
      {
        $image_index +=1;
        $name = "sitemap-image-".$image_index.".xml";
      }

      clearstatcache();
      if (file_exists(ABSPATH.'/'.$name)) {
          unlink(ABSPATH.'/'.$name);
      }

      click5_ping_sitemap_to_google(site_url().'/'.$name, 'categories');

      if (file_put_contents(ABSPATH.'/'.basename($name), $file_xml)) {
          array_push($result_array, array('url' => esc_url(site_url().'/'.basename($name)), 'lastmod' => $lastmod));
      } else {
          return false;
      }
  } else {
      return false;
  }
  return $result_array;
}

function click5_get_acf_video_recursive($acf_fields, $p_item, $link_post, $url_exist, $marker, $lastmod, $file_xml)
{
  global $video_xml;
  global $gUrl_exsist;
  global $gMarker;

  $recursive_return = '';

  foreach ($acf_fields as $acf_val_l1)
  {
    if(is_array($acf_val_l1))
    {
      if(count($acf_val_l1)>0)
      {
        if(isset($acf_val_l1[0]))
        {
        $type_acf = gettype($acf_val_l1[0]);
          if( $type_acf!= "object")
          {
            $recursive_return = click5_get_acf_video_recursive($acf_val_l1, $p_item, $link_post, $url_exist, $marker, $lastmod, $file_xml);
          }
        }
      }
    }
    else
    {
      if(gettype($acf_val_l1)!= "object")
      {
        if (strpos($acf_val_l1, 'youtube') !== false || strpos($acf_val_l1, 'vimeo') !== false)
        {
          preg_match_all('~\S*\bwww\.youtube\.com\S*~', $acf_val_l1, $matches);
          if(count($matches[0])>0)
          {
            $link_post = get_permalink($p_item->ID);
            if($gUrl_exsist == false)
            {
              $marker++;
              $url_exist = true;
              $gUrl_exsist = true;
              $gMarker++;
              $recursive_return .= '<url>';
              $recursive_return .= '<loc>'.esc_url($link_post).'</loc>';
              $time_mod = explode(" ", $p_item->post_modified);
              $recursive_return .= '<lastmod>'. $time_mod[0] .'</lastmod>';
              if($time_mod[0] > $lastmod)
              {
                $lastmod = $time_mod[0];
              }
            }
        
            $marker++;
            
              $gMarker++;
              $ind = 0;
              $length = count($matches[0]);                            
            
              while(true)
              {
                  if($ind<$length)
                  {
                    $matches[0][$ind] = str_replace('src=', '', $matches[0][$ind]);
                    $matches[0][$ind] = str_replace('"', '', $matches[0][$ind]);            
                    $matches[0][$ind] = str_replace('embed/', 'watch?v=', $matches[0][$ind]);
                    $matches[0][$ind] = str_replace('[embed]', '', $matches[0][$ind]);
                    $matches[0][$ind] = str_replace('[/embed]', '', $matches[0][$ind]);
                   
                    $youtube = "https://www.youtube.com/oembed?url=". $matches[0][$ind] ."&format=json";


                    $result = wp_remote_get( $youtube, array());

                    if(isset($result))
                    {
                      if($result['response']['code']==200)
                      {
                        $youtube_content =  json_decode($result['body'], true);
                        $title_vid ="";
                        if(isset($youtube_content))
                        {
                          $title_vid = $youtube_content["title"];
                          $title_vid = str_replace('&', '&#38;', $title_vid);
                        }
                        //$recursive_return .= '<video:title>'.$title_vid.'</video:title>';
                        parse_str( parse_url( $matches[0][$ind], PHP_URL_QUERY ), $my_array_of_vars );
                        
                        $image = "https://img.youtube.com/vi/". $my_array_of_vars['v'] ."/default.jpg";
                        $recursive_return .= '<video:video>';
                        $recursive_return .= "<video:thumbnail_loc>" .$image."</video:thumbnail_loc>";

                       // $title_vid = $youtube_content["title"];
                        //$title_vid = str_replace('&', '&#38;', $title_vid);
                        $recursive_return .= '<video:title>'.$title_vid.'</video:title>';
                        $recursive_return .= '<video:description>'.$title_vid.'</video:description>';
                        //$file_xml .= '<video:content_loc>'.$title_vid.'</video:content_loc>';
          
                        $recursive_return .= '<video:player_loc>'.$matches[0][$ind].'</video:player_loc>';
                        $recursive_return .= '</video:video>';
                      
                      }
                    }  
                    
                    $ind = $ind +1;
                    
                  }
                  else
                  {
                    $video_xml.= $recursive_return;
                    break;
                  }
              }
          }
        }
      }
    }
  }

  return $recursive_return;
}

function click5_get_acf_image_recursive($acf_fields, $p_item, $link_post, $url_exist, $marker, $lastmod, $file_xml)
{
  foreach ($acf_fields as $acf_val_l1)
  {
    if(is_array($acf_val_l1))
    {
      if(count($acf_val_l1)>0)
      {
        if(isset($acf_val_l1[0]))
        {
          $type_acf = gettype($acf_val_l1[0]);
          if( $type_acf!= "object")
          {
            click5_get_acf_image_recursive($acf_val_l1, $p_item, $link_post, $url_exist, $marker, $lastmod, $file_xml);
          }
        }
      }
    }
    else
    {
      if(is_numeric($acf_val_l1))
      {
        $post_item = get_post($acf_val_l1);
        if( isset($post_item))
        {
          if(strpos($post_item->guid, ".jpg") !== false || strpos($post_item->guid, '.png') !== false || strpos($post_item->guid, '.jpeg') !== false)
          {
            if($url_exist == false)
            {
              $file_xml .= '<url>';
              $file_xml .= '<loc>'.esc_url($link_post).'</loc>';
              $time_mod = explode(" ", $p_item->post_modified);
              $file_xml .= '<lastmod>'. $time_mod[0] .'</lastmod>';
              $url_exist  = true;
              $marker++;
            }
            if($p_item->post_modified > $lastmod)
            {
              $lastmod = date('Y-m-d', strtotime($p_item->post_modified));
            }
            $link_post1 = wp_get_attachment_url($post_item->ID);
            $file_xml .= '<image:image>';
            $file_xml .= '<image:loc>'.$link_post1.'</image:loc>';
            $file_xml .= '</image:image>';
            $url_exist = true;
          }
        }
      }
      else if ((strpos($acf_val_l1, ".jpg") !== false || strpos($acf_val_l1, '.png') !== false || strpos($acf_val_l1, '.jpeg') !== false) && strpos($acf_val_l1,'ttp')!== false)
      {
        if($url_exist == false)
        {
          $file_xml .= '<url>';
          $file_xml .= '<loc>'.esc_url($link_post).'</loc>';
          $time_mod = explode(" ", $p_item->post_modified);
          $file_xml .= '<lastmod>'. $time_mod[0] .'</lastmod>';
          $url_exist  = true;
          $marker++;
        }
        if($p_item->post_modified > $lastmod)
        {
          $lastmod = date('Y-m-d', strtotime($p_item->post_modified));
        }
        $file_xml .= '<image:image>';

        //get src from $acf_val_l1
        $pattern = '@src="([^ ]+(\.gif|\.jpg|\.jpeg|\.png))"@';
        
        preg_match_all($pattern, $acf_val_l1, $matches);
        $current_domain = get_site_url();
        $split_counter=0;
          if(count($matches[1])>0){
          $split_counter +=1;
          $url_exist  = true;
          $marker++;

          $ind = 0;
          $length = count($matches[1]);
          $acf_val_l1_new = '';
          while(true)
          {
            if($ind<$length)
            {
              if(!strpos($matches[1][$ind],"src=")!==false)
              {
               
               
                if (function_exists('str_contains'))
                {             
                  if(str_contains($matches[1][$ind], $current_domain) == false)
                  {
                    $acf_val_l1_new .= '<image:loc>'.$current_domain .$matches[1][$ind].'</image:loc>';
                  }
                  else
                  {
                    $acf_val_l1_new.= '<image:loc>'.$matches[1][$ind].'</image:loc>';
                  }
                }
                else
                {
                  if(strpos($matches[1][$ind], $current_domain) == false)
                  {
                    $acf_val_l1_new .= '<image:loc>'.$current_domain .$matches[1][$ind].'</image:loc>';
                  }
                  else
                  {
                    $acf_val_l1_new .= '<image:loc>'.$matches[1][$ind].'</image:loc>';
                  }
                }
                
              }   
              $ind = $ind +1;
            }
            else
            {
              break;
            }
          }
       }

        $file_xml .= '<image:loc>'.$acf_val_l1_new.'</image:loc>';
        $file_xml .= '</image:image>';
        $url_exist = true;
      }
    }
  }

  return $file_xml;

}

function click5_get_acf_images($acf_fields, $p_item, $link_post, $url_exist, $marker, $lastmod)
{
  $file_xml = "";
  foreach ($acf_fields as $acf_val_l1)
  {
    if(is_array($acf_val_l1))
    {
      if(count($acf_val_l1)>0)
      {
        $type_acf = gettype($acf_val_l1[0]);
        if( $type_acf!= "object")
        {
          
          foreach ($acf_val_l1 as $acf_val_l2)
          {
            if(is_array($acf_val_l2))
            {
              if(count($acf_val_l2)>0)
              {
                foreach ($acf_val_l2 as $acf_val_l3)
                {
                  if(is_numeric($acf_val_l3))
                  {
                    $post_item = get_post($acf_val_l3);
                    if( isset($post_item))
                    {
                      if(strpos($post_item->guid, ".jpg") !== false || strpos($post_item->guid, '.png') !== false || strpos($post_item->guid, '.jpeg') !== false)
                      {
                        if($p_item->post_modified > $lastmod)
                        {
                          $lastmod = date('Y-m-d', strtotime($p_item->post_modified));
                        }
                        $link_post1 = wp_get_attachment_url($post_item->ID);
                        $file_xml .= '<image:image>';
                        $file_xml .= '<image:loc>'.$link_post1.'</image:loc>';
                        $file_xml .= '</image:image>';
                        $url_exist = true;
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }
  return $file_xml;

}

function click5_sitemap_generate_doc_list($name) {
    if (file_exists(ABSPATH.'/sitemap-' . $name . '.xml')) {
      unlink(ABSPATH.'/sitemap-' . $name . '.xml');
    }
    $marker_array = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20);
    foreach($marker_array as $marker_item) {
      if (file_exists(ABSPATH.'/sitemap-' . $name . '-'. $marker_item . '.xml')) {
        unlink(ABSPATH.'/sitemap-' . $name . '-'. $marker_item . '.xml');
      }
    }
    

    $lastmod = date('Y-m-d', strtotime('09/14/2000'));

    $ids_pdf = get_posts(array(
        'post_type' => 'attachment',
        'post_mime_type' => 'application/pdf',
        'fields'          => 'ids',
        'posts_per_page'  => -1
    ));

    $ids_doc = get_posts(array(
        'post_type' => 'attachment',
        'post_mime_type' => 'application/msword',
        'fields'          => 'ids',
        'posts_per_page'  => -1
    ));

    $ids_docx = get_posts(array(
        'post_type' => 'attachment',
        'post_mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'fields'          => 'ids',
        'posts_per_page'  => -1
    ));

    $doc_list = array();
    $result_list = array();

    if($ids_pdf || $ids_doc || $ids_docx){
        if($ids_pdf) {
            foreach($ids_pdf as $id_pdf) {
                $pdf = get_post($id_pdf);
                $time_mod = explode(" ", $pdf->post_modified);
                $file_xml_list = '';
                $file_xml_list .= '<url>';
                $file_xml_list .= '<loc>'.$pdf->guid.'</loc>';
                $file_xml_list .= '<lastmod>'. $time_mod[0] .'</lastmod>';
                if($time_mod[0] > $lastmod)
                {
                  $lastmod = $time_mod[0];
                }
                $file_xml_list .= '</url>';
                array_push($doc_list, $file_xml_list); 
            }
        }

        if($ids_doc) {
            foreach($ids_doc as $id_doc) {

                $doc = get_post($id_doc);
                $time_mod = explode(" ", $doc->post_modified);
                $file_xml_list = '';
                $file_xml_list .= '<url>';
                $file_xml_list .= '<loc>'.$doc->guid.'</loc>';
                $file_xml_list .= '<lastmod>'. $time_mod[0] .'</lastmod>';
                if($time_mod[0] > $lastmod)
                {
                  $lastmod = $time_mod[0];
                }
                $file_xml_list .= '</url>';
                array_push($doc_list, $file_xml_list); 

            }
        }

        if($ids_docx) {
            foreach($ids_docx as $id_docx) {
                $docx = get_post($id_docx);
                $time_mod = explode(" ", $docx->post_modified);
                $file_xml_list = '';
                $file_xml_list .= '<url>';
                $file_xml_list .= '<loc>'.$docx->guid.'</loc>';
                $file_xml_list .= '<lastmod>'. $time_mod[0] .'</lastmod>';
                if($time_mod[0] > $lastmod)
                {
                  $lastmod = $time_mod[0];
                }
                $file_xml_list .= '</url>';
                array_push($doc_list, $file_xml_list); 

            }
        }

        $multiple_arrarys = array_chunk($doc_list, 1000, true);
        foreach ($multiple_arrarys as $key => $value) {
          if(count($multiple_arrarys) == 1) { 
            $file_name = 'sitemap-' . $name . '.xml';
          } else {
            $index_file = 1 + $key;
            $file_name = 'sitemap-' . $name . '-' . $index_file . '.xml';
          }
          $file_xml = '<?xml version="1.0" encoding="UTF-8"?>';
          $file_xml .= '<?xml-stylesheet type="text/xsl" href="'.esc_url(plugins_url('', __FILE__)).'/css/front/template.xsl" ?>';

          $file_xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
          foreach($value as $v) {
            $file_xml .= $v;
          }

          
      
          $file_xml .= '</urlset>';
          clearstatcache();
          if (file_exists(ABSPATH.'/'.$file_name)) {
            unlink(ABSPATH.'/'.$file_name);
          }
    
          click5_ping_sitemap_to_google(site_url().'/'.$file_name, $file_name);
    
          if (file_put_contents(ABSPATH.'/'.basename($file_name), $file_xml)) {
            array_push($result_list, array('url' => esc_url(site_url().'/'.basename($file_name)), 'lastmod' => $lastmod));
          }
        }  
        
    
        return $result_list;
    }
}

function click5_sitemap_generate_category_list($name) {
  if (file_exists(ABSPATH.'/sitemap-category-index.xml')) {
    unlink(ABSPATH.'/sitemap-category-index.xml');
  }
  $marker_array = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20);
  foreach($marker_array as $marker_item) {
    if (file_exists(ABSPATH.'/sitemap-category-index-'. $marker_item . '.xml')) {
      unlink(ABSPATH.'/sitemap-category-index-'. $marker_item . '.xml');
    }
  }
  $category_list = array();
  $result_list = array();

  $lastmod = date('Y-m-d');

  $categories = get_categories();

  if($categories){
    foreach($categories as $category) {

      $permalink = get_category_link($category->term_id);
      if(function_exists("str_contains"))
      {
        if(str_contains($permalink, './'))
        {
            if(function_exists("str_replace")){
              $permalink = str_replace('./','',$permalink );
            }
        }
      }
      $file_xml_list = '';
      $file_xml_list .= '<url>';
      $file_xml_list .= '<loc>'.$permalink.'</loc>';
      $file_xml_list .= '<lastmod>'.esc_attr($lastmod).'</lastmod>';     
      $file_xml_list .= '</url>';
      array_push($category_list, $file_xml_list);
    }
    $multiple_arrarys = array_chunk($category_list, 1000, true);
    foreach ($multiple_arrarys as $key => $value) {
      if(count($multiple_arrarys) == 1) { 
        $file_name = 'sitemap-category-index.xml';
      } else {
        $index_file = 1 + $key;
        $file_name = 'sitemap-category-index-' . $index_file . '.xml';
      }
      $file_xml = '<?xml version="1.0" encoding="UTF-8"?>';
      $file_xml .= '<?xml-stylesheet type="text/xsl" href="'.esc_url(plugins_url('', __FILE__)).'/css/front/template.xsl" ?>';

      $file_xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
      foreach($value as $v) {
        $file_xml .= $v;
      }

      
      $file_xml .= '</urlset>';

      clearstatcache();
      if (file_exists(ABSPATH.'/'.$file_name)) {
        unlink(ABSPATH.'/'.$file_name);
      }

      click5_ping_sitemap_to_google(site_url().'/'.$file_name, 'categories');

      if (file_put_contents(ABSPATH.'/'.basename($file_name), $file_xml)) {
        array_push($result_list, array('url' => esc_url(site_url().'/'.basename($file_name)), 'lastmod' => $lastmod));
      }

    }
    return $result_list;
  } else {
    update_option('click5_sitemap_seo_xml_categories', false);
  }
}

function click5_sitemap_generate_tag_list($name) {
  if (file_exists(ABSPATH.'/sitemap-tag.xml')) {
    unlink(ABSPATH.'/sitemap-tag.xml');
  }
  $marker_array = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20);
  foreach($marker_array as $marker_item) {
    if (file_exists(ABSPATH.'/sitemap-tag-'. $marker_item . '.xml')) {
      unlink(ABSPATH.'/sitemap-tag-'. $marker_item . '.xml');
    }
  }
  $tag_list = array();
  $result_list = array();

  $lastmod = date('Y-m-d');
  $tag_list_array = get_tags();

  if($tag_list_array){

    foreach($tag_list_array as $tag_item) {

      $permalink = get_tag_link($tag_item->term_id);
      $file_xml_list = '';
      $file_xml_list .= '<url>';
      $file_xml_list .= '<loc>'.$permalink.'</loc>';
      $file_xml_list .= '<lastmod>'.esc_attr($lastmod).'</lastmod>';
      $file_xml_list .= '</url>';
      array_push($tag_list, $file_xml_list);
    }
    $multiple_arrarys = array_chunk($tag_list, 1000, true);
    foreach ($multiple_arrarys as $key => $value) {
      if(count($multiple_arrarys) == 1) { 
        $file_name = 'sitemap-tag.xml';
      } else {
        $index_file = 1 + $key;
        $file_name = 'sitemap-tag-index-' . $index_file . '.xml';
      }
      $file_xml = '<?xml version="1.0" encoding="UTF-8"?>';
      $file_xml .= '<?xml-stylesheet type="text/xsl" href="'.esc_url(plugins_url('', __FILE__)).'/css/front/template.xsl" ?>';

      $file_xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
      foreach($value as $v) {
        $file_xml .= $v;
      }

      $file_xml .='</urlset>';
      
      clearstatcache();
      if (file_exists(ABSPATH.'/'.$file_name)) {
        unlink(ABSPATH.'/'.$file_name);
      }

      click5_ping_sitemap_to_google(site_url().'/'.$file_name, 'tags');

      if (file_put_contents(ABSPATH.'/'.basename($file_name), $file_xml)) {
        array_push($result_list, array('url' => esc_url(site_url().'/'.basename($file_name)), 'lastmod' => $lastmod));
      }

    }
    return $result_list;
  }
}

function click5_sitemap_generate_author_list($name) {
  if (file_exists(ABSPATH.'/sitemap-author.xml')) {
    unlink(ABSPATH.'/sitemap-author.xml');
  }
  $marker_array = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20);
  foreach($marker_array as $marker_item) {
    if (file_exists(ABSPATH.'/sitemap-author-'. $marker_item . '.xml')) {
      unlink(ABSPATH.'/sitemap-author-'. $marker_item . '.xml');
    }
  }
  $author_list = array();
  $result_list = array();

  $post_types = get_post_types(array('public' => true), 'names');
  
  //$lastmod = date('Y-m-d');
  $lastmod = date('Y-m-d', strtotime('09/14/2000'));
  $users = get_users();

  if ( ! empty( $users ) ) {
      foreach( $users as $user ) {

        if ( count_user_posts( $user->ID, $post_types ) >= 1 ) {

          $permalink = get_author_posts_url($user->ID);
          if($user->user_registered === null || empty($user->user_registered)) {
            $newDate = date("Y-m-d", new DateTime('now'));  
           } else {
            $newDate = date("Y-m-d", strtotime($user->user_registered));
          }
          $file_xml_list = '';
          $file_xml_list .= '<url>';
          $file_xml_list .= '<loc>'.$permalink.'</loc>';
          $file_xml_list .= '<lastmod>'.esc_attr($newDate).'</lastmod>';
          if($newDate > $lastmod)
            {
              $lastmod = $newDate;
            }
          $file_xml_list .= '</url>';
          array_push($author_list, $file_xml_list);  
        }
      }
      //-----------------------------------------------------------------------------
      $multiple_arrarys = array_chunk($author_list, 1000, true);
      foreach ($multiple_arrarys as $key => $value) {
        if(count($multiple_arrarys) == 1) { 
          $file_name = 'sitemap-author.xml';
        } else {
          $index_file = 1 + $key;
          $file_name = 'sitemap-author-' . $index_file . '.xml';
        }
        $file_xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $file_xml .= '<?xml-stylesheet type="text/xsl" href="'.esc_url(plugins_url('', __FILE__)).'/css/front/template.xsl" ?>';
      
        $file_xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach($value as $v) {
          $file_xml .= $v;
        }

          
          $file_xml .= '</urlset>';

        clearstatcache();
        if (file_exists(ABSPATH.'/'.$file_name)) {
          unlink(ABSPATH.'/'.$file_name);
        }

        click5_ping_sitemap_to_google(site_url().'/'.$file_name, 'authors');

        if (file_put_contents(ABSPATH.'/'.basename($file_name), $file_xml)) {
          array_push($result_list, array('url' => esc_url(site_url().'/'.basename($file_name)), 'lastmod' => $lastmod));
        } else {
          //return false;
        }

      }

      return $result_list;
  } else {
    //update_option('click5_sitemap_seo_xml_tags', false);
  }
      //---------------------------------------------------------------------------- 
}
/*
function click5_sitemap_generate_category_list($name) {
  
  $file_xml = '<?xml version="1.0" encoding="UTF-8"?>';
  $file_xml .= '<?xml-stylesheet type="text/xsl" href="'.esc_url(plugins_url('', __FILE__)).'/css/front/template.xsl" ?>';

  $file_xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

  $lastmod = date('Y-m-d');

  $categories = get_categories();

  if($categories){
    foreach($categories as $category) {

      $permalink = get_category_link($category->term_id);

      $file_xml .= '<url>';
      $file_xml .= '<loc>'.$permalink.'</loc>';
      $file_xml .= '<lastmod>'.esc_attr($lastmod).'</lastmod>';     
      $file_xml .= '</url>';

    }

    $file_xml .= '</urlset>';

    clearstatcache();
    if (file_exists(ABSPATH.'/'.$name)) {
      unlink(ABSPATH.'/'.$name);
    }

    click5_ping_sitemap_to_google(site_url().'/'.$name, 'categories');

    if (file_put_contents(ABSPATH.'/'.basename($name), $file_xml)) {
      return array('url' => esc_url(site_url().'/'.basename($name)), 'lastmod' => $lastmod);
    } else {
      return false;
    }
  } else {
    update_option('click5_sitemap_seo_xml_categories', false);
  }
}*/
/*

function click5_sitemap_generate_tag_list($name) {
  
  $file_xml = '<?xml version="1.0" encoding="UTF-8"?>';
  $file_xml .= '<?xml-stylesheet type="text/xsl" href="'.esc_url(plugins_url('', __FILE__)).'/css/front/template.xsl" ?>';

  $file_xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

  $lastmod = date('Y-m-d');

  $tag_list = get_tags();

  if($tag_list){

    foreach($tag_list as $tag_item) {

      $permalink = get_tag_link($tag_item->term_id);

      $file_xml .= '<url>';
      $file_xml .= '<loc>'.$permalink.'</loc>';
      $file_xml .= '<lastmod>'.esc_attr($lastmod).'</lastmod>';
      $file_xml .= '</url>';

    }

    $file_xml .= '</urlset>';

    clearstatcache();
    if (file_exists(ABSPATH.'/'.$name)) {
      unlink(ABSPATH.'/'.$name);
    }

    click5_ping_sitemap_to_google(site_url().'/'.$name, 'tags');

    if (file_put_contents(ABSPATH.'/'.basename($name), $file_xml)) {
      return array('url' => esc_url(site_url().'/'.basename($name)), 'lastmod' => $lastmod);
    } else {
      return false;
    }

  } else {
    //update_option('click5_sitemap_seo_xml_tags', false);
  }
}

*/
function click5_sitemap_generate_custom_taxonomy_list($name, $cpt) {
  if (file_exists(ABSPATH.'/sitemap-' . $name . '.xml')) {
    unlink(ABSPATH.'/sitemap-' . $name . '.xml');
  }
  $marker_array = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20);
  foreach($marker_array as $marker_item) {
    if (file_exists(ABSPATH.'/sitemap-' . $name . '-'. $marker_item . '.xml')) {
      unlink(ABSPATH.'/sitemap-' . $name . '-'. $marker_item . '.xml');
    }
  }
  $tax_list = array();
  $result_list = array();

  $lastmod = date('Y-m-d');

  $taxonomy_objects = get_terms($cpt);

  if($taxonomy_objects) {
    
    foreach ( $taxonomy_objects as $term) {
      $termlink = get_term_link ( $term );      
      if( strpos($termlink, '?') === false ){
        $file_xml_list = '';
        $file_xml_list .= '<url>';
        $file_xml_list .= '<loc>'.$termlink.'</loc>';
        $file_xml_list .= '<lastmod>'.esc_attr($lastmod).'</lastmod>';
        $file_xml_list .= '</url>';
        array_push($tax_list, $file_xml_list); 
      } 
    }
    $multiple_arrarys = array_chunk($tax_list, 1000, true);
    foreach ($multiple_arrarys as $key => $value) {
      if(count($multiple_arrarys) == 1) { 
        $file_name = 'sitemap-' . $name . '.xml';
      } else {
        $index_file = 1 + $key;
        $file_name = 'sitemap-' . $name . '-' . $index_file . '.xml';
      }
      $file_xml = '<?xml version="1.0" encoding="UTF-8"?>';
      $file_xml .= '<?xml-stylesheet type="text/xsl" href="'.esc_url(plugins_url('', __FILE__)).'/css/front/template.xsl" ?>';
      $file_xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
      foreach($value as $v) {
        $file_xml .= $v;
      }
      
      $file_xml .= '</urlset>';
      clearstatcache();
      if (file_exists(ABSPATH.'/'.$file_name)) {
        unlink(ABSPATH.'/'.$file_name);
      }

      click5_ping_sitemap_to_google(site_url().'/'.$file_name, 'tax_'.$file_name);

      if (file_put_contents(ABSPATH.'/'.basename($file_name), $file_xml)) {
        array_push($result_list, array('url' => esc_url(site_url().'/'.basename($file_name)), 'lastmod' => $lastmod));
      } else {
        //return false;
      }
    }  
    

    return $result_list;
  }
}
/*
function click5_sitemap_generate_custom_taxonomy_list($name, $cpt) {
  
  $file_xml = '<?xml version="1.0" encoding="UTF-8"?>';
  $file_xml .= '<?xml-stylesheet type="text/xsl" href="'.esc_url(plugins_url('', __FILE__)).'/css/front/template.xsl" ?>';
  $file_xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
  $lastmod = date('Y-m-d');

  $taxonomy_objects = get_terms($cpt);

  if($taxonomy_objects) {
    
    foreach ( $taxonomy_objects as $term) {
  
      $termlink = get_term_link ( $term );
        
      if( strpos($termlink, '?') === false ){
        $file_xml .= '<url>';
        $file_xml .= '<loc>'.$termlink.'</loc>';
        $file_xml .= '<lastmod>'.esc_attr($lastmod).'</lastmod>';
        $file_xml .= '</url>';
      }
  
    }

    $file_xml .= '</urlset>';

    clearstatcache();
    if (file_exists(ABSPATH.'/'.$name)) {
      unlink(ABSPATH.'/'.$name);
    }

    click5_ping_sitemap_to_google(site_url().'/'.$name, 'tax_'.$name);

    if (file_put_contents(ABSPATH.'/'.basename($name), $file_xml)) {
      return array('url' => esc_url(site_url().'/'.basename($name)), 'lastmod' => $lastmod);
    } else {
      return false;
    }
  } else {

    update_option('click5_sitemap_seo_xml_'.$cpt.'_tax', false);
    
  }
}*

/*
function click5_sitemap_generate_author_list($name) {

  $post_types = get_post_types(array('public' => true), 'names');
  
  $file_xml = '<?xml version="1.0" encoding="UTF-8"?>';
  $file_xml .= '<?xml-stylesheet type="text/xsl" href="'.esc_url(plugins_url('', __FILE__)).'/css/front/template.xsl" ?>';

  $file_xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

  //$lastmod = date('Y-m-d');
  $lastmod = date('Y-m-d', strtotime('09/14/2000'));
  $users = get_users();

  if ( ! empty( $users ) ) {
      foreach( $users as $user ) {

        if ( count_user_posts( $user->ID, $post_types ) >= 1 ) {

          $permalink = get_author_posts_url($user->ID);
          if($user->user_registered === null || empty($user->user_registered)) {
            $newDate = date("Y-m-d", new DateTime('now'));  
           } else {
            $newDate = date("Y-m-d", strtotime($user->user_registered));
          }
          $file_xml .= '<url>';
          $file_xml .= '<loc>'.$permalink.'</loc>';
          $file_xml .= '<lastmod>'.esc_attr($newDate).'</lastmod>';
          if($newDate > $lastmod)
            {
              $lastmod = $newDate;
            }
          $file_xml .= '</url>';

        }
      }
  }

  $file_xml .= '</urlset>';

  clearstatcache();
  if (file_exists(ABSPATH.'/'.$name)) {
    unlink(ABSPATH.'/'.$name);
  }

  click5_ping_sitemap_to_google(site_url().'/'.$name, 'authors');

  if (file_put_contents(ABSPATH.'/'.basename($name), $file_xml)) {
    return array('url' => esc_url(site_url().'/'.basename($name)), 'lastmod' => $lastmod);
  } else {
    return false;
  }
  
}
*/

function click5_sitemap_generate_CreateRootXML($name, $items) {
  $file_xml = '<?xml version="1.0" encoding="UTF-8"?>';
  $file_xml .= '<?xml-stylesheet type="text/xsl" href="'.esc_url(plugins_url('', __FILE__)).'/css/front/index-template.xsl" ?>';

  $file_xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

  //$lastmod = date('Y-m-d');
  $lastmod = date('Y-m-d', strtotime('09/14/2000'));

  foreach($items as $page) {
    if($page){
      $file_xml .= '<sitemap>';
      $file_xml .= '<loc>'.esc_url($page['url']).'</loc>';
      $file_xml .= '<lastmod>'.esc_attr($page['lastmod']).'</lastmod>';
  
      if($page['lastmod'] > $lastmod)
      {
        $lastmod = $page['lastmod'];
      }
      $file_xml .= '</sitemap>';
    }
  }

  $file_xml .= '</sitemapindex>';

  clearstatcache();
  if (file_exists(ABSPATH.'/'.$name)) {
    unlink(ABSPATH.'/'.$name);
  }

  click5_ping_sitemap_to_google(site_url().'/'.$name, 'root');

  if (file_put_contents(ABSPATH.'/'.basename($name), $file_xml)) {
    return array('url' => esc_url(site_url().'/'.basename($name)), 'lastmod' => $lastmod);
  } else {
    return false;
  }
}

function click5_sitemap_pluralName($name) {
  return (substr($name, -1) == 's') ? $name : $name.'s';
}


function click5_sitemap_generate_sitemap_XML_DoWork() {
  
  try
  {
    if(boolval(esc_attr(get_option('click5_sitemap_seo_robots_txt'))) || boolval(esc_attr( get_option('click5_sitemap_seo_sitemap_xml')))) {
      if(!is_writable(ABSPATH)) {  
        if(boolval(esc_attr(get_option('click5_sitemap_seo_robots_txt'))) && boolval(esc_attr( get_option('click5_sitemap_seo_sitemap_xml')))) { 
          return '407';
        } elseif (boolval(esc_attr(get_option('click5_sitemap_seo_robots_txt'))) && !boolval(esc_attr( get_option('click5_sitemap_seo_sitemap_xml')))) { 
          return '405';
        } elseif (!boolval(esc_attr(get_option('click5_sitemap_seo_robots_txt'))) && boolval(esc_attr( get_option('click5_sitemap_seo_sitemap_xml')))) { 
          return '406';
        }
      }
    }

    click5_sitemap_generate_delete_existing_sitemaps();

    
    if(boolval(esc_attr( get_option('click5_sitemap_seo_sitemap_xml')))){

      $sitemap_type = esc_attr( get_option('click5_sitemap_seo_sitemap_type'));
      $post_types = array();
      $_post_types = click5_sitemap_get_post_types();
      foreach($_post_types as $post_type) {
        if(boolval(esc_attr( get_option('click5_sitemap_seo_post_type_'.$post_type)))) {
          $post_types[] = $post_type;
        }
      }
      if ($sitemap_type == 'splitted') {
        $sitemapsCreated = array();
        foreach($post_types as $post_type) {
          $pages = get_posts(array('post_status' => 'publish', 'numberposts' => -1, 'post_type' => $post_type));
          $multiple_arrarys = array_chunk($pages, 1000, true);
          foreach ($multiple_arrarys as $key => $value) {
            if(count($multiple_arrarys) == 1) {
              $file_name = 'sitemap-'.$post_type.'.xml';
            } else {
              $part_name = $key + 1;
              $file_name = 'sitemap-'.$post_type. '-'. $part_name .'.xml';
            }
            $result = click5_sitemap_generate_CreateXML($file_name, $value, click5_sitemap_getCustomUrlsXML($post_type));
            if (is_array($result)) {
              $sitemapsCreated[] = $result;
            }
          }
        }

        if(boolval(esc_attr( get_option('click5_sitemap_seo_xml_videos')))){
          $name1 = 'sitemap-video.xml';
          $post_types = click5_sitemap_get_post_types();
          foreach($post_types as $single_type) {
            if($single_type == 'video' || $single_type == 'Video' || $single_type == 'VIDEO' )
            {
              $name1 = 'sitemap-video-index.xml';
              break;
            }
          }
          
            $file_list = click5_sitemap_generate_video_list($name1);
            if(isset($file_list))
            {
              if(is_array($file_list))
              {
                foreach($file_list as $item) {
                  $sitemapsCreated[] = $item;
                }
              }
            }
      }

        if(boolval(esc_attr( get_option('click5_sitemap_seo_xml_images')))){
          $name1 = 'sitemap-image.xml';
          $post_types = click5_sitemap_get_post_types();
          foreach($post_types as $single_type) {
            if($single_type == 'image' || $single_type == 'Image' || $single_type == 'IMAGE' )
            {
              $name1 = 'sitemap-image-index.xml';
              break;
            }
          }
          $file_list = click5_sitemap_generate_image_list($name1);
          if(isset($file_list))
          {
            if(is_array($file_list))
            {
              foreach($file_list as $item) {
                $sitemapsCreated[] = $item;
              }
            }
          }
      }

         

          if(boolval(esc_attr( get_option('click5_sitemap_seo_xml_docs')))){
            $file_list = click5_sitemap_generate_doc_list('doc');
            if(isset($file_list))
            {
              if(is_array($file_list))
              {
                foreach($file_list as $item) {
                  $sitemapsCreated[] = $item;
                }
              }
            }
          }

        if(boolval(esc_attr( get_option('click5_sitemap_seo_xml_categories')))){
          $name1 = 'sitemap-category.xml';
          $post_types = click5_sitemap_get_post_types();
          foreach($post_types as $single_type) {
            if($single_type == 'category' || $single_type == 'Category' || $single_type == 'CATEGORY')
            {
              $name1 = 'sitemap-category-index.xml';
              break;
            }
          }
          $file_list = click5_sitemap_generate_category_list($name1);
          if(isset($file_list))
          {
            if(is_array($file_list))
            {
              foreach($file_list as $item) {
                $sitemapsCreated[] = $item;
              }
            }
          }
        }

        if(boolval(esc_attr( get_option('click5_sitemap_seo_xml_tags')))){
          $file_list = click5_sitemap_generate_tag_list('sitemap-tag.xml');
          if(isset($file_list))
          {
            if(is_array($file_list))
            {
              foreach($file_list as $item) {
                $sitemapsCreated[] = $item;
              }
            }
          }
        }

      


        $cpt_args = array(
          'public'   => true,
          '_builtin' => false,
        );
        $cpt_output = 'names';
        $cpt_operator = 'and';
        $cpt_types = get_taxonomies( $cpt_args, $cpt_output, $cpt_operator ); 
        
        foreach ( $cpt_types  as $cpt_type ) { 

          if(boolval(esc_attr( get_option('click5_sitemap_seo_xml_'.$cpt_type.'_tax')))){
      
            $file_list = click5_sitemap_generate_custom_taxonomy_list($cpt_type, $cpt_type);

            if(isset($file_list))
          {
            if(is_array($file_list))
            {
              foreach($file_list as $item) {
                $sitemapsCreated[] = $item;
              }
            }
          }

          }
        
        }


        if(boolval(esc_attr( get_option('click5_sitemap_seo_xml_authors')))){
          $name1 = 'sitemap-author.xml';
          $post_types = click5_sitemap_get_post_types();
          foreach($post_types as $single_type) {
            if($single_type == 'author' || $single_type == 'Author' || $single_type == 'AUTHOR' )
            {
              $name1 = 'sitemap-author-index.xml';
              break;
            }
          }

          $file_list = click5_sitemap_generate_author_list($name1);
          if(isset($file_list))
          {
            if(is_array($file_list))
            {
              foreach($file_list as $item) {
                $sitemapsCreated[] = $item;
              }
            }
          }
        }

        $customCategoriesXML = click5_sitemap_getCustomCategoriesCustomUrlsXML();
        sort($customCategoriesXML);
        foreach($customCategoriesXML as $custom_category) {

          if(boolval(esc_attr( get_option('click5_sitemap_seo_xml_'.str_replace(' ', '_', $custom_category).'_custom')))){

            $subname = strtolower(str_replace(' ', '-', $custom_category));
            $result = click5_sitemap_generate_CreateXML('sitemap-'.$subname.'.xml', array(), click5_sitemap_getCustomUrlsXML($custom_category));
            if (is_array($result)) {
              $sitemapsCreated[] = $result;
            }

          }

          
        }
        click5_sitemap_generate_CreateRootXML('sitemap-index.xml', $sitemapsCreated);
      } else if ($sitemap_type == 'one_file') {
        $pages = get_posts(array('post_status' => 'publish', 'numberposts' => -1, 'post_type' => $post_types));
        $custom_urls_list = click5_sitemap_getCustomUrlsXML();
        click5_sitemap_generate_CreateXML('sitemap-index.xml', $pages, $custom_urls_list);
      }

      return '/sitemap-index.xml';

    }

    if (boolval(esc_attr(get_option('click5_sitemap_seo_robots_txt')))) {   
      $robots_txt = ABSPATH.'/robots.txt'; 
      if(is_writable($robots_txt)) { 
        click5_sitemap_generate_robots_txt();
      } else {
        return "404";
      }
    } else {
      if (file_exists(ABSPATH.'/robots.txt')) {
        
        if(boolval(esc_attr(get_option('click5_sitemap_seo_robots_backup'))) && file_exists(ABSPATH.'/robots-click5-backup.txt') ){
          unlink(ABSPATH.'/robots.txt');

          //Backup old robots.txt file
          if (file_exists(ABSPATH.'/robots-click5-backup.txt')) {
            //rename(ABSPATH.'/robots-click5-backup.txt', ABSPATH.'/robots.txt');
          }

        } elseif (empty(get_option('click5_sitemap_seo_robots_backup'))){
          unlink(ABSPATH.'/robots.txt');
        }
      }
    }
  }
  catch(Exception $e)
  {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
  }

}

function click5_sitemap_generate_delete_existing_sitemaps() {
  $files_to_delete = glob(ABSPATH.'/*sitemap*.xml');
  foreach($files_to_delete as $file) {
    unlink($file);
  }
  return $files_to_delete;
}

function click5_sitemap_print_robots_txt($ret = false) {

 
  $path = ABSPATH;
  $robots_txt = ABSPATH.'/robots.txt';
  clearstatcache();
  if (!file_exists($robots_txt)) {
    if ($ret) {
      return 'robots.txt not generated yet. <br/> <div><button type="button" name="update_txt" style=" vertical-align: middle; margin-top:20px; padding-right:14px; padding-left:14px;" class="button button-primary">Save Changes</button></div>';
    } else {
      echo 'robots.txt not generated yet. <br/> <div><button type="button" name="update_txt" style=" vertical-align: middle; margin-top:20px;padding-right:14px; padding-left:14px;" class="button button-primary">Save Changes</button></div>';
    }
  } else {
    if ($ret) {
      return '<a href="'.esc_url(get_bloginfo('url').'/robots.txt').'" target="_blank" rel="nofollow">'.esc_url(get_bloginfo('url').'/robots.txt').'</a>'.'<textarea rows="7" disabled="disabled" style="margin-top: 15px; resize: none;">'.esc_attr(file_get_contents($robots_txt)).'</textarea>';
    } else {
      if((esc_attr( get_option('click5_sitemap_seo_robots_txt') )) == 1 ) {
        echo '<a href="'.esc_url(get_bloginfo('url').'/robots.txt').'" target="_blank" rel="nofollow">'.esc_url(get_bloginfo('url').'/robots.txt').'</a>'.'<form><textarea name="file_text" rows="7" style="margin-top: 15px; resize: none;">'.esc_attr(file_get_contents($robots_txt)).'</textarea><div><button type="button" name="update_txt" style="padding-right:10px; padding-left:10px; vertical-align: middle; margin-top: 20px" class="button button-primary">Save Changes</button></form></div>';
      } else {
        echo '<a href="'.esc_url(get_bloginfo('url').'/robots.txt').'" target="_blank" rel="nofollow">'.esc_url(get_bloginfo('url').'/robots.txt').'</a>'.'<form><textarea name="file_text" disabled="disabled" rows="7" style="margin-top: 15px; resize: none;">'.esc_attr(file_get_contents($robots_txt)).'</textarea><div><button type="button" name="update_txt" style="padding-right:10px; padding-left:10px; vertical-align: middle; margin-top:20px;" disabled="disabled" class="button button-primary">Save Changes</button></form></div>';
      }
    }
  }
}

function click5_sitemap_generate_robots_txt() {
  //Check old robots.txt file
  if (file_exists(ABSPATH.'/robots.txt') && !file_exists(ABSPATH.'/robots-click5-backup.txt')) {
    //rename(ABSPATH.'/robots.txt', ABSPATH.'/robots-click5-backup.txt');
  }

  $content = '';

  if (boolval(esc_attr(get_option('click5_sitemap_seo_include_sitemap_xml')))) {
    $content = 'Sitemap: '.esc_url(site_url().'/'.basename('sitemap.xml')).PHP_EOL;
  }

  $content .= 'User-agent: *'.PHP_EOL;
  global $gTxtData;
  //$content .= "\n";
  $content .= $gTxtData;

  $blacklistSeoIDArray = json_decode(get_option('click5_sitemap_seo_blacklisted_array'));
  $blacklistSeoID = array_column($blacklistSeoIDArray ? $blacklistSeoIDArray : array(), 'ID');

  
  $content .= PHP_EOL; 
  $post_types = click5_sitemap_get_post_types();

  
  $robots_txt = ABSPATH.'/robots.txt';

  file_put_contents($robots_txt, $content);
}


function click5_sitemap_simulate_get_order_element($item) {
  $item = (array)$item;
  $newItem = array('ID' => $item['ID'], 'children' => array());

  if (isset($item['children'][0])) {
    foreach($item['children'] as &$subitem) {
      $newItem['children'][] = click5_sitemap_simulate_get_order_element($subitem);
    }
    return $newItem;
  } else {
    return array('ID' => $item['ID']);
  }
}

function click5_sitemap_reset_sitemap_order_inline() {
  $defaultOrder = click5_sitemap_get_order_list();
  $arraySerialized = array();
  foreach($defaultOrder as $item) {
    $arraySerialized[] = click5_sitemap_simulate_get_order_element($item);
  }

  $orderItems = click5_sitemap_order_list_setup_order_values($arraySerialized);

  $data_string = json_encode($orderItems);
  $byte_size = mb_strlen($data_string, '8bit');
  update_option('click5_sitemap_order_list2', '');
  update_option('click5_sitemap_order_list3', '');
  update_option('click5_sitemap_order_list4', '');
  update_option('click5_sitemap_order_list', json_encode($orderItems));
  /*
  if($byte_size > 700000) {
    $string_array_ele = str_split($data_string, 700000);
    global $wpdb;
    foreach($string_array_ele as $key => $value) {
      if($key == 0) {
        $robot_exist = $wpdb->query( $wpdb->prepare( "UPDATE  " . $wpdb->prefix . "options SET `option_value` = '" . $value . "' WHERE option_name ='%s'", 'click5_sitemap_order_list' ));
      } else if($key == 1) {
        //$robot_exist = $wpdb->query( $wpdb->prepare( "UPDATE  " . $wpdb->prefix . "options SET `option_value` = CONCAT(option_value, '" . $value . "') WHERE option_name ='%s'", 'click5_sitemap_order_list' ));
        $robot_exist = $wpdb->query( $wpdb->prepare( "UPDATE  " . $wpdb->prefix . "options SET `option_value` = '" . $value . "' WHERE option_name ='%s'", 'click5_sitemap_order_list2' ));
      } else if($key == 2) {
        //$robot_exist = $wpdb->query( $wpdb->prepare( "UPDATE  " . $wpdb->prefix . "options SET `option_value` = CONCAT(option_value, '" . $value . "') WHERE option_name ='%s'", 'click5_sitemap_order_list' ));
        $robot_exist = $wpdb->query( $wpdb->prepare( "UPDATE  " . $wpdb->prefix . "options SET `option_value` = '" . $value . "' WHERE option_name ='%s'", 'click5_sitemap_order_list3' ));
      } else if($key == 3) {
        //$robot_exist = $wpdb->query( $wpdb->prepare( "UPDATE  " . $wpdb->prefix . "options SET `option_value` = CONCAT(option_value, '" . $value . "') WHERE option_name ='%s'", 'click5_sitemap_order_list' ));
        $robot_exist = $wpdb->query( $wpdb->prepare( "UPDATE  " . $wpdb->prefix . "options SET `option_value` = '" . $value . "' WHERE option_name ='%s'", 'click5_sitemap_order_list4' ));
      }
    }
  } else {
    update_option('click5_sitemap_order_list', json_encode($orderItems));
  }
  */

}

function click5_sitemap_auto_sitemap_XML( $post_id ) {

  $post_current = get_post($post_id);
  if(isset($post_current))
  {
    if($post_current->post_type != "nav_menu_item")
    {
      click5_sitemap_reset_sitemap_order_inline();

      $is_enabled_auto = boolval(esc_attr( get_option('click5_sitemap_seo_auto')));
      $is_enabled_cron = boolval(esc_attr( get_option('click5_sitemap_seo_cron')));

      if (!$is_enabled_auto && !$is_enabled_cron) {
        return;
      }
      // If this is just a revision, don't send the email.
      if ( wp_is_post_revision( $post_id ) || $post_current->post_status == "auto-draft") {
        return;
      }

      $result = click5_sitemap_generate_sitemap_XML_DoWork();
    }
  }
}

$is_enabled_auto = boolval(esc_attr( get_option('click5_sitemap_seo_auto')));
if($is_enabled_auto){
  add_action( 'save_post', 'click5_sitemap_auto_sitemap_XML' );
}
add_action( 'save_post', 'click5_set_old_order' );




// for API authentication
function click5_sitemap_requestAuthentication($request) {
  $token = $request->get_header('token');
  $user = $request->get_header('user');
  $saved_token = get_option('click5_sitemap_authentication_token_'.$user);
  $result = $saved_token ? ( $token ? ( strcmp($token, $saved_token) === 0 ) : false ) : false;

  return $result;
}



// API for loading pages dynamicaly

function click5_sitemap_API_request_pages( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }

  if (!isset($_GET['search'])) {
    return null;
  }

  $searchQuery = sanitize_text_field($_GET['search']);
  if(empty($searchQuery))
    $searchQuery = "";
  $allPostTypes = click5_sitemap_get_post_types();
  $categories = get_categories( array(
    'orderby' => 'name',
    'order'   => 'ASC'
  ) );
  $users = get_users();
  $tag_list_array = get_tags();
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

  $postArray = array();
  $catArray = array();

  if (isset($_GET['type'])) {
    $typeSelected = sanitize_text_field($_GET['type']);
    if (in_array($typeSelected, $allPostTypes)) {
      global $wpdb;
      if(!empty($searchQuery))
      {
        $search_query1 = "SELECT ID FROM {$wpdb->prefix}posts
          WHERE post_type = %s
          AND post_title LIKE %s AND post_status = 'publish'" ;
        $like = '%' . $searchQuery . '%';
        $results = $wpdb->get_results($wpdb->prepare($search_query1,$typeSelected, $like), ARRAY_A);
        $quote_ids = array_column($results, 'ID');
        $postArray = get_posts(array('post_type'=>$typeSelected, 'orderby'=>'title', 'order'=>'ASC', 'nopaging' => true, 'post__in' => $quote_ids));
        if(count($quote_ids) === 0)
          $postArray = null;
      }
      else
      {
        $postArray = get_posts(array('search' => $searchQuery, 'post_status' => 'publish', 'numberposts' => -1, 'post_type' => $typeSelected, 'orderby' => array('post_type' => 'ASC',  'title' => 'ASC'), 'order' => 'ASC'));
      }
    } else if ($typeSelected === 'all') {
      global $wpdb;
      if(!empty($searchQuery))
      {
        $search_query1 = "SELECT ID FROM {$wpdb->prefix}posts
         WHERE post_title LIKE %s AND post_status = 'publish'" ;
        $like = '%' . $searchQuery . '%';
        $results = $wpdb->get_results($wpdb->prepare($search_query1, $like), ARRAY_A);
        $quote_ids = array_column($results, 'ID');
        $postArray = get_posts(array('post_type'=>$allPostTypes, 'orderby'=>'title', 'order'=>'ASC', 'nopaging' => true, 'post__in' => $quote_ids));
        if(count($quote_ids) === 0)
          $postArray = null;
      }
      else
      {
        $postArray = get_posts(array('s' => $searchQuery, 'post_status' => 'publish', 'numberposts' => -1, 'post_type' => $allPostTypes, 'nopaging' => true, 'orderby' => array('post_type' => 'ASC',  'title' => 'ASC'), 'order' => 'ASC'));
      }
      foreach($categories as $cat_item) {
        if(!empty($searchQuery)){
          if(strpos(strtolower($cat_item->name),strtolower($searchQuery)) !== false)
            array_push($postArray, array("ID" => "9999" . $cat_item->cat_ID, "url" => "", "post_type" => "category", "post_title" => $cat_item->name));
        }else{
          array_push($postArray, array("ID" => "9999" . $cat_item->cat_ID, "url" => "", "post_type" => "category", "post_title" => $cat_item->name));
        }
      }
      foreach($users as $user_item) {
        if ( count_user_posts( $user_item->ID) >= 1 ) { 
          if(!empty($searchQuery)){
            if(strpos(strtolower($user_item->data->display_name),strtolower($searchQuery)) !== false)
              array_push($postArray, array("ID" => "8888" . $user_item->ID, "url" => "", "post_type" => "authors", "post_title" => $user_item->data->display_name));
          }else{
            array_push($postArray, array("ID" => "8888" . $user_item->ID, "url" => "", "post_type" => "authors", "post_title" => $user_item->data->display_name));
          }
        }
      }
      foreach($tag_list_array as $tag_item) {
        if(!empty($searchQuery)){
          if(strpos(strtolower($tag_item->name),strtolower($searchQuery)) !== false)
            array_push($postArray, array("ID" => "7777" . $tag_item->term_id, "url" => "", "post_type" => "tag", "post_title" => $tag_item->name));
        }else{
          array_push($postArray, array("ID" => "7777" . $tag_item->term_id, "url" => "", "post_type" => "tag", "post_title" => $tag_item->name));
        }
      }

      foreach($cpt_types as $cpt) {
        $taxonomy_objects = get_terms($cpt);
        foreach($taxonomy_objects as $taxonomy_item) {
          if(!empty($searchQuery)){
            if(strpos(strtolower($taxonomy_item->name),strtolower($searchQuery)) !== false)
              array_push($postArray, array("ID" => "5555" . $taxonomy_item->term_id, "url" => "", "post_type" => $taxonomy_item->taxonomy, "post_title" =>  $taxonomy_item->name));
          }else{
            array_push($postArray, array("ID" => "5555" . $taxonomy_item->term_id, "url" => "", "post_type" => $taxonomy_item->taxonomy, "post_title" =>  $taxonomy_item->name));
          }
        }
      }
    
    } else if ($typeSelected === 'category') { 
      foreach($categories as $cat_item) {
        if(!empty($searchQuery)){
          if(strpos(strtolower($cat_item->name),strtolower($searchQuery)) !== false)
            array_push($postArray, array("ID" => "9999" . $cat_item->cat_ID, "url" => "", "post_type" => "category", "post_title" => $cat_item->name));
        }else{
          array_push($postArray, array("ID" => "9999" . $cat_item->cat_ID, "url" => "", "post_type" => "category", "post_title" => $cat_item->name));
        }
      }
    } else if ($typeSelected === 'authors') {  
      foreach($users as $user_item) {
        if ( count_user_posts( $user_item->ID) >= 1 ) { 
          if(!empty($searchQuery)){
            if(strpos(strtolower($user_item->data->display_name),strtolower($searchQuery)) !== false)
              array_push($postArray, array("ID" => "8888" . $user_item->ID, "url" => "", "post_type" => "authors", "post_title" => $user_item->data->display_name));
          }else{
            array_push($postArray, array("ID" => "8888" . $user_item->ID, "url" => "", "post_type" => "authors", "post_title" => $user_item->data->display_name));
          }
        }
      }
    } else if ($typeSelected === 'tag') { 
      foreach($tag_list_array as $tag_item) {
        if(!empty($searchQuery)){
          if(strpos(strtolower($tag_item->name),strtolower($searchQuery)) !== false || empty($searchQuery))
            array_push($postArray, array("ID" => "7777" . $tag_item->term_id, "url" => "", "post_type" => "tag", "post_title" => $tag_item->name));
        }else{
          array_push($postArray, array("ID" => "7777" . $tag_item->term_id, "url" => "", "post_type" => "tag", "post_title" => $tag_item->name));
        }
      }
    } else if (in_array($typeSelected, $taxonomies_array)) {
      $taxonomy_objects = get_terms($typeSelected);
      foreach($taxonomy_objects as $taxonomy_item) {
        if(!empty($searchQuery)){
          if(strpos(strtolower($taxonomy_item->name),strtolower($searchQuery)) !== false || empty($searchQuery))
            array_push($postArray, array("ID" => "5555" . $taxonomy_item->term_id, "url" => "", "post_type" => $taxonomy_item->taxonomy, "post_title" =>  $taxonomy_item->name));
        }else{
          array_push($postArray, array("ID" => "5555" . $taxonomy_item->term_id, "url" => "", "post_type" => $taxonomy_item->taxonomy, "post_title" =>  $taxonomy_item->name));
        }
      }
    }
  } else {
    $postArray = get_posts(array('s' => $searchQuery, 'post_status' => 'publish', 'numberposts' => -1, 'post_type' => $allPostTypes, 'orderby' => array('post_type' => 'ASC',  'title' => 'ASC'), 'order' => 'ASC'));
  }

  $urlMapped = array();
  $blacklistArray = json_decode(get_option('click5_sitemap_blacklisted_array'));
  $blacklist = array_column($blacklistArray ? $blacklistArray : array(), 'ID');
  $blacklistSeoArray = json_decode(get_option('click5_sitemap_seo_blacklisted_array'));
  $blacklistSeoID = array_column($blacklistSeoArray ? $blacklistSeoArray : array(), 'ID');

  $typeTab = sanitize_text_field($_GET['type_tab']);

  foreach($postArray as $postItem) {
    $postItem = (array) $postItem;

    if ($typeTab == 'html' && in_array($postItem['ID'], $blacklist)) {
      continue;
    } else if ($typeTab == 'seo' && in_array($postItem['ID'], $blacklistSeoID)) {
      continue;
    }

    $postItem['url'] = esc_url(get_permalink($postItem['ID']));
    $urlMapped[] = $postItem;
  }

  $final_data = array();
  $o1 = get_option('click5_sitemap_display_post');
  $o2 = get_option('click5_sitemap_display_page');
  $standard_types = array("post", "page", "category", "tag", "authors");

  if($_GET['type_tab'] == "html"){
    foreach($urlMapped as $url_item) {
      if($url_item["post_type"] == "post" && boolval(esc_attr(get_option('click5_sitemap_display_post')))) {
        array_push($final_data, $url_item);
      }
      if($url_item["post_type"] == "page" && boolval(esc_attr(get_option('click5_sitemap_display_page')))) {
        array_push($final_data, $url_item);
      }
      if($url_item["post_type"] == "category" && boolval(esc_attr(get_option('click5_sitemap_display_cat_tax')))) {
        array_push($final_data, $url_item);
      }
      if($url_item["post_type"] == "tag" && boolval(esc_attr(get_option('click5_sitemap_display_tag_tax')))) {
        array_push($final_data, $url_item);
      }
      if($url_item["post_type"] == "authors" && boolval(esc_attr(get_option('click5_sitemap_display_authors_tax')))) {
        array_push($final_data, $url_item);
      }
      if(!in_array($url_item["post_type"], $standard_types) && boolval(esc_attr(get_option('click5_sitemap_display_' . $url_item["post_type"])))) {
        array_push($final_data, $url_item);
      }
    }
  }else if($_GET['type_tab'] == "seo"){
    foreach($urlMapped as $url_item) {
      if($url_item["post_type"] == "post" && boolval(esc_attr(get_option('click5_sitemap_seo_post_type_post')))) {
        array_push($final_data, $url_item);
      }
      if($url_item["post_type"] == "page" && boolval(esc_attr(get_option('click5_sitemap_seo_post_type_page')))) {
        array_push($final_data, $url_item);
      }
      if($url_item["post_type"] == "category" && boolval(esc_attr(get_option('click5_sitemap_seo_xml_categories')))) {
        array_push($final_data, $url_item);
      }
      if($url_item["post_type"] == "tag" && boolval(esc_attr(get_option('click5_sitemap_seo_xml_tags')))) {
        array_push($final_data, $url_item);
      }
      if($url_item["post_type"] == "authors" && boolval(esc_attr(get_option('click5_sitemap_seo_xml_authors')))) {
        array_push($final_data, $url_item);
      }
      if(!in_array($url_item["post_type"], $standard_types) && boolval(esc_attr(get_option('click5_sitemap_seo_post_type_' . $url_item["post_type"])))) {
        array_push($final_data, $url_item);
      }

      if(!in_array($url_item["post_type"], $standard_types) && boolval(esc_attr(get_option('click5_sitemap_seo_xml_' . $url_item["post_type"]."_tax")))) {
        array_push($final_data, $url_item);
      }
    }
  }
  
  return $final_data;
}

function click5_sitemap_API_get_blacklisted( WP_REST_Request $request ) {
   if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }

  $array = json_decode(get_option('click5_sitemap_blacklisted_array'));
  $optionedArray = array();
  if ($array) {
    $blacklistSeoIDArray = json_decode(get_option('click5_sitemap_seo_blacklisted_array'));
    $blacklistSeoID = array_column($blacklistSeoIDArray ? $blacklistSeoIDArray : array(), 'ID');
    foreach($array as $arr_item) {
      $arr_item = (array)$arr_item;
      $arr_item['seo_blocked'] = in_array($arr_item['ID'], $blacklistSeoID);
      $optionedArray[] = $arr_item;
    }
  }
  $standard_types = array("post", "page", "category", "tag", "authors");
  $final_data = array();
  foreach($optionedArray as $url_item) {
    if($url_item["post_type"] == "post" && boolval(esc_attr(get_option('click5_sitemap_display_post')))) {
      array_push($final_data, $url_item);
    }
    if($url_item["post_type"] == "page" && boolval(esc_attr(get_option('click5_sitemap_display_page')))) {
      array_push($final_data, $url_item);
    }
    if($url_item["post_type"] == "category" && boolval(esc_attr(get_option('click5_sitemap_display_cat_tax')))) {
      array_push($final_data, $url_item);
    }
    if($url_item["post_type"] == "tag" && boolval(esc_attr(get_option('click5_sitemap_display_tag_tax')))) {
      array_push($final_data, $url_item);
    }
    if($url_item["post_type"] == "authors" && boolval(esc_attr(get_option('click5_sitemap_display_authors_tax')))) {
      array_push($final_data, $url_item);
    }
    if(!in_array($url_item["post_type"], $standard_types) && boolval(esc_attr(get_option('click5_sitemap_display_' . $url_item["post_type"])))) {
      array_push($final_data, $url_item);
    }
  }

  return json_encode($final_data);
}

function click5_sitemap_validateID($ID) {
  return is_numeric($ID) || is_numeric(intval($ID));
}

function click5_sitemap_API_add_to_blacklisted( WP_REST_Request $request ) {
   if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }

  if (!isset($_POST['ID']) || !isset($_POST['post_title']) || !isset($_POST['post_type'])) {
    return false;
  }

  $postID = sanitize_key($_POST['ID']);

  //validate if an ID is numeric
  if (!click5_sitemap_validateID($postID)) {
    return false;
  }

  $postTitle = sanitize_text_field($_POST['post_title']);
  $postType = sanitize_text_field($_POST['post_type']);

  $new_item = array('ID' => $postID, 'post_title' => $postTitle, 'post_type' => $postType, 'url' => get_permalink($postID));
  $blacklist = json_decode(get_option('click5_sitemap_blacklisted_array'));

  if (!$blacklist) {
    $blacklist = array();
  }

  $blacklist[] = $new_item;

  update_option('click5_sitemap_blacklisted_array', json_encode($blacklist));

  return json_encode($new_item);
}

function click5_sitemap_API_clear_blacklist( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }

  update_option('click5_sitemap_blacklisted_array', json_encode(array()));
  return true;
}

function click5_sitemap_API_check_robots_create ( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }
  $robots_txt = ABSPATH.'/robots.txt';
  if(is_writable(ABSPATH) && is_writable($robots_txt)) { 
    return false;
  } else {
    return true;
  }
}

function click5_sitemap_API_check_robots_file ( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }
  
  if(is_writable(ABSPATH)) { 
    if(get_option('click5_sitemap_delete_robot') == true) {
      update_option('click5_sitemap_seo_robots_txt', false); 
      unlink(ABSPATH.'/robots.txt');
      update_option('click5_sitemap_delete_robot', false);
      if(get_option('click5_sitemap_delete_robot_revert') == true) { 
        rename(ABSPATH.'/robots-click5-backup.txt', ABSPATH.'/robots.txt');
        update_option('click5_sitemap_delete_robot_revert', false);
      } else  {
        unlink(ABSPATH.'/robots-click5-backup.txt');
      }
    } else {
      if(get_option('click5_sitemap_create_robots_txt') == true) { 
        update_option('click5_sitemap_create_robots_txt', false); 
        update_option('click5_sitemap_seo_robots_txt', true);
        //-------------------------------------------------------------
        if (file_exists(ABSPATH.'/robots.txt') && !file_exists(ABSPATH.'/robots-click5-backup.txt')) {
          rename(ABSPATH.'/robots.txt', ABSPATH.'/robots-click5-backup.txt');
          
        }elseif(file_exists(ABSPATH.'/robots.txt') && file_exists(ABSPATH.'/robots-click5-backup.txt')) {
          unlink(ABSPATH.'/robots.txt');
        }
        $content = '';
        if (boolval(esc_attr(get_option('click5_sitemap_seo_include_sitemap_xml')))) {
          $content = 'Sitemap: '.esc_url(site_url().'/'.basename('sitemap.xml')).PHP_EOL;
        }
        $content .= 'User-agent: *'.PHP_EOL;
        $blacklistSeoIDArray = json_decode(get_option('click5_sitemap_seo_blacklisted_array'));
        $blacklistSeoID = array_column($blacklistSeoIDArray ? $blacklistSeoIDArray : array(), 'ID');
    
        $robots_txt = ABSPATH.'/robots.txt';
    
        file_put_contents($robots_txt, $content);
    
        $robots_txt = ABSPATH.'/robots.txt';
        $robots_backup_txt = ABSPATH.'/robots-click5-backup.txt';
        if(file_exists($robots_backup_txt)) {
          $backup_content = "";
          $handle_backup = fopen($robots_backup_txt, "r");
          if ($handle_backup) {
            while (($line = fgets($handle_backup)) !== false) {
              $empty_line = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "#####", $line);       
              if(!empty($line) && "#####" != $empty_line) {
                if($line[0] != "#") {
                  $single_line = "# " . $line;
                } else {
                  $single_line = $line;
                }
                $backup_content .= $single_line;
              }  
            }
            fclose($handle_backup);
          }
          $robots_txt_data = file_get_contents($robots_txt);
          $robots_txt_data .= $backup_content;
          //$robots_backup_txt_data = file_get_contents($robots_backup_txt);
    
          //$robots_txt_data .= str_replace("Sitemap:", "# Sitemap:", $robots_backup_txt_data);
          file_put_contents($robots_txt, preg_replace('/^[ \t]*[\r\n]+/m', '', $robots_txt_data));
        }
        //----------------------------------------------------------------------
      } else {
        if(get_option('click5_sitemap_seo_robots_txt') == true) { 
          $postBody = (array)(json_decode(stripslashes(file_get_contents("php://input"))));
          $text = "";
          $text_array = $postBody['text'];
          foreach($text_array as $item) {
            $text .= $item . "\n";
          }
          $robots_txt = ABSPATH.'/robots.txt';
          file_put_contents($robots_txt, $text);
        }
      }
    }
    return false;
  } else {
    return true;
  }
}

function click5_sitemap_API_unblacklist ( WP_REST_Request $request ) {
   if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }

  if (!isset($_GET['ID'])) {
    return false;
  }

  $getID = sanitize_key($_GET['ID']);

  //validate if an ID is numeric
  if (!click5_sitemap_validateID($getID)) {
    return false;
  }

  $blacklist = json_decode(get_option('click5_sitemap_blacklisted_array'));

  $newBlacklist = array();

  foreach($blacklist as $blacklistItem) {
    if ($blacklistItem->ID !== $getID) {
      $newBlacklist[] = $blacklistItem;
    }
  }

  update_option('click5_sitemap_blacklisted_array', json_encode($newBlacklist));

  return $newBlacklist;
}

function click5_sitemap_API_generate_xml_sitemap ( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }

  return click5_sitemap_generate_sitemap_XML_DoWork();
}

function click5_sitemap_API_seo_block_page (WP_REST_Request $request) {
   if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }

  if (!isset($_POST['ID']) || !isset($_POST['post_title']) || !isset($_POST['post_type'])) {
    return false;
  }

  $postID = sanitize_key($_POST['ID']);

  //validate if an ID is numeric
  if (!click5_sitemap_validateID($postID)) {
    return false;
  }

  $postTitle = sanitize_text_field($_POST['post_title']);
  $postType = sanitize_text_field($_POST['post_type']);


  $new_item = array('ID' => $postID, 'post_title' => $postTitle, 'post_type' => $postType, 'url' => get_permalink($postID));
  $blacklistSeo = json_decode(get_option('click5_sitemap_seo_blacklisted_array'));

  if (!$blacklistSeo) {
    $blackblacklistSeolist = array();
  }

  $blacklistSeo[] = $new_item;

  update_option('click5_sitemap_seo_blacklisted_array', json_encode($blacklistSeo));

  return json_encode($new_item);
}

function click5_sitemap_API_get_seo_block_list (WP_REST_Request $request) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }

  $array = get_option('click5_sitemap_seo_blacklisted_array');
  return $array ? $array : '[]';
}

function click5_sitemap_API_get_seo_unblock ( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }

  if (!isset($_GET['ID'])) {
    return false;
  }

  $getID = sanitize_key($_GET['ID']);

  //validate if an ID is numeric
  if (!click5_sitemap_validateID($getID)) {
    return false;
  }

  $blacklist = json_decode(get_option('click5_sitemap_seo_blacklisted_array'));

  $newBlacklist = array();

  foreach($blacklist as $blacklistItem) {
    if ($blacklistItem->ID !== $getID) {
      $newBlacklist[] = $blacklistItem;
    }
  }

  update_option('click5_sitemap_seo_blacklisted_array', json_encode($newBlacklist));

  return $newBlacklist;
}

function click5_sitemap_API_get_seo_clear ( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }

  update_option('click5_sitemap_seo_blacklisted_array', json_encode(array()));
  return true;
}

function click5_sitemap_API_generate_manual ( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }

  $postBody = (array)(json_decode(stripslashes(file_get_contents("php://input"))));
  $options = (array) $postBody['options'];
  foreach($options as $option) {
    $option = (array) $option;
    $optionName = sanitize_key($option['name']);
    $optionValue = sanitize_text_field($option['value']);
    update_option($optionName, $optionValue);
  }

  return click5_sitemap_generate_sitemap_XML_DoWork();
}

function click5_sitemap_API_print_robots_txt ( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }
  clearstatcache();

  if (boolval(esc_attr(get_option('click5_sitemap_seo_robots_txt')))) {

    
    click5_sitemap_generate_robots_txt();

    return click5_sitemap_print_robots_txt(true);
  } else {
    if (file_exists(ABSPATH.'/robots.txt')) {

      if(boolval(esc_attr(get_option('click5_sitemap_seo_robots_backup'))) && file_exists(ABSPATH.'/robots-click5-backup.txt') ){
        unlink(ABSPATH.'/robots.txt');

        //Backup old robots.txt file
        if (file_exists(ABSPATH.'/robots-click5-backup.txt')) {
          //rename(ABSPATH.'/robots-click5-backup.txt', ABSPATH.'/robots.txt');
        }

      } elseif (empty(get_option('click5_sitemap_seo_robots_backup'))){
        unlink(ABSPATH.'/robots.txt');
      }    
    }
  }
  
}

function click5_sitemap_API_print_sitemap_urls ( WP_REST_Request $request ) {

  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }

  click5_sitemap_generate_sitemap_XML_DoWork();

  clearstatcache();
  $resultArray = array();
  $sitemaps = glob(ABSPATH.'/*sitemap*.xml');

  if(boolval(esc_attr( get_option('click5_sitemap_seo_sitemap_xml')))){

    foreach( $sitemaps as $sitemap ) {
      if (strpos($sitemap, 'index.xml') !== false && strpos($sitemap, 'video') == false && strpos($sitemap, 'image') == false && strpos($sitemap, 'author') == false && strpos($sitemap, 'category') == false) {
        array_unshift($resultArray, site_url().'/'.str_replace('-index', '', basename($sitemap)));
      } else {
        $resultArray[] = site_url().'/'.basename($sitemap);
      }
    }
    return $resultArray;
  }
}

function click5_sitemap_API_reload_sitemap_urls ( WP_REST_Request $request ) {

  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }

  //click5_sitemap_generate_sitemap_XML_DoWork();

  clearstatcache();
  $resultArray = array();
  $sitemaps = glob(ABSPATH.'/*sitemap*.xml');

  if(boolval(esc_attr( get_option('click5_sitemap_seo_sitemap_xml')))){

    foreach( $sitemaps as $sitemap ) {
      if (strpos($sitemap, 'index.xml') !== false && strpos($sitemap, 'video') == false && strpos($sitemap, 'image') == false && strpos($sitemap, 'author') == false && strpos($sitemap, 'category') == false) {
        array_unshift($resultArray, site_url().'/'.str_replace('-index', '', basename($sitemap)));
      } else {
        $resultArray[] = site_url().'/'.basename($sitemap);
      }
    }
    return $resultArray;
  }
}

function click5_sitemap_API_add_custom_url ( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }

  $new_item = (array)(json_decode(stripslashes(file_get_contents("php://input"))));

  $custom_urls_list = json_decode(get_option('click5_sitemap_urls_list'));

  if (!$custom_urls_list) {
    $custom_urls_list = array();
  }

  $id = 1;
  foreach($custom_urls_list as $custom_url) {
    $numberArr = explode('_', $custom_url->ID);

    if (intval($numberArr[1]) >= $id) {
      $id = intval($numberArr[1]) + 1;
    }
  }

  $new_item['title'] = sanitize_text_field($new_item['title']);
  $new_item['url'] =esc_url_raw($new_item['url']);
  $new_item['category']->use_custom = rest_sanitize_boolean($new_item['category']->use_custom);
  $new_item['category']->name = sanitize_text_field($new_item['category']->name);
  $new_item['new_tab'] = rest_sanitize_boolean($new_item['new_tab']);
  $new_item['last_mod'] = sanitize_text_field($new_item['last_mod']);

  /* TODO: add validation */

  if (empty($new_item['title'])) {
    return click5_sitemap_send_notification('Title field can not be empty!', 'warning');
  }

  if (empty($new_item['url'])) {
    return click5_sitemap_send_notification('URL field can not be empty', 'warning');
  }

  if ($new_item['category']->use_custom) {
    if (empty($new_item['category']->name)) {
      return click5_sitemap_send_notification('Category Name field can not be empty when using custom URL Category', 'warning');
    }
  }


  $new_item['ID'] = 'c_'.$id;
  $new_item['enabledHTML'] = true;
  $new_item['enabledXML'] = true;

  $custom_urls_list[] = $new_item;

  update_option('click5_sitemap_urls_list', json_encode($custom_urls_list));


  $getCustomCat = click5_sitemap_getCustomCategoriesCustomUrlsXML();

  foreach($getCustomCat as $custom_category) {
    add_option('click5_sitemap_seo_xml_'.str_replace(' ', '_', $custom_category).'_custom', true);
  }

  click5_sitemap_generate_sitemap_XML_DoWork();

  return true;
}

function click5_sitemap_API_get_custom_url_list ( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }

  return json_decode(get_option('click5_sitemap_urls_list'));
}

function click5_sitemap_API_post_custom_url_clear ( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }

  update_option('click5_sitemap_urls_list', json_encode($custom_urls_list));

  return true;
}

function click5_sitemap_API_get_custom_url_delete_one ( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }

  if (!isset($_GET['ID'])) {
    return false;
  }

  $getID = sanitize_key($_GET['ID']);
  $idToRmvArray = explode('_', $getID);

  if(!click5_sitemap_validateID($idToRmvArray[1])) {
    return false;
  }

  $idToRmv = intval($idToRmvArray[1]);
  $custom_urls_list = (array) json_decode(get_option('click5_sitemap_urls_list'));

  $updated_urls_list = array();

  foreach($custom_urls_list as $custom_url) {
    $custom_url = (array)$custom_url;
    $custom_urlIDArray = explode('_', $custom_url['ID']);
    if (intval($custom_urlIDArray[1]) == $idToRmv) {
      continue;
    }

    $updated_urls_list[] = $custom_url;
  }

  update_option('click5_sitemap_urls_list', json_encode($updated_urls_list));

  click5_sitemap_generate_sitemap_XML_DoWork();

  return true;
}

function click5_sitemap_API_get_custom_url_toggle_HTML ( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }

  if (!$request->get_params()['ID']) {
    return false;
  }

  if (!$request->get_params()['newVal']) {
    return false;
  }

  $idtoGetArray = explode('_', sanitize_key($request->get_params()['ID']));

  if (!click5_sitemap_validateID($idtoGetArray[1])) {
    return false;
  }

  $idToChange = intval($idtoGetArray[1]);

  // variable below doesn't need sanitization and validation since its casted to boolean.
  $newVal = $request->get_params()['newVal'] == 'true';

  $custom_urls_list = (array) json_decode(get_option('click5_sitemap_urls_list'));

  $updated_urls_list = array();

  foreach($custom_urls_list as $custom_url) {
    $custom_url = (array)$custom_url;
    //well value below is already stored in DB so no need to sanitize or validate
    $custom_urlIDArray = explode('_', $custom_url['ID']);
    if (intval($custom_urlIDArray[1]) == $idToChange) {
      $custom_url['enabledHTML'] = $newVal;
    }

    $updated_urls_list[] = $custom_url;
  }

  update_option('click5_sitemap_urls_list', json_encode($updated_urls_list));

  return true;
}

function click5_sitemap_API_get_custom_url_toggle_XML ( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }

  if (!$request->get_params()['ID']) {
    return false;
  }

  if (!$request->get_params()['newVal']) {
    return false;
  }

  $idtoGetArray = explode('_', sanitize_key($request->get_params()['ID']));

  if (!click5_sitemap_validateID($idtoGetArray[1])) {
    return false;
  }

  $idToChange = intval($idtoGetArray[1]);
  $newVal = ($request->get_params()['newVal']) == 'true';

  $custom_urls_list = (array) json_decode(get_option('click5_sitemap_urls_list'));

  $updated_urls_list = array();

  foreach($custom_urls_list as $custom_url) {
    $custom_url = (array)$custom_url;
    $custom_urlIDArray = explode('_', $custom_url['ID']);
    if (intval($custom_urlIDArray[1]) == $idToChange) {
      $custom_url['enabledXML'] = $newVal;
    }

    $updated_urls_list[] = $custom_url;
  }

  update_option('click5_sitemap_urls_list', json_encode($updated_urls_list));

  return true;
}

function click5_sitemap_API_get_custom_url_single ( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return click5_sitemap_send_notification('Failed to authenticate', 'error');
  }

  if (!$request->get_params()['ID']) {
    return false;
  }

  $idtoGetArray = explode('_', sanitize_key($request->get_params()['ID']));

  if(!click5_sitemap_validateID($idtoGetArray[1])) {
    return false;
  }

  $idToGet = intval($idtoGetArray[1]);
  $custom_urls_list = (array) json_decode(get_option('click5_sitemap_urls_list'));

  foreach($custom_urls_list as $custom_url) {
    $custom_urlIDArray = explode('_', $custom_url->ID);
    if (intval($custom_urlIDArray[1]) == $idToGet) {
      return $custom_url;
    }
  }

  return false;
}


function click5_sitemap_API_post_custom_url_save_edit ( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }

  $edit_item = (array)(json_decode(stripslashes(file_get_contents("php://input"))));
  $idArray = explode('_', sanitize_key($request->get_params()['ID']));

  if (!click5_sitemap_validateID($idArray[1])) {
    return false;
  }

  /* TODO: finish */

  $idToUpdate = intval($idArray[1]);
  $custom_urls_list = (array) json_decode(get_option('click5_sitemap_urls_list'));
  $newArray = array();

  foreach($custom_urls_list as $custom_url) {
    $customUrlIDArray = explode('_', $custom_url->ID);

    if ($customUrlIDArray[1] == $idToUpdate) {
      $copy = (array)$custom_url;
      $custom_url = (array)$edit_item;

      $custom_url['title'] = sanitize_text_field($custom_url['title']);
      $custom_url['url'] = esc_url_raw($custom_url['url']);
      $custom_url['category']->use_custom = rest_sanitize_boolean($custom_url['category']->use_custom);
      $custom_url['category']->name = sanitize_text_field($custom_url['category']->name);
      $custom_url['new_tab'] = rest_sanitize_boolean($custom_url['new_tab']);
      $custom_url['last_mod'] = sanitize_text_field($custom_url['last_mod']);

      if (empty($custom_url['title'])) {
        return click5_sitemap_send_notification('Title field can not be empty!', 'warning');
      }

      if (empty($custom_url['url'])) {
        return click5_sitemap_send_notification('URL field can not be empty', 'warning');
      }

      if ($custom_url['category']->use_custom) {
        if (empty($custom_url['category']->name)) {
          return click5_sitemap_send_notification('Category Name field can not be empty when using custom URL Category', 'warning');
        }
      }

      $custom_url['enabledXML'] = $copy['enabledXML'];
      $custom_url['enabledHTML'] = $copy['enabledHTML'];
    }

    $newArray[] = $custom_url;
  }

  update_option('click5_sitemap_urls_list', json_encode($newArray));

  click5_sitemap_generate_sitemap_XML_DoWork();

  return true;
}

function click5_sitemap_order_list_recursive_children_setup(&$orderItems, $item) {
  if(isset($item->children)) {
        if (count($item->children)) {
          $orderValueChild = 100;
          foreach($item->children as $child) {
            $child = (object)$child;
            $child->ID = sanitize_key($child->ID);

            if (strpos($child->ID, 'c_') !== false) {
              // ID is from custom post
              if (!click5_sitemap_validateID(str_replace('c_', '', $child->ID))) {
                continue;
              }
            }
            else if (strpos($child->ID, 'g_') !== false) {
              //groups doesnt have to be validated
            }
            else {
              if (!click5_sitemap_validateID($child->ID)) {
                continue;
              }
            }

            $childItem = array(
              'ID' => $child->ID,
              'is_category' => false,
              'parent' => $item->ID,
              'order' => $orderValueChild
            );

            $orderItems[] = $childItem;
            $orderValueChild += 100;

            click5_sitemap_order_list_recursive_children_setup($orderItems, $child);
          }
        }
      }
}

function click5_sitemap_order_list_setup_order_values($newOrder) {
    $orderValue = 100;
    $orderItems = array();
    foreach($newOrder as $firstLevelOrder) {
      $firstLevelOrder = (object)$firstLevelOrder;
      $isCategory = ctype_digit($firstLevelOrder->ID) ? false : true;

      if ($isCategory) {
        $firstLevelOrder->ID = sanitize_text_field($firstLevelOrder->ID);

        if (empty($firstLevelOrder->ID)) {
          continue;
        }
        /* TODO: code function to validate category by existing categories */

      } else {
        $firstLevelOrder->ID = sanitize_key($firstLevelOrder->ID);

        if (strpos($firstLevelOrder->ID, 'c_') !== false) {
          // ID is from custom post

          if (!click5_sitemap_validateID(str_replace('c_', '', $firstLevelOrder->ID))) {
            continue;
          }
        } else if (strpos($firstLevelOrder->ID, 'g_') !== false) {
          //also skipping validation here for groups
        } else {
          if (!click5_sitemap_validateID($firstLevelOrder->ID)) {
            continue;
          }
        }
      }

      $orderItem = array(
        'ID' => $firstLevelOrder->ID,
        'is_category' => $isCategory,
        'order' => $orderValue
      );

      $orderItems[] = $orderItem;

      click5_sitemap_order_list_recursive_children_setup($orderItems, $firstLevelOrder);

      $orderValue += 100;//na wypadek gdyby powstaÅ‚y jakies strony ktore trzeba umiescic miedzy
    }

    return $orderItems;
}

function click5_sitemap_API_post_update_sitemap_order ( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }
  $rawInput = file_get_contents("php://input");

  $newOrder = (array)(json_decode(html_entity_decode($rawInput)));
  $orderItems = click5_sitemap_order_list_setup_order_values($newOrder);
  $data_string = json_encode($orderItems);
  $byte_size = mb_strlen($data_string, '8bit');
  update_option('click5_sitemap_order_list2', '');
  update_option('click5_sitemap_order_list3', '');
  update_option('click5_sitemap_order_list4', '');
  update_option('click5_sitemap_order_list', json_encode($orderItems));
  /*
  if($byte_size > 700000) {
    $string_array_ele = str_split($data_string, 700000);
    global $wpdb;
    foreach($string_array_ele as $key => $value) {
      if($key == 0) {
        $robot_exist = $wpdb->query( $wpdb->prepare( "UPDATE  " . $wpdb->prefix . "options SET `option_value` = '" . $value . "' WHERE option_name ='%s'", 'click5_sitemap_order_list' ));
      } else if($key == 1) {
        //$robot_exist = $wpdb->query( $wpdb->prepare( "UPDATE  " . $wpdb->prefix . "options SET `option_value` = CONCAT(option_value, '" . $value . "') WHERE option_name ='%s'", 'click5_sitemap_order_list' ));
        $robot_exist = $wpdb->query( $wpdb->prepare( "UPDATE  " . $wpdb->prefix . "options SET `option_value` = '" . $value . "' WHERE option_name ='%s'", 'click5_sitemap_order_list2' ));
      } else if($key == 2) {
        //$robot_exist = $wpdb->query( $wpdb->prepare( "UPDATE  " . $wpdb->prefix . "options SET `option_value` = CONCAT(option_value, '" . $value . "') WHERE option_name ='%s'", 'click5_sitemap_order_list' ));
        $robot_exist = $wpdb->query( $wpdb->prepare( "UPDATE  " . $wpdb->prefix . "options SET `option_value` = '" . $value . "' WHERE option_name ='%s'", 'click5_sitemap_order_list3' ));
      } else if($key == 3) {
        //$robot_exist = $wpdb->query( $wpdb->prepare( "UPDATE  " . $wpdb->prefix . "options SET `option_value` = CONCAT(option_value, '" . $value . "') WHERE option_name ='%s'", 'click5_sitemap_order_list' ));
        $robot_exist = $wpdb->query( $wpdb->prepare( "UPDATE  " . $wpdb->prefix . "options SET `option_value` = '" . $value . "' WHERE option_name ='%s'", 'click5_sitemap_order_list4' ));
      }
    }
  } else {
    update_option('click5_sitemap_order_list', json_encode($orderItems));
  }
  */
  return $orderItems;
}


function click5_sitemap_API_post_update_sitemap_order_save_btn ( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }

  $rawInput = file_get_contents("php://input");
  $newOrder = (array)(json_decode(html_entity_decode($rawInput)));
  if(isset($newOrder[count($newOrder)-1]->save_order)){
    update_option("click5_sitemap_custom_sorting",1);
    array_splice($newOrder,(count($newOrder)-1),1);
  }
  $orderItems = click5_sitemap_order_list_setup_order_values($newOrder);

  
	$new_array = array();

  foreach($orderItems as $value){ 

      if(isset($value['parent'])){
        $parent = $value['parent'];
      } else {
        $parent = false;
      }

      $new_array[] = array(
        'ID' => $value['ID'],
        'order' => $value['order'],
        'parent' => $parent
      );
  }

  return update_option('click5_sitemap_order_list_old', json_encode($new_array));
}

function click5_sitemap_API_post_update_nested_elements ( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }

  $rawInput = file_get_contents("php://input");
  $nestedElements = (array)(json_decode(html_entity_decode($rawInput)));

  return update_option('click5_sitemap_order_list_nested', json_encode($nestedElements));
}
  
function click5_sitemap_API_mark_print_robots_txt ( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }
  update_option('click5_sitemap_create_robots_txt', false); 
  update_option('click5_sitemap_delete_robot_revert', false);
  return update_option('click5_sitemap_delete_robot', true);
}

function click5_sitemap_API_mark_print_robots_txt_revert ( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }
  update_option('click5_sitemap_create_robots_txt', false); 
  update_option('click5_sitemap_delete_robot_revert', true);
  return update_option('click5_sitemap_delete_robot', true);
}

function click5_sitemap_API_mark_add_robots_txt ( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }
  update_option('click5_sitemap_create_robots_txt', true); 
  return update_option('click5_sitemap_delete_robot', false);
}


function click5_sitemap_API_get_sitemap_order ( WP_REST_Request $request ) {
  return (array) json_decode(get_option('click5_sitemap_order_list'));
}

function click5_sitemap_API_post_reset_sitemap_order ( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }

  
  return click5_sitemap_HTML_sitemap_display_order_list();

}


function click5_sitemap_API_post_total_reset_sitemap_order ( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }

  update_option('click5_sitemap_order_list', '');
  update_option('click5_sitemap_order_list_old', '');
  update_option('click5_sitemap_order_list_nested', '');
  update_option("click5_sitemap_custom_sorting",0);

  return click5_sitemap_HTML_sitemap_display_order_list();

}


function click5_sitemap_API_update_option_AJAX ( WP_REST_Request $request ) {
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }

  $postBody = (array)(json_decode(stripslashes(file_get_contents("php://input"))));

  $type = sanitize_text_field($postBody['type']);
  $optionName = sanitize_text_field($postBody['option_name']);
  $optionValue = sanitize_text_field($postBody['option_value']);

  //validate if we are targeting current plugin option
  if (!(strpos($optionName, 'click5_sitemap') !== false)) {
    return false;
  }

  if ($type === 'bool')
    update_option($optionName, boolval($optionValue));
  else
    update_option($optionName, $optionValue);

  return true;
}

function click5_sitemap_API_update_html_option_AJAX ( WP_REST_Request $request ) { 
  if (!click5_sitemap_requestAuthentication($request)) {
    return false;
  }
  $postBody = (array)(json_decode(stripslashes(file_get_contents("php://input"))));
  foreach($postBody as $key => $value) {
    if(strpos($key, 'click5_sitemap_') !== false) {
      update_option($key, $value);
    }
  }
  return true;
}


function click5_sitemap_debug (WP_REST_Request $request) {
  $items_per_page = intval(esc_attr( get_option('click5_sitemap_html_pagination_items_per_page') ));
  return $items_per_page;
}

function click5_ping_sitemap_to_google($url, $name) {

  $ping_url = '';
  $sitemap_url = $url;
  
  $ping_url = "http://www.google.com/webmasters/tools/ping?sitemap=" . urlencode($sitemap_url);
  $search_response = wp_remote_get( $ping_url );
  if($Search_response['response']['code']=200)
  {
    update_option( 'google_ping_' . $name, 'success ' . $url );
  }
  else 
  {
    update_option( 'google_ping_' . $name, 'error ' . $url );
  }
}


add_action( 'rest_api_init', function () {
  register_rest_route( 'click5_sitemap/API', '/request_pages', array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_API_request_pages',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route( 'click5_sitemap/API', '/get_blacklisted', array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_API_get_blacklisted',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route( 'click5_sitemap/API', '/add_to_blacklisted', array(
    'methods' => 'POST',
    'callback' => 'click5_sitemap_API_add_to_blacklisted',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route( 'click5_sitemap/API', '/clear_blacklist', array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_API_clear_blacklist',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/unblacklist',array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_API_unblacklist',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/checkrobots',array(
    'methods' => 'POST',
    'callback' => 'click5_sitemap_API_check_robots_file',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/checkrobotsgenerate',array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_API_check_robots_create',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/generate_xml_sitemap',array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_API_generate_xml_sitemap',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/seo_block_page',array(
    'methods' => 'POST',
    'callback' => 'click5_sitemap_API_seo_block_page',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/get_seo_block_list',array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_API_get_seo_block_list',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/get_seo_unblock',array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_API_get_seo_unblock',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/get_seo_clear',array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_API_get_seo_clear',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/generate_manual',array(
    'methods' => 'POST',
    'callback' => 'click5_sitemap_API_generate_manual',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/print_robots_txt',array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_API_print_robots_txt',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/mark_delete_robots_txt',array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_API_mark_print_robots_txt',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/mark_delete_robots_txt_revert',array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_API_mark_print_robots_txt_revert',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/mark_add_robots_txt',array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_API_mark_add_robots_txt',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/print_sitemap_urls',array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_API_print_sitemap_urls',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/reload_sitemap_urls',array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_API_reload_sitemap_urls',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/add_custom_url',array(
    'methods' => 'POST',
    'callback' => 'click5_sitemap_API_add_custom_url',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/get_custom_url_list',array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_API_get_custom_url_list',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/post_custom_url_clear',array(
    'methods' => 'POST',
    'callback' => 'click5_sitemap_API_post_custom_url_clear',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/get_custom_url_delete_one',array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_API_get_custom_url_delete_one',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/get_custom_url_toggle_HTML',array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_API_get_custom_url_toggle_HTML',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/get_custom_url_toggle_XML',array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_API_get_custom_url_toggle_XML',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/get_custom_url_single',array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_API_get_custom_url_single',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/post_custom_url_save_edit',array(
    'methods' => 'POST',
    'callback' => 'click5_sitemap_API_post_custom_url_save_edit',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/get_order_list_HTML',array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_HTML_sitemap_display_order_list',
    'permission_callback' => '__return_true',
  ) );
  register_rest_route('click5_sitemap/API', '/get_order_list_HTML_nested',array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_HTML_sitemap_display_order_list_and_nested',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/get_order_list_HTML_nested_count',array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_HTML_sitemap_display_order_list_and_nested_count',
    'permission_callback' => '__return_true',
  ) );
  
  register_rest_route('click5_sitemap/API', '/post_update_list_HTML',array(
    'methods' => 'POST',
    'callback' => 'click5_sitemap_API_post_update_sitemap_order',
    'permission_callback' => '__return_true',
  ) );
  register_rest_route('click5_sitemap/API', '/post_update_list_HTML_save_btn',array(
    'methods' => 'POST',
    'callback' => 'click5_sitemap_API_post_update_sitemap_order_save_btn',
    'permission_callback' => '__return_true',
  ) );
  register_rest_route('click5_sitemap/API', '/post_update_nested_elements',array(
    'methods' => 'POST',
    'callback' => 'click5_sitemap_API_post_update_nested_elements',
    'permission_callback' => '__return_true',
  ) );
  register_rest_route('click5_sitemap/API', '/get_nested_elements',array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_HTML_get_nestedElements',
    'permission_callback' => '__return_true',
  ) );
  register_rest_route('click5_sitemap/API', '/get_sitemap_order',array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_API_get_sitemap_order',
    'permission_callback' => '__return_true',
  ) );
  register_rest_route('click5_sitemap/API', '/reset_sitemap_order',array(
    'methods' => 'POST',
    'callback' => 'click5_sitemap_API_post_reset_sitemap_order',
    'permission_callback' => '__return_true',
  ) );
  register_rest_route('click5_sitemap/API', '/total_reset_sitemap_order',array(
    'methods' => 'POST',
    'callback' => 'click5_sitemap_API_post_total_reset_sitemap_order',
    'permission_callback' => '__return_true',
  ) );
  register_rest_route('click5_sitemap/API', '/update_option_AJAX',array(
    'methods' => 'POST',
    'callback' => 'click5_sitemap_API_update_option_AJAX',
    'permission_callback' => '__return_true',
  ) );

  register_rest_route('click5_sitemap/API', '/update_html_option_AJAX',array(
    'methods' => 'POST',
    'callback' => 'click5_sitemap_API_update_html_option_AJAX',
    'permission_callback' => '__return_true',
  ) );  



  register_rest_route('click5_sitemap/API', '/debug',array(
    'methods' => 'GET',
    'callback' => 'click5_sitemap_debug',
    'permission_callback' => '__return_true',
  ) );
});



?>