const redirectToYoast = () => {
  document.location.href="admin.php?page=wpseo_dashboard#top#features";
}

const redirectToAioSeo = () => {
  document.location.href="admin.php?page=aioseo-sitemaps#/general-sitemap";
}

const sortOrderList = () => {
  if(jQuery("#sorting_notification").hasClass("disabled") && jQuery("#click5_sitemap_display_style").val() == "merge")
    return;
  var ul = jQuery("#sitemap-order");
  var arr = jQuery.makeArray(ul.children("li"));

  arr.sort(function (a, b) {
    var textA = a.value;
    var textB = b.value;

    if (textA < textB) return -1;
    if (textA > textB) return 1;

    return 0;
  });

  ul.empty();

  jQuery.each(arr, function () {
    ul.append(this);
  });
}

const eventNeedToUpdateOrderList = new Event('click5_sitemap_force_order_list_update');

const relocateSubItems = () => {
  document.querySelectorAll('#sitemap-order > li > ol li').forEach(subli => {
    if(subli.hasAttribute('name')) {
      let newLocation = subli.getAttribute('name');
      if (subli.parentElement.parentElement) {
        if (subli.parentElement.parentElement.hasAttribute('data-value')) {
          if (newLocation !== subli.parentElement.parentElement.getAttribute('data-value')) {
            //document.querySelector('#sitemap-order li[data-value="' + newLocation + '"] > ol').append(subli);
          }
        }
      }

    }
  });
}

const sortSubOrderList = () => {
  if(jQuery("#sorting_notification").hasClass("disabled"))
    return;
  document.querySelectorAll('#sitemap-order > li ol').forEach(ol => {
    var ul = jQuery(ol);
    var arr = jQuery.makeArray(ul.children("li"));

    arr.sort(function (a, b) {
      var textA = a.value;
      var textB = b.value;

      if (textA < textB) return -1;
      if (textA > textB) return 1;

      return 0;
    });

    ul.empty();

    jQuery.each(arr, function () {
      ul.append(this);
    });
  })
}

const sortListByCurrentSortData = () => {
  getRequest(c5resturl.wpjson + 'click5_sitemap/API/get_sitemap_order', (data) => {
    if (data) {
      if(data.length) {
        data.forEach(sortObject => {
          sortElement = document.querySelector('li[data-value="'+sortObject.ID+'"]');
          if(sortElement) {
            sortElement.setAttribute('value', sortObject.order);
            if (sortObject.parent) {
              sortElement.setAttribute('name', sortObject.parent);
            }
          }
        });
        sortOrderList();
        relocateSubItems();
        sortSubOrderList();
        jQuery("#btnSaveOrder").trigger('click');
      }
    }
  });
}

const sortOrderClick = () => {
  /*serializeOrderList();
  BTNserializeOrderList();*/
  window.location.reload();
}

