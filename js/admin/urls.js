const serializeOrderListnew = () => {
  let arrayResult = [];
  document.querySelectorAll('#sitemap-order > *').forEach(firstLevelNode => {
    arrayResult.push(getOrderElement(firstLevelNode));
  });

  postRequestJSON(c5resturl.wpjson + 'click5_sitemap/API/post_update_list_HTML', arrayResult, (data) => {
  });
}


const showHideInputCategory = (self, inputTextEl, valueItem) => {

  var opt = self.options[self.selectedIndex];
  var selected = opt.getAttribute('data-custom');

  if (self.value == 'custom') {
    inputTextEl.value = '';
    inputTextEl.style.display = 'inline-block';
    valueItem.value = true;
  } else if (selected == 'true') {
    inputTextEl.style.display = 'none';
    inputTextEl.value = self.value;
    valueItem.value = true;
  } else {
    inputTextEl.style.display = 'none';
    inputTextEl.value = self.value;
    valueItem.value = false;
  }
}

const removeOne = (self) => {
  getRequest(c5resturl.wpjson + 'click5_sitemap/API/get_custom_url_delete_one?ID=' + self.getAttribute('data-value'), (data) => {
      if (data == true) {
        self.parentElement.parentElement.remove();
        click5_sitemap_notification('success', 'Settings have been saved.', 2000);

        if (!document.querySelectorAll('#custom_urls_list > li').length) {
          document.getElementById('click5_no_records_found').style.display = 'block';
          document.getElementById('custom_urls_list').style.display = 'none';
          document.getElementById('click5_clear_custom_list').style.display = 'none';
        }
      }
  });
}
let customURLEdited = false;
const toggleEditCustom = (self) => {
  getRequest(c5resturl.wpjson + 'click5_sitemap/API/get_custom_url_single?ID=' + self.getAttribute('data-value'), (data) => {
    jQuery(window).unbind('beforeunload');
    jQuery("#edit_custom_url input").change(function(){
      jQuery(window).on('beforeunload',function(){
        return false;
      });
    });
    jQuery("#edit_custom_url select").change(function(){
      jQuery(window).on('beforeunload',function(){
        return false;
      });
    });
      if (data !== false) {
        document.getElementById('edit_url_title').value = data.title;
        document.getElementById('edit_url_url').value = data.url;
        document.getElementById('edit_url_last_mod_date').value = data.last_mod;
        if (data.category.use_custom) {
          document.getElementById('edit_url_category_select').value = data.category.name;
          document.getElementById('edit_url_category_text').value = data.category.name;
          document.getElementById('edit_url_category_text').style.display = 'none';
          document.getElementById('edit_url_category_use_custom').value = true;
        } else {
          document.getElementById('edit_url_category_text').value = data.category.name;
          //document.getElementById('edit_url_category_text').style.display = 'none';
          document.getElementById('edit_url_category_select').value = data.category.name;
          document.getElementById('edit_url_category_use_custom').value = false;
        }

        document.getElementById('edit_url_category_select').addEventListener('change', function(e) {
          showHideInputCategory(this, document.getElementById('edit_url_category_text'), document.getElementById('edit_url_category_use_custom'));
        });

        document.getElementById('edit_url_open_new_tab').checked = data.new_tab;
        document.getElementById('edit_url_last_mod_date').value = data.last_mod;

        document.getElementById('saveURLbtn').setAttribute('data-value', data.ID);

        document.getElementById('edit_custom_url').style.display = 'block';

        //showHideInputCategory(this, document.getElementById('edit_url_category_text'), document.getElementById('edit_url_category_use_custom'));
      }
  });
}

const validAddNew = () => {
  if (!document.getElementById('add_url_title').value.length) {
    return false;
  }

  if (!document.getElementById('add_url_url').value.length) {
    return false;
  }

  if (!document.getElementById('add_url_category_text').value.length && jQuery("#add_url_category_select option:selected").val() == "custom") {
    return false;
  }

  return true;
}

