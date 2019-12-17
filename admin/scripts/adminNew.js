var sizes = [],
  sizesCount = 0;

function iframeLoaded(id) {
  var iFrameID = document.getElementById(id);
  if (iFrameID) {
    iFrameID.height = "";
    iFrameID.height = iFrameID.contentWindow.document.body.scrollHeight + "px";
  }
}

window.statusAjax = false;
$(document).ready(function() {
  $(document).on("click", ".add_data_to_input", function() {
    $(".good_id").removeClass("hidden");
    $(".result").addClass("hidden");
    $("input[name='good_id']").val($(this).attr("data-id"));
    $("input[name='art']").val($(this).attr("data-art"));
    $("input[name='name_rus']").val($(this).attr("data-name_rus"));
    $("input[name='name_eng']").val($(this).attr("data-name_eng"));
  });

  $(document).on("click", ".arts_data_to_input", function() {
    $(".result").addClass("hidden");
    $("input[data-name='art']").val($(this).attr("data-art"));
    $("input[data-name='name']").val($(this).attr("data-name"));
    $("input[data-name='id']").val($(this).attr("data-id"));
  });

  $(".aarts_get_fields p input").keyup(function() {
    if ($(this).val().length > 2 && window.statusAjax == false) {
      var data = new FormData();
      data.append("arts", "Y");
      $.each($(".aarts_get_fields p input"), function(key, value) {
        var item = $(value)
          .parent()
          .parent();
        data.append("table", item.attr("data-table"));
        data.append(item.attr("data-field"), $(value).val());
      });
      window.statusAjax = true;
      setTimeout(function() {
        $.ajax({
          async: false,
          cache: false,
          contentType: false,
          processData: false,
          dataType: "json",
          type: "POST",
          url: "/manage/blockedit/",
          data: data,
          success: function(data) {
            if (data.success) {
              $(".result").html("");
              $(".result").removeClass("hidden");
              $.each(data.data, function(key, value) {
                var div = $("<div>", {
                  class: "arts_data_to_input",
                  "data-name": value.name_rus,
                  "data-id": value.id,
                  "data-art": value.art
                }).html(
                  "#" +
                    value.id +
                    " арт: <b>" +
                    value.art +
                    "</b> название: <b>" +
                    value.name_rus +
                    "</b>"
                );

                $(".result").append(div);
                window.statusAjax = false;
              });
            } else if (data.error) {
              $(".result").html(data.error);
              window.statusAjax = false;
            }
          }
        });
      }, 2000);
    }
  });

  /**
   * Via Profit modified
   * @author via-profit.ru
   *
   */
  var keyUpIntervalHandle = null;
  $(".fast_add_input").keyup(function() {
    var $inputHandle = $(this);

    if (keyUpIntervalHandle) {
      clearTimeout(keyUpIntervalHandle);
    }

    keyUpIntervalHandle = setTimeout(function() {
      var postData = {
        chakge: "Y"
      };

      $inputHandle
        .parent()
        .parent()
        .find("input.fast_add_input")
        .each(function(i, elem) {
          postData[elem.name] = elem.value.trim();
        });

      $.post(
        "/manage/blockedit/",
        postData,
        function(response) {
          if (response.success) {
            $(".result").html("");
            $(".result").removeClass("hidden");
            $.each(response.data, function(key, value) {
              var div = $("<div>", {
                class: "add_data_to_input",
                "data-id": value.id,
                "data-art": value.art,
                "data-name_rus": value.name_rus,
                "data-name_eng": value.name_eng
              }).html(
                "#" +
                  value.id +
                  " арт: <b>" +
                  value.art +
                  "</b> название: <b>" +
                  value.name_rus +
                  " / " +
                  value.name_eng +
                  "</b>"
              );

              $(".result").append(div);
            });
          } else if (response.error) {
            $(".result").html(response.error);
          }
        },
        "json"
      );
    }, 300);
  });

  /*$('.fast_add_input').keyup(function () {
        var data = new FormData();
        data.append('chakge','Y');
        var ajax = false;
        $.each($(this).parent().parent().find('input.fast_add_input'),function (key,value) {
        	if($(value).attr('name'),$(value).val().length > 2){
                ajax = true;
			}
            data.append($(value).attr('name'),$(value).val());

        });
        if (ajax) {
            setTimeout(function () {
                $.ajax({
                    async: false,
                    cache: false,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    type: "POST",
                    url: "/manage/blockedit/",
                    data: data,
                    success: function (data) {
                        if (data.success) {
                            $('.result').html('');
                            $('.result').removeClass('hidden')
                            $.each(data.data, function (key, value) {
                                var div = $('<div>', {
                                    class: 'add_data_to_input',
                                    'data-id': value.id,
                                    'data-art': value.art,
                                    'data-name_rus': value.name_rus,
                                    'data-name_eng': value.name_eng,
                                }).html('#' + value.id + ' арт: <b>' + value.art + '</b> название: <b>' + value.name_rus + ' / ' + value.name_eng + '</b>');

                                $('.result').append(div);
                            })
                        } else if (data.error) {
                            $('.result').html(data.error);
                        }
                    }
                });
            },2000)
        }
    });*/

  $("#OPEN_FAST_ADD").click(function() {
    $(".add_fast").removeClass("hidden");
    $("input[autofocus='autofocus']").focus();
    $(this).remove();
  });

  $("#SAVE_ADD_FAST").click(function() {
    var data = new FormData();
    data.append("ajax", "Y");
    $.each(
      $(this)
        .parent()
        .parent()
        .find("input"),
      function(key, value) {
        data.append($(value).attr("name"), $(value).val());
      }
    );
    $.ajax({
      async: false,
      cache: false,
      contentType: false,
      processData: false,
      dataType: "json",
      type: "POST",
      url: "/manage/blockedit/",
      data: data,
      success: function(data) {
        if (data.success) {
          location.reload();
        }
      }
    });
  });

  $(".trtree").removeClass("click");
  $(document).click(function() {
    $(".trtree").removeClass("click");
  });

  // Переключение языков в админке
  $(".lang-change").click(function() {
    var t = $(this),
      lang = t.attr("data-id");

    $.post(
      "/",
      {
        mode: "lang",
        lang: lang
      },
      function(data) {
        window.location.href = window.location.href;
      }
    );
    return false;
  });

  // Очистка кэша
  $("#clean-cache").click(function() {
    $.post(
      "/manage/",
      {
        mode: "clean"
      },
      function() {
        alert("Кэш очищен");
      }
    );

    return false;
  });

  //Сохранение миниатюры
  $("#imageSave").on("click", saveMini);

  //Удаление изображения
  $(".erase-button").on("click", deleteImage);

  // Перемещение блоков к другому родителю
  $("#moveToSelect").on("change", function() {
    var str = "",
      t = $(this),
      value = t.val(),
      newParent = t.find("option:selected").attr("data-id");
    (parent = $("#hide-parent").val()),
      (btemplate = $("#hide-template").val()),
      (attr = "");

    if (value == 0) {
      return false;
    }

    $(".group-checkbox").each(function() {
      attr = $(this).prop("checked");
      if (attr) {
        str += $(this).attr("data-id") + ";";
      }
    });

    if (
      str !== "" &&
      confirm("Вы действительно хотите переместить выбранные блоки?")
    ) {
      window.location.href =
        "/manage/blockedit/_amoveto_parent" +
        parent +
        "_new" +
        newParent +
        "_template" +
        btemplate +
        "_ids" +
        str +
        "/";
    }
  });

  //TinyMce
  tinymce.init({
    selector: ".elgrow_html",
    relative_urls: false,
    language: "ru",
    fontsize_formats: "0.75em 0.875em 1em 1.125em 1.25em 1.5em 1.75em 2em",
    style_formats: [
      {
        title: "Изображение слева",
        selector: "img",
        styles: {
          float: "left",
          margin: "0 20px 20px 0"
        }
      },
      {
        title: "Изображение справа",
        selector: "img",
        styles: {
          float: "right",
          margin: "0 0 20px 20px"
        }
      }
    ],
    image_advtab: true,
    remove_script_host: false,
    plugins:
      "advlist autolink link image lists charmap print code paste table textcolor visualblocks filemanager media",
    toolbar:
      "undo redo | bold italic underline strikethrough subscript superscript | alignleft aligncenter alignright alignjustify | forecolor backcolor | formatselect fontsizeselect | bullist numlist | outdent indent | link unlink anchor | image media |removeformat"
  });

  //TinyMce readonly
  tinymce.init({
    selector: ".elgrow_html_readonly",
    readonly: 1,
    relative_urls: false,
    language: "ru",
    fontsize_formats: "0.75em 0.875em 1em 1.125em 1.25em 1.5em 1.75em 2em",
    style_formats: [
      {
        title: "Изображение слева",
        selector: "img",
        styles: {
          float: "left",
          margin: "0 20px 20px 0"
        }
      },
      {
        title: "Изображение справа",
        selector: "img",
        styles: {
          float: "right",
          margin: "0 0 20px 20px"
        }
      }
    ],
    image_advtab: true,
    remove_script_host: false,
    plugins:
      "advlist autolink link image lists charmap print code paste table textcolor visualblocks filemanager media",
    toolbar:
      "undo redo | bold italic underline strikethrough subscript superscript | alignleft aligncenter alignright alignjustify | forecolor backcolor | formatselect fontsizeselect | bullist numlist | outdent indent | link unlink anchor | image media |removeformat"
  });

  // Чекбоксы - триггеры
  $("body").on("ifToggled", ".ch-live", function(e) {
    var $t = $(this);
    $.post(
      "/manage/blockedit/",
      {
        mode: "trigger",
        value: $t.prop("checked"),
        template: $("#hide-template").val(),
        id: $t.attr("data-id"),
        field: $t.attr("data-field")
      },
      function() {}
    );
  });

  // Мультиселекты триггеры
  $("body").on("change", ".mselect-live", function(e) {
    var $t = $(this);
    $.post(
      "/manage/blockedit/",
      {
        mode: "mselecttrigger",
        value: $t.val(),
        template: $("#hide-template").val(),
        id: $t.attr("data-id"),
        field: $t.attr("data-field")
      },
      function() {}
    );
  });

  // Селекты триггеры
  $("body").on("change", ".select-live", function(e) {
    var $t = $(this);
    callback = $t.attr("data-callback");

    $.post(
      "/manage/blockedit/",
      {
        mode: "selecttrigger",
        value: $t.val(),
        template: $("#hide-template").val(),
        id: $t.attr("data-id"),
        field: $t.attr("data-field"),
        callback: $t.attr("data-callback")
      },
      function(data) {
        alert(data);
      }
    );
  });

  // Кнопки отправки рассылки - триггеры
  $("body").on("click", ".subscribe-live", function(e) {
    $(this).attr({ disabled: "disabled" });
    var $t = $(this);
    $.post(
      "/manage/blockedit/",
      {
        mode: "subscribetrigger",
        template: $("#hide-template").val(),
        id: $t.attr("data-id"),
        field: $t.attr("data-field")
      },
      function(data) {
        alert("Рассылка завершена");
        $(".subscribe-live").removeAttr("disabled");
      }
    );
  });

  //tabs in block template

  $(".tabber").on("click", "li", function() {
    var t = $(this),
      tab = t.attr("data-tab");

    curTab = tab;
    $(".tabber li").removeClass("active");
    t.addClass("active");
    $("#tab-content > *").hide();
    $("#addFieldsTable_" + tab).show();

    return false;
  });

  //tabs in block edit
  $(".block-tabber").on("click", "li", function() {
    var t = $(this),
      tab = t.attr("data-tab");

    $(".block-tabber li").removeClass("active");
    t.addClass("active");
    $(".block-tab").hide();
    $("#block-tab_" + tab).show();

    return false;
  });

  // new sort
  $("th[data-key]").click(function() {
    var key = $(this).attr("data-key");

    if (!key) return false;

    // Если вбит поиск то добавляем его в Get
    var searchVal = $("#search-blocks-text").val(),
      searchGet = "";
    if (searchVal.length) {
      searchGet = "&getsearch=" + searchVal;
    }

    var filterVal = $("#filter").val(),
      filterGet = "";
    if (filterVal && filterVal.length) {
      filterGet = "&filter=" + filterVal;
    }

    // Генерим Get url
    if (window.location.search.indexOf(key + "=asc") > 0) {
      getUrl = "?" + key + "=desc" + searchGet + filterGet + "&sort";
    } else {
      getUrl = "?" + key + "=asc" + searchGet + filterGet + "&sort";
    }

    urlPath =
      window.location.origin + window.location.pathname.replace(/\/$/g, "");

    newUrl = urlPath + getUrl;

    window.location.href = newUrl;
  });

  // search blocks
  var searchTimeoutId;
  $("#search-blocks-text").keyup(function() {
    clearTimeout(searchTimeoutId);
    searchTimeoutId = setTimeout(startPostSearch, 350);
  });
  $(document).on("click", "#serach_more", function() {
    var el = $(this),
      start = el.attr("data-start"),
      text = $("#search-blocks-text").val();
    $.post(window.location.href, { start: start, search: text }, function(
      data
    ) {
      el.after(data);
      el.remove();
      $(".ch").iCheck({
        checkboxClass: "icheckbox_flat-blue",
        radioClass: "iradio_flat-blue"
      });
      $(".select-styled").chosen();
      $(".group-checkbox").iCheck({
        checkboxClass: "icheckbox_minimal",
        radioClass: "iradio_minimal"
      });
      $(".trtree").jscontext({
        html: $("#popup-index").html(),
        closeOnMouseLeave: true,
        bind: "right-click",
        open: function() {
          $("#hide-blockid").val(this.substr(6));
        }
      });
    });
  });
  function startPostSearch() {
    var text = $("#search-blocks-text").val(),
      regExp = new RegExp("[.]*" + text + "[.]*", "i"),
      table = $("#tree"),
      tableTbody = $("#tree tbody");

    $.post(window.location.href, { nolimit: true, search: text }, function(
      data
    ) {
      tableTbody.html("");
      tableTbody.html(
        $(data)
          .find("#tree tbody")
          .html()
      );

      if (text.length) {
        $(".pagination").hide();
      } else {
        $(".pagination").show();
      }

      $(".ch").iCheck({
        checkboxClass: "icheckbox_flat-blue",
        radioClass: "iradio_flat-blue"
      });
      $(".select-styled").chosen();
      $(".group-checkbox").iCheck({
        checkboxClass: "icheckbox_minimal",
        radioClass: "iradio_minimal"
      });
      $(".trtree").jscontext({
        html: $("#popup-index").html(),
        closeOnMouseLeave: true,
        bind: "right-click",
        open: function() {
          $("#hide-blockid").val(this.substr(6));
        }
      });
    });
  }

  // maxlength
  $("[data-maxlength]").keyup(function() {
    var $t = $(this),
      $p = $t.prev(),
      $b = $p.find("b"),
      val = $t.val(),
      maxlength = $t.attr("data-maxlength"),
      length = val.length,
      left = maxlength - length;

    if (left < 0) {
      left = 0;
      $t.val($t.val().substr(0, maxlength));
    }

    $b.text(left);
  });
});