const loadOrderList22 = (callback = undefined) => {
  getRequest(c5resturl.wpjson + 'click5_sitemap/API/get_order_list_HTML_nested', (dataFull) => {
    var data = dataFull[0];
    var dataOrder = dataFull[2];
    let list = '<div class="dd" id="orderList"><ol class="dd-list" id="sitemap-order">';
    if (data) {
      if (data.length) {
        list += data;
      }
    }
    list += '</ol></div>';

    let listEl = jQuery(list);
    jQuery(listEl).hide();
    jQuery('#post-body-content > #order-sitemap > div').prepend(listEl);
    if (dataOrder) {
      if(dataOrder.length) {
        dataOrder.forEach(sortObject => {
          sortElement = document.querySelector('li[data-value="'+sortObject.ID+'"]');
          if(sortElement) {
            sortElement.setAttribute('value', sortObject.order);
            if (sortObject.parent) {
              sortElement.setAttribute('name', sortObject.parent);
            }
          }
        });
        sortOrderList();
        relocateSubItems();
        sortSubOrderList();

        serializeOrderListNoNotification();
        BTNserializeOrderList();
      }
    }

      jQuery(listEl).show();

    getRequest(c5resturl.wpjson + 'click5_sitemap/API/get_nested_elements', (nestedElements) => {
      forceSerializeOrderList();
      
      let currentParrent;

      jQuery('#orderList').nestable({
        maxDepth: 3,
        expandBtnHTML: '<button class="dd-expand" data-action="expand" type="button"></button>',
        collapseBtnHTML: '<button class="dd-collapse" data-action="collapse" type="button"></button>',
        onDragStart: function(l,e){
          currentParrent = jQuery(e).parent().closest('li').attr('data-value')
        },
        beforeDragStop: function(l, e, p){

            var currentElementValue = jQuery(e).attr('data-value')
            var currentElementValueTitle = jQuery(e).find('.dd-handle').text()
            var newParentValue = p[0].offsetParent.dataset.value

            if(jQuery(e).hasClass("group") && p[0].offsetParent.className.includes('group')){
              return false;
            }

            if(jQuery(e).hasClass('sub-item') && p[0].getAttribute('id') === 'sitemap-order') {
              return false;
            }

            if (!jQuery(e).hasClass("group") && currentParrent != newParentValue) {

              jQuery(e).attr('name', newParentValue);

              if(!jQuery(e).hasClass('original-nested')){
                jQuery(e).addClass('custom-nested');
              }
              
              let main_list = false;
              if ((jQuery('li.dd-item.group[data-value='+newParentValue+']').length > 0) && jQuery(e).hasClass('custom-nested')){
                main_list = true;
              }

              let original_parent = false;
              if(jQuery(e).hasClass('original-nested')){
                original_parent = currentParrent
              }

              let toOriginalNested = false;
              p[0].childNodes.forEach(childNode => {
                if(childNode.className.includes('original-nested')){
                  toOriginalNested = true;
                }
              });

              item = {
                element: currentElementValue,
                parent: newParentValue,
                title: currentElementValueTitle,
                original_parent: original_parent,
                toOriginalNested: toOriginalNested
              };
      
              var added = false;
              jQuery.map(nestedElements, function(elementOfArray, indexInArray) {
                if (elementOfArray.element == item.element) {
                  added = true;
                }
              });
              if (!added) {
                nestedElements.push(item);
              } else {
                jQuery.each( nestedElements, function( key, value ) {
                  if( value.element == item.element) {
                      value.parent = item.parent;
                  }
                });
              }

              if(main_list){
                nestedElements = jQuery.grep(nestedElements, function(value) {
                  return value.element != item.element;
                });
              }

              if(original_parent){
                nestedElements = jQuery.grep(nestedElements, function(value) {
                  return value.original_parent != item.parent;
                });
              }
      
              postRequestJSON(c5resturl.wpjson + 'click5_sitemap/API/post_update_nested_elements', nestedElements, (data) => {
              });

            }

        }
      })
      jQuery('#orderList').nestable('collapseAll');

      jQuery("#orderList li.sub-item").each(function() {
        var this_elem = jQuery(this)
        if (this_elem.hasClass("dd-collapsed")) {
          this_elem.removeClass("dd-collapsed")
        }
        if(this_elem.find('ol li').length == 0){
          var handlediv = this_elem.find('div.dd-handle').detach();
          this_elem.empty().append(handlediv);
        }

        var original_nested = this_elem.find('ol li').not('.custom-nested');
        original_nested.addClass('original-nested')
        
      });

      jQuery("#orderList li").each(function() {
        var this_elem = jQuery(this);
        this_elem.attr('id', this_elem.data().value);
      });  
    });

    if (callback) {
      callback();
    }
  });
};

let dragged = false;