const loadCustomURLSlist = () => {
  document.querySelectorAll('#custom_urls_list > li').forEach(el => el.remove());
  toggleLoader('loader_custom_urls_list', true);
  document.getElementById('click5_no_records_found').style.display = 'none';
  document.getElementById('custom_urls_list').style.display = 'none';
  getRequest(c5resturl.wpjson + 'click5_sitemap/API/get_custom_url_list', (data) => {
    toggleLoader('loader_custom_urls_list', false);

    if (!hasElementsArr(data)) {
      document.getElementById('click5_clear_custom_list').style.display = 'none';
      document.getElementById('click5_no_records_found').style.display = 'block';
    } else {
      document.getElementById('custom_urls_list').style.display = 'block';
      document.getElementById('click5_clear_custom_list').style.display = 'none';
      data.forEach(el => {
        let newListElement = document.createElement('li');
        let urlElement = document.createElement('a');
        urlElement.setAttribute('href', el.url);
        urlElement.setAttribute('target', '_blank');
        urlElement.innerHTML = '[' + el.category.name + '] ' + el.title;
        newListElement.appendChild(urlElement);
        let controlsOfElement = document.createElement('div');
        controlsOfElement.className = 'click5_sitemap_float_right click5_controls';
        controlsOfElement.style.display = 'flex';
        controlsOfElement.innerHTML += '<label><input type="checkbox" class="click5_custom_urls_enabledHTML" data-value="' + el.ID + '" value="1" ' + (el.enabledHTML ? 'checked' : '') + '>HTML</label>';
        controlsOfElement.innerHTML += '<label><input type="checkbox" class="click5_custom_urls_enabledXML" data-value="' + el.ID + '" value="1" ' + (el.enabledXML ? 'checked' : '') + '>XML</label>';
        controlsOfElement.innerHTML += '<a href="#" data-value="' + el.ID + '" class="click5_includedCustomRemove">Remove</a>';
        controlsOfElement.innerHTML += '<a href="#" data-value="' + el.ID + '" class="click5_includedCustomEdit">Edit</a>';
        newListElement.appendChild(controlsOfElement);

        document.getElementById('custom_urls_list').appendChild(newListElement);
      });
      document.querySelectorAll('.click5_includedCustomRemove').forEach(el => {
        el.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();

          removeOne(this);
        });
      });
      document.querySelectorAll('.click5_includedCustomEdit').forEach(el => {
        el.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();

          toggleEditCustom(this);
        });
      });
      document.querySelectorAll('.click5_custom_urls_enabledHTML').forEach(el => {
        el.addEventListener('change', function (e) {
          e.preventDefault();
          e.stopPropagation();

          let isChecked = e.target.checked;
          getRequest(c5resturl.wpjson + 'click5_sitemap/API/get_custom_url_toggle_HTML?ID=' + this.getAttribute('data-value') + '&newVal=' + isChecked, (data) => {
            if (data == true) {
              click5_sitemap_notification('success', 'Settings have been saved.', 2000);
            }
          });
        });
      });
      document.querySelectorAll('.click5_custom_urls_enabledXML').forEach(el => {
        el.addEventListener('change', function (e) {
          e.preventDefault();
          e.stopPropagation();

          let isChecked = e.target.checked;
          getRequest(c5resturl.wpjson + 'click5_sitemap/API/get_custom_url_toggle_XML?ID=' + this.getAttribute('data-value') + '&newVal=' + isChecked, (data) => {
            if (data == true) {
              click5_sitemap_notification('success', 'Settings have been saved.', 2000);
            }
          });
        });
      });
    }
  })
}