function addCat(a) {
  if ($(a).attr("rel") > 0) {
    var parent = $("#hide-parent").val();
    if (!parent) return false;
    window.location.href = "/manage/catedit/_aadd_parent" + parent + "/";
  }
}

function editCat(a) {
  if ($(a).attr("rel") > 0) {
    var parent = $("#hide-parent").val();
    if (!parent) return false;
    window.location.href = "/manage/catedit/_aedit_parent" + parent + "/";
  }
}

function showHideCat(a) {
  if ($(a).attr("rel") > 0) {
    var parent = $("#hide-parent").val();
    if (!parent) return false;
    window.location.href = "/manage/catedit/_ashowhide_parent" + parent + "/";
  }
}

function delCat(a) {
  if ($(a).attr("rel") > 0) {
    var parent = $("#hide-parent").val();
    if (!parent) return false;
    if (
      confirm("Вы действительно хотите удалить эту страницу и все дочерние?")
    ) {
      window.location.href = "/manage/catedit/_adel_parent" + parent + "/";
    } else {
      $(".trtree").removeClass("click");
    }
  }
}

function blockList(a) {
  if ($(a).attr("rel") > 0) {
    var parent = $("#hide-parent").val(),
      page = $("#hide-page").val();

    if (!parent) return false;

    window.location.href = "/manage/blockedit/_alist_parent" + parent + "/";
  }
}