const loadOrderList = (callback = undefined) => {
    
      getRequest(c5resturl.wpjson + 'click5_sitemap/API/get_order_list_HTML_nested', (dataFull) => {
        var data = dataFull[0];
        
        var dataOrder = dataFull[2];
        let list = '<div class="dd" id="orderList"><ol class="dd-list" id="sitemap-order">';
        if (data) {
          if (data.length) {
            list += data;
          }
        }
        list += '</ol></div>';

        let listEl = jQuery(list);
        jQuery(listEl).hide();
        jQuery('#post-body-content > #order-sitemap > div').prepend(listEl);
        dataOrder.reverse();
        if (dataOrder) {
          if(dataOrder.length) {
            dataOrder.forEach(sortObject => {
              sortElement = document.querySelector('li[data-value="'+sortObject.ID+'"]');
              if(sortElement) {
                sortElement.setAttribute('value', sortObject.order);
                if (sortObject.parent) {
                  sortElement.setAttribute('name', sortObject.parent);
                }
              }
            });
            sortOrderList();
            relocateSubItems();
            sortSubOrderList();

            serializeOrderListNoNotification();
            BTNserializeOrderList();
          }
        }
          jQuery(listEl).show();
        

        getRequest(c5resturl.wpjson + 'click5_sitemap/API/get_nested_elements', (nestedElements) => {
          
          let currentParrent;
          let currentParentFull;

          jQuery('#orderList').nestable({
            maxDepth: 3,
            expandBtnHTML: '<button class="dd-expand" data-action="expand" type="button"></button>',
            collapseBtnHTML: '<button class="dd-collapse" data-action="collapse" type="button"></button>',
            onDragStart: function(l,e){
              currentParrent = jQuery(e).parent().closest('li').attr('data-value')
              currentParentFull = jQuery(e).parent().closest('li');
            },
            beforeDragStop: function(l, e, p){

                if(e.hasClass("dd-item") && (currentParrent != undefined || jQuery('#click5_sitemap_display_style').val() == "merge")){
                  let _ps = jQuery(e.attr("id")).parent(0).find(".dd-item[value="+(parseInt(e.val())+100)+"]");;
                  let _px = e.parent().find(".dd-item[value="+(parseInt(e.val())-100)+"]");
                  dragged = true;
                }

                var currentElementValue = jQuery(e).attr('data-value')
                var currentElementValueTitle = jQuery(e).find('.dd-handle').text()
                var newParentValue = p[0].offsetParent.dataset.value

                if(typeof newParentValue != 'undefined'){
                  newParent = p[0].offsetParent;
                  if(newParent.offsetParent.id != 'sitemap-order'){
                    newParent = newParent.offsetParent.offsetParent.dataset.value;
                  }else{
                    newParent = newParent.dataset.value;
                  }
      
                  let found = false;

                  while(!found){
                    if(currentParentFull.parent().attr('id') != 'sitemap-order'){
                      currentParentFull = currentParentFull.parent();
                    }else{
                      found = true;
                    }
                  }
      
                  if(currentParentFull.closest('li').data('value') != newParent)
                    return false;
                }
                
                if(jQuery(e).hasClass("group") && p[0].offsetParent.className.includes('group')){
                  return false;
                }

                if(jQuery(e).hasClass('sub-item') && p[0].getAttribute('id') === 'sitemap-order') {
                  return false;
                }

                if (!jQuery(e).hasClass("group") && currentParrent != newParentValue) {

                  jQuery(e).attr('name', newParentValue);

                  if(!jQuery(e).hasClass('original-nested')){
                    jQuery(e).addClass('custom-nested');
                  }
                  
                  let main_list = false;
                  if ((jQuery('li.dd-item.group[data-value='+newParentValue+']').length > 0) && jQuery(e).hasClass('custom-nested')){
                    main_list = true;
                  }

                  let original_parent = false;
                  if(jQuery(e).hasClass('original-nested')){
                    original_parent = currentParrent
                  }

                  let toOriginalNested = false;
                  p[0].childNodes.forEach(childNode => {
                    if(childNode.className.includes('original-nested')){
                      toOriginalNested = true;
                    }
                  });

                  item = {
                    element: currentElementValue,
                    parent: newParentValue,
                    title: currentElementValueTitle,
                    original_parent: original_parent,
                    toOriginalNested: toOriginalNested
                  };
          
                  var added = false;
                  jQuery.map(nestedElements, function(elementOfArray, indexInArray) {
                    if (elementOfArray.element == item.element) {
                      added = true;
                    }
                  });
                  if (!added) {
                    nestedElements.push(item);
                  } else {
                    jQuery.each( nestedElements, function( key, value ) {
                      if( value.element == item.element) {
                          value.parent = item.parent;
                      }
                    });
                  }

                  if(main_list){
                    nestedElements = jQuery.grep(nestedElements, function(value) {
                      return value.element != item.element;
                    });
                  }

                  if(original_parent){
                    nestedElements = jQuery.grep(nestedElements, function(value) {
                      return value.original_parent != item.parent;
                    });
                  }

                  postRequestJSON(c5resturl.wpjson + 'click5_sitemap/API/post_update_nested_elements', nestedElements, (data) => {
                  });

                }

            }
          })
          jQuery('#orderList').nestable('collapseAll');

          jQuery("#orderList li.sub-item").each(function() {
            var this_elem = jQuery(this)
            if (this_elem.hasClass("dd-collapsed")) {
              this_elem.removeClass("dd-collapsed")
            }
            if(this_elem.find('ol li').length == 0){
              var handlediv = this_elem.find('div.dd-handle').detach();
              this_elem.empty().append(handlediv);
            }

            var original_nested = this_elem.find('ol li').not('.custom-nested');
            original_nested.addClass('original-nested')
            
          });

          jQuery("#orderList li").each(function() {
            var this_elem = jQuery(this);
            this_elem.attr('id', this_elem.data().value);
          });  
        });

        if (callback) {
          callback();
        }
      });
};