const addNewURL = () => {
  if (!validAddNew()) {
    click5_sitemap_notification('warning', 'Fill all required New Custom URL fields!', 2000);
    return;
  }
  document.querySelectorAll('#custom_urls_list > li').forEach(el => el.remove());
  toggleLoader('loader_custom_urls_list', true);
  document.getElementById('click5_no_records_found').style.display = 'none';
  document.getElementById('custom_urls_list').style.display = 'none';

  jQuery("#add_url_category_select").val('custom').change();
  jQuery("#add_url_category_text").css("display","inline-block");

  let useCustom = 'false';
  let categoryText  = '';
  if(jQuery("#add_url_category_select option:selected").val() == "custom"){
    useCustom = 'true';
  }else{
    categoryText = jQuery("#add_url_category_select option:selected").val();
  }
    

  const object = {
    title: document.getElementById('add_url_title').value,
    url: document.getElementById('add_url_url').value,
    category: {
      use_custom: document.getElementById('add_url_category_use_custom').value == 'true',
      name: document.getElementById('add_url_category_text').value
    },
    new_tab: document.getElementById('add_url_open_new_tab').checked,
    last_mod: document.getElementById('add_url_last_mod_date').value != '' ? document.getElementById('add_url_last_mod_date').value : document.getElementById('add_url_last_mod_date').placeholder
  };

  document.getElementById('add_url_title').value = '';
  document.getElementById('add_url_url').value = '';
  document.getElementById('add_url_category_use_custom').value = useCustom;
  document.getElementById('add_url_category_text').value = categoryText;
  document.getElementById('add_url_open_new_tab').checked = false;
  document.getElementById('add_url_last_mod_date').value = '';


  postRequestJSON(c5resturl.wpjson + 'click5_sitemap/API/add_custom_url', object, (data) => {
      if (data == true) {
        jQuery(window).unbind('beforeunload');
        click5_sitemap_notification('success', 'New custom URL has been added.', 2000);

        document.querySelectorAll('#custom_urls_list > li').forEach(el => {
          el.remove();
        });

        toggleLoader('loader_custom_urls_list', true);
        loadCustomURLSlist();
      } else {
        click5_sitemap_notification('error', 'Something went wrong.', 2000);
      }
  });

  function isCustomOptionExist(opt){
    for (i = 0; i < document.getElementById("add_url_category_select").length; ++i){
      if (document.getElementById("add_url_category_select").options[i].value == opt){
        return true;
      }
    }
    return false;
  }

  function isCustomOptionExistEditMode(opt){
    for (i = 0; i < document.getElementById("edit_url_category_select").length; ++i){
      if (document.getElementById("edit_url_category_select").options[i].value == opt){
        return true;
      }
    }
    return false;
  }

  if(object.category.use_custom == true && !isCustomOptionExist(object.category.name)){
      var select = document.getElementById('add_url_category_select');
      var opt = document.createElement('option');
      opt.value = object.category.name;
      opt.innerHTML = object.category.name;
      opt.setAttribute("data-custom", "true");
      select.appendChild(opt);
  }

  if(object.category.use_custom == true && !isCustomOptionExistEditMode(object.category.name)){
    var edit_select = document.getElementById('edit_url_category_select');
    var opt = document.createElement('option');
    opt.value = object.category.name;
    opt.innerHTML = object.category.name;
    opt.setAttribute("data-custom", "true");
    edit_select.appendChild(opt);
  }
  
  jQuery('#add_url_last_mod_date, #edit_url_last_mod_date').datepicker('hide');
}

const saveEdition = (id) => {
  const object = {
    ID: id,
    title: document.getElementById('edit_url_title').value,
    url: document.getElementById('edit_url_url').value,
    category: {
      use_custom: document.getElementById('edit_url_category_use_custom').value == 'true',
      name: document.getElementById('edit_url_category_text').value
    },
    new_tab: document.getElementById('edit_url_open_new_tab').checked,
    last_mod: document.getElementById('edit_url_last_mod_date').value
  };

  postRequestJSON(c5resturl.wpjson + 'click5_sitemap/API/post_custom_url_save_edit?ID=' + id, object, (data) => {
    if (data == true) {
      click5_sitemap_notification('success', 'URL has been saved.', 2000);
      document.getElementById('edit_custom_url').style.display = 'none';
      loadCustomURLSlist();
    } else {
      click5_sitemap_notification('error', 'Something went wrong.', 2000);
    }
  });

  jQuery('#add_url_last_mod_date, #edit_url_last_mod_date').datepicker('hide');

  
}