function moveCat(a) {
  if ($(a).attr("rel") > 0) {
    var parent = $("#hide-parent").val();
    $("#tr" + parent + "jscontext").remove();

    beginMove(parent, "move");
  }
}

function copyCat(a) {
  if ($(a).attr("rel") > 0) {
    var parent = $("#hide-parent").val();
    $("#tr" + parent + "jscontext").remove();

    beginMove(parent, "copy");
  }
}

function beginMove(parent, mode) {
  var add = $(
    '<img style="position: absolute;" class="moveIco" src="/admin/decor/standart/img/icons/ico-add.png"/>'
  );
  var up = $(
    '<img style="position: absolute;" class="moveIco" src="/admin/decor/standart/img/icons/ico-up.png"/>'
  );
  var down = $(
    '<img style="position: absolute;" class="moveIco" src="/admin/decor/standart/img/icons/ico-down.png"/>'
  );
  $("body").append(add);
  $("body").append(up);
  $("body").append(down);
  $(".moveIco").hide();

  $(".trtree").bind("mousemove", function(e) {
    $(document).bind("keyup", function(e) {
      if (e.keyCode == 27) {
        $(".trtree").unbind("mousemove");
        add.remove();
        up.remove();
        down.remove();
      }
    });
    var curId = $(e.currentTarget)
      .attr("id")
      .substr(2);
    if (parent != curId) {
      var height = $(curId).height();

      var offset = $("#tree").offset();
      var currentMouse = parseInt(e.pageY) - parseInt(offset.top);
      var currentOffset = e.currentTarget.offsetTop;
      var img, top, newParent, after, before;

      if (currentMouse - currentOffset <= 5 && curId != 1) {
        img = up;
        top = currentOffset + offset.top;

        parentNew = 0;
        after = 0;
        before = curId;
      }
      if (currentMouse - currentOffset >= 15 && curId != 1) {
        img = down;
        top = currentOffset + 4 + offset.top;

        parentNew = 0;
        after = curId;
        before = 0;
      }
      if (
        currentMouse - currentOffset < 15 &&
        currentMouse - currentOffset > 5
      ) {
        img = add;
        top = e.pageY - 7;

        parentNew = curId;
        after = 0;
        before = 0;
      }

      $(e.currentTarget).bind("click", function() {
        window.location.href =
          "/manage/catedit/_a" +
          mode +
          "_parent" +
          parent +
          "_newparent" +
          parentNew +
          "_after" +
          after +
          "_before" +
          before +
          "/";
      });

      $(".moveIco").hide();
      img.show();
      img.css("top", top + "px");
      img.css("left", e.pageX - 25);
    }
  });
}