//recursive
const getOrderElement = (node) => {
  if (node.childElementCount > 1) {
    let nodeObj = { ID: node.getAttribute('data-value'), children: [] }
    node.lastElementChild.childNodes.forEach(childNode => {
      nodeObj.children.push(getOrderElement(childNode, nodeObj));
    });
    return nodeObj;
  } else {
    return { ID: node.getAttributeNode("data-value").value };
  }
}

const serializeOrderList = () => {
  let arrayResult = [];
  document.querySelectorAll('#sitemap-order > *').forEach(firstLevelNode => {
    arrayResult.push(getOrderElement(firstLevelNode));
  });
    
  postRequestJSON(c5resturl.wpjson + 'click5_sitemap/API/post_update_list_HTML', arrayResult, (data) => {
    click5_sitemap_notification('success', 'HTML Sitemap order has been updated.');
  });
}

const serializeOrderListNoNotification = () => {
  let arrayResult = [];
  document.querySelectorAll('#sitemap-order > *').forEach(firstLevelNode => {
    arrayResult.push(getOrderElement(firstLevelNode));
  });
  postRequestJSON(c5resturl.wpjson + 'click5_sitemap/API/post_update_list_HTML', arrayResult, (data) => {
    //click5_sitemap_notification('success', 'HTML Sitemap order has been updated.');
  });
}

const BTNserializeOrderList = () => {
  let arrayResult = [];

  document.querySelectorAll('#sitemap-order > *').forEach(firstLevelNode => {
    arrayResult.push(getOrderElement(firstLevelNode));
  });

  if(saveOrder && dragged){
    arrayResult.push({"save_order":true});
  }

  postRequestJSON(c5resturl.wpjson + 'click5_sitemap/API/post_update_list_HTML_save_btn', arrayResult, (data) => {
  });
}

const reloadOrderList = (callback = undefined) => {
  document.querySelector('#sitemap-order').remove();
  loadOrderList(callback);

}

const updateEnableCustomHeading = (checkboxElement) => {
  const option_name = checkboxElement.getAttribute('name');
  const option_value = checkboxElement.checked;
  postRequestJSON(c5resturl.wpjson + 'click5_sitemap/API/update_option_AJAX', { option_name, option_value, type: 'bool' }, (data) => {
    if (data == true) {
      click5_sitemap_notification('success', 'Settings have been saved.', 2000);
      reloadOrderList(serializeOrderList);
    }
  });
}

