
function getRequestNoAuth(url, callback) {
  var xhr = new XMLHttpRequest();
  xhr.open('GET', url);
  xhr.onload = function () {
    if (xhr.status === 200) {
      let resObject = [];
      try {
        resObject = JSON.parse(xhr.responseText)
      } catch (e) {

      }
      callback(resObject);
    }
    else {
    }
  };
  xhr.send();
}

const sortOrderList = () => {
  let lists = [];
  document.querySelectorAll('.click5_sitemap > ul').forEach(ul => {
    let newObj = {
      ulElem: ul,
      h2Elem: h2Elem = document.querySelector('.click5_sitemap h2[data-value="' + ul.getAttribute('data-value') + '"]'),
      order: parseInt(ul.getAttribute('value'))
    };
    lists.push(newObj);
  });

  lists.sort(function (a, b) {
    var textA = a.order;
    var textB = b.order;

    if (textA < textB) return -1;
    if (textA > textB) return 1;

    return 0;
  });

  lists.forEach(sitemapList => {
    if (sitemapList.h2Elem) {
      let copyH2 = sitemapList.h2Elem.cloneNode(true);
      sitemapList.h2Elem.remove();
      document.querySelector('.click5_sitemap').appendChild(copyH2);
    }

    let copyUl = sitemapList.ulElem.cloneNode(true);
    sitemapList.ulElem.remove();
    document.querySelector('.click5_sitemap').appendChild(copyUl);
  });
}

const relocateSubItems = () => {
  document.querySelectorAll('.click5_sitemap > ul > li').forEach(subli => {
    if (subli.hasAttribute('name')) {
      let newLocation = subli.getAttribute('name');
      if (subli.parentElement) {
        if (newLocation !== subli.parentElement.getAttribute('data-value')) {
          document.querySelector('.click5_sitemap > ul[data-value="' + newLocation + '"]').append(subli);
        }
      }

    }
  });
}

const sortSubOrderList = () => {
  document.querySelectorAll('.click5_sitemap > ul').forEach(ol => {
    let arr = [];

    ol.querySelectorAll('li').forEach(li => {
      let newObj = {
        li: li,
        order: parseInt(li.getAttribute('value'))
      };

      arr.push(newObj);
    });

    arr.sort(function (a, b) {
      var textA = a.order;
      var textB = b.order;

      if (textA < textB) return -1;
      if (textA > textB) return 1;

      return 0;
    });

    arr.forEach(element => {
      let copyLi = element.li.cloneNode(true);
      element.li.remove();
      ol.append(copyLi);
    });
  })
}

const sortListByCurrentSortData = () => {
  getRequestNoAuth(c5resturl.wpjson + 'click5_sitemap/API/get_sitemap_order', (data) => {
    if (data) {
      if (data.length) {
        data.forEach(sortObject => {
          let sortElement = document.querySelectorAll('.click5_sitemap li[data-value="' + sortObject.ID + '"], .click5_sitemap h2[data-value="' + sortObject.ID + '"], .click5_sitemap ul[data-value="' + sortObject.ID + '"]');
          sortElement.forEach(sortElement => {
            if (sortElement) {
              sortElement.setAttribute('value', sortObject.order);
              if (sortObject.parent) {
                sortElement.setAttribute('name', sortObject.parent);
              }
            }
          });
        });
        sortOrderList();
        relocateSubItems();
        sortSubOrderList();
      }
    }

    document.querySelectorAll('.click5_sitemap').forEach(sitemap => {
      sitemap.style.display = 'block';
    });
  });
}

document.addEventListener('DOMContentLoaded', () => {
  sortListByCurrentSortData();
});