function editBlock() {
  var parent = $("#hide-parent").val(),
    blockid = $("#hide-blockid").val(),
    btemplate = $("#hide-template").val(),
    querystring = $("#hide-query-string").val()
      ? $("#hide-query-string").val()
      : "",
    page = $("#hide-page").val();

  // для поиска — заменяем или добавляем getsearch
  var searchVal = $("#search-blocks-text").val();
  if (/getsearch=[^\&]*\&/g.test(querystring)) {
    querystring = querystring.replace(
      /getsearch=[^\&]*\&/g,
      searchVal ? "getsearch=" + searchVal + "&" : "&"
    );
  } else if (/&sort$/g.test(querystring)) {
    querystring = querystring.replace(
      /&sort$/g,
      searchVal ? "&getsearch=" + searchVal + "&sort" : "&sort"
    );
  } else {
    querystring = "?getsearch=" + searchVal + "&sort";
  }

  if (!querystring.length) querystring += "?";
  else querystring += "&";
  querystring += "filter=" + $("#filter").val();

  if (!parent) return false;
  window.location.href =
    "/manage/blockedit/_aedit_id" +
    blockid +
    "_template" +
    btemplate +
    "_parent" +
    parent +
    "_page" +
    page +
    querystring +
    "/";
}

function editItemBlock() {
  var parent = $("#hide-parent").val();
  var blockid = $("#hide-blockid").val();
  var btemplate = $("#hide-template").val();
  var blockparent = $("#hide-blockparent").val();
  if (!parent) return false;
  window.location.href =
    "/manage/blockedit/_aitemedit_id" +
    blockid +
    "_template" +
    btemplate +
    "_parent" +
    parent +
    "_blockparent" +
    blockparent +
    "/";
}