const copyToClipboard = (str) => {
  const el = document.createElement('textarea');
  el.value = str;
  document.body.appendChild(el);
  el.select();
  document.execCommand('copy');
  document.body.removeChild(el);
  click5_sitemap_notification('success', 'Copied to clipboard.', 2000);
}

const resetSitemapOrder = () => {
  /*postRequestJSON(c5resturl.wpjson + 'click5_sitemap/API/reset_sitemap_order', {}, (data) => {
    click5_sitemap_notification('success', 'Default Sitemap order has been applied.', 2000);
    if (data) {
      if (data.length) {
        reloadOrderList(serializeOrderList);
      }
    }
  });*/

  return true;
}


const TotalresetSitemapOrder = () => {
  postRequestJSON(c5resturl.wpjson + 'click5_sitemap/API/total_reset_sitemap_order', {}, (data) => {
    click5_sitemap_notification('success', 'Default Sitemap order has been applied.', 2000);
    if (data) {
      if (data.length) {
        reloadOrderList(serializeOrderList);
      }
    }
  });
}

const updateGeneralHtmlOptions = () => {
  
  document.getElementById("items_1000_error").style.display = "none";
  let dataOptions = [];
  document.querySelectorAll('.setting_block input').forEach(item => {
    let opt_name = item.id;
    if(item.type == "checkbox") {
      dataOptions[opt_name] = item.checked;
    } else {    
      dataOptions[opt_name] = item.value;
    }
  });
  document.querySelectorAll('.setting_block select').forEach(item => {
    let opt_select = item.id;
    dataOptions[opt_select] = item.value;
  });
  let itemsCount = parseInt(dataOptions["click5_sitemap_html_pagination_items_per_page"]);
  if(itemsCount > 1000) {
    document.getElementById("items_1000_error").style.display = "block";
  } else {
    let obData = Object.assign({}, dataOptions);
    postRequestJSON(c5resturl.wpjson + 'click5_sitemap/API/update_html_option_AJAX', obData, (data) => {
      click5_sitemap_notification('success', 'HTML options have been saved.', 2000);
      //serializeOrderList();
      //BTNserializeOrderList();
      
      //jQuery(window).unbind('beforeunload');
      //window.location.reload();
      //forceSerializeOrderList();
      //BTNserializeOrderList();
      jQuery('.dd').remove();
      
      loadOrderList22();
    });
  }
}

const forceSerializeOrderList = () => {
  let arrayResult = [];
  document.querySelectorAll('#sitemap-order > *').forEach(firstLevelNode => {
    arrayResult.push(getOrderElement(firstLevelNode));
  });
  postRequestJSON(c5resturl.wpjson + 'click5_sitemap/API/post_update_list_HTML', arrayResult, (data) => {

    //jQuery(window).unbind('beforeunload');
   // window.location.reload();
    let arrayResult = [];

    document.querySelectorAll('#sitemap-order > *').forEach(firstLevelNode => {
      arrayResult.push(getOrderElement(firstLevelNode));
    });

    postRequestJSON(c5resturl.wpjson + 'click5_sitemap/API/post_update_list_HTML_save_btn', arrayResult, (data) => {
      jQuery(window).unbind('beforeunload');
      window.location.reload();
      //jQuery('.dd').remove();
      //loadOrderList();
      
      //jQuery('.dd').remove();
      //loadOrderList();
    });
  });
}

let saveOrder = false;

