jQuery(function($) {
  if (typeof PRODU_DATA.select === "undefined") {
    return;
  }

  //Initialize previously saved categories (If any).
  $.each(PRODU_DATA.subCategories, function (i, value) {
    let taxId = i.split("_")[1];

    jstreeProduInit(taxId);
  });

  //What to do when a main category is selected.
  PRODU_DATA.select.on("select2:select", function (e) {
    let taxId = e.params.data.id;

    jstreeProduInit(taxId);

    if (typeof PRODU_DATA.subCategories === "undefined") {
      PRODU_DATA.subCategories = {};
    }
    PRODU_DATA.subCategories["cat_" + e.params.data.id] = [];
    $("input[name=produ-sub-categories]").val(
      JSON.stringify(PRODU_DATA.subCategories)
    );
  });

  //What to do when a main category is unselected.
  PRODU_DATA.select.on("select2:unselect", function (e) {
    $("[data-taxonomy-id=" + e.params.data.id + "]").remove();
    delete PRODU_DATA.subCategories["cat_" + e.params.data.id];
    $("input[name=produ-sub-categories]").val(
      JSON.stringify(PRODU_DATA.subCategories)
    );
  });

  function jstreeProduInit(taxId) {
    $("#produ-sub-sections").append(
      '<div data-taxonomy-id="' + taxId + '" class="jstree_produ_div"></div>'
    );
    $("[data-taxonomy-id=" + taxId + "]")
      .on("select_node.jstree", function (e, data) {
        let parentId = $(e.target).data("taxonomy-id");

        if (typeof PRODU_DATA.subCategories === "undefined") {
          PRODU_DATA.subCategories = {};
        }

        if (
          typeof PRODU_DATA.subCategories["cat_" + parentId] === "undefined"
        ) {
          PRODU_DATA.subCategories["cat_" + parentId];
        }

        // If a parent node was selected, add all of its children.
        if (data.node.children.length > 0) {
          PRODU_DATA.subCategories["cat_" + parentId] = data.node.children;
        } else {
          PRODU_DATA.subCategories["cat_" + parentId] = data.selected;
        }

        $("input[name=produ-sub-categories]").val(
          JSON.stringify(PRODU_DATA.subCategories)
        );
      })
      .on("deselect_node.jstree", function (e, data) {
        let parentId = $(e.target).data("taxonomy-id");

        // If a parent node was deselected, delete all the node.
        if (data.node.children.length > 0) {
          PRODU_DATA.subCategories["cat_" + parentId] = [];
        } else {
          PRODU_DATA.subCategories["cat_" + parentId] = data.selected;
        }

        $("input[name=produ-sub-categories]").val(
          JSON.stringify(PRODU_DATA.subCategories)
        );
      })
      .on("ready.jstree", function (e, data) {
        if (typeof PRODU_DATA.subCategories !== "undefined") {
          $("[data-taxonomy-id=" + taxId + "]")
            .jstree()
            .select_node(
              PRODU_DATA.subCategories["cat_" + taxId],
              false,
              false
            );
        }
      })
      .jstree({
        core: {
          data: {
            url: PRODU_DATA.tax_endpoint + taxId,
            dataType: "json",
          },
          'check_callback': function(operation, node, node_parent, node_position, more) {
            if (operation === 'close_node') {
                return false;
            }
            return true;
        },
        },
        plugins: ["checkbox", "wholerow"],
      })
      .on("loaded.jstree", function(e, data) {
        // Expande todos los nodos después de cargar el árbol
        data.instance.open_all();
    })
    .on("before_close.jstree", function(e, data) {
        // Previene que los nodos se cierren
        return false;
    });
  }
});

acf.addAction("new_field/type=produCustomTaxonomyField", function (field) {
  let props = {
    allowNull: false,
    placeholder: "Select",
    multiple: true,
    ajax: true,
    ajaxAction: "acf/fields/taxonomy/query",
    ajaxData: function (data) {
      data.field_key = field.get("key");
      data.conditional_logic = false;
      return data;
    },
    ajaxResults: function (json) {
      return json;
    },
    escapeMarkup: function (markup) {
      return markup;
    }
  };

  let select = field.$el.find("select");
  // select.select2(props);
  acf.select2.init(select, props, field);

  if (typeof PRODU_DATA === "undefined") {
    const PRODU_DATA = {};
  }
  PRODU_DATA.select = select;

  let subCategories = jQuery("input[name=produ-sub-categories]").val();

  if (subCategories !== "") {
    PRODU_DATA.subCategories = JSON.parse(subCategories);
  }
});

jQuery('.jstree').on('loaded.jstree', function(e, data) {
  $(this).find('.jstree-anchor').on('click', function(event) {
    event.preventDefault();
    event.stopImmediatePropagation();
    return false;
  });
});

jQuery(document).on('click', '.jstree-anchor', function(event) {
  event.preventDefault();
  event.stopImmediatePropagation();
  return false;
});