function showHideBlock() {
  var parent = $("#hide-parent").val();
  var blockid = $("#hide-blockid").val();
  var btemplate = $("#hide-template").val();
  window.location.href =
    "/manage/blockedit/_ashowhide_id" +
    blockid +
    "_template" +
    btemplate +
    "_parent" +
    parent +
    "/";
}

function delBlock(a) {
  var parent = $("#hide-parent").val();
  var blockid = $("#hide-blockid").val();
  var btemplate = $("#hide-template").val();

  if (confirm("Вы действительно хотите удалить блок?")) {
    window.location.href =
      "/manage/blockedit/_adel_id" +
      blockid +
      "_template" +
      btemplate +
      "_parent" +
      parent +
      "/";
  } else {
    $(".trtree").removeClass("click");
  }
}

function delItemBlock(a) {
  var parent = $("#hide-parent").val(),
    blockid = $("#hide-blockid").val(),
    btemplate = $("#hide-template").val(),
    blockparent = $("#hide-blockparent").val();

  if (confirm("Вы действительно хотите удалить блок?")) {
    window.location.href =
      "/manage/blockedit/_aitemdel_id" +
      blockid +
      "_template" +
      btemplate +
      "_parent" +
      parent +
      "_blockparent" +
      blockparent +
      "/";
  } else {
    $(".trtree").removeClass("click");
  }
}