(() => {
  document.addEventListener("DOMContentLoaded", function (event) {
    if (hasParameter('&tab=seo') || hasParameter('&tab=urls')) {
      return;
    }

    loadOrderList();


    toggleLoader('loader_results', true);
    toggleLoader('loader_blacklisted', true);

  
    let inputSearch = document.querySelector('#page_search');
    let selectType = document.querySelector('#page_type');
    let hiddenAllTypes = document.querySelector('#all_types');

    if(jQuery('#btnSaveOrder').length != 0){
      document.querySelector('#btnSaveOrder').addEventListener('click', function (e) {

        saveOrder = true;
        e.preventDefault();
        e.stopPropagation();
        serializeOrderList();
        BTNserializeOrderList();
        if(dragged){
          jQuery("#sorting_notification").removeClass("disabled");
          jQuery("#sorting_notification").addClass("enabled");
        }
  
  
      });
    }


    document.addEventListener('click5_sitemap_force_order_list_update', function() {
      reloadOrderList(serializeOrderList);
    });

    

    document.getElementById('btnClearBlacklist').addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();

      confirm('Are you sure you want delete all blacklisted pages?');

      getRequest(c5resturl.wpjson + 'click5_sitemap/API/clear_blacklist', (data) => {
        if (data == true) {
          document.querySelectorAll('#click5_sitemap_already_blacklisted > li').forEach(el => {
            el.remove();
          });
          //loadBlacklist();
          //reloadOrderList(serializeOrderList);
          searchFunc(inputSearch, selectType, hiddenAllTypes);
          this.style.display = 'none';
          //click5_sitemap_notification('success', 'Blacklist saved.', 2000);
        } else {
          click5_sitemap_notification('error', 'Something went wrong.', 2000);
        }
      });
    });

    if(jQuery('#btnResetOrder').length != 0){
      document.getElementById('btnResetOrder').addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dragged = false;
        TotalresetSitemapOrder();
        jQuery("#sorting_notification").addClass("disabled");
        jQuery("#sorting_notification").removeClass("enabled");
      });
    }
    
    document.querySelectorAll('#enableSitemap input[type="checkbox"]').forEach(checkbox => {
      checkbox.addEventListener('change', function(e) {
        //updateEnableCustomHeading(this);
        //resetSitemapOrder();
      });
    });
    document.querySelectorAll('#enableSitemap select:not(#click5_sitemap_html_blog_group_by)').forEach(checkbox => {
      checkbox.addEventListener('change', function(e) {
        const option_name = this.getAttribute('name');
        const option_value = this.value;
      });
    });

    document.querySelectorAll('#enableSitemap #click5_sitemap_html_blog_group_by').forEach(checkbox => {
      checkbox.addEventListener('change', function(e) {
        const option_name = this.getAttribute('name');
        const option_value = this.value;
      });
    });

    document.querySelectorAll('#enableSitemap input[type="number"]').forEach(checkbox => {
      checkbox.addEventListener('change', debounce(function (e) {
        const option_name = this.getAttribute('name');
        const option_value = parseInt(this.value);
      }, 300));
    });
    document.querySelectorAll('#enableSitemap input[type="text"]').forEach(checkbox => {
      checkbox.addEventListener('input', debounce(function(e) {
        const option_name = this.getAttribute('name');
        const option_value = this.value;
        if (option_value.length) {
          this.previousElementSibling.control.checked = true;
        } else {
          this.previousElementSibling.control.checked = false;
        }

      }, 300));
    });

    loadBlacklist();

    document.addEventListener('blacklist_updated', () => {
      //reloadOrderList(serializeOrderList);
      resetSitemapOrder();
    });

    searchFunc(inputSearch, selectType, hiddenAllTypes);


    selectType.addEventListener('change', function (e) {
      searchFunc(inputSearch, selectType, hiddenAllTypes);
    });
    inputSearch.addEventListener('input', debounce(function (e) {
      searchFunc(inputSearch, selectType, hiddenAllTypes);
    }, 300));

    document.querySelector('#copy-me').addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
  
      const toCopy = this.querySelector('strong').innerText.trim();
      copyToClipboard(toCopy);
    });
  });


})();


jQuery(document).ready(function() { 

  jQuery('[name="update_txt"]').click(function(e) {
    e.preventDefault();
    var text_data = jQuery('[name="file_text"]').val();
    var send_data;
    if(text_data == undefined) {
      send_data = null;
    } else {
      send_data = text_data.split('\n');
    }

    jQuery(window).unbind('beforeunload');
    postRequestJSON(c5resturl.wpjson + 'click5_sitemap/API/checkrobots', {text: send_data}, (data) => {
      if(data) {
        jQuery('[name="robots_error"]').css( "display", "block" );
      } else {
        location.reload();
      }
    });
    

    
  });
})