define([
    'jquery',
    'jquery/ui',
    'Magento_Ui/js/modal/confirm',
    "Magento_Ui/js/modal/alert",
    "loader",
], function ($, jqueryUi, modalConfirm, modalAlert, loader) {
    'use strict';

    $.widget('mage.merchandiser', {
        options: {},
        currentPage: 0,
        allLoaded: false,
        inLoad: false,
        saveOffsetActions: 0,
        needSave: false,

        _create: function ()
        {
            this.changeNbColumns();
            this.checkOverload();
            this.bindEvents();
            this.loadProducts();
        },

        bindEvents: function()
        {
            // window unload
            window.onbeforeunload = function(e) {
                if (this.needSave) {
                    return this.options.translate.needSave;
                } else {
                    return null;
                }
            }.bind(this);

            // window scroll
            $(window).scroll(function(){
                // infinite load
                if ($('body').height() - 300 <= $(window).scrollTop() + $(window).height()) {
                    this.loadProducts(false);
                }

                // actions bar sticky
                if ($(window).scrollTop() >= this.saveOffsetActions) {
                    $(this.options.elements.actions).addClass('sticky');
                } else {
                    $(this.options.elements.actions).removeClass('sticky');
                }
            }.bind(this));

            // store change
            $(this.options.elements.store_switcher).on('change', function(event){
                var val = $(event.currentTarget).val();
                window.location.href = this.changeUrlParameter('store', val);
            }.bind(this));

            // category change
            $(this.options.elements.category_switcher).on('change', function(event){
                var val = $(event.currentTarget).val();
                var url = this.changeUrlParameter('category', val);

                window.history.pushState('html', 'title', url);

                this.loadProducts(true);
            }.bind(this));

            // change nb columns
            $(this.options.elements.nb_columns).on('change', function(){
                this.changeNbColumns();
            }.bind(this));

            // btn reload
            $(this.options.elements.btn_reload).on('click', function(){
                this.reloadProducts();
            }.bind(this));

            // btn put to top
            $(this.options.elements.btn_put_to_top).on('click', function(){
                this.putToTop();
            }.bind(this));

            // btn remove overload
            $(this.options.elements.btn_remove_overload).on('click', function(){
                this.removeOverload();
            }.bind(this));

            // btn save
            $(this.options.elements.btn_save).on('click', function(){
                this.savePositions();
            }.bind(this));

            // btn apply to global
            $(this.options.elements.btn_apply_to_global).on('click', function(){
                this.applyToGlobal();
            }.bind(this));

            // btn add skus
            $(this.options.elements.btn_add_skus).on('click', function(){
                this.addProductsCategory();
            }.bind(this));

            // btn auto sort
            $(this.options.elements.btn_auto_sort).on('click', function(){
                this.autoSortProducts();
            }.bind(this));
        },

        bindEventsProduct: function ()
        {
            // bind delete buttons
            $(this.options.elements.btns_delete).off('click').on('click', function(event){
                var productId = $(event.currentTarget).data('id');
                this.removeProductCategory(productId);
            }.bind(this));
        },

        loadProducts: function(reset)
        {
            if ($(this.options.elements.category_switcher).val() !== '') {
                if (reset === true) {
                    this.currentPage = 0;
                    this.allLoaded = false;
                    $(this.options.elements.products).html('');
                }

                if (this.inLoad === false && this.allLoaded === false) {
                    this.inLoad = true;
                    this.currentPage++;

                    $('body').loader('show');
                    $.ajax({
                        url: this.options.urls.productsLoad,
                        method: "POST",
                        data: {
                            store: $(this.options.elements.store_switcher).val(),
                            category: $(this.options.elements.category_switcher).val(),
                            p: this.currentPage
                        }
                    })
                        .fail(function(response) {
                            this.inLoad = false;
                        }.bind(this))
                        .done(function(response) {
                            $('body').loader('hide');

                            if (response.error) {
                                modalAlert({
                                    title: 'Error',
                                    content: response.error,
                                    actions: {
                                        always: function(){}
                                    }
                                });
                            }  else {
                                if (this.currentPage * this.options.max_products >= response.total) {
                                    this.allLoaded = true;
                                }

                                // update total
                                $(this.options.elements.count).find('span').html(response.total);

                                // update container
                                var container = $(this.options.elements.products);
                                container.html(container.html()+response.html);

                                // show elements
                                $(this.options.elements.elements).show();

                                // show actions
                                $(this.options.elements.actions).show();
                                if (this.saveOffsetActions === 0) {
                                    this.saveOffsetActions = $('.actions').offset()['top'];
                                }

                                // disabled btn save
                                if (this.needSave) {
                                    $(this.options.elements.btn_save).addClass('disabled');
                                }

                                container.off('click', '.product-middle').on('click', '.product-middle', function () {
                                    $(this).parents('li').toggleClass('selected');
                                });

                                // make list sortable
                                container.sortable({
                                    delay: 150,
                                    cancel: ".product-top, .product-bottom",
                                    update: function(event, ui) {
                                        this.flagNeedSave(true);
                                    }.bind(this),
                                    helper: function (e, item) {
                                        //Basically, if you grab an unhighlighted item to drag, it will deselect (unhighlight) everything else
                                        if (!item.hasClass('selected')) {
                                            item.addClass('selected').siblings().removeClass('selected');
                                        }

                                        //////////////////////////////////////////////////////////////////////
                                        //HERE'S HOW TO PASS THE SELECTED ITEMS TO THE `stop()` FUNCTION:

                                        //Clone the selected items into an array
                                        var elements = item.parent().children('.selected').clone();

                                        //Add a property to `item` called 'multidrag` that contains the
                                        //  selected items, then remove the selected items from the source list
                                        item.data('multidrag', elements).siblings('.selected').remove();

                                        //Now the selected items exist in memory, attached to the `item`,
                                        //  so we can access them later when we get to the `stop()` callback

                                        //Create the helper
                                        var helper = $('<li/>');
                                        return helper.append(elements);
                                    },
                                    stop: function (e, ui) {
                                        //Now we access those items that we stored in `item`s data!
                                        var elements = ui.item.data('multidrag');

                                        //`elements` now contains the originally selected items from the source list (the dragged items)!!

                                        //Finally I insert the selected items after the `item`, then remove the `item`, since
                                        //  item is a duplicate of one of the selected items.
                                        ui.item.after(elements).remove();

                                        this.bindEventsProduct();
                                    }.bind(this)
                                });

                                this.bindEventsProduct();
                            }

                            this.inLoad = false;
                        }.bind(this));
                }
            }
        },

        reloadProducts: function()
        {
            modalConfirm({
                title: $.mage.__('Question'),
                content: $.mage.__(this.options.translate.reloadProducts),
                actions: {
                    confirm: function () {
                        this.loadProducts(true);
                    }.bind(this)
                }
            });
        },

        changeNbColumns: function()
        {
            var element = $(this.options.elements.products);
            element.removeClass('nb-col3').removeClass('nb-col4').removeClass('nb-col5').removeClass('nb-col6');
            element.addClass('nb-col'+$(this.options.elements.nb_columns).val());
        },

        savePositions: function()
        {
            if (!$(this.options.elements.btn_save).hasClass('disabled')) {
                var positions = [];
                $(this.options.elements.container_items).each(function(element){
                    positions.push($(this).data('id'));
                });

                $('body').loader('show');

                $.ajax({
                    url: this.options.urls.savePositions,
                    method: "POST",
                    data: {
                        store: $(this.options.elements.store_switcher).val(),
                        category: $(this.options.elements.category_switcher).val(),
                        positions: positions.join(',')
                    }
                })
                    .done(function(response) {
                        $('body').loader('hide');

                        if (response.message) {
                            this.flagNeedSave(false);
                            this.checkOverload();

                            modalAlert({
                                title: '',
                                content: response.message,
                                actions: {
                                    always: function(){}
                                }
                            });
                        }
                    }.bind(this));
            }
        },

        checkOverload: function()
        {
            if ($(this.options.elements.store_switcher).val() !== '') {
                $('body').loader('show');

                $.ajax({
                    url: this.options.urls.checkOverload,
                    method: "POST",
                    data: {
                        store: $(this.options.elements.store_switcher).val(),
                        category: $(this.options.elements.category_switcher).val()
                    }
                })
                    .done(function(response) {
                        $('body').loader('hide');

                        if (response.message) {
                            modalAlert({
                                title: 'Error',
                                content: response.message,
                                actions: {
                                    always: function(){}
                                }
                            });
                        }  else {
                            if (response.overload === 1) {
                                $(this.options.elements.btn_remove_overload).removeClass('disabled');
                                $(this.options.elements.btn_apply_to_global).removeClass('disabled');
                            } else {
                                $(this.options.elements.btn_remove_overload).addClass('disabled');
                                $(this.options.elements.btn_apply_to_global).addClass('disabled');
                            }
                        }
                    }.bind(this));
            }
        },

        removeOverload: function()
        {
            if (!$(this.options.elements.btn_remove_overload).hasClass('disabled')) {
                modalConfirm({
                    title: $.mage.__('Question'),
                    content: $.mage.__(this.options.translate.removeOverload),
                    actions: {
                        confirm: function () {
                            $('body').loader('show');

                            $.ajax({
                                url: this.options.urls.removeOverload,
                                method: "POST",
                                data: {
                                    store: $(this.options.elements.store_switcher).val(),
                                    category: $(this.options.elements.category_switcher).val()
                                }
                            })
                                .done(function(response) {
                                    $('body').loader('hide');

                                    if (response.message) {
                                        modalAlert({
                                            title: 'Error',
                                            content: response.message,
                                            actions: {
                                                always: function(){}
                                            }
                                        });
                                    }  else {
                                        this.checkOverload();

                                        this.loadProducts(true);
                                    }
                                }.bind(this));
                        }.bind(this)
                    }
                });
            }
        },

        putToTop: function()
        {
            // change position
            $($(this.options.elements.container_items+'.selected').get().reverse()).each(function(index, element){
                $(element).detach().prependTo(this.options.elements.products);

                this.flagNeedSave(true);
            }.bind(this));

            // unselect
            $(this.options.elements.container_items+'.selected').removeClass('selected');
        },

        applyToGlobal: function()
        {
            if (!$(this.options.elements.btn_apply_to_global).hasClass('disabled')) {
                modalConfirm({
                    title: $.mage.__('Question'),
                    content: $.mage.__(this.options.translate.applyToGlobal),
                    actions: {
                        confirm: function () {
                            $('body').loader('show');

                            $.ajax({
                                url: this.options.urls.applyToGlobal,
                                method: "POST",
                                data: {
                                    store: $(this.options.elements.store_switcher).val(),
                                    category: $(this.options.elements.category_switcher).val()
                                }
                            })
                                .done(function(response) {
                                    $('body').loader('hide');

                                    if (response.message) {
                                        modalAlert({
                                            title: '',
                                            content: response.message,
                                            actions: {
                                                always: function(){}
                                            }
                                        });
                                    }
                                }.bind(this));
                        }.bind(this)
                    }
                });
            }
        },

        addProductsCategory: function()
        {
            if ($(this.options.elements.input_add_skus).val() !== '') {
                $('body').loader('show');

                $.ajax({
                    url: this.options.urls.addProductsCategory,
                    method: "POST",
                    data: {
                        category: $(this.options.elements.category_switcher).val(),
                        skus: $(this.options.elements.input_add_skus).val()
                    }
                })
                    .done(function(response) {
                        $('body').loader('hide');

                        if (response.message) {
                            modalAlert({
                                title: 'Error',
                                content: response.message,
                                actions: {
                                    always: function(){}
                                }
                            });
                        } else {
                            this.loadProducts(true);
                        }
                    }.bind(this));
            } else {
                modalAlert({
                    title: 'Warning',
                    content: this.options.translate.addSkusEmpty,
                    actions: {
                        always: function(){}
                    }
                });
            }
        },

        removeProductCategory: function(productId)
        {
            modalConfirm({
                title: $.mage.__('Question'),
                content: $.mage.__(this.options.translate.removeProductCategory),
                actions: {
                    confirm: function () {
                        $('body').loader('show');

                        $.ajax({
                            url: this.options.urls.removeProductCategory,
                            method: "POST",
                            data: {
                                category: $(this.options.elements.category_switcher).val(),
                                product: productId
                            }
                        })
                            .done(function(response) {
                                $('body').loader('hide');

                                if (response.message) {
                                    modalAlert({
                                        title: 'Error',
                                        content: response.message,
                                        actions: {
                                            always: function(){}
                                        }
                                    });
                                } else {
                                    // remove item
                                    $(this.options.elements.products+' li[data-id='+productId+']').remove();
                                }
                            }.bind(this));
                    }.bind(this)
                }
            });
        },

        autoSortProducts: function()
        {
            modalConfirm({
                title: $.mage.__('Question'),
                content: $.mage.__(this.options.translate.autoSortProducts),
                actions: {
                    confirm: function () {
                        $('body').loader('show');

                        $.ajax({
                            url: this.options.urls.autoSortProducts,
                            method: "POST",
                            data: {
                                store: $(this.options.elements.store_switcher).val(),
                                category: $(this.options.elements.category_switcher).val(),
                                sort: $(this.options.elements.input_auto_sort).val()
                            }
                        })
                            .done(function(response) {
                                $('body').loader('hide');

                                if (response.message) {
                                    modalAlert({
                                        title: '',
                                        content: response.message,
                                        actions: {
                                            always: function(){}
                                        }
                                    });
                                } else {
                                    this.loadProducts(true);
                                }
                            }.bind(this));
                    }.bind(this)
                }
            });
        },

        flagNeedSave: function(flag)
        {
            if (flag === true) {
                $(this.options.elements.btn_save).removeClass('disabled');
                this.needSave = true;
            } else {
                $(this.options.elements.btn_save).addClass('disabled');
                this.needSave = false;
            }
        },

        changeUrlParameter: function(code, value) {
            var parameters = this.getUrlParameters();

            var indexParameter = -1;
            for (var i = 0; i < parameters.length; i++) {
                if (parameters[i].code === code) {
                    indexParameter = i;
                }
            }

            if (value !== '') {
                if (indexParameter > -1) {
                    parameters[indexParameter] = {'code': code, 'value': value};
                } else {
                    parameters.push({'code': code, 'value': value});
                }
            } else {
                if (indexParameter > -1) {
                    parameters[indexParameter] = undefined;
                }
            }

            return location.protocol+'//'+location.hostname+location.pathname+this.mergeUrlParameters(parameters);
        },

        getUrlParameters: function() {
            var parameters = [];
            var search = window.location.search;

            if (search.substring(0, 1) === '?') {
                search = search.substring(1, search.length);
            }

            var entries = search.split('&');
            for (var i = 0; i < entries.length; i++) {
                if (entries[i].length > 0) {
                    var entry = entries[i].split('=');
                    if (entry.length === 2) {
                        parameters.push({'code': entry[0], 'value': entry[1]});
                    }
                }
            }

            return parameters;
        },

        mergeUrlParameters: function(parameters) {
            var str = [];
            for (var i = 0; i < parameters.length; i++) {
                if (parameters[i] !== undefined) {
                    str.push(parameters[i].code+'='+parameters[i].value);
                }
            }
            return str.length > 0 ? '?'+str.join('&') : '';
        }
    });

    return $.mage.merchandiser;
});