var groupDel = function() {
  var str = "",
    parent = $("#hide-parent").val(),
    btemplate = $("#hide-template").val(),
    prop;

  $(".group-checkbox").each(function() {
    prop = $(this).prop("checked");
    if (prop === true) {
      str += $(this).attr("data-id") + ";";
    }
  });

  if (
    str !== "" &&
    confirm("Вы действительно хотите удалить выбранные блоки?")
  ) {
    window.location.href =
      "/manage/blockedit/_agroupdel_parent" +
      parent +
      "_template" +
      btemplate +
      "_ids" +
      str +
      "/";
  }
};

var groupHide = function() {
  var str = "",
    parent = $("#hide-parent").val(),
    btemplate = $("#hide-template").val(),
    prop;

  $(".group-checkbox").each(function() {
    prop = $(this).prop("checked");
    if (prop === true) {
      str += $(this).attr("data-id") + ";";
    }
  });

  if (
    str !== "" &&
    confirm("Вы действительно хотите скрыть выбранные блоки?")
  ) {
    window.location.href =
      "/manage/blockedit/_agrouphide_parent" +
      parent +
      "_template" +
      btemplate +
      "_ids" +
      str +
      "/";
  }
};

var groupShow = function() {
  var str = "",
    parent = $("#hide-parent").val(),
    btemplate = $("#hide-template").val(),
    prop;

  $(".group-checkbox").each(function() {
    prop = $(this).prop("checked");
    if (prop === true) {
      str += $(this).attr("data-id") + ";";
    }
  });

  if (
    str !== "" &&
    confirm("Вы действительно хотите показать выбранные блоки?")
  ) {
    window.location.href =
      "/manage/blockedit/_agroupshow_parent" +
      parent +
      "_template" +
      btemplate +
      "_ids" +
      str +
      "/";
  }
};

function moveBlock() {
  var blockid = $("#hide-blockid").val();
  $("#block_" + blockid + "jscontext").remove();
  $("#block_" + blockid).addClass("inmove");
  beginMoveBlock(blockid, "move");
}

function copyBlock() {
  var parent = $("#hide-parent").val();
  var blockid = $("#hide-blockid").val();
  var btemplate = $("#hide-template").val();

  if (confirm("Вы действительно хотите создать копию блока?")) {
    window.location.href =
      "/manage/blockedit/_acopy_id" +
      blockid +
      "_template" +
      btemplate +
      "_parent" +
      parent +
      "/";
  } else {
    $(".trtree").removeClass("click");
  }
}

function beginMoveBlock(blockid, mode) {
  var up = $(
    '<img style="position: absolute;" class="moveIco" src="/admin/decor/standart/img/icons/ico-up.png"/>'
  );
  var down = $(
    '<img style="position: absolute;" class="moveIco" src="/admin/decor/standart/img/icons/ico-down.png"/>'
  );

  var parent = $("#hide-parent").val();
  var btemplate = $("#hide-template").val();

  $("body").append(up);
  $("body").append(down);
  $(".moveIco").hide();

  $(".trtree").bind("mousemove", function(e) {
    $(document).bind("keyup", function(e) {
      if (e.keyCode == 27) {
        $(".trtree").unbind("mousemove");
        $("#block_" + blockid).removeClass("inmove");
        up.remove();
        down.remove();
      }
    });
    var curId = $(e.currentTarget)
      .attr("id")
      .substr(6);
    if (blockid != curId) {
      var height = $(e.currentTarget).height();

      var offset = $("#tree").offset();
      var currentMouse = parseInt(e.pageY) - parseInt(offset.top);
      var currentOffset = e.currentTarget.offsetTop;
      var img, top, newParent, after, before;

      if (currentMouse - currentOffset <= 10) {
        img = up;
        top = currentOffset + offset.top;

        after = 0;
        before = curId;
      }
      if (currentMouse - currentOffset >= 11) {
        img = down;
        top = currentOffset + 8 + offset.top;

        after = curId;
        before = 0;
      }

      $(e.currentTarget).bind("click", function() {
        window.location.href =
          "/manage/blockedit/_a" +
          mode +
          "_parent" +
          parent +
          "_after" +
          after +
          "_before" +
          before +
          "_template" +
          btemplate +
          "_id" +
          blockid +
          "/";
      });

      $(".moveIco").hide();
      img.show();
      img.css("top", top + "px");
      img.css("left", e.pageX - 25);
    }
  });
}

