layui.use(['table', 'form', 'dropdown', 'laytpl', 'laydate'], function(){
    var table = layui.table
    , dropdown = layui.dropdown
    , layer = top.layui.layer
    , laytpl = layui.laytpl
    , pageTable
    , form = layui.form
    , laydate = layui.laydate
    , active = {
        l: null,
        cols: [
            [{
                field: "member_id",
                title: "ID",
                align: 'center',
                width: 50,
                fixed: "left",
                sort: true
            }, {
                title: "会员信息",
                minWidth: 200,
                templet: '#table-userinfo'
            }, {
                title: "推荐人",
                minWidth: 200,
                templet(d) {
                    return d.source ? laytpl(document.getElementById('table-userinfo').innerHTML).render(d.source) : '----'
                }
            }, {
                field: "levelname",
                title: "会员等级",
                width: 120
            }, {
                field: "label",
                title: "标签",
                minWidth: 260,
                templet: '#table-label'
            }, {
                field: "status",
                title: "状态",
                width: 90,
                align: 'center',
                templet: '#table-userstatus'
            }, {
                field: "balance_money",
                title: "余额",
                width: 120,
                templet(d) {
                    return '&yen;' + d.balance_money
                }
            }, {
                field: "lastonline_time",
                title: "最后在线",
                minWidth: 160,
                templet(d) {
                    return d.lastonline_time == 0 ? '----' : d.lastonline_time;
                }
            }, {
                field: "create_time",
                title: "创建时间",
                minWidth: 160,
                sort: true,
                templet(d) {
                    return d.create_time == 0 ? '----' : d.create_time;
                }
            }, {
                title: "操作",
                width: 150,
                fixed: "right",
                toolbar: "#table-tool"
            }]
        ],
        add(){
            ns.open(route.add, '添加新会员', [ '680px', '425px' ]).then(() => {
                pageTable.reload()
            })
        },
        edit(e) {
            location.replace(route.edit + "?id=" + e.data.member_id)
        },
        modity_parent(e) {
            ns.open(route.modifyParent + "?id=" + e.data.member_id, '推荐人变更', [ '96%', '90%' ]).then(() => {
                pageTable.reload()
            })
        },
        tree(e) {
            var template = document.getElementById('user-tree').innerHTML, flag = false, that;
            laytpl(template).render(e.data, function(html) {
                layer.open({
                    title: '会员' + e.data.nickname + '关系图',
                    skin: 'layer-tips-class',
                    type: 1,
                    area: ['90%', '90%'],
                    content: html,
                    success: that => {
                        that.find('[lay-filter="refresh"]').on('click', () => loadTree(e)),
                        that.find('[lay-filter="reload"]').on('click', () => buildTree(e)),
                        loadTree(e);

                        function loadTree() {
                            if (flag) return false;
                            flag = true,
                            loader = layer.load(2),
                            $.getJSON(route.tree + '?id=' + e.data.member_id, function(r) {
                                flag = false,
                                layer.close(loader),
                                that.find('#mixed').html(parseTree(r.data)),
                                that.find('#mixed').treeview()
                            })
                        }

                        function buildTree() {
                            if (flag) return false;
                            flag = true,
                            loader = layer.load(2),
                            $.ajax({
                                url: route.tree,
                                type: 'POST',
                                dataType: 'json',
                                data: {id: e.data.member_id},
                                success(r) {
                                    flag = false;
                                    layer.close(loader);
                                    if (r.code == 0) {
                                        loadTree()
                                    } else {
                                        layer.alert(r.message);
                                    }
                                }
                            })
                        }

                        function parseTree(v) {
                            var html = '';
                            if (typeof(v) === 'object' && v !== null) {
                                v.forEach(function(value, i) {
                                    html += '<li><span' + (value.children?' class="folder"':' class="file"') + '>' + 
                                        '' +
                                            value.nickname + "@" + value.member_id + 
                                        '<strong>(' + value.username + ')</strong>' +
                                    '</span>';
                                    if (value.children) {
                                        html += '<ul>' + parseTree(value.children) + '</ul>'
                                    }
                                    html += '</li>'
                                })
                            } else {
                                html += '数据加载错误!'
                            }

                            return html
                        }
                    }
                })
            })
        },
        pwd(e) {
            var template = document.getElementById('user-pwd').innerHTML, flag = false;
            laytpl(template).render(e.data, function(html) {
                layer.open({
                    type: 1,
                    area: ['500px', '310px'],
                    title: '修改密码',
                btn: ['保存', '返回'],
                    content: html,
                    success(obj, index) {
                        top.layui.form.on('submit(user-pwd)', function(obj) {
                            obj.field = top.layui.form.val("user-pwd");
                            if (obj.field.password != obj.field.password2) {
                                layer.msg("输入错误,两次密码不一致!", {icon: 0, shade: .3, time: 800});
                                return false;
                            }

                            if (!/^[\S]{6,16}$/.test(obj.field.password)) {
                                layer.msg("密码必须为 6 到 16 位的非空字符!", {icon: 0, shade: .3, time: 800});
                                return false;
                            }

                            if (flag) return false;

                            layer.close(index),
                            flag = true,
                            ns.silent(route.password, {id: e.data.member_id, password: obj.field.password}, res => {
                                if (res.code == 0) {
                                    flag = false,
                                    setTimeout(() => pageTable.reload(), 100)
                                } else {
                                    flag = false,
                                    layer.alert(res.message)
                                }
                            });

                            return false;
                        }),
                        top.layui.form.render(null, 'user-pwd')
                    },
                    yes(index, obj, that) {
                        obj.find('form').submit();
                    }
                })
            })
        },
        order_combo(e) {
            var template = document.getElementById('user-combo').innerHTML, flag = false;
            laytpl(template).render(e.data, function(html) {
                layer.open({
                    type: 1,
                    area: ['500px', '360px'],
                    title: '购买套餐',
                    btn: ['保存', '返回'],
                    content: html,
                    success(obj, index) {
                        top.layui.form.on('select(combo_id)', function(obj) {
                            var elem = $(obj.elem), opt = elem.children('option:selected');
                            elem.prev().val(parseFloat(opt.data('money')))
                            console.log(obj.value)
                        }),
                        top.layui.form.on('submit(user-combo)', function(obj) {
                            if (flag) return false;
                            top.layui.form.render(null, 'user-combo'),
                            obj.field = top.layui.form.val("user-combo");
                            obj.field.num = parseInt(obj.field.num);
                            obj.field.combo_money = parseFloat(obj.field.combo_money);
                            obj.field.member_id = e.data.member_id;
                            if (isNaN(obj.field.num)) {
                                layer.msg("填写套餐数量不正确!", {icon: 0, shade: .3, time: 800});
                                return false;
                            }
                            if (obj.field.is_deduct == 1) {
                                var order_money = obj.field.combo_money * obj.field.num;
                                if (e.data.balance_money < order_money) {
                                    layer.msg("会员账户余额不足", {icon: 0, shade: .3, time: 800});
                                    return false;
                                }
                            }
                            layer.close(index),
                            flag = true,
                            ns.silent(route.buyCombo, obj.field, res => {
                                if (res.code == 0) {
                                    flag = false,
                                    active.order_complete(res.data)
                                } else {
                                    flag = false,
                                    layer.alert(res.message)
                                }
                            });
                            return false;
                        }),
                        top.layui.form.render(null, 'user-combo')
                    },
                    yes(index, obj, that) {
                        obj.find('form').submit();
                    }
                })
            })
        },
        order_complete(d) {
            d.status = true;
            d.out_trade_no = d.params.out_trade_no;
            d.response = { protocol: 'system', pay_type: d.pay_type, pay_status: 'OK' },
            ns.silent(route.orderComplete, d, res => {
                if (res.code == 0) {
                    setTimeout(() => pageTable.reload(), 100)
                }
            }, {msg: false})
        },
        adjust_balance(e) {
            var template = document.getElementById('user-balance').innerHTML, flag = false;
            laytpl(template).render(e.data, function(html) {
                layer.open({
                    type: 1,
                    area: ['500px', '330px'],
                    title: '账户充值',
                    btn: ['保存', '返回'],
                    content: html,
                    success(obj, index) {
                        top.layui.form.on('submit(user-balance)', function(obj) {
                            obj.field = top.layui.form.val("user-balance");
                            obj.field.amount = parseFloat(obj.field.amount);
                            obj.field.id = e.data.member_id;
                            if (isNaN(obj.field.amount)) {
                                layer.msg("填写金额不正确!", {icon: 0, shade: .3, time: 800});
                                return false;
                            }

                            if (flag) return false;

                            layer.close(index),
                            flag = true,
                            ns.silent(route.recharge, obj.field, res => {
                                if (res.code == 0) {
                                    flag = false,
                                    setTimeout(() => pageTable.reload(), 100)
                                } else {
                                    flag = false,
                                    layer.alert(res.message)
                                }
                            });

                            return false;
                        }),
                        top.layui.form.render(null, 'user-pwd')
                    },
                    yes(index, obj, that) {
                        obj.find('form').submit();
                    }
                })
            })
        },
        label(e) {
            active.labels(e.data.label_list).then(res => {
                var template = document.getElementById('user-label').innerHTML, flag = false;
                laytpl(template).render({ list: res.map(function(v) {
                        v.selected = false,
                        e.data.label_list.forEach(l => {
                            if (l.id == v.id) {
                                v.selected = true;
                                return v;
                            }
                        });
                        return v;
                    }), id: e.data.member_id }, function(html) {
                    layer.open({
                        type: 1,
                        area: ['680px', '510px'],
                        title: '会员标签',
                        btn: ['保存', '返回'],
                        content: html,
                        success(obj, index) {
                            top.layui.util.on('lay-on', {
                                label: function(o) {
                                    let id = o.data('id');
                                    if (o.data('selected') == '1') {
                                        o.data('selected', '0'),
                                        o.addClass('layui-btn-primary'),
                                        e.data.label_list = e.data.label_list.filter(v => {
                                            if (v.id == id) {
                                                return false;
                                            }
                                            return true;
                                        })
                                    } else {
                                        o.data('selected', '1'),
                                        o.removeClass('layui-btn-primary'),
                                        e.data.label_list.push({ id: o.data('id'), labelname: o.text() })
                                    }
                                    obj.find('#ids').val(e.data.label_list.map(v => v.id ))
                                }
                            }),
                            top.layui.form.on('submit(user-label)', function(obj) {
                                obj.field = top.layui.form.val("user-label");
                                layer.close(index),
                                flag = true,
                                ns.silent(route.label, obj.field, res => {
                                    if (res.code == 0) {
                                        flag = false,
                                        setTimeout(() => pageTable.reload(), 100)
                                    } else {
                                        flag = false,
                                        layer.alert(res.message)
                                    }
                                });
                                return false;
                            }),
                            top.layui.form.render(null, 'user-label')
                        },
                        yes(index, obj, that) {
                            obj.find('form').submit();
                        }
                    })
                })
            })
        },
        del(e) {
            layer.confirm('您确定要删除该会员？', {
                title: '友情提示',
                icon: 3,
                btn: ['是的', '再想想']
            }, function(i) {
                layer.close(i),
                ns.silent(route.del, {id: e.data.member_id}, res => {
                    if (res.code == 0) {
                        setTimeout(() => pageTable.reload(), 100)
                    } else {
                        layer.alert(res.message)
                    }
                })
            });
        },
        status(uid, status){
            ns.silent(route.status + '?id=' + uid, {status: status?1:0});
        },
        refresh(o) {
            var othis = $(this).find('div'), anim = othis.data('anim');
            if (!othis.hasClass(anim)) {
                othis.removeClass(anim);
                othis.addClass(anim);
                setTimeout(() => {
                    pageTable.reload(),
                    othis.removeClass(anim)
                }, 1300)
            }
        },
        render() {
            pageTable = table.render({
                elem: "#LAY-list-table",
                url: location.href,
                size: 'lg',
                toolbar: '#table-toolbar',
                cols: active.cols,
                response: { msgName: 'message' },
                skin: 'line',
                page: !0,
                limit: 20,
                limits: [20, 50, 200, 500],
                height: "full-220",
                text: { none: "无数据" },
                parseData: function(res) {
                    return {
                        code: res.code,
                        msg: res.message,
                        count: res.data.count,
                        data: res.data.list
                    }
                },
                done: function() {
                    ns.page = this.page.curr;
                }
            }),

            table.on('toolbar(LAY-list-table)', function(o) {
                active[o.event] ? active[o.event].call(this, o) : '';
            }),

            table.on("tool(LAY-list-table)", function(e) {
                if (e.event == 'more') return dropdown.render(active.menus(e.data.membr_id, this, e));
                active[e.event] ? active[e.event].call(this, e) : '';
            }),

            table.on("pagebar(LAY-list-table)", function(e) {
                console.log(e);
            })
        },
        menus(id, that, obj) {
            var s = [
                { title: '重置密码', id: 'pwd' }, 
                { title: '设置标签', id: 'label' },
                { type: '-' },
                { title: '推荐人变更', id: 'modity_parent' },
                { title: '关系团队', id: 'tree' },
                { type: '-' },
                { title: '购买套餐', id: 'order_combo' },
                { title: '账户充值', id: 'adjust_balance' },
                { title: '删除会员', id: 'del' }
            ];
            return {
                elem: that,
                show: true,
                data: s,
                click: function(ds, othis){
                    typeof(active[ds.id]) != 'undefined' && active[ds.id].call(that, obj)
                },
                align: 'right',
                style: 'box-shadow: 1px 1px 10px rgb(0 0 0 / 12%);'
            }
        },
        labels(ls) {
            return new Promise((resolve, reject) => {
                if (this.l == null) {
                    $.getJSON(route.labels, {page: 1, limit: 9999}, r => {
                        if (r.code == 0) {
                            this.l = r.data.list;
                            resolve(this.l);
                        } else {
                            reject(r.message);
                        }
                    })
                } else {
                    resolve(this.l);
                }
            })
        }
    };

    jQuery = $ = layui.$,

    form.on('submit(LAY-btn-search)', function(data){
        var field = data.field;
        table.reload('LAY-list-table', { 
            where: field,
            page: { curr: 1 }
        });
        return false;
    }),

    form.on('switch(status)', function(obj){
        active.status(obj.value, obj.elem.checked)
    }),
        
    laydate.render({
        elem: "#regtime",
        type: "date",
        range: true,
    }),

    window.addEventListener("load", function() {
        top.ns.require({treeview: [
            '/static/jquery-treeview/jquery.treeview.css', 
            '/static/jquery-treeview/jquery.treeview.js'
        ]}),
        active.render()
    });
})