const loadOrderListnew = (callback = undefined) => {
  getRequest(c5resturl.wpjson + 'click5_sitemap/API/get_order_list_HTML', (data) => {
    let list = '<ol class="sortable sitemap-order" id="sitemap-order">';
    if (data) {
      if (data.length) {
        list += data;
      }
    }
    list += '</ol>';

    let listEl = jQuery(list);
    jQuery(listEl).hide();
    jQuery('#post-body-content > #order-sitemap > div').prepend(listEl);

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

          serializeOrderListnew();
          BTNserializeOrderList();

        }
      }
    });


    jQuery(listEl).show();
    
    getRequest(c5resturl.wpjson + 'click5_sitemap/API/get_nested_elements', (nestedElements) => {
      
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

    });

    
  });
};


const reloadOrderListnew = (callback = undefined) => {
  document.querySelector('#sitemap-order').remove();
  reloadOrder(callback);
}

const reloadOrder = (callback = undefined) => {
  getRequest(c5resturl.wpjson + 'click5_sitemap/API/get_order_list_HTML', (data) => {
    let list = '<ol class="sortable sitemap-order" id="sitemap-order">';
    if (data) {
      if (data.length) {
        list += data;
      }
    }
    list += '</ol>';

    let listEl = jQuery(list);
    jQuery(listEl).hide();
    jQuery('#post-body-content > #order-sitemap > div').prepend(listEl);

    getRequest(c5resturl.wpjson + 'click5_sitemap/API/get_sitemap_order', (data) => {
      if (data) {
        if(data.length) {
          serializeOrderListnew();
          BTNserializeOrderList();
        }
      }
    });

    jQuery(listEl).show();
    
    getRequest(c5resturl.wpjson + 'click5_sitemap/API/get_nested_elements', (nestedElements) => {
      
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

    });

  });
};


(() => {
  document.addEventListener("DOMContentLoaded", function (event) {
    if (!hasParameter('&tab=urls')) {
      return;
    }
    toggleLoader('loader_custom_urls_list', true);
    loadCustomURLSlist();
    
    showHideInputCategory(document.getElementById('add_url_category_select'), document.getElementById('add_url_category_text'), document.getElementById('add_url_category_use_custom'));
    document.getElementById('add_url_category_select').addEventListener('change', function(e) {
      showHideInputCategory(this, document.getElementById('add_url_category_text'), document.getElementById('add_url_category_use_custom'));
    });

    document.getElementById('addNewURLbtn').addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();

      addNewURL();
      reloadOrderList(serializeOrderList);
      
    });

    document.getElementById('click5_clear_custom_list').addEventListener('click', function(e) {
      e.stopPropagation();
      e.preventDefault();

      if (confirm('Are you sure you want delete all Custom URLs?')) {
        postRequest(c5resturl.wpjson + 'click5_sitemap/API/post_custom_url_clear', '', (data) => {
          if (data == true) {
            click5_sitemap_notification('success', 'Settings have been saved.', 2000);
            loadCustomURLSlist();
          }
        });
      }
    });

    document.getElementById('cancelURLbtn').addEventListener('click', function(e) {
      jQuery(window).unbind('beforeunload');
      e.preventDefault();
      e.stopPropagation();

      document.getElementById('edit_custom_url').style.display = 'none';
    });

    document.getElementById('saveURLbtn').addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();

      const idEdit = this.getAttribute('data-value');
      saveEdition(idEdit);

      postRequestJSON(c5resturl.wpjson + 'click5_sitemap/API/reset_sitemap_order', {}, (data) => {
        if (data) {
          if (data.length) {
            reloadOrderListnew(serializeOrderListnew);
          }
        }
      });

      
    });

    loadOrderListnew();

    jQuery(function() {
      jQuery('#add_url_last_mod_date, #edit_url_last_mod_date').datepicker({
        language: 'en-US',
        format: 'mm/dd/yyyy',
        date: new Date(jQuery("#add_url_last_mod_date").data("currentData"))
      });
    });


  });
})();