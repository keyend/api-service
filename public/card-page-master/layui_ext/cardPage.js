/**
 扩展一个 mymod 模块
 **/
// new_element = document.createElement('link');
// new_element.setAttribute('rel', 'stylesheet');
// new_element.setAttribute('href', PROJECT_PREFIX + '/js/lay-module/cardPage/cardPage.css');
// document.body.appendChild(new_element);
/**
 * 自己简单封装的ajax
 *
 */
var AjaxUtil = {
    get: function (url, param, fun, buttonElementList, async = true) {
        if (buttonElementList) {
            //按钮置灰
            this.buttonDisabled(buttonElementList);
        }
        var ajaxOption = {
            cache: false,//关闭缓存
            async: async,//默认为true
            url: url,
            data: param,
            method: "get",
            dataType: "json",
            // headers: this.getTokenHeander,
            success: function (resultData) {
                if (buttonElementList) {
                    AjaxUtil.buttonDisabledRemove(buttonElementList);
                }
                AjaxUtil.errorHanler(resultData, fun)
            },
            error: function (err) {

            }
        };

        layui.jquery.ajax(ajaxOption);
    },
    /**
     *
     * @param url 请求地址
     * @param param 请求参数
     * @param fun 回调函数
     * @param contentType  是否json true是 false否
     * @param formData     是否form  true是 false否
     * @param buttonElementList 请求时按钮至灰 元素选择器 可传集合或者单个元素
     */
    post: function (url, param, fun, contentType, formData, buttonElementList, async = true) {
        if (buttonElementList) {
            //按钮置灰
            this.buttonDisabled(buttonElementList);
        }
        var ajaxOption = {
            cache: false,//关闭缓存
            async: async,//默认为true
            url: url,
            // data: (param) ? JSON.stringify(param) : null,
            method: "post",
            dataType: "json",
            // headers: this.getTokenHeander,
            success: function (resultData) {
                if (buttonElementList) {
                    AjaxUtil.buttonDisabledRemove(buttonElementList);
                }
                AjaxUtil.errorHanler(resultData, fun)
            },
            error: function (err) {

            }
        };
        if (param) {
            if (formData) {
                ajaxOption.data = param;
            } else {
                ajaxOption.data = JSON.stringify(param);
            }
        }
        if (contentType) {
            ajaxOption.contentType = "application/json; charset=UTF-8";
        }
        layui.jquery.ajax(ajaxOption);
    },
    postJson: function (url, param, fun, buttonElementList, async) {
        this.post(url, param, fun, true, false, buttonElementList, async);
    },
    postForm: function (url, param, fun, buttonElementList, async) {
        this.post(url, param, fun, false, true, buttonElementList, async);
    },
    send: function (method, url, param, fun, contentType, async = true, buttonElementList) {
        if ("get" == method.toLowerCase()) {
            this.get(url, param, fun, buttonElementList, async)
        }
        if ("post" == method.toLowerCase()) {
            if (contentType) {
                this.postJson(url, param, fun, buttonElementList, async)
            } else {
                this.postForm(url, param, fun, buttonElementList, async)
            }
        }
    },
    /**
     * 批量移除按钮置灰
     */
    buttonDisabledRemove: function (buttonElementList) {
        if (buttonElementList instanceof Array) {
            layui.jquery.each(buttonElementList, function (index, item) {
                this.buttonDisabledRemoveOne(item);
            })
        } else {
            this.buttonDisabledRemoveOne(buttonElementList);
        }
    },
    /**
     * 单个移除按钮置灰
     * @param buttonElement
     */
    buttonDisabledRemoveOne: function (buttonElement) {
        var DISABLED = 'layui-btn-disabled';
        // let jqButton = layui.jquery(buttonCssSelect);
        if (buttonElement) {
            //移除禁止提交状态
            buttonElement.removeClass(DISABLED);
            buttonElement.removeAttr('disabled');
        }

    },
    /**
     * 按钮置灰 防止重复点击
     */
    buttonDisabled: function (buttonElementList) {
        //防止重复点击: 单击之后提交按钮不可选,防止重复提交
        if (buttonElementList instanceof Array) {
            layui.jquery.each(buttonElementList, function (index, item) {
                this.buttonDisabledOne(item);
            })
        } else {
            this.buttonDisabledOne(buttonElementList);
        }

    },
    buttonDisabledOne: function (buttonElement) {
        var DISABLED = 'layui-btn-disabled';
        if (buttonElement) {
            buttonElement.addClass(DISABLED); // 添加样式
            buttonElement.attr('disabled', 'disabled');  // 添加属性
        }
    },
    /**
     *  系统响应500时 弹出提示消息
     * @param res
     * @param fun
     */
    errorHanler: function (res, fun) {
        if (res.code != 0) {
            if (res.msg) {
                layer.msg(res.msg, {icon: 2})
            } else {
                layer.msg("接口异常", {icon: 2})
            }
            return;
        }
        fun(res)
    }
    /*,
    getTokenHeander: {"Authorization": "Bearer " + storage.getItem("token")}
*/
}
layui.define(['laypage'], function (exports) { //提示：模块也可以依赖其它模块，如：layui.define('mod1', callback);
    Array.prototype.remove = function (val) {
        var index = this.indexOf(val);
        if (index > -1) {
            this.splice(index, 1);
        }
    };
    var $ = layui.jquery
        , laypage = layui.laypage
        , MOD_NAME = "cardPage"
        , cardPage = {
            set: function (options) {
                var that = this;
                that.config = $.extend({}, that.config, options);
                return that;
            },
            index: layui.cardPage ? (layui.cardPage.index + 10000) : 0,
            that: []
        }
        //构造器
        , Class = function (options) {
            var that = this;
            that.index = ++cardPage.index;
            that.config = $.extend({}, that.config, cardPage.config, options);
            that.render();
            return that;
        }
        ,
        sortHtml = '<span class="layui-table-sort layui-inline" lay-sort="${lay-sort}"  style="margin-left: 0px"><i class="layui-edge layui-table-sort-asc" title="升序"></i><i class="layui-edge layui-table-sort-desc" title="降序"></i></span>'


    ;

    //默认配置
    Class.prototype.config = {
        cols: [],//列名
        data: [],//数据
        colMd: 3,//列占行的宽度 3/12
        url: '',
        method: 'get',
        contentType: false,
        where: {},
        sortName: '', //排序字段
        sortType: '',//排序类型 asc desc
        toolbar: '', // 右侧工具条 #id
        eventList: {}// 事件列表
        , page: {
            disable: false,
            limit: 12,
            limits: [8, 12, 24, 48, 96, 192]
        },
        sortList: [],
        multiSortSwitch: false // 是否开启多排序
    };

    //渲染视图
    Class.prototype.render = function () {
        var that = this
            , conf = that.config
            , elem = conf.elem
        ;
        $(elem).css({"position": "relative"})
        that.setData();
        that.renderData()
        that.renderPage()

        return that;
    }


    Class.prototype.reload = function (options) {
        var that = this
            , conf = that.config
            , elem = conf.elem

        // console.log(conf.sortList)
        // let confTemp = $.extend({}, that.config, options);
        that.mergeConf(options)
        $(elem).find(".layui-bg-gray").remove();
        $(elem).find(".cardPage-page").remove();
        that.setData();
        that.renderData()
        that.renderPage(conf.where.page, conf.where.limit)
        return that;

    }
    /**
     * 加载分页对象
     */
    Class.prototype.renderPage = function (curr, limit) {
        var that = this
            , conf = that.config
            , data = conf.data
            , elem = conf.elem

        if (data && conf.page.disable) {
            $(elem).append("<div class='cardPage-page'></div>")
            laypage.render({
                elem: $(elem + ' div:last') //注意，这里的 test1 是 ID，不用加 # 号
                , count: data.total //数据总数，从服务端得到
                , limit: limit || conf.page.limit
                , limits: conf.page.limits
                , curr: curr || 1
                , layout: ['prev', 'page', 'next', 'limit', 'count', 'refresh', 'skip']
                , jump: function (obj, first) {
                    // console.log(obj)
                    // console.log(first)
                    //首次不执行
                    if (!first) {
                        //do something
                        // conf.data.shift()

                        that.reload({where: {page: obj.curr, limit: obj.limit}})
                    }

                }
            });
        }

    }

    /**
     * 数据转换视图
     */
    Class.prototype.renderData = function () {
        var that = this
            , conf = that.config
            , data = conf.data
            , elem = conf.elem
        ;


        if (data && data.records) {
            $(elem).append('<div class="layui-bg-gray" style="padding: 30px;"><div class="layui-row layui-col-space15"></div></div>');
            let rowElem = $(elem).find('.layui-row');
            let cols = conf.cols;
            data.records.forEach((item, index) => {
                if (index % (12 / conf.colMd) == 0) {
                    $(rowElem).append("<div class='layui-row row-hight'></div>")
                }
                let head = '<div class="layui-card-header">${head}</div>';
                let body = '<div class="layui-card-body">${body}</div>';
                let headHtml = '';
                let bodyHtml = '';
                for (let i = 0; i < cols.length; i++) {
                    if (cols[i].topName) {
                        headHtml = cols[0].title + ':' + item[cols[0].field];
                        if (conf.toolbar) {
                            let toolbarHtml = $(conf.toolbar).html();
                            headHtml += toolbarHtml;
                        }
                        continue;
                    }
                    if (item[cols[i].field] instanceof Array) {
                        let separator = cols[i].separator;
                        if (separator === 'empty') {
                            separator = '';
                        }
                        if (separator === undefined) {
                            separator = ':';
                        }

                        item[cols[i].field].forEach(sonItem => {
                            bodyHtml += (cols[i].titleFomart(sonItem) + separator + cols[i].contextFomart(sonItem) + "</br>")
                        })
                    } else {
                        bodyHtml += ((cols[i].sort ? '<span class="card-single-title">' + cols[i].title + '</span>' + that.sortHtml(cols[i]) : ('<span>' + cols[i].title + '</span>')) + '<span class="card-single-right">:' + getColContext(item, cols[i]) + "</span></br>")

                    }

                }
                let html = '<div class="layui-col-md3 card-single"> <div class="layui-card">' + head.replace("${head}", headHtml) + body.replace("${body}", bodyHtml) + '</div></div>';

                $(rowElem).find(".layui-row.row-hight:last").append(html)
                if (conf.toolbar) {
                    let aList = $(rowElem).find(".layui-row.row-hight:last").find("a[card-event]");
                    if (aList.length > 0) {
                        $(aList[aList.length - 1]).on("click", function () {
                            let event = $(this).attr("card-event");
                            let call = conf.eventList[event];
                            call(item)
                        })
                    }
                }


            })
            that.sortBindClick()
        }
        // else {
        //     $(elem).append("<div>无数据</div>")
        // }

    }

    /**
     * 加载事件
     * @param id
     * @param call
     * @returns {*}
     */
    Class.prototype.on = function (id, call) {
        var that = this
            , conf = that.config
        conf.eventList[id] = call;
    }
    /**
     * 加载数据
     */
    Class.prototype.setData = function () {
        var that = this
            , conf = that.config
        //获取数据
        if (conf.sortName && conf.sortType) {
            conf.where.sortName = conf.sortName
            conf.where.sortType = conf.sortType
        } else {
            conf.where.sortName = ''
            conf.where.sortType = ''
        }
        if (!conf.where.page) {
            conf.where.page = 1;
        }
        if (!conf.where.limit) {
            conf.where.limit = conf.page.limit
        }
        if (conf.multiSortSwitch) {
            conf.where.sortList = conf.sortList
        }
        AjaxUtil.send(conf.method, conf.url, conf.where, function (res) {
            if (typeof conf.parseData === 'function') {
                conf.data = conf.parseData(res)
            } else {
                conf.data = res.data;
            }
        }, conf.contentType, false, '')
    }

    /**
     * 合并配置信息
     * @param options
     */
    Class.prototype.mergeConf = function (options) {
        var that = this
            , conf = that.config

        if (options) {
            for (var key in options) {
                if (conf[key]) {
                    conf[key] = Object.assign(conf[key], options[key])
                } else {
                    conf[key] = Object.assign({}, options[key])
                }
            }
        }


    }


    /**
     * 绑定排序
     */
    Class.prototype.sortBindClick = function () {
        var that = this
            , conf = that.config
        $('.layui-table-sort-asc').click(function () {
            let sortName = that.findFiled($(this).parent().prev().text());
            let sortType = "asc";
            conf.sortName = sortName;
            conf.sortType = sortType;
            if (conf.multiSortSwitch) {
                let sortObj = conf.sortList.find(item => item.sortName == sortName);
                if (sortObj) {
                    conf.sortList.remove(sortObj)
                }
                conf.sortList.push({sortName: sortName, sortType: sortType})
            }


            that.reload()
        })
        $('.layui-table-sort-desc').click(function () {
            that.findFiled($(this).parent().prev().text())
            let sortName = that.findFiled($(this).parent().prev().text())
            let sortType = "desc";
            conf.sortName = sortName;
            conf.sortType = sortType;
            if (conf.multiSortSwitch) {
                let sortObj = conf.sortList.find(item => item.sortName == sortName);
                if (sortObj) {
                    conf.sortList.remove(sortObj)
                }
                conf.sortList.push({sortName: sortName, sortType: sortType})
            }

            that.reload()
        })
        $('.card-single-title').click(function () {
            conf.sortName = ''
            conf.sortType = ''
            if (conf.multiSortSwitch) {
                let sortName = that.findFiled($(this).text())
                let removeSort = conf.sortList.find(item => item.sortName == sortName);
                if (removeSort) {
                    conf.sortList.remove(removeSort)
                }
            }
            that.reload()
        })
    }


    /**
     * 查找标题对应字段名
     * @param title
     * @returns {*}
     */
    Class.prototype.findFiled = function (title) {
        var that = this
            , conf = that.config
        let cols = conf.cols;
        return cols.find(item => item.title == title).field;
    }

    /**
     * 获取sortHtml内容
     * @returns {string|string}
     */
    Class.prototype.sortHtml = function (colObj) {
        var that = this
            , conf = that.config
        if (conf.multiSortSwitch) {
            let sortObj = conf.sortList.find(item => item.sortName == colObj.field);
            return (sortObj) ? sortHtml.replace("${lay-sort}", sortObj.sortType) : sortHtml;
        }
        return (conf.sortName && conf.sortType && conf.sortName == colObj.field) ? sortHtml.replace("${lay-sort}", conf.sortType) : sortHtml;
    }
    //核心入口
    cardPage.render = function (options) {
        var ins = new Class(options);
        if (options.id) {
            cardPage.that[options.id] = ins;
        }
        // 回调方法
        // var ex = thisIns.call(ins);
        // ins.config.thisIns = ex;
        return ins;
    };
    //操作当前实例
    cardPage.reload = function (id, options) {
        if (!cardPage.that[id]) {
            console.log("table id = " + id + "，未初始化或者table没有配置id")
            return;
        }
        cardPage.that[id].reload(options)
    }

    /**
     * 获取col 内容
     * @param dataObj
     * @param colObj
     * @returns {string|*}
     */
    function getColContext(dataObj, colObj) {
        if (colObj.fomart) {
            return colObj.fomart(dataObj, dataObj[colObj.field])
        } else {
            return dataObj[colObj.field];
        }

    }


    exports(MOD_NAME, cardPage);
});