//Сохранение миниатюры
function saveMini() {
  var x1 = $("#x1").val(),
    y1 = $("#y1").val(),
    x2 = $("#x2").val(),
    y2 = $("#y2").val(),
    w = $("#w").val(),
    h = $("#h").val(),
    realW = $("#realW").val(),
    realH = $("#realH").val(),
    fileName = $("#fileName").val(),
    fieldName = $("#fieldName").val(),
    subSizes = sizes[fieldName];

  $.post(
    "/admin/uploadimage.php",
    {
      mode: "crop",
      x1: x1,
      x2: x2,
      y1: y1,
      y2: y2,
      w: w,
      h: h,
      realW: realW,
      realH: realH,
      fileName: fileName,
      resize: sizesCount + 1
    },
    function(data) {
      //Увеличиваем счетчик ресайза
      sizesCount++;

      //Закрываем кроппер
      $.fancybox.close();

      //Если в ресайзах еще есть элементы - ресайзим по ним дальше
      if (typeof subSizes[sizesCount] != "undefined") {
        successImageLoad(null, fileName, fieldName);
      }
      //Если нет - то сбрасываем счетчик ресайзов
      else {
        sizesCount = 0;
      }
    }
  );
}

function successImageLoad(file, response, fieldName) {
  if (response != "no") {
    var api,
      subSizes = sizes[fieldName];

    //Кадрирование
    $("#image-cont").html(
      "<img src='/files/temp/" + response + "' alt='' id='crop-target'>"
    );

    $.fancybox({
      href: "#form-image",
      fitToView: false,
      modal: true,
      autoResize: false,
      autoSize: false,
      width: 1200,
      height: 1000,
      autoWidth: false,
      autoHeight: false,
      margin: 0
    });

    $("#realW").val(subSizes[sizesCount][0]);
    $("#realH").val(subSizes[sizesCount][1]);
    $("#fileName").val(response);
    $("#fieldName").val(fieldName);

    function writeCoords(c) {
      $("#x1").val(c.x);
      $("#y1").val(c.y);
      $("#x2").val(c.x2);
      $("#y2").val(c.y2);
      $("#w").val(c.w);
      $("#h").val(c.h);
    }

    $("#crop-target").Jcrop(
      {
        bgOpacity: 0.5,
        bgColor: "white",
        setSelect: [0, 0, subSizes[sizesCount][0], subSizes[sizesCount][1]],
        addClass: "jcrop-dark",
        onChange: writeCoords,
        onSelect: writeCoords,
        aspectRatio: subSizes[sizesCount]["aspectRatio"]
      },
      function() {
        api = this;
        api.setOptions({ bgFade: true });
        api.ui.selection.addClass("jcrop-selection");
      }
    );
  } else {
    alert("Неподходящий формат файла");
  }
}

function deleteImage() {
  if (!confirm("Удалить файл?")) {
    return false;
  }
  var t = $(this),
    fileName = t.attr("data-delete-name"),
    sName = t.attr("data-delete-sname"),
    fieldName = t.attr("data-field-name"),
    val = $("#" + fieldName + "imageload").val();

  $.post(
    "/admin/uploadimage.php",
    {
      mode: "delete",
      fileName: fileName
    },
    function(data) {
      $("#" + sName).hide();
      val = val.replace(fileName, "");
      val = val.replace(";;", ";");
      $("#" + fieldName + "imageload").val(val);
    }
  );

  return false;